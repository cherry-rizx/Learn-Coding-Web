// Halaman boss: editor + sandbox + submit + AI tutor.
// Catatan: test case dinilai di browser (keterbatasan yang disadari).
import { createRunner } from './runners.js';

const { api, el, icon, renderHearts, countdowns } = window.App;
const boot = JSON.parse(document.getElementById('boss-boot').textContent);
const root = document.getElementById('boss');

const outputBox = root.querySelector('[data-output]');
const testsBox = root.querySelector('[data-tests]');
const runState = root.querySelector('[data-run-state]');
const saveState = root.querySelector('[data-save-state]');
const heartsBox = root.querySelector('[data-hearts-live]');
const failDots = root.querySelector('[data-fail-dots]');
const runBtn = root.querySelector('[data-run]');
const submitBtn = root.querySelector('[data-submit]');
const aiBox = root.querySelector('[data-ai]');
const aiBody = root.querySelector('[data-ai-body]');
const modal = document.querySelector('[data-modal]');
const modalBody = document.querySelector('[data-modal-body]');

let failCount = boot.fail_count;
let hearts = boot.hearts;
let busy = false;
let lastSummary = '';
let livePreviewTimer = null;
let livePreviewQueued = false;

/* ---------- editor ---------- */
const MODES = {
  html: 'htmlmixed',
  css: 'htmlmixed',
  javascript: 'javascript',
  sql: 'text/x-sqlite',
  php: 'application/x-httpd-php',
};
const textarea = root.querySelector('[data-editor]');
textarea.value = boot.draft ?? boot.challenge.starter;
let editor = null;

if (window.CodeMirror) {
  editor = window.CodeMirror.fromTextArea(textarea, {
    mode: MODES[boot.lang] || 'htmlmixed',
    lineNumbers: true,
    tabSize: 2,
    indentUnit: 2,
    lineWrapping: true,
    autoCloseBrackets: true,
    matchBrackets: true,
    autoCloseTags: boot.lang === 'html' || boot.lang === 'css' || boot.lang === 'php',
    extraKeys: {
      'Ctrl-Enter': () => run(),
      'Cmd-Enter': () => run(),
      Tab: (cm) => cm.execCommand(cm.somethingSelected() ? 'indentMore' : 'insertSoftTab'),
    },
  });
} else {
  // Cadangan kalau CodeMirror gagal dimuat: textarea biasa dengan Tab = 2 spasi
  textarea.addEventListener('keydown', (e) => {
    if (e.key === 'Tab') {
      e.preventDefault();
      textarea.setRangeText('  ', textarea.selectionStart, textarea.selectionEnd, 'end');
    }
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) run();
  });
}

const getCode = () => (editor ? editor.getValue() : textarea.value);
const setCode = (code) => (editor ? editor.setValue(code) : (textarea.value = code));

// Preview DOM berjalan otomatis setelah jeda singkat; SQL/PHP tetap lewat Run
// karena keduanya memulai worker/runtime penuh untuk setiap eksekusi.
const supportsLivePreview = boot.runner === 'iframe';

/* ---------- autosave draf ---------- */
let saveTimer = null;
let lastSaved = getCode();
function scheduleSave() {
  saveState.textContent = 'Belum tersimpan';
  renderTests(null);
  if (!supportsLivePreview) runState.textContent = 'Kode berubah — tekan Run untuk melihat hasil terbaru';
  clearTimeout(saveTimer);
  saveTimer = setTimeout(saveDraft, 1500);
  scheduleLivePreview();
}

function scheduleLivePreview() {
  if (!supportsLivePreview) return;

  runState.textContent = 'Memperbarui preview...';
  clearTimeout(livePreviewTimer);
  livePreviewTimer = setTimeout(() => {
    if (busy) {
      livePreviewQueued = true;
      return;
    }
    run(false, true);
  }, 500);
}
async function saveDraft() {
  const code = getCode();
  if (code === lastSaved) {
    saveState.textContent = 'Tersimpan';
    return;
  }
  saveState.textContent = 'Menyimpan...';
  const res = await api(boot.draftUrl, { code }, 'POST', { keepalive: true }); // keepalive: tetap terkirim saat tab ditutup
  if (res.ok) {
    lastSaved = code;
    saveState.textContent = 'Draf tersimpan';
  } else {
    saveState.textContent = 'Gagal menyimpan draf';
  }
}
if (editor) editor.on('change', scheduleSave);
else textarea.addEventListener('input', scheduleSave);
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'hidden') saveDraft();
});

/* ---------- runner ---------- */
const runner = createRunner(boot.runner, {
  lang: boot.lang,
  setup: boot.challenge.setup,
  mount: outputBox,
  assetsUrl: boot.assetsUrl,
  onStatus: (text) => (runState.textContent = text),
});

function setBusy(on, label) {
  busy = on;
  runBtn.disabled = on;
  submitBtn.disabled = on || (!boot.is_replay && hearts.hearts === 0);
  runState.replaceChildren();
  if (on) runState.append(el('span', { class: 'spinner' }), ' ' + (label || 'Menjalankan...'));
}

async function run(switchTab = true, livePreview = false) {
  if (busy) {
    if (livePreview) livePreviewQueued = true;
    return;
  }
  if (livePreview) {
    livePreviewQueued = false;
  } else {
    clearTimeout(livePreviewTimer);
    livePreviewQueued = false;
  }
  if (switchTab) showTab('output');
  setBusy(true, livePreview ? 'Memperbarui preview...' : 'Menjalankan...');
  await runner.run(getCode());
  setBusy(false);
  runState.textContent = livePreview ? 'Preview langsung' : 'Selesai dijalankan';

  if (livePreviewQueued) {
    livePreviewQueued = false;
    run(false, true);
  }
}

function renderTests(results) {
  testsBox.replaceChildren();
  const tests = boot.challenge.tests;
  tests.forEach((t, i) => {
    const r = results ? results[i] : null;
    const cls = r ? (r.pass ? 'is-pass' : 'is-fail') : 'is-wait';
    const li = el('li', { class: cls }, icon(r ? (r.pass ? 'check' : 'x') : 'target'),
      el('div', {}, el('span', { text: t.desc }), r && !r.pass && r.detail ? el('div', { class: 'small muted', text: r.detail }) : null));
    testsBox.appendChild(li);
  });
}

function renderFailDots() {
  failDots.replaceChildren('Gagal: ');
  for (let i = 1; i <= boot.max_fails; i++) {
    failDots.appendChild(el('i', { class: i <= failCount ? 'is-fail' : '' }));
  }
}

async function submit() {
  if (busy) return;
  showTab('output');
  setBusy(true, 'Menjalankan test case...');
  const code = getCode();
  const { results, errors } = await runner.test(code, boot.challenge.tests);
  renderTests(results);

  const passed = results.length > 0 && results.every((r) => r.pass);
  lastSummary = results.map((r) => `${r.pass ? 'LULUS' : 'GAGAL'}: ${r.desc}${r.detail ? ' (' + r.detail + ')' : ''}`).join('\n')
    + (errors.length ? '\nError: ' + errors.join(' | ') : '');

  setBusy(true, 'Mengirim hasil...');
  const res = await api(boot.submitUrl, { code, passed });
  setBusy(false);
  lastSaved = code;

  if (res.status === 409 && res.data?.error === 'no_hearts') return showNoHearts(res.data.hearts);
  if (!res.ok || !res.data) {
    runState.textContent = 'Gagal mengirim. Cek koneksi lalu coba lagi.';
    return;
  }

  const r = res.data;
  if (r.passed) return showWin(r);

  failCount = r.fail_count;
  hearts = r.hearts;
  renderFailDots();
  renderHearts(heartsBox, hearts);
  runState.textContent = `Belum lulus${r.hearts_lost ? ` (-${r.hearts_lost} hearts)` : ''}`;

  if (r.cycle_over) return showCycleOver();
  if (!boot.is_replay && hearts.hearts === 0) showNoHearts(hearts);

  if (r.hint === 'manual') offerHint();
  if (r.hint === 'auto') requestHint('auto');
}

/* ---------- AI tutor ---------- */
function offerHint() {
  aiBox.classList.remove('hidden');
  aiBody.replaceChildren(
    el('p', { text: 'Test case belum lulus. Mau petunjuk? AI akan menunjukkan letak kesalahan dan konsep yang perlu dicek, tanpa memberi kode jawaban.' }),
    el('button', { class: 'btn', type: 'button', onclick: () => requestHint('manual') }, icon('bulb'), ' Minta Petunjuk'),
  );
}

async function requestHint(trigger) {
  aiBox.classList.remove('hidden');
  aiBody.replaceChildren(el('p', {}, el('span', { class: 'spinner' }), trigger === 'auto' ? ' AI sedang menyiapkan penjelasan...' : ' Meminta petunjuk...'));
  const res = await api(boot.hintUrl, { trigger, summary: lastSummary });

  if (res.status === 429) {
    aiBody.replaceChildren(el('p', { text: res.data?.message || 'Terlalu sering meminta petunjuk. Tunggu sebentar.' }));
    return;
  }
  const text = res.ok && res.data ? res.data.text : 'Petunjuk sedang tidak tersedia.';
  const fallback = !res.ok || !res.data || res.data.status === 'fallback';
  aiBody.replaceChildren(
    el('div', { class: 'small muted', style: 'margin-bottom:6px', text: trigger === 'auto' ? 'Penjelasan otomatis setelah submit gagal ke-2 atau lebih' : 'Petunjuk' }),
    renderAiText(text),
    fallback ? el('button', { class: 'btn btn-sm', type: 'button', style: 'margin-top:10px', onclick: () => requestHint(trigger) }, icon('refresh'), ' Coba minta lagi') : null,
  );
}

// Teks AI: blok ``` jadi <pre>, sisanya teks biasa (tanpa innerHTML)
function renderAiText(text) {
  const box = el('div', { class: 'ai-text' });
  String(text).split(/```[a-zA-Z]*\n?([\s\S]*?)```/g).forEach((part, i) => {
    if (i % 2 === 1) box.appendChild(el('pre', {}, el('code', { text: part.replace(/\n$/, '') })));
    else if (part.trim()) box.appendChild(window.App.richText(part.trim()));
  });
  return box;
}

/* ---------- modal hasil ---------- */
function openModal(...children) {
  modalBody.replaceChildren(...children);
  modal.classList.remove('hidden');
}

function starsRow(n) {
  const s = el('div', { class: 'stars stars-lg', style: 'margin:8px 0' });
  for (let i = 1; i <= 3; i++) s.appendChild(icon(i <= n ? 'star' : 'star-outline', i <= n ? 'is-on' : 'is-off'));
  return s;
}

function showWin(r) {
  const buttons = el('div', { class: 'row', style: 'justify-content:center;margin-top:18px' });
  if (r.next) buttons.appendChild(el('a', { class: 'btn btn-primary btn-lg', href: r.next }, 'Level berikutnya ', icon('arrow-right')));
  buttons.appendChild(el('a', { class: 'btn', href: boot.levelUrl }, icon('refresh'), ' Main lagi'));
  buttons.appendChild(el('a', { class: 'btn btn-ghost', href: boot.mapUrl }, icon('map'), ' Peta'));
  openModal(
    el('img', { src: boot.kodiUrl, alt: '' }),
    el('h2', { text: boot.levelType === 'final_boss' ? 'Final boss dikalahkan!' : 'Mini boss dikalahkan!' }),
    starsRow(r.stars),
    el('p', { class: 'muted', text: `Lulus di submit ke-${r.submit_no}. Kodemu tersimpan di portofolio.` }),
    buttons,
  );
}

function showCycleOver() {
  openModal(
    el('img', { src: boot.kodiUrl, alt: '' }),
    el('h2', { text: 'Siklus ini gagal' }),
    starsRow(1),
    el('p', { text: `Kamu sudah gagal ${boot.max_fails} kali. Level dicatat 1 bintang (belum lulus). Siklus baru akan memakai variasi tantangan yang berbeda.` }),
    el('div', { class: 'row', style: 'justify-content:center;margin-top:18px' },
      el('a', { class: 'btn btn-boss btn-lg', href: boot.levelUrl }, icon('refresh'), ' Mulai siklus baru'),
      el('a', { class: 'btn btn-ghost', href: boot.libraryUrl }, icon('book'), ' Baca materi')),
  );
}

function showNoHearts(h) {
  hearts = h;
  renderHearts(heartsBox, h);
  setBusy(false);
  const timer = el('div', { class: 'big-timer', 'data-countdown': String(h.next_in), 'data-reload-on-zero': '' });
  openModal(
    el('img', { src: boot.kodiUrl, alt: '' }),
    el('h2', { text: 'Hearts habis' }),
    el('p', { class: 'muted', text: 'Draf kodemu aman tersimpan. Heart berikutnya terisi dalam:' }),
    timer,
    el('div', { class: 'row', style: 'justify-content:center;margin-top:18px' },
      el('a', { class: 'btn', href: boot.libraryUrl }, icon('book'), ' Baca materi'),
      el('a', { class: 'btn btn-ghost', href: boot.mapUrl }, icon('map'), ' Peta')),
  );
  countdowns(modalBody);
}

/* ---------- tab (layar kecil) ---------- */
function showTab(name) {
  root.querySelectorAll('[data-tab]').forEach((b) => b.classList.toggle('is-active', b.dataset.tab === name));
  root.querySelectorAll('[data-pane]').forEach((p) => p.classList.toggle('is-hidden-mobile', p.dataset.pane !== name));
  if (name === 'editor' && editor) setTimeout(() => editor.refresh(), 0);
}
root.querySelectorAll('[data-tab]').forEach((b) => b.addEventListener('click', () => showTab(b.dataset.tab)));

/* ---------- mulai ---------- */
root.querySelector('[data-reset]').addEventListener('click', () => {
  if (window.confirm('Kembalikan editor ke kode awal? Perubahanmu akan hilang.')) setCode(boot.challenge.starter);
});
runBtn.addEventListener('click', () => run());
submitBtn.addEventListener('click', submit);

renderHearts(heartsBox, hearts);
renderFailDots();
renderTests(null);
saveState.textContent = 'Draf tersimpan otomatis';
if (boot.hint === 'manual') offerHint();
if (boot.hint === 'auto') offerHint();
if (!boot.is_replay && hearts.hearts === 0) showNoHearts(hearts);
run(false); // tampilkan hasil kode awal tanpa pindah tab (di HP tetap di editor)
