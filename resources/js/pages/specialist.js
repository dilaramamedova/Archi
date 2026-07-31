// Page module for "specialist" — ported from the inline <script> of the old
// specialist.html. The old page routed the two card buttons through archi.js
// ([data-fcalc] -> calculator.html, "send message" -> the login modal); both targets
// now arrive from Blade as data-url, and the heart's `.on` class became data-on.

const HEART_ON = '/assets/ic-heart.svg';
const HEART_OFF = '/assets/ic-heart2.svg';

function initFavourite() {
  const fav = document.getElementById('ppFav');
  const icon = fav && fav.querySelector('img');
  if (!fav || !icon) return;

  fav.addEventListener('click', () => {
    const on = fav.dataset.on !== 'true';
    fav.dataset.on = on ? 'true' : 'false';
    fav.setAttribute('aria-pressed', on ? 'true' : 'false');
    icon.src = on ? HEART_ON : HEART_OFF;
  });
}

// Both buttons simply navigate; the URL comes from Blade so no route is hardcoded.
function initNavButtons() {
  ['ppCalc', 'ppMsg'].forEach((id) => {
    const btn = document.getElementById(id);
    if (!btn || !btn.dataset.url) return;
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = btn.dataset.url;
    });
  });
}

export default function init() {
  initFavourite();
  initNavButtons();
}

init();
