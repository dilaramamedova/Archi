// Page module for "sell" — handles image upload preview, condition chips, and form
// submission via AJAX to the backend. Products are saved to the database and require
// admin approval before appearing in the catalog.

import { lockScroll, unlockScroll } from '../shared/scroll-lock.js';

// Tailwind class sets for the modal buttons (built in JS, scanned via @source "../js").
const BTN_BASE = 'flex h-[50px] items-center justify-center text-base font-semibold transition duration-200';
const BTN_PRIMARY = BTN_BASE + ' bg-yellow text-ink hover:brightness-[.93]';
const BTN_GHOST = BTN_BASE + ' border border-black/20 bg-white text-ink hover:bg-gray-soft';

const link = (className, href, label) => {
  const a = document.createElement('a');
  a.className = className;
  a.href = href;
  a.textContent = label;
  return a;
};

export default function init() {
  const form = document.getElementById('sellForm');
  if (!form) return;

  const $ = (id) => document.getElementById(id);
  const d = form.dataset;

  /* ---- image upload + preview ---- */
  let selectedFile = null;
  const upBox = $('upBox');
  const upInput = $('upInput');
  const upPrev = $('upPrev');

  upInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    selectedFile = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
      upPrev.src = ev.target.result;
      upPrev.hidden = false;
      upBox.dataset.has = 'true';
    };
    reader.readAsDataURL(file);
  });

  $('upRm').addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    selectedFile = null;
    upInput.value = '';
    upPrev.hidden = true;
    upBox.dataset.has = 'false';
  });

  /* ---- condition chips (role=radiogroup) ---- */
  const chips = [...document.querySelectorAll('#pCond .sl-chip')];
  const preselected = chips.find((c) => c.dataset.on === 'true');
  let cond = preselected ? preselected.dataset.v : '';

  // roving tabindex: only the checked chip is in the tab order, arrows move between them
  const select = (chip, focus) => {
    chips.forEach((x) => {
      const on = x === chip;
      x.dataset.on = String(on);
      x.setAttribute('aria-checked', String(on));
      x.tabIndex = on ? 0 : -1;
    });
    cond = chip.dataset.v;
    if (focus) chip.focus();
  };

  chips.forEach((c, i) => {
    c.addEventListener('click', () => select(c, false));
    c.addEventListener('keydown', (e) => {
      const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
      if (!step) return;
      e.preventDefault();
      select(chips[(i + step + chips.length) % chips.length], true);
    });
  });

  /* ---- submit ---- */
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const name = $('pName').value.trim();
    const cat = $('pCat').value;
    const price = parseFloat($('pPrice').value);

    const missingFields = !name || !cat || isNaN(price) || !(price >= 0);
    // The listing is published straight away, so the server requires a photo; check it
    // here too or the seller only ever sees a bare 422 — and say which one is missing.
    if (missingFields || !selectedFile) {
      archiPopup.error(missingFields ? d.lFormError : d.lImageRequired);
      return;
    }

    const old = parseFloat($('pOld').value);
    const desc = $('pDesc').value.trim();

    // Determine condition value for the backend
    const conditionMap = {};
    conditionMap[d.lCondNew || 'Yeni'] = 'new';
    conditionMap[d.lCondUsed || 'İşlənmiş'] = 'used';
    const conditionValue = conditionMap[cond] || 'new';

    // Build FormData for multipart upload
    const fd = new FormData();
    fd.append('name', name);
    // Only send a real category id — fallback (non-DB) options have empty values.
    if (/^\d+$/.test(cat)) {
      fd.append('category_id', cat);
    }
    fd.append('price', price);
    if (!isNaN(old) && old > 0) {
      fd.append('old_price', old);
    }
    fd.append('condition', conditionValue);
    fd.append('description', desc);
    if (selectedFile) {
      fd.append('image', selectedFile);
    }

    // Disable submit button during request
    const submitBtn = $('pSubmit');
    submitBtn.disabled = true;

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      const res = await fetch(d.urlStore || '/sell', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
        body: fd,
      });

      if (res.status === 401) {
        // Not authenticated — redirect to login
        window.location.href = d.urlLogin || '/login';
        return;
      }

      if (res.status === 422) {
        // Validation error
        const data = await res.json();
        const messages = data.errors ? Object.values(data.errors).flat() : [data.message || document.body.dataset.errGeneric];
        archiPopup.error(messages.join('. '));
        return;
      }

      if (!res.ok) {
        throw new Error('Server error');
      }

      // Success — show the modal
      const data = await res.json();
      $('okName').textContent = '"' + name + '"';

      const nudge = $('regNudge');
      const btns = $('okBtns');
      btns.replaceChildren();

      // User is authenticated (POST requires auth), so show authenticated flow
      nudge.classList.add('hidden');
      btns.append(
        link(BTN_PRIMARY, d.urlHome, d.lViewSite),
        link(BTN_GHOST, d.urlSell, d.lAddAnother)
      );

      openModal();
    } catch (ex) {
      archiPopup.error(d.lServerError || document.body.dataset.errGeneric);
    } finally {
      submitBtn.disabled = false;
    }
  });

  /* ---- success modal open/close ---- */
  // Same contract as the shared login modal: scroll lock, focus moved inside, Escape closes.
  const ov = $('okOv');

  let modalLocked = false;

  function openModal() {
    // <x-ui.modal> visibility contract: data-on flips the overlay to display:flex
    ov.dataset.on = 'true';
    ov.classList.add('animate-[fadeIn_0.2s_ease]');
    document.body.dataset.lmLock = 'true';
    if (!modalLocked) { lockScroll(); modalLocked = true; }
    const first = $('okBtns').firstElementChild;
    if (first) setTimeout(() => first.focus(), 60);
  }

  function closeModal() {
    if (ov.dataset.on !== 'true') return;
    ov.dataset.on = 'false';
    ov.classList.remove('animate-[fadeIn_0.2s_ease]');
    delete document.body.dataset.lmLock;
    if (modalLocked) { unlockScroll(); modalLocked = false; }
    $('pSubmit').focus();
  }

  ov.querySelector('[data-modal-close]').addEventListener('click', closeModal);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
  });
}
