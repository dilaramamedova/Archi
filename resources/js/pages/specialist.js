// Page module for "specialist" — the favourite heart toggle and the "get in touch"
// phone reveal. The number is not in the page source: it is fetched from the
// auth-only JSON endpoint the button carries in data-phone-url. Guests have no URL —
// their button carries [data-login], so the shared login modal opens instead.

const HEART_ON = '/assets/icon-heart-rounded.svg';
const HEART_OFF = '/assets/icon-heart-pointed.svg';

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

function initContactPhone() {
  const btn = document.getElementById('ppContact');
  const link = document.getElementById('ppContactPhone');
  if (!btn || !link) return;

  const url = btn.dataset.phoneUrl;
  if (!url) return; // guest: login-modal.js owns the click

  let revealed = false;
  btn.addEventListener('click', async () => {
    if (revealed) return;
    btn.disabled = true;
    try {
      const res = await fetch(url, { headers: { Accept: 'application/json' } });
      const data = res.ok ? await res.json() : {};
      link.textContent = data.phone || btn.dataset.noPhone || '';
      if (data.tel) link.href = `tel:${data.tel}`;
      else link.removeAttribute('href');
      link.hidden = false;
      revealed = true;
    } finally {
      btn.disabled = false;
    }
  });
}

export default function init() {
  initFavourite();
  initContactPhone();
}
