// Runner sandbox per bahasa. Semua kode user berjalan terisolasi:
// - HTML/CSS/JS: iframe sandbox="allow-scripts" (TANPA allow-same-origin)
// - SQL: sql.js di Web Worker
// - PHP: php-wasm di Web Worker
// Worker dimatikan kalau melewati batas waktu (misal infinite loop).

const TIMEOUT_IFRAME = 5000;
const TEST_VIEWPORT = 1024; // lebar iframe saat menjalankan test case
const TIMEOUT_SQL = 8000;
const TIMEOUT_PHP = 20000; // termasuk unduh php-wasm pertama kali

export function createRunner(kind, opts) {
  if (kind === 'sqljs') return sqlRunner(opts);
  if (kind === 'phpwasm') return phpRunner(opts);
  return iframeRunner(opts);
}

/* ------------------------------------------------------------------ */
/* iframe: HTML, CSS, JavaScript                                       */
/* ------------------------------------------------------------------ */

function iframeRunner({ lang, setup, mount }) {
  let frame = null;

  // Harness ditulis 1 baris supaya nomor baris error user tidak bergeser
  function harness(token, tests) {
    const testsJson = tests ? JSON.stringify(tests).replace(/</g, '\\u003c') : 'null';
    return '<script>(function(){var T=' + JSON.stringify(token) + ',L=[];window.__logs=L;'
      + 'function S(t,p){try{parent.postMessage({__mk:T,type:t,payload:p},"*")}catch(e){}}'
      + 'function F(a){if(typeof a==="string")return a;try{return JSON.stringify(a)}catch(e){return String(a)}}'
      + '["log","info","warn","error"].forEach(function(m){var o=console[m];console[m]=function(){var s=Array.prototype.map.call(arguments,F).join(" ");L.push(s);S("console",{level:m,text:s});try{o.apply(console,arguments)}catch(e){}}});'
      + 'window.addEventListener("error",function(e){S("console",{level:"error",text:(e.message||"Error")+(e.lineno?" (baris "+e.lineno+")":"")})});'
      + 'var X=' + testsJson + ';'
      + 'window.addEventListener("load",function(){setTimeout(function(){if(X){S("results",X.map(function(t){try{return{pass:!!(new Function("return ("+t.check+");"))()}}catch(e){return{pass:false,error:String(e&&e.message||e)}}}))}S("done",{})},60)});'
      + '})();<\/script>';
  }

  function buildDoc(code, token, tests) {
    const h = harness(token, tests);
    if (lang === 'javascript') {
      const safe = code.replace(/<\/script/gi, '<\\/script');
      return '<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:system-ui,sans-serif;padding:8px}</style>'
        + h + '</head><body>' + (setup || '') + '<script>' + safe + '\n<\/script></body></html>';
    }
    if (/<head[^>]*>/i.test(code)) return code.replace(/<head[^>]*>/i, (m) => m + h);
    if (/<html[^>]*>/i.test(code)) return code.replace(/<html[^>]*>/i, (m) => m + h);
    return h + code;
  }

  // Eksekusi yang sedang tampil. Listener tetap hidup supaya console.log
  // dari klik tombol / setTimeout setelah halaman dimuat tetap muncul.
  let current = null;

  window.addEventListener('message', (e) => {
    if (!current || !frame || e.source !== frame.contentWindow || !e.data || e.data.__mk !== current.token) return;
    const { type, payload } = e.data;
    if (type === 'console') {
      current.logs.push(payload);
      appendLog(current.consoleBox, payload);
      if (payload.level === 'error') current.consoleBox.classList.remove('hidden');
    } else if (type === 'results') {
      current.results = payload;
    } else if (type === 'done') {
      current.finish(false);
    }
  });

  function execute(code, tests) {
    return new Promise((resolve) => {
      const token = Math.random().toString(36).slice(2);

      mount.replaceChildren();
      frame = document.createElement('iframe');
      frame.className = 'preview-frame';
      frame.setAttribute('sandbox', 'allow-scripts'); // jangan pernah ditambah allow-same-origin
      frame.setAttribute('title', 'Hasil kode');
      const consoleBox = document.createElement('div');
      consoleBox.className = 'console' + (lang === 'javascript' ? '' : ' hidden');
      if (lang === 'javascript') frame.style.minHeight = '140px';
      if (tests) {
        // Test selalu memakai viewport tetap supaya hasil tidak tergantung lebar layar user
        frame.style.width = TEST_VIEWPORT + 'px';
        frame.style.minWidth = TEST_VIEWPORT + 'px';
        mount.style.overflowX = 'auto';
        const note = document.createElement('div');
        note.className = 'small muted';
        note.style.padding = '4px 8px';
        note.textContent = `Test dijalankan pada lebar layar ${TEST_VIEWPORT}px`;
        mount.append(note);
      } else {
        mount.style.overflowX = '';
      }
      mount.append(frame, consoleBox);

      const run = { token, consoleBox, logs: [], results: null, finished: false };
      run.finish = (timedOut) => {
        if (run.finished) return;
        run.finished = true;
        clearTimeout(timer);
        resolve({ logs: run.logs, results: run.results, timedOut });
      };
      current = run;

      const timer = setTimeout(() => run.finish(true), TIMEOUT_IFRAME);
      frame.srcdoc = buildDoc(code, token, tests);
    });
  }

  return {
    label: lang === 'javascript' ? 'Console' : 'Preview',
    async run(code) {
      const r = await execute(code, null);
      return { timedOut: r.timedOut };
    },
    async test(code, tests) {
      const r = await execute(code, tests);
      const results = tests.map((t, i) => ({
        desc: t.desc,
        pass: !r.timedOut && !!(r.results && r.results[i] && r.results[i].pass),
        detail: r.timedOut ? 'Waktu habis. Ada perulangan yang tidak berhenti?' : (r.results && r.results[i] && r.results[i].error) || '',
      }));
      return { results, errors: r.logs.filter((l) => l.level === 'error').map((l) => l.text) };
    },
  };
}

function appendLog(box, entry) {
  const line = document.createElement('div');
  line.className = entry.level === 'error' ? 'c-err' : entry.level === 'warn' ? 'c-muted' : '';
  line.textContent = (entry.level === 'error' ? '✖ ' : '› ') + entry.text;
  box.appendChild(line);
  box.scrollTop = box.scrollHeight;
}

/* ------------------------------------------------------------------ */
/* Worker helper                                                       */
/* ------------------------------------------------------------------ */

function inWorker(url, type, message, timeout) {
  return new Promise((resolve) => {
    const worker = new Worker(url, type === 'module' ? { type: 'module' } : undefined);
    const timer = setTimeout(() => {
      worker.terminate();
      resolve({ timedOut: true });
    }, timeout);
    worker.onmessage = (e) => {
      clearTimeout(timer);
      worker.terminate();
      resolve(e.data);
    };
    worker.onerror = (e) => {
      clearTimeout(timer);
      worker.terminate();
      resolve({ fatal: e.message || 'Runner gagal dimuat.' });
    };
    worker.postMessage(message);
  });
}

/* ------------------------------------------------------------------ */
/* sql.js                                                              */
/* ------------------------------------------------------------------ */

function sqlRunner({ setup, mount, assetsUrl }) {
  const url = assetsUrl + '/js/sql-worker.js';

  function show(data) {
    mount.replaceChildren();
    const box = document.createElement('div');
    box.className = 'sql-result';
    if (data.timedOut) box.appendChild(msg('Waktu habis. Query terlalu berat?', true));
    if (data.fatal) box.appendChild(msg(data.fatal, true));
    if (data.error) box.appendChild(msg('Error: ' + data.error, true));
    (data.sets || []).forEach((set) => box.appendChild(table(set)));
    if (!data.error && !data.fatal && !data.timedOut && (!data.sets || data.sets.length === 0)) {
      box.appendChild(msg('Query berhasil dijalankan (tidak ada hasil tabel).' + (data.changes ? ` ${data.changes} baris berubah.` : ''), false));
    }
    mount.appendChild(box);
  }

  return {
    label: 'Hasil query',
    async run(code) {
      const data = await inWorker(url, 'classic', { setup, code, tests: null }, TIMEOUT_SQL);
      show(data);
      return data;
    },
    async test(code, tests) {
      const data = await inWorker(url, 'classic', { setup, code, tests }, TIMEOUT_SQL);
      show(data);
      const results = tests.map((t, i) => ({
        desc: t.desc,
        pass: !!(data.results && data.results[i] && data.results[i].pass),
        detail: (data.results && data.results[i] && data.results[i].detail) || (data.timedOut ? 'Waktu habis.' : ''),
      }));
      return { results, errors: [data.error, data.fatal].filter(Boolean) };
    },
  };
}

function table(set) {
  const t = document.createElement('table');
  const head = t.createTHead().insertRow();
  set.columns.forEach((c) => {
    const th = document.createElement('th');
    th.textContent = c;
    head.appendChild(th);
  });
  const body = t.createTBody();
  set.values.forEach((row) => {
    const tr = body.insertRow();
    row.forEach((v) => {
      tr.insertCell().textContent = v === null ? 'NULL' : String(v);
    });
  });
  return t;
}

function msg(text, isError) {
  const p = document.createElement('p');
  p.className = isError ? 'alert alert-error' : 'muted';
  p.textContent = text;
  return p;
}

/* ------------------------------------------------------------------ */
/* php-wasm                                                            */
/* ------------------------------------------------------------------ */

function phpRunner({ mount, assetsUrl, onStatus }) {
  const url = assetsUrl + '/js/php-worker.mjs';
  let warmed = false;

  function show(data) {
    mount.replaceChildren();
    const box = document.createElement('div');
    box.className = 'console is-tall';
    if (data.timedOut) appendLog(box, { level: 'error', text: 'Waktu habis. Ada perulangan yang tidak berhenti?' });
    if (data.fatal) appendLog(box, { level: 'error', text: data.fatal });
    if (data.output !== undefined) {
      const out = document.createElement('div');
      out.textContent = data.output === '' ? '(tidak ada output)' : data.output;
      if (data.output === '') out.className = 'c-muted';
      box.appendChild(out);
    }
    if (data.errors) appendLog(box, { level: 'error', text: data.errors });
    mount.appendChild(box);
  }

  async function exec(message) {
    if (!warmed && onStatus) onStatus('Menyiapkan PHP (pertama kali agak lama)...');
    const data = await inWorker(url, 'module', message, TIMEOUT_PHP);
    warmed = true;
    return data;
  }

  return {
    label: 'Output PHP',
    async run(code) {
      const data = await exec({ code, tests: null });
      show(data);
      return data;
    },
    async test(code, tests) {
      const data = await exec({ code, tests });
      show(data);
      const results = tests.map((t, i) => ({
        desc: t.desc,
        pass: !!(data.results && data.results[i] && data.results[i].pass),
        detail: (data.results && data.results[i] && data.results[i].detail) || (data.timedOut ? 'Waktu habis.' : ''),
      }));
      return { results, errors: [data.errors, data.fatal].filter(Boolean) };
    },
  };
}
