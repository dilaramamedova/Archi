// Shared login modal — ported from the old archi.js (window.ARCHI.openLogin).
// The markup is server-rendered by <x-login-modal/> in the layout, so this module only
// wires the open/close behaviour. The navbar "sign in" link keeps its /login href, so
// without JS the standalone page still works as the fallback.

const overlay = document.getElementById('lmOverlay');

function open() {
  if (!overlay) return;
  overlay.dataset.on = 'true';
  document.body.dataset.lmLock = 'true';
  const first = overlay.querySelector('input');
  if (first) setTimeout(() => first.focus(), 60);
}

function close() {
  if (!overlay) return;
  overlay.dataset.on = 'false';
  delete document.body.dataset.lmLock;
}

// Any element carrying [data-login] opens the modal too (old behaviour).
// On /login itself the navbar link just follows its href — a modal there would only
// duplicate the page underneath it.
const onLoginPage = document.body.dataset.page === 'login';
const openers = onLoginPage ? '[data-login]' : '.signin .txt, [data-login]';

document.querySelectorAll(openers).forEach((el) =>
  el.addEventListener('click', (e) => {
    if (!overlay) return; // no modal on the page -> follow the /login href
    e.preventDefault();
    open();
  })
);

if (overlay) {
  document.getElementById('lmClose')?.addEventListener('click', close);

  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) close();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay.dataset.on === 'true') close();
  });

  document.getElementById('lmForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    // Same flag the /login page and register.js write, so sell.js agrees on auth state.
    try {
      localStorage.setItem('archi-auth', '1');
    } catch (err) {
      /* storage unavailable */
    }
    const ok = document.getElementById('lmOk');
    if (ok) ok.dataset.on = 'true';
  });
}

export { open, close };
