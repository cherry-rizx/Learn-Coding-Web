// Alur kuis. Browser hanya menampilkan soal & mengirim jawaban; penilaian di server.
(function () {
  'use strict';

  const { api, el, icon, richText, renderHearts } = window.App;
  const boot = JSON.parse(document.getElementById('quiz-boot').textContent);

  const root = document.getElementById('quiz');
  const stage = root.querySelector('[data-stage]');
  const progressBar = root.querySelector('[data-progress]');
  const heartsBox = root.querySelector('[data-hearts-live]');
  const counter = root.querySelector('[data-counter]');
  const checkBtn = root.querySelector('[data-check]');
  const footer = root.querySelector('[data-footer]');

  const fb = document.querySelector('[data-feedback]');
  const fbIcon = fb.querySelector('[data-fb-icon]');
  const fbTitle = fb.querySelector('[data-fb-title]');
  const fbBody = fb.querySelector('[data-fb-body]');
  const fbNext = fb.querySelector('[data-fb-next]');

  let state = null;
  let picked = null; // mcq: id opsi, arrange: daftar id
  let busy = false;
  let onFeedbackNext = null;

  async function load() {
    const res = await api(boot.stateUrl, null, 'GET');
    if (!res.ok || !res.data) return fatal();
    if (res.data.restart) return (window.location.href = boot.levelUrl);
    render(res.data);
  }

  function fatal(msg) {
    stage.replaceChildren(el('div', { class: 'alert alert-error' }, icon('alert'), el('div', { text: msg || 'Gagal memuat soal. Coba muat ulang halaman.' })));
  }

  function render(s) {
    state = s;
    picked = s.question.type === 'arrange' ? [] : null;
    closeFeedback();

    progressBar.style.width = (s.index / s.total) * 100 + '%';
    renderHearts(heartsBox, s.hearts);
    counter.textContent = `Soal ${s.index + 1} dari ${s.total}` + (s.is_replay ? ' · mode ulang (tanpa potong hearts)' : '');

    if (s.blocked) return renderBlocked(s.hearts);

    footer.classList.remove('hidden');
    const q = s.question;
    const nodes = [
      el('div', { class: 'q-type', text: q.type === 'arrange' ? 'Susun potongan berikut' : 'Pilih jawaban yang benar' }),
      el('div', { class: 'q-prompt' }, richText(q.prompt)),
    ];

    if (s.phase === 'retry' && s.clue) {
      nodes.push(el('div', { class: 'alert alert-warn' }, icon('bulb'), el('div', {}, el('strong', { text: 'Clue: ' }), richText(s.clue))));
    }
    if (s.phase === 'reveal' && s.reveal) {
      nodes.push(el('div', { class: 'alert alert-error' }, icon('eye'), el('div', {},
        el('strong', { text: 'Jawaban benar: ' }),
        el('div', { class: 'reveal-box', text: s.reveal.text }),
        s.explanation ? el('p', { style: 'margin:8px 0 0' }, richText(s.explanation)) : null,
        el('p', { class: 'small', style: 'margin:6px 0 0', text: 'Masukkan jawaban benar di atas untuk lanjut (tidak ada penalti).' }),
      )));
    }

    nodes.push(q.type === 'arrange' ? renderArrange(q) : renderMcq(q));
    stage.replaceChildren(...nodes);

    checkBtn.textContent = s.phase === 'first' ? 'Periksa' : s.phase === 'retry' ? 'Coba lagi' : 'Kirim jawaban benar';
    updateCheck();
  }

  function renderMcq(q) {
    const box = el('div', { class: 'options', role: 'radiogroup' });
    const keys = 'ABCDEFGH';
    q.options.forEach((opt, i) => {
      const btn = el('button', {
        class: 'option' + (looksLikeCode(opt.content) ? ' option-code' : ''),
        type: 'button',
        role: 'radio',
        'aria-checked': 'false',
        'data-id': opt.id,
      }, el('span', { class: 'key', text: keys[i] || String(i + 1) }), el('span', { text: opt.content }));
      btn.addEventListener('click', () => {
        if (busy) return;
        picked = opt.id;
        box.querySelectorAll('.option').forEach((b) => {
          const on = Number(b.dataset.id) === opt.id;
          b.classList.toggle('is-selected', on);
          b.setAttribute('aria-checked', on ? 'true' : 'false');
        });
        updateCheck();
      });
      box.appendChild(btn);
    });
    return box;
  }

  function renderArrange(q) {
    const answer = el('div', { class: 'arrange-answer', 'aria-label': 'Jawabanmu' });
    const bank = el('div', { class: 'arrange-bank', 'aria-label': 'Potongan tersedia' });
    const tokens = new Map();

    q.options.forEach((opt) => {
      const token = el('button', { class: 'token', type: 'button', text: opt.content });
      tokens.set(opt.id, token);
      token.addEventListener('click', () => {
        if (busy || token.classList.contains('is-used')) return;
        token.classList.add('is-used');
        picked.push(opt.id);
        const chip = el('button', { class: 'token', type: 'button', text: opt.content, title: 'Ketuk untuk melepas' });
        chip.addEventListener('click', () => {
          if (busy) return;
          const idx = [...answer.children].indexOf(chip);
          picked.splice(idx, 1);
          chip.remove();
          token.classList.remove('is-used');
          updateCheck();
        });
        answer.appendChild(chip);
        updateCheck();
      });
      bank.appendChild(token);
    });

    return el('div', {}, answer, bank);
  }

  function looksLikeCode(text) {
    return /[<>{};=()$]/.test(text);
  }

  function updateCheck() {
    checkBtn.disabled = busy || picked === null || (Array.isArray(picked) && picked.length === 0);
  }

  async function check() {
    if (checkBtn.disabled) return;
    busy = true;
    updateCheck();
    const res = await api(boot.answerUrl, { answer: picked });
    busy = false;

    if (res.status === 409 && res.data?.error === 'no_hearts') return renderBlocked(res.data.hearts);
    if (res.status === 409 && res.data?.error === 'restart') return (window.location.href = boot.levelUrl);
    if (!res.ok || !res.data) {
      updateCheck();
      return openFeedback('bad', 'Koneksi bermasalah', 'Jawaban belum terkirim. Coba lagi.', 'Tutup', closeFeedback);
    }

    const r = res.data;
    const advance = () => (r.finished ? showResult(r.finished) : render(r.state));

    switch (r.result) {
      case 'correct':
        markOptions(picked, 'is-correct');
        openFeedback('good', pick(['Benar!', 'Mantap!', 'Tepat sekali!']), r.explanation, 'Lanjut', advance);
        break;
      case 'correct_retry':
        markOptions(picked, 'is-correct');
        openFeedback('good', 'Benar, setelah clue!', r.explanation, 'Lanjut', advance);
        break;
      case 'copied':
        openFeedback('good', 'Sip, sudah benar.', 'Ingat-ingat jawaban ini ya.', 'Lanjut', advance);
        break;
      case 'wrong': {
        markOptions(picked, 'is-wrong');
        const lost = r.hearts_lost > 0 ? `-${r.hearts_lost} heart. ` : '';
        state.hearts = r.hearts;
        renderHearts(heartsBox, r.hearts);
        openFeedback('hint', 'Kurang tepat', lost + 'Baca clue-nya, lalu coba sekali lagi.', 'Coba lagi', () =>
          render(Object.assign({}, state, { phase: 'retry', clue: r.clue })));
        break;
      }
      case 'reveal':
        markOptions(picked, 'is-wrong');
        markOptions(r.reveal.ids, 'is-correct');
        openFeedback('bad', 'Masih belum tepat', null, 'Masukkan jawaban benar', () =>
          render(Object.assign({}, state, { phase: 'reveal', reveal: r.reveal, explanation: r.explanation })), r.reveal.text, r.explanation);
        break;
      case 'must_match':
        openFeedback('bad', 'Belum sama', 'Masukkan jawaban persis seperti jawaban benar yang ditampilkan.', 'Ulangi', () => render(state));
        break;
      default:
        fatal();
    }
  }

  function markOptions(ids, cls) {
    if (state.question.type !== 'mcq') return;
    const list = Array.isArray(ids) ? ids : [ids];
    stage.querySelectorAll('.option').forEach((b) => {
      b.disabled = true;
      if (list.includes(Number(b.dataset.id))) b.classList.add(cls);
    });
  }

  function openFeedback(kind, title, body, btnLabel, next, revealText, explanation) {
    fb.className = 'feedback is-open is-' + kind;
    fbIcon.replaceChildren(icon(kind === 'good' ? 'check' : kind === 'hint' ? 'bulb' : 'x'));
    fbTitle.textContent = title;
    const parts = [];
    if (revealText) {
      parts.push(el('div', { text: 'Jawaban yang benar:' }), el('div', { class: 'reveal-box', text: revealText }));
    }
    if (body) parts.push(el('p', {}, richText(body)));
    if (explanation) parts.push(el('p', {}, richText(explanation)));
    fbBody.replaceChildren(...parts);
    fbNext.textContent = btnLabel;
    onFeedbackNext = next;
    footer.classList.add('hidden');
    fbNext.focus();
  }

  function closeFeedback() {
    fb.className = 'feedback';
    footer.classList.remove('hidden');
    onFeedbackNext = null;
  }

  function renderBlocked(hearts) {
    closeFeedback();
    footer.classList.add('hidden');
    renderHearts(heartsBox, hearts);
    const timer = el('div', { class: 'big-timer', 'data-countdown': String(hearts.next_in) });
    timer.addEventListener('countdown:end', load);
    stage.replaceChildren(el('div', { class: 'card wait-box' },
      el('img', { src: boot.kodiUrl, alt: '', width: '110' }),
      el('h2', { text: 'Hearts kamu habis' }),
      el('p', { class: 'muted', text: 'Progres kuis tersimpan. Heart berikutnya terisi dalam:' }),
      timer,
      el('div', { class: 'row', style: 'justify-content:center;margin-top:16px' },
        el('a', { class: 'btn', href: boot.libraryUrl }, icon('book'), ' Baca materi'),
        el('a', { class: 'btn btn-ghost', href: boot.mapUrl }, icon('map'), ' Ke peta')),
    ));
    window.App.countdowns(stage);
  }

  function showResult(f) {
    closeFeedback();
    footer.classList.add('hidden');
    progressBar.style.width = '100%';
    const stars = el('div', { class: 'stars stars-lg' });
    for (let i = 1; i <= 3; i++) stars.appendChild(icon(i <= f.stars ? 'star' : 'star-outline', i <= f.stars ? 'is-on' : 'is-off'));

    const buttons = el('div', { class: 'row', style: 'justify-content:center;margin-top:20px' });
    if (f.passed && f.next) buttons.appendChild(el('a', { class: 'btn btn-primary btn-lg', href: f.next }, 'Level berikutnya ', icon('arrow-right')));
    buttons.appendChild(el('a', { class: 'btn' + (f.passed ? '' : ' btn-primary btn-lg'), href: f.retry }, icon('refresh'), f.passed ? ' Ulangi' : ' Coba lagi'));
    buttons.appendChild(el('a', { class: 'btn btn-ghost', href: boot.mapUrl }, icon('map'), ' Peta'));

    stage.replaceChildren(el('div', { class: 'card result' },
      el('img', { src: boot.kodiUrl, alt: '' }),
      el('h1', { text: f.passed ? (f.stars === 3 ? 'Sempurna!' : 'Level lulus!') : 'Belum lulus' }),
      stars,
      el('p', { class: 'muted', text: `${f.correct} dari ${f.total} benar di jawaban pertama.` }),
      el('p', { text: f.passed ? 'Bintang terbaikmu di level ini sudah disimpan.' : 'Butuh minimal 7 jawaban benar untuk lulus. Baca lagi materinya, lalu coba lagi!' }),
      buttons,
    ));
  }

  function pick(list) {
    return list[Math.floor(Math.random() * list.length)];
  }

  checkBtn.addEventListener('click', check);
  fbNext.addEventListener('click', () => onFeedbackNext && onFeedbackNext());

  // Keyboard: 1-8 pilih opsi, Enter periksa / lanjut
  document.addEventListener('keydown', (e) => {
    if (e.target.matches('input, textarea')) return;
    if (e.key === 'Enter') {
      if (fb.classList.contains('is-open')) {
        if (document.activeElement !== fbNext) { e.preventDefault(); fbNext.click(); }
      } else if (!checkBtn.disabled && document.activeElement?.tagName !== 'BUTTON') {
        e.preventDefault();
        check();
      }
    }
    if (/^[1-8]$/.test(e.key) && !fb.classList.contains('is-open')) {
      stage.querySelectorAll('.option')[Number(e.key) - 1]?.click();
    }
  });

  load();
})();
