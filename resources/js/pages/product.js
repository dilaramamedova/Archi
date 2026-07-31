// Page module for "product" — ported from the inline <script> of the old product.html.
// The card grids, the review cards and the rating distribution are server-rendered now,
// so only the interactive parts remain. State classes moved to data-* attributes
// (ARCHITECTURE.md §7.1); the round card cursor comes from shared/cursor.js.

// Gallery: picking a thumbnail cross-fades the main image.
function initGallery() {
  const main = document.getElementById('pdMainImg');
  const thumbs = [...document.querySelectorAll('#pdThumbs .pd-thumb')];
  if (!main || !thumbs.length) return;

  thumbs.forEach((t) =>
    t.addEventListener('click', () => {
      thumbs.forEach((x) => (x.dataset.on = 'false'));
      t.dataset.on = 'true';
      const img = t.querySelector('img');
      if (!img) return;
      const src = img.getAttribute('src');
      main.style.opacity = 0;
      setTimeout(() => {
        main.src = src;
        main.style.opacity = 1;
      }, 120);
    })
  );
}

function initQty() {
  const qty = document.getElementById('qtyVal');
  const minus = document.getElementById('qtyMinus');
  const plus = document.getElementById('qtyPlus');
  if (!qty || !minus || !plus) return;

  const value = () => +qty.value || 1;
  minus.addEventListener('click', () => { qty.value = Math.max(1, value() - 1); });
  plus.addEventListener('click', () => { qty.value = value() + 1; });
  qty.addEventListener('change', () => { if (value() < 1) qty.value = 1; });
}

// The navbar badge is rendered on load by shared/navbar.js; after a cart write the
// count has to be refreshed here.
function syncCartBadge() {
  const badge = document.getElementById('navCartCount');
  if (!badge) return;
  try {
    const cart = JSON.parse(localStorage.getItem('archi-cart') || '[]');
    badge.textContent = cart.length;
    badge.style.display = cart.length ? 'flex' : 'none';
  } catch (e) {
    // localStorage blocked — skip silently
  }
}

// Rebuilds the button content as <img> + text node, exactly like the old markup.
function setButton(btn, icon, label) {
  btn.textContent = '';
  const img = document.createElement('img');
  img.src = icon;
  img.alt = '';
  btn.append(img, document.createTextNode(label));
}

function initAddToCart() {
  const btn = document.getElementById('addCart');
  if (!btn) return;
  const d = btn.dataset;

  function store() {
    try {
      const name = (document.querySelector('.pd-info h1') || {}).textContent || '';
      const cat = (document.querySelector('.pd-info .cat') || {}).textContent || '';
      const now = (document.querySelector('.pd-price .now') || {}).textContent || '0';
      const price = parseFloat(now.replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
      const cart = JSON.parse(localStorage.getItem('archi-cart') || '[]');
      if (!cart.some((c) => c.name === name.trim())) {
        cart.push({
          name: name.trim(),
          brand: d.cartBrand || '',
          cat: cat.trim(),
          calc: d.cartUnit || '',
          price,
          stock: d.cartStock || '',
          inStock: true,
        });
        localStorage.setItem('archi-cart', JSON.stringify(cart));
      }
    } catch (e) {
      // localStorage blocked — the visual feedback still runs
    }
  }

  btn.addEventListener('click', () => {
    btn.dataset.added = 'true';
    setButton(btn, '/assets/ic-check.svg', d.labelAdded || '');
    store();
    syncCartBadge();
    setTimeout(() => {
      btn.dataset.added = 'false';
      setButton(btn, '/assets/ic-cart.svg', d.labelAdd || '');
      syncCartBadge();
    }, 1800);
  });
}

function initWish() {
  [document.getElementById('pdWish'), document.getElementById('pdHeartTop')].forEach((el) => {
    if (!el) return;
    el.addEventListener('click', () => {
      el.dataset.liked = el.dataset.liked === 'true' ? 'false' : 'true';
    });
  });
}

function initTabs() {
  const tabs = [...document.querySelectorAll('#pdTabs button')];
  if (!tabs.length) return;
  tabs.forEach((b) =>
    b.addEventListener('click', () => {
      tabs.forEach((x) => (x.dataset.on = 'false'));
      document.querySelectorAll('.pd-pane').forEach((p) => (p.dataset.on = 'false'));
      b.dataset.on = 'true';
      const pane = document.querySelector('.pd-pane[data-pane="' + b.dataset.pane + '"]');
      if (pane) pane.dataset.on = 'true';
    })
  );
}

function initReviewFilter() {
  const buttons = [...document.querySelectorAll('.rev-filter button')];
  buttons.forEach((b) =>
    b.addEventListener('click', () => {
      buttons.forEach((x) => (x.dataset.on = 'false'));
      b.dataset.on = 'true';
    })
  );
}

// The "reviews" counter in the meta row scrolls down to the rating section.
function initScrollToReviews() {
  const target = document.getElementById('reviews');
  if (!target) return;
  document.querySelectorAll('[data-goto="reviews"]').forEach((el) =>
    el.addEventListener('click', () => target.scrollIntoView({ behavior: 'smooth' }))
  );
}

function initReveal() {
  document.querySelectorAll('.sec-head, .pd-section .sec-tag').forEach((el) => el.classList.add('reveal'));
  document.querySelectorAll('#simGrid .pcard').forEach((el, i) => el.classList.add('reveal', 'd' + ((i % 3) + 1)));

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          e.target.classList.add('in');
          io.unobserve(e.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );
  document.querySelectorAll('.reveal').forEach((el) => io.observe(el));
}

export default function init() {
  initGallery();
  initQty();
  initAddToCart();
  initWish();
  initTabs();
  initReviewFilter();
  initScrollToReviews();
  initReveal();
}

init();
