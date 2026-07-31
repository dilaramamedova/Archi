// Page module for "home" — ported from the inline <script> of the old index.html.
// Shared behaviour (navbar, round product cursor) lives in resources/js/shared/.
// The card grids are rendered server-side now, so only the listings a visitor posted
// on /sell (localStorage) are still built here.

const esc = (s) =>
  String(s == null ? '' : s).replace(
    /[&<>"']/g,
    (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]
  );

const withParam = (url, key, value) =>
  url + (url.indexOf('?') === -1 ? '?' : '&') + key + '=' + encodeURIComponent(value);

// Prepends the visitor's own listings (stored by /sell) and keeps the grid at 4 cards.
function initUserProducts() {
  const grid = document.getElementById('prodGrid');
  if (!grid) return;

  let items = [];
  try {
    items = JSON.parse(localStorage.getItem('archi-products') || '[]');
  } catch (e) {
    return;
  }
  if (!Array.isArray(items) || !items.length) return;

  const d = grid.dataset;
  const card = (p) => `
    <a class="pcard" href="${esc(d.urlProduct)}">
      <div class="prod-cursor"><span>${esc(d.lCursor)}</span></div>
      <div class="ph">
        <img class="prod" src="${esc(p.img || '/assets/prod-kafel.png')}" alt="">
        <div class="badges"><span class="b mine">${esc(d.lMine)}</span><span class="b">${esc(p.rate)}</span></div>
        <div class="heart"><img src="/assets/ic-heart2.svg" alt=""></div>
        <div class="dots"><i class="on"></i><i></i><i></i></div>
      </div>
      <div class="rating"><img src="/assets/ic-star.svg" alt=""><p>${esc(d.lNew)} <span>${esc(p.reviews)}</span></p></div>
      <div class="cat">${esc(p.cat)}</div>
      <div class="name">${esc(p.name)}</div>
      <div class="price"><span class="now">${esc(p.now)}</span>${p.old ? `<span class="old">${esc(p.old)}</span>` : ''}${p.off ? `<span class="off">${esc(p.off)}</span>` : ''}</div>
    </a>`;

  grid.insertAdjacentHTML('afterbegin', items.map(card).join(''));
  while (grid.children.length > 4) grid.removeChild(grid.lastElementChild);
}

function initPromoCopy() {
  document.querySelectorAll('.pb-copy').forEach((b) =>
    b.addEventListener('click', () => {
      try {
        if (navigator.clipboard) navigator.clipboard.writeText(b.dataset.code || '');
      } catch (e) {
        // clipboard blocked — the code stays readable on screen
      }
      const previous = b.textContent;
      b.textContent = b.dataset.copied || previous;
      setTimeout(() => { b.textContent = previous; }, 1500);
    })
  );
}

// Shared carousel driver: `apply(i)` renders slide i, the dots are also the controls.
function carousel(dotsEl, count, apply, delay) {
  if (!dotsEl) return;
  const dots = [...dotsEl.querySelectorAll('i')];
  let i = 0;
  let timer;

  function show(n) {
    i = (n + count) % count;
    apply(i);
    dots.forEach((d, k) => d.classList.toggle('on', k === i));
  }
  function restart() {
    clearInterval(timer);
    timer = setInterval(() => show(i + 1), delay);
  }

  dots.forEach((d, k) => d.addEventListener('click', () => { show(k); restart(); }));
  show(0);
  restart();
}

function initPromoCarousel() {
  const wrap = document.getElementById('heroPromo');
  const img = document.getElementById('hpImg');
  const cta = document.getElementById('hpCta');
  if (!wrap) return;

  let slides = [];
  try {
    slides = JSON.parse(wrap.dataset.slides || '[]');
  } catch (e) {
    return;
  }
  if (!slides.length) return;

  carousel(document.getElementById('hpDots'), slides.length, (n) => {
    if (img) img.src = slides[n].img;
    if (cta) cta.href = slides[n].href;
  }, 4000);
}

// Role slider: the slides are in the HTML, only the track moves — so switching the
// site language never breaks the copy.
function initRoleSlider() {
  const track = document.getElementById('huTrack');
  if (!track) return;
  const count = track.querySelectorAll('.hu-slide').length;
  carousel(document.getElementById('huDots'), count, (n) => {
    track.style.transform = 'translateX(-' + n * 100 + '%)';
  }, 4500);
}

// Side calculator (paint / roof / tile / laminate) — live estimate.
function initSideCalc() {
  const root = document.getElementById('sideCalc');
  const tabs = document.getElementById('scTabs');
  const body = document.getElementById('scBody');
  if (!root || !tabs || !body) return;

  const closeBtn = document.getElementById('scClose');
  if (closeBtn) closeBtn.addEventListener('click', () => root.classList.remove('open'));

  let l = {};
  try {
    l = JSON.parse(root.dataset.labels || '{}');
  } catch (e) {
    l = {};
  }
  const urlCalculator = root.dataset.urlCalculator || '/calculator';

  const MATERIALS = {
    boya: {
      unit: l.unitLiter, hint: l.hintPaint,
      fields: [['L', l.length, l.meter, 4], ['W', l.width, l.meter, 3], ['H', l.height, l.meter, 2.7]],
      calc: (v) => {
        const wall = 2 * (v.L + v.W) * v.H;
        const liters = Math.ceil((wall * 2) / 10);
        return { qty: liters, area: Math.round(wall), price: liters * 12 };
      },
    },
    dam: {
      unit: l.unitSheet, hint: l.hintRoof,
      fields: [['L', l.length, l.meter, 4], ['W', l.width, l.meter, 3]],
      calc: (v) => {
        const area = v.L * v.W * 1.3;
        return { qty: Math.ceil(area / 2.1), area: Math.round(area), price: Math.round(area * 15) };
      },
    },
    kafel: {
      unit: l.unitBox, hint: l.hintFloor,
      fields: [['L', l.length, l.meter, 4], ['W', l.width, l.meter, 3]],
      calc: (v) => {
        const need = v.L * v.W * 1.1;
        return { qty: Math.ceil(need / 1.44), area: Math.round(v.L * v.W), price: Math.round(need * 23) };
      },
    },
    laminant: {
      unit: l.unitPack, hint: l.hintFloor,
      fields: [['L', l.length, l.meter, 4], ['W', l.width, l.meter, 3]],
      calc: (v) => {
        const need = v.L * v.W * 1.07;
        return { qty: Math.ceil(need / 2.13), area: Math.round(v.L * v.W), price: Math.round(need * 18) };
      },
    },
  };

  const qtyEl = document.getElementById('scQty');
  const unitEl = document.getElementById('scUnit');
  const areaEl = document.getElementById('scArea');
  const hintEl = document.getElementById('scHint');
  const priceEl = document.getElementById('scPrice');
  const fullEl = document.getElementById('scFull');
  let current = 'boya';

  function renderFields() {
    const m = MATERIALS[current];
    body.innerHTML =
      '<div class="sc-group"><label>' + esc(l.roomSize) + '</label><div class="sc-inputs">' +
      m.fields
        .map((f) =>
          '<div class="sc-field"><span>' + esc(f[1]) + '</span><div class="ip">' +
          '<input type="number" class="sc-in" data-k="' + f[0] + '" value="' + f[3] + '" min="0" step="any">' +
          '<span class="u">' + esc(f[2]) + '</span></div></div>')
        .join('') +
      '</div></div>';
    body.querySelectorAll('input').forEach((i) => i.addEventListener('input', compute));
  }

  function compute() {
    const m = MATERIALS[current];
    const v = {};
    body.querySelectorAll('.sc-in').forEach((el) => { v[el.dataset.k] = parseFloat(el.value) || 0; });
    m.fields.forEach((f) => { if (v[f[0]] === undefined) v[f[0]] = 0; });

    const r = m.calc(v);
    qtyEl.textContent = r.qty;
    unitEl.textContent = m.unit;
    areaEl.textContent = r.area + ' m²';
    hintEl.textContent = m.hint;
    priceEl.textContent = Math.round(r.price);
    if (fullEl) fullEl.href = withParam(urlCalculator, 'mat', current);
  }

  tabs.querySelectorAll('button').forEach((b) =>
    b.addEventListener('click', () => {
      tabs.querySelectorAll('button').forEach((x) => x.classList.remove('on'));
      b.classList.add('on');
      current = b.dataset.mat;
      renderFields();
      compute();
    })
  );

  renderFields();
  compute();
}

function initReveal() {
  document
    .querySelectorAll('.sec-head, .sale-marquee, .foot-cols, .foot-news')
    .forEach((el) => el.classList.add('reveal'));
  document
    .querySelectorAll('#prodGrid .pcard, #campGrid .pcard, #specGrid .scard, #blogGrid .post, .cat-row .cat-thumb, .foot-col')
    .forEach((el, i) => el.classList.add('reveal', 'd' + ((i % 3) + 1)));

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
  initUserProducts();
  initPromoCopy();
  initPromoCarousel();
  initRoleSlider();
  initSideCalc();
  initReveal();
}

init();
