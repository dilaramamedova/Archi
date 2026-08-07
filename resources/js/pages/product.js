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
// count has to be refreshed here.  When a server count is available (logged-in user)
// prefer it over the localStorage length.
function syncCartBadge(serverCount) {
  const badge = document.getElementById('navCartCount');
  if (!badge) return;
  if (typeof serverCount === 'number') {
    badge.textContent = serverCount;
    badge.style.display = serverCount ? 'flex' : 'none';
    return;
  }
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
  let resetTimer = 0;

  // Writes the product into the cart. Returns true only when it was really pushed,
  // so a repeat click can say "already in cart" instead of faking a success.
  function store() {
    try {
      const name = (document.querySelector('.pd-info h1') || {}).textContent || '';
      const cat = (document.querySelector('.pd-info .cat') || {}).textContent || '';
      const now = (document.querySelector('.pd-price .now') || {}).textContent || '0';
      const price = parseFloat(now.replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
      const qtyInput = document.getElementById('qtyVal');
      const qty = qtyInput ? Math.max(1, +qtyInput.value || 1) : 1;
      const cart = JSON.parse(localStorage.getItem('archi-cart') || '[]');
      const existing = cart.find((c) => c.name === name.trim());
      if (existing) {
        // Update quantity instead of silently ignoring
        existing.qty = (existing.qty || 1) + qty;
        localStorage.setItem('archi-cart', JSON.stringify(cart));
        return false;
      }
      cart.push({
        name: name.trim(),
        brand: d.cartBrand || '',
        cat: cat.trim(),
        calc: d.cartUnit || '',
        price,
        qty,
        stock: d.cartStock || '',
        inStock: true,
      });
      localStorage.setItem('archi-cart', JSON.stringify(cart));
      return true;
    } catch (e) {
      // localStorage blocked — nothing is persisted, but the click is still a fresh add
      return true;
    }
  }

  btn.addEventListener('click', async () => {
    clearTimeout(resetTimer);
    const added = store();
    btn.dataset.added = added ? 'true' : 'already';
    setButton(
      btn,
      added ? '/assets/icon-check-green.svg' : '/assets/icon-cart.svg',
      (added ? d.labelAdded : d.labelInCart) || ''
    );
    syncCartBadge();

    // Persist to the server for logged-in users
    const productId = d.productId;
    const qty = document.getElementById('qtyVal');
    if (productId) {
      try {
        const res = await fetch('/api/cart', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf(),
          },
          body: JSON.stringify({
            product_id: +productId,
            quantity: qty ? +qty.value || 1 : 1,
          }),
        });
        if (res.ok) {
          const data = await res.json();
          syncCartBadge(data.count);
        }
      } catch { /* guest user or network error — localStorage fallback already ran */ }
    }

    resetTimer = setTimeout(() => {
      btn.dataset.added = 'false';
      setButton(btn, '/assets/icon-cart.svg', d.labelAdd || '');
    }, 1800);
  });
}

function csrf() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function isGuest() {
  return !document.querySelector('[data-user]');
}

function requireAuth() {
  if (isGuest()) {
    window.location.href = '/login';
    return true;
  }
  return false;
}

function initHelpful() {
  document.querySelectorAll('.rev-card .help').forEach((btn) => {
    const n = btn.querySelector('.n');
    if (!n) return;
    btn.addEventListener('click', async () => {
      if (requireAuth()) return;
      const url = btn.dataset.url;
      if (!url) {
        const on = btn.dataset.on !== 'true';
        btn.dataset.on = on ? 'true' : 'false';
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        const base = parseInt(n.textContent, 10) || 0;
        n.textContent = base + (on ? 1 : 0);
        return;
      }
      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        });
        if (!res.ok) return;
        const data = await res.json();
        n.textContent = data.helpful_count;
        btn.dataset.on = data.voted ? 'true' : 'false';
        btn.setAttribute('aria-pressed', data.voted ? 'true' : 'false');
      } catch { /* network error — ignore */ }
    });
  });
}

function initReviewForm() {
  const form = document.getElementById('revForm');
  if (!form) return;

  const starsWrap = form.querySelector('.stars-select');
  const starBtns = form.querySelectorAll('.star-btn');
  const ratingInput = form.querySelector('input[name="rating"]');

  starBtns.forEach((star) => {
    star.addEventListener('click', () => {
      const val = star.dataset.star;
      if (ratingInput) ratingInput.value = val;
      if (starsWrap) starsWrap.dataset.rating = val;
      starBtns.forEach((s) => {
        const img = s.querySelector('img');
        if (img) img.style.opacity = +s.dataset.star <= +val ? '1' : '0.3';
      });
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

    const url = form.dataset.url || form.action;
    const body = new FormData(form);
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body,
      });
      const data = await res.json();
      if (res.ok) {
        form.style.display = 'none';
        const success = document.getElementById('revSuccess');
        if (success) success.style.display = 'flex';
      } else {
        if (btn) btn.disabled = false;
        const errEl = document.getElementById('revError');
        if (errEl) { errEl.textContent = data.message || document.body.dataset.errGeneric; errEl.style.display = 'block'; }
      }
    } catch {
      if (btn) btn.disabled = false;
      const errEl = document.getElementById('revError');
      if (errEl) { errEl.textContent = document.body.dataset.errNetwork; errEl.style.display = 'block'; }
    }
  });
}

function initWish() {
  const els = [document.getElementById('pdWish'), document.getElementById('pdHeartTop')].filter(Boolean);
  if (!els.length) return;

  // Both heart buttons share state — keep them in sync.
  function setAll(liked) {
    els.forEach((e) => (e.dataset.liked = liked ? 'true' : 'false'));
  }

  // Read product_id from the add-to-cart button (it carries data-product-id).
  const productId = document.getElementById('addCart')?.dataset?.productId;

  els.forEach((el) => {
    el.addEventListener('click', async () => {
      if (requireAuth()) return;
      const nowLiked = el.dataset.liked !== 'true';
      setAll(nowLiked);

      if (!productId) return;
      try {
        await fetch('/api/wishlist/toggle', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf(),
          },
          body: JSON.stringify({ product_id: +productId }),
        });
      } catch { /* guest or network error — optimistic toggle stays */ }
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
  const container = document.getElementById('revCards');
  if (!container) return;

  function applyFilter(filter) {
    const cards = [...container.querySelectorAll('.rev-card')];
    cards.forEach((c) => (c.style.display = ''));

    if (filter === '5star') {
      cards.forEach((c) => {
        if (+c.dataset.rating !== 5) c.style.display = 'none';
      });
    } else if (filter === 'photo') {
      cards.forEach((c) => {
        if (c.dataset.hasPhoto !== 'true') c.style.display = 'none';
      });
    }

    const visible = cards.filter((c) => c.style.display !== 'none');
    if (filter === 'newest') {
      visible.sort((a, b) => +b.dataset.date - +a.dataset.date);
    } else if (filter === 'helpful') {
      visible.sort((a, b) => +b.dataset.helpful - +a.dataset.helpful);
    }
    visible.forEach((c) => container.appendChild(c));
  }

  buttons.forEach((b) =>
    b.addEventListener('click', () => {
      buttons.forEach((x) => (x.dataset.on = 'false'));
      b.dataset.on = 'true';
      applyFilter(b.dataset.filter);
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

function initFbtAddAll() {
  const btn = document.querySelector('.fbt-addall');
  if (!btn) return;

  btn.addEventListener('click', async () => {
    // Collect product IDs from every pcard in the FBT section (including the main product).
    const container = document.getElementById('fbtCards');
    if (!container) return;

    const cards = container.querySelectorAll('.pcard');
    const ids = [];
    cards.forEach((card) => {
      // Each pcard may carry a data-product-id; the main product also has its id
      // on the add-to-cart button.
      const pid = card.dataset.productId;
      if (pid) ids.push(+pid);
    });

    // Fallback: if cards don't have data-product-id, at least add the main product.
    const mainId = document.getElementById('addCart')?.dataset?.productId;
    if (!ids.length && mainId) ids.push(+mainId);

    // Fire individual add-to-cart requests in parallel.
    const token = csrf();
    const results = await Promise.allSettled(
      ids.map((id) =>
        fetch('/api/cart', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
          },
          body: JSON.stringify({ product_id: id, quantity: 1 }),
        }).then((r) => r.json())
      )
    );

    // Update badge with the latest count from the last successful response.
    const last = results.filter((r) => r.status === 'fulfilled' && r.value?.count != null).pop();
    if (last) syncCartBadge(last.value.count);

    // Visual feedback.
    const origText = btn.textContent;
    btn.textContent = '✓';
    setTimeout(() => { btn.textContent = origText; }, 1800);
  });
}

export default function init() {
  initGallery();
  initQty();
  initAddToCart();
  initWish();
  initTabs();
  initReviewFilter();
  initHelpful();
  initReviewForm();
  initScrollToReviews();
  initFbtAddAll();
  initReveal();
}
