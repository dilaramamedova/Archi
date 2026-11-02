// Page module for the B2B section — registered for two slugs in app.js:
//   b2b          directory: market toggle, filter bottom-sheet, sort auto-submit,
//                consultation lead modal (plan CTAs)
//   b2b-company  profile: copy-e-mail button
// Every block guards on its own elements, so one module serves both pages.

import popup from '../shared/popup.js';
import { lockScroll, unlockScroll } from '../shared/scroll-lock.js';

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const genericError = () => document.body.dataset.errGeneric || 'Error';
const networkError = () => document.body.dataset.errNetwork || genericError();

function announce(text) {
  const live = document.getElementById('b2Live');
  if (!live || !text) return;
  live.textContent = '';
  setTimeout(() => { live.textContent = text; }, 30);
}

/* ------------------------------------------------- market toggle (pricing) */

function initMarketToggle() {
  const btns = $$('[data-market-btn]');
  const input = document.getElementById('b2MarketInput');
  const hero = document.querySelector('.b2-hero');
  if (!btns.length) return;

  const apply = (market) => {
    btns.forEach((b) => b.setAttribute('aria-selected', b.dataset.marketBtn === market ? 'true' : 'false'));
    $$('[data-market-head]').forEach((el) => { el.hidden = el.dataset.marketHead !== market; });
    $$('[data-market-plans]').forEach((el) => { el.hidden = el.dataset.marketPlans !== market; });
    if (input) input.value = market;
    if (hero) hero.dataset.market = market;
  };

  btns.forEach((b) => b.addEventListener('click', () => apply(b.dataset.marketBtn)));
}

/* ---------------------------------------------------------- sort auto-submit */

function initSort() {
  $$('[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form?.requestSubmit());
  });
}

/* ------------------------------------------------------- filter bottom sheet */

function initFilterSheet() {
  const sheet = document.getElementById('b2Filters');
  const btn = document.getElementById('b2FilterBtn');
  const scrim = document.getElementById('b2Scrim');
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
      setTimeout(() => document.getElementById('b2FiltersClose')?.focus(), 50);
    } else {
      sheet.removeAttribute('role');
      sheet.removeAttribute('aria-modal');
    }
  };

  btn.addEventListener('click', () => setOpen(true));
  document.getElementById('b2FiltersClose')?.addEventListener('click', () => { setOpen(false); btn.focus(); });
  scrim.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sheet.dataset.open === 'true') { setOpen(false); btn.focus(); }
  });
  mq.addEventListener?.('change', (e) => { if (!e.matches) setOpen(false); });
}

/* ----------------------------------------------------- consultation lead modal */

function initLeadModal() {
  const modal = document.getElementById('b2LeadModal');
  const form = document.getElementById('b2LeadForm');
  if (!modal || !form) return;

  const formStep = $('[data-lead-step="form"]', modal);
  const doneStep = $('[data-lead-step="done"]', modal);
  const errorBox = $('[data-lead-error]', form);
  const submit = $('.b2-lead-submit', form);
  const planBox = $('[data-lead-planbox]', form);
  const planName = $('[data-lead-planname]', form);
  const planKey = $('[data-lead-plankey]', form);
  const message = $('#b2LeadMessage', form);
  const prefillTpl = form.dataset.prefill || '';
  let opener = null;
  let locked = false;

  const open = (trigger, plan, key = '') => {
    opener = trigger ?? null;
    if (planKey) planKey.value = key;
    // reset to the form step each time it opens
    if (formStep) formStep.hidden = false;
    if (doneStep) doneStep.hidden = true;

    if (plan) {
      if (planName) planName.textContent = plan;
      if (planBox) planBox.hidden = false;
      if (message && !message.value.trim()) message.value = prefillTpl.replace('__PLAN__', plan);
    } else if (planBox) {
      planBox.hidden = true;
    }

    modal.dataset.on = 'true';
    if (!locked) { lockScroll(); locked = true; }
    setTimeout(() => $('#b2LeadName', form)?.focus(), 60);
  };

  const close = () => {
    if (modal.dataset.on !== 'true') return;
    modal.dataset.on = 'false';
    if (locked) { unlockScroll(); locked = false; }
    opener?.focus?.();
  };

  $$('[data-plan-cta]').forEach((btn) => {
    btn.addEventListener('click', () => open(btn, btn.dataset.planName || '', btn.dataset.planKey || ''));
  });
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

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    showError('');
    submit.disabled = true;
    submit.setAttribute('aria-busy', 'true');

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: new FormData(form),
      });
      const json = await res.json().catch(() => ({}));

      if (res.status === 201 || res.ok) {
        const text = $('[data-lead-done-text]', modal);
        if (text && json.message) text.textContent = json.message;
        if (formStep) formStep.hidden = true;
        if (doneStep) doneStep.hidden = false;
        $('[data-modal-close]', doneStep)?.focus();
        announce(json.message || '');
        return;
      }

      if (res.status === 422 && json.errors) {
        showError(Object.values(json.errors).flat().join(' '));
        return;
      }

      showError(json.message || form.dataset.error || genericError());
    } catch {
      showError(networkError());
    } finally {
      submit.disabled = false;
      submit.removeAttribute('aria-busy');
    }
  });
}

/* ------------------------------------------------------- copy e-mail (profile) */

function initCopy() {
  $$('.b2c-copy[data-copy]').forEach((button) => {
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
        const area = document.createElement('textarea');
        area.value = value;
        area.setAttribute('readonly', '');
        area.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
        document.body.appendChild(area);
        area.select();
        try { ok = document.execCommand('copy'); } catch { ok = false; }
        area.remove();
      }

      if (!ok) { popup.error(networkError()); return; }
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

export default function b2b() {
  initMarketToggle();
  initSort();
  initFilterSheet();
  initLeadModal();
  initCopy();
}
