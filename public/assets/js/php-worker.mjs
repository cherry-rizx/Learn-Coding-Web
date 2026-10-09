// Web Worker (module): menjalankan PHP user dengan php-wasm.
// Build "web" php-wasm menyentuh window/document saat inisialisasi, jadi diberi shim kecil.
self.window = self;
self.document = {
  querySelector: () => null,
  getElementById: () => null,
  addEventListener() {},
  removeEventListener() {},
  createElement: () => ({ style: {} }),
  body: null,
  documentElement: {},
  title: '',
  hidden: false,
  visibilityState: 'visible',
};

let PhpWeb = null;

async function runPhp(code) {
  if (!PhpWeb) ({ PhpWeb } = await import('../../vendor/php-wasm/PhpWeb.mjs'));
  // Instance baru per eksekusi: fungsi/variabel dari run sebelumnya tidak terbawa
  const php = new PhpWeb({ version: '8.3' });
  let stdout = '';
  let stderr = '';
  php.addEventListener('output', (e) => { stdout += e.detail.join(''); });
  php.addEventListener('error', (e) => { stderr += e.detail.join(''); });
  await php.binary;
  await php.run(code);
  return { stdout, stderr };
}

const normalize = (s) => String(s).replace(/\r\n/g, '\n').trim();

// Pastikan posisi akhir kode berada di mode PHP sebelum menambah kode cek
function endInPhp(code) {
  const open = Math.max(code.lastIndexOf('<?php'), code.lastIndexOf('<?='));
  const close = code.lastIndexOf('?>');
  return close > open || open === -1 ? code + '\n<?php\n' : code + '\n';
}

self.onmessage = async (e) => {
  const { code, tests } = e.data;
  try {
    const main = await runPhp(code);
    let results = null;

    if (tests) {
      results = tests.map(() => ({ pass: false, detail: '' }));

      tests.forEach((t, i) => {
        if (t.type !== 'output') return;
        const out = normalize(main.stdout);
        const want = normalize(t.expect);
        const ok = t.match === 'contains' ? out.includes(want) : out === want;
        results[i] = { pass: ok, detail: ok ? '' : 'Output belum sesuai.' };
      });

      const checks = tests.map((t, i) => ({ t, i })).filter(({ t }) => t.type === 'php');
      if (checks.length) {
        let script = endInPhp(code) + ';\n';
        checks.forEach(({ t, i }) => {
          script += `try { $__ok = (bool) (${t.check}); } catch (\\Throwable $__e) { $__ok = false; }\n`
            + `echo "\\n@@T${i}:" . ($__ok ? '1' : '0') . "@@\\n";\n`;
        });
        const res = await runPhp(script);
        checks.forEach(({ i }) => {
          const m = res.stdout.match(new RegExp('@@T' + i + ':([01])@@'));
          results[i] = m
            ? { pass: m[1] === '1', detail: '' }
            : { pass: false, detail: 'Tes tidak sempat berjalan (ada error fatal atau exit?).' };
        });
      }
    }

    self.postMessage({ output: main.stdout, errors: main.stderr.trim(), results });
  } catch (err) {
    self.postMessage({ fatal: 'PHP gagal dijalankan: ' + (err && err.message ? err.message : String(err)) });
  }
};
