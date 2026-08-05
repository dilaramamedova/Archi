// Page module for "home" — ported from the inline <script> of the old index.html.
// Shared behaviour (navbar, round product cursor) lives in resources/js/shared/.
// The card grids are rendered server-side now, so only the listings a visitor posted
// on /sell (localStorage) are still built here.

const esc = (s) =>
  String(s == null ? '' : s).replace(
    /[&<>"']/g,
    (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]
  );

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
        <img class="prod" src="${esc(p.img || '/assets/product-marble-tile.png')}" alt="">
        <div class="badges"><span class="b mine">${esc(d.lMine)}</span><span class="b">${esc(p.rate)}</span></div>
        <div class="heart"><img src="/assets/icon-heart-pointed.svg" alt=""></div>
        <div class="dots"><i class="on"></i><i></i><i></i></div>
      </div>
      <div class="rating"><img src="/assets/icon-star-yellow.svg" alt=""><p>${esc(d.lNew)} <span>${esc(p.reviews)}</span></p></div>
      <div class="cat">${esc(p.cat)}</div>
      <div class="name">${esc(p.name)}</div>
      <div class="price"><span class="now">${esc(p.now)}</span>${p.old ? `<span class="old">${esc(p.old)}</span>` : ''}${p.off ? `<span class="off">${esc(p.off)}</span>` : ''}</div>
    </a>`;

  grid.insertAdjacentHTML('afterbegin', items.map(card).join(''));
  while (grid.children.length > 4) grid.removeChild(grid.lastElementChild);
}

// The two labels are both in the markup (see .pb-swap); only the state flips, so a
// double click can never leave the button stuck on the confirmation.
function initPromoCopy() {
  document.querySelectorAll('.pb-copy').forEach((b) => {
    let timer;
    b.addEventListener('click', () => {
      try {
        if (navigator.clipboard) navigator.clipboard.writeText(b.dataset.code || '');
      } catch (e) {
        // clipboard blocked — the code stays readable on screen
      }
      b.dataset.on = 'true';
      clearTimeout(timer);
      timer = setTimeout(() => { b.dataset.on = 'false'; }, 1500);
    });
  });
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
  const btnText = document.getElementById('hpBtnText');
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
    if (btnText && slides[n].btn) btnText.textContent = slides[n].btn;
  }, 4000);
}

// Role slider: the slides are in the HTML, only the track moves — so switching the
// site language never breaks the copy.
function initRoleSlider() {
  const track = document.getElementById('roleTrack');
  if (!track) return;
  const count = track.querySelectorAll('.hr-slide').length;
  carousel(document.getElementById('roleDots'), count, (n) => {
    track.style.transform = 'translateX(-' + n * 100 + '%)';
  }, 4500);
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
  initReveal();
}
