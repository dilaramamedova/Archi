// Page module for the tender section — registered for three slugs in app.js:
//   tenders         board: sort auto-submit, phone filter sheet, bookmark toggles
//   tender          detail: "Yadda saxla" toggle, copy-e-mail button
//   tenders-locked  lock card: login modal for guests, package request modal
// Every block guards on its own elements, so one module serves all three pages.

import popup from '../shared/popup.js';
import { clearErrors, showErrors } from '../shared/auth.js';
import { lockScroll, unlockScroll } from '../shared/scroll-lock.js';
import { open as openLoginModal } from '../shared/login-modal.js';

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const genericError = () => document.body.dataset.errGeneric || 'Error';
const networkError = () => document.body.dataset.errNetwork || genericError();

function announce(text) {
  const live = document.getElementById('tdLive');
  if (!live || !text) return;
  live.textContent = '';
  // a fresh text node is what makes screen readers re-read an identical message
  setTimeout(() => { live.textContent = text; }, 30);
}

/* ---------------------------------------------------------------- board */

function initSort() {
  $$('[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form?.requestSubmit());
  });
}

function initFilterSheet() {
  const sheet = document.getElementById('tdFilters');
  const btn = document.getElementById('tdFilterBtn');
  const scrim = document.getElementById('tdScrim');
  if (!sheet || !btn || !scrim) return;

  const mq = window.matchMedia('(max-width: 980px)');
  let locked = false;

  const setOpen = (open) => {
    sheet.dataset.open = open ? 'true' : 'false';
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    scrim.hidden = !open;
    if (open && !locked) { lockScroll(); locked = true; }
    if (!open && locked) { unlockScroll(); locked = false; }
    if (open) {
      sheet.setAttribute('role', 'dialog');
      sheet.setAttribute('aria-modal', 'true');
      setTimeout(() => document.getElementById('tdFiltersClose')?.focus(), 50);
    } else {
      sheet.removeAttribute('role');
      sheet.removeAttribute('aria-modal');
    }
  };

  btn.addEventListener('click', () => setOpen(true));
  document.getElementById('tdFiltersClose')?.addEventListener('click', () => { setOpen(false); btn.focus(); });
  scrim.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sheet.dataset.open === 'true') { setOpen(false); btn.focus(); }
  });
  // Rotating a tablet past the breakpoint must not leave the page scroll-locked.
  mq.addEventListener?.('change', (e) => { if (!e.matches) setOpen(false); });
}

/* ------------------------------------------------- bookmark (both pages) */

async function toggleSave(button) {
  if (button.getAttribute('aria-busy') === 'true') return;
  button.setAttribute('aria-busy', 'true');

  try {
    const res = await fetch(button.dataset.saveUrl, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });
    const json = await res.json().catch(() => ({}));

    if (!res.ok) {
      popup.error(json.message || genericError());
      return;
    }

    const saved = Boolean(json.saved);
    const page = button.closest('.td-page');
    const label = saved ? page?.dataset.savedLabel : page?.dataset.saveLabel;

    button.setAttribute('aria-pressed', saved ? 'true' : 'false');
    if (label) {
      const text = button.querySelector('.lbl');
      if (text) {
        text.textContent = label;
      } else {
        button.setAttribute('aria-label', label);
        button.title = label;
      }
    }
    announce(json.message);
  } catch {
    popup.error(networkError());
  } finally {
    button.removeAttribute('aria-busy');
  }
}

function initSave() {
  $$('[data-save-url]').forEach((button) => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      toggleSave(button);
    });
  });
}

/* ---------------------------------------------------------------- detail */

function initCopy() {
  $$('.tdd-copy[data-copy]').forEach((button) => {
    const label = button.querySelector('.lbl');
    const original = label?.textContent ?? '';
    let timer = null;

    button.addEventListener('click', async () => {
      const value = button.dataset.copy;
      let ok = false;

      try {
        await navigator.clipboard.writeText(value);
        ok = true;
      } catch {
        // http:// or an older browser — fall back to a temporary textarea
        const area = document.createElement('textarea');
        area.value = value;
        area.setAttribute('readonly', '');
        area.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
        document.body.appendChild(area);
        area.select();
        try { ok = document.execCommand('copy'); } catch { ok = false; }
        area.remove();
      }

      if (!ok) return;
      button.dataset.done = 'true';
      if (label) label.textContent = button.dataset.copied;
      announce(button.dataset.copied);
      clearTimeout(timer);
      timer = setTimeout(() => {
        delete button.dataset.done;
        if (label) label.textContent = original;
      }, 2000);
    });
  });
}

/* ----------------------------------------------------------- lock card */

function initLoginButtons() {
  // Guests: same modal as the navbar "Daxil ol", but sign-in lands back on this URL
  // (a tender link shared by a colleague opens the tender after login).
  $$('[data-tender-login]').forEach((el) => {
    el.addEventListener('click', (e) => {
      if (!document.getElementById('lmOverlay')) return; // no modal → follow the /login link
      e.preventDefault();
      openLoginModal({ redirectTo: window.location.href });
    });
  });
}

function initRequestModal() {
  const modal = document.getElementById('tdReqModal');
  const form = document.getElementById('tdReqForm');
  if (!modal || !form) return;

  const formStep = $('[data-req-step="form"]', modal);
  const doneStep = $('[data-req-step="done"]', modal);
  const errorBox = $('[data-req-error]', form);
  const submit = $('.td-req-submit', form);
  let opener = null;
  let locked = false;

  const open = (trigger) => {
    opener = trigger ?? null;
    modal.dataset.on = 'true';
    if (!locked) { lockScroll(); locked = true; }
    setTimeout(() => {
      const target = doneStep && !doneStep.hidden
        ? $('[data-modal-close]', doneStep)
        : $('input:checked, input:not([type=hidden]), textarea', form);
      target?.focus();
    }, 60);
  };

  const close = () => {
    if (modal.dataset.on !== 'true') return;
    modal.dataset.on = 'false';
    if (locked) { unlockScroll(); locked = false; }
    opener?.focus?.();
  };

  $$('[data-open-request]').forEach((btn) => btn.addEventListener('click', () => open(btn)));
  $$('[data-modal-close]', modal).forEach((btn) => btn.addEventListener('click', close));
  modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.dataset.on === 'true') close();
  });

  const showError = (text) => {
    if (!errorBox) return;
    errorBox.textContent = text;
    errorBox.hidden = !text;
  };

  const switchToPending = () => {
    $$('[data-lock-block="default"]').forEach((el) => { el.hidden = true; });
    $$('[data-lock-block="pending"]').forEach((el) => { el.hidden = false; });
    $$('[data-hide-on-pending]').forEach((el) => { el.hidden = true; });
    // The request button is gone, so focus would otherwise fall back to <body>.
    opener = $('[data-lock-block="pending"] a') ?? null;
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);
    showError('');

    const data = new FormData(form);
    const payload = {
      tender_package_id: data.get('tender_package_id') || null,
      full_name: (data.get('full_name') || '').toString().trim(),
      company_name: (data.get('company_name') || '').toString().trim(),
      phone: (data.get('phone') || '').toString().trim(),
      email: (data.get('email') || '').toString().trim(),
      message: (data.get('message') || '').toString().trim(),
    };

    submit.disabled = true;
    submit.setAttribute('aria-busy', 'true');

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
      });
      const json = await res.json().catch(() => ({}));

      if (res.status === 201 || res.ok) {
        const text = $('[data-req-done-text]', modal);
        if (text) text.textContent = json.message || form.dataset.sent;
        formStep.hidden = true;
        doneStep.hidden = false;
        $('[data-modal-close]', doneStep)?.focus();
        switchToPending();
        return;
      }

      if (res.status === 422 && json.errors && Object.keys(json.errors).length) {
        showErrors(form, json.errors, false);
        form.querySelector('.ui-control-error')?.focus();
        return;
      }

      // 422 without field errors = already pending / already active (TenderController)
      showError(json.message || form.dataset.error || genericError());
    } catch {
      showError(networkError());
    } finally {
      submit.disabled = false;
      submit.removeAttribute('aria-busy');
    }
  });
}

export default function tenders() {
  initSort();
  initFilterSheet();
  initSave();
  initCopy();
  initLoginButtons();
  initRequestModal();
}
