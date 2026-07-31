// Page module for "sell" — ported from the inline <script> of the old sell.html:
// image upload preview, condition chips and the success modal. The listing is kept in
// localStorage ('archi-products') and rendered back by the home page grid.
// Routes and labels come from data-* attributes on the form — never hardcoded here.

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
  let imgData = '';
  const upBox = $('upBox');
  const upInput = $('upInput');
  const upPrev = $('upPrev');

  upInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
      imgData = ev.target.result;
      upPrev.src = imgData;
      upPrev.hidden = false;
      upBox.dataset.has = 'true';
    };
    reader.readAsDataURL(file);
  });

  $('upRm').addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    imgData = '';
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
  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const name = $('pName').value.trim();
    const cat = $('pCat').value;
    const price = parseFloat($('pPrice').value);
    const err = $('slErr');

    if (!name || !cat || isNaN(price) || !(price >= 0)) {
      err.dataset.on = 'true'; // <x-ui.alert> visibility contract
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }
    err.dataset.on = 'false';

    const old = parseFloat($('pOld').value);
    const hasOld = !isNaN(old) && old > price;

    const product = {
      name,
      cat,
      now: price.toFixed(2) + ' ' + d.lCurrency,
      old: hasOld ? old.toFixed(2) + ' ' + d.lCurrency : '',
      off: hasOld ? '-' + Math.round((1 - price / old) * 100) + '%' : '',
      rate: cond, // shown as the "new / used" badge on the card
      reviews: d.lReviewsZero,
      desc: $('pDesc').value.trim(),
      img: imgData || '',
      mine: true,
    };

    // persist so the listing shows up on the site
    try {
      const list = JSON.parse(localStorage.getItem('archi-products') || '[]');
      list.unshift(product);
      localStorage.setItem('archi-products', JSON.stringify(list));
    } catch (ex) {
      // quota exceeded (large image) — the modal still confirms the listing
    }

    /* ---- success modal ---- */
    $('okName').textContent = '“' + name + '”';

    let authed = false;
    try {
      authed = localStorage.getItem('archi-auth') === '1';
    } catch (ex) {
      // localStorage blocked — treat the visitor as a guest
    }

    const nudge = $('regNudge');
    const btns = $('okBtns');
    btns.replaceChildren();

    if (authed) {
      nudge.classList.add('hidden');
      btns.append(
        link(BTN_PRIMARY, d.urlHome, d.lViewSite),
        link(BTN_GHOST, d.urlSell, d.lAddAnother)
      );
    } else {
      // sign-up is offered, but the product is already published — nobody is lost
      nudge.classList.remove('hidden');
      btns.append(
        link(BTN_PRIMARY, d.urlRegister, d.lSignUp),
        link(BTN_GHOST, d.urlHome, d.lNotNow)
      );
    }

    openModal();
  });

  /* ---- success modal open/close ---- */
  // Same contract as the shared login modal: scroll lock, focus moved inside, Escape closes.
  const ov = $('okOv');

  function openModal() {
    // <x-ui.modal> visibility contract: data-on flips the overlay to display:flex
    ov.dataset.on = 'true';
    ov.classList.add('animate-[fadeIn_0.2s_ease]');
    document.body.dataset.lmLock = 'true';
    const first = $('okBtns').firstElementChild;
    if (first) setTimeout(() => first.focus(), 60);
  }

  function closeModal() {
    if (ov.dataset.on !== 'true') return;
    ov.dataset.on = 'false';
    ov.classList.remove('animate-[fadeIn_0.2s_ease]');
    delete document.body.dataset.lmLock;
    $('pSubmit').focus();
  }

  ov.querySelector('[data-modal-close]').addEventListener('click', closeModal);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
  });

  // clicking the overlay backdrop does not close it — the choice should be deliberate,
  // the close button and Escape are the deliberate ways out
}
