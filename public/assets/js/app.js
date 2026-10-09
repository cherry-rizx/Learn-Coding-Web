// Helper bersama semua halaman. Tanpa library, tanpa innerHTML untuk data user.
(function () {
  'use strict';

  const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content || '';
  const SVG_NS = 'http://www.w3.org/2000/svg';

  const App = {
    // fetch JSON ke server dengan token CSRF di header
    async api(url, data, method = 'POST', extra = {}) {
      const headers = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };
      if (method !== 'GET') {
        headers['Content-Type'] = 'application/json';
        headers[meta('csrf-header')] = meta('csrf-token');
      }
      try {
        const res = await fetch(url, {
          method,
          headers,
          credentials: 'same-origin',
          body: method === 'GET' ? undefined : JSON.stringify(data || {}),
          ...extra,
        });
        let json = null;
        try { json = await res.json(); } catch (e) { json = null; }
        return { ok: res.ok, status: res.status, data: json };
      } catch (e) {
        return { ok: false, status: 0, data: null };
      }
    },

    // Ikon dari sprite SVG, dibuat lewat DOM API
    icon(name, cls = '') {
      const svg = document.createElementNS(SVG_NS, 'svg');
      svg.setAttribute('class', ('icon ' + cls).trim());
      svg.setAttribute('aria-hidden', 'true');
      const use = document.createElementNS(SVG_NS, 'use');
      use.setAttribute('href', '#' + name); // sprite inline di layout
      svg.appendChild(use);
      return svg;
    },

    el(tag, attrs = {}, ...children) {
      const node = document.createElement(tag);
      for (const [k, v] of Object.entries(attrs)) {
        if (v === null || v === undefined || v === false) continue;
        if (k === 'class') node.className = v;
        else if (k === 'text') node.textContent = v;
        else if (k.startsWith('on')) node.addEventListener(k.slice(2), v);
        else node.setAttribute(k, v === true ? '' : v);
      }
      for (const c of children.flat()) {
        if (c === null || c === undefined || c === false) continue;
        node.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
      }
      return node;
    },

    // Teks dengan `kode` -> elemen <code>, tetap lewat textContent
    richText(text) {
      const frag = document.createDocumentFragment();
      String(text || '').split(/(`[^`]+`)/g).forEach((part) => {
        if (part.startsWith('`') && part.endsWith('`') && part.length > 1) {
          frag.appendChild(App.el('code', { text: part.slice(1, -1) }));
        } else if (part) {
          frag.appendChild(document.createTextNode(part));
        }
      });
      return frag;
    },

    fmtTime(sec) {
      sec = Math.max(0, Math.round(sec));
      const m = Math.floor(sec / 60);
      const s = sec % 60;
      return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    },

    // Hitung mundur untuk elemen [data-countdown]
    countdowns(root = document) {
      root.querySelectorAll('[data-countdown]').forEach((el) => {
        if (el.dataset.ticking) return;
        el.dataset.ticking = '1';
        let left = parseInt(el.dataset.countdown, 10) || 0;
        const end = Date.now() + left * 1000;
        const tick = () => {
          left = Math.round((end - Date.now()) / 1000);
          el.textContent = App.fmtTime(left);
          if (left <= 0) {
            clearInterval(timer);
            if (el.hasAttribute('data-reload-on-zero')) window.location.reload();
            else el.dispatchEvent(new CustomEvent('countdown:end', { bubbles: true }));
          }
        };
        const timer = setInterval(tick, 1000);
        tick();
      });
    },

    // Render deretan hearts (dipakai halaman kuis & boss)
    renderHearts(container, hearts) {
      container.replaceChildren();
      for (let i = 1; i <= hearts.max; i++) {
        container.appendChild(App.icon(i <= hearts.hearts ? 'heart' : 'heart-outline', i <= hearts.hearts ? '' : 'is-empty'));
      }
      container.setAttribute('aria-label', `${hearts.hearts} dari ${hearts.max} hearts`);
    },
  };

  window.App = App;

  document.addEventListener('DOMContentLoaded', () => {
    App.countdowns();

    // Menu navigasi di HP
    document.querySelector('[data-toggle-nav]')?.addEventListener('click', () => {
      document.getElementById('main-nav')?.classList.toggle('is-open');
    });

    // Tutup dropdown akun saat klik di luar
    document.addEventListener('click', (e) => {
      document.querySelectorAll('details.user-menu[open]').forEach((d) => {
        if (!d.contains(e.target)) d.removeAttribute('open');
      });
    });

    // Konfirmasi sebelum submit form tertentu
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
      form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) e.preventDefault();
      });
    });

    // Carousel kartu materi
    document.querySelectorAll('[data-cards]').forEach((box) => {
      const cards = [...box.querySelectorAll('.mcard')];
      const dots = [...box.querySelectorAll('[data-dots] span')];
      const prev = box.querySelector('[data-prev]');
      const next = box.querySelector('[data-next]');
      let i = 0;
      const show = (n) => {
        i = Math.max(0, Math.min(cards.length - 1, n));
        cards.forEach((c, k) => c.classList.toggle('is-active', k === i));
        dots.forEach((d, k) => d.classList.toggle('is-active', k === i));
        if (prev) prev.disabled = i === 0;
        if (next) next.disabled = i === cards.length - 1;
      };
      prev?.addEventListener('click', () => show(i - 1));
      next?.addEventListener('click', () => show(i + 1));
    });

    // Tutorial pertama kali
    const tut = document.getElementById('tutorial');
    if (tut) {
      const steps = [...tut.querySelectorAll('.tut-step')];
      const dots = [...tut.querySelectorAll('[data-tut-dots] span')];
      const nextBtn = tut.querySelector('[data-tut-next]');
      let step = 0;
      const show = (n) => {
        step = n;
        steps.forEach((s, k) => s.classList.toggle('is-active', k === n));
        dots.forEach((d, k) => d.classList.toggle('is-active', k === n));
        nextBtn.firstChild.textContent = n === steps.length - 1 ? 'Ayo mulai! ' : 'Lanjut ';
      };
      const close = () => {
        tut.classList.add('hidden');
        if (tut.dataset.seen === '0') {
          tut.dataset.seen = '1';
          App.api(tut.dataset.doneUrl, {});
        }
      };
      nextBtn.addEventListener('click', () => (step < steps.length - 1 ? show(step + 1) : close()));
      tut.querySelector('[data-tut-skip]').addEventListener('click', close);
      document.querySelector('[data-open-tutorial]')?.addEventListener('click', () => {
        show(0);
        tut.classList.remove('hidden');
      });
    }
  });
})();
