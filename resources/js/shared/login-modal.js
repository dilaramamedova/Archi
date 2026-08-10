import { authFetch, setLoading } from './auth.js';

const overlay = document.getElementById('lmOverlay');

// Where to land after a successful sign-in. Null → wherever the server sends us.
// The cart sets it so checkout resumes on the cart instead of bouncing to the cabinet.
let redirectOverride = null;

function open(options = {}) {
  if (!overlay) return;
  redirectOverride = options.redirectTo ?? null;
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

const onLoginPage = document.body.dataset.page === 'login';
const openers = onLoginPage ? '[data-login]' : '.signin .txt:not([data-user]):not(.nav-logout), [data-login]';

document.querySelectorAll(openers).forEach((el) =>
  el.addEventListener('click', (e) => {
    if (!overlay) return;
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

  const form = document.getElementById('lmForm');
  const ok = document.getElementById('lmOk');
  const err = document.getElementById('lmErr');
  const btn = form?.querySelector('[type=submit]');

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (ok) ok.dataset.on = 'false';
    if (err) err.dataset.on = 'false';
    if (btn) setLoading(btn, true);

    const data = {
      identifier: form.querySelector('[name=identifier]').value,
      password: form.querySelector('[name=password]').value,
      remember: form.querySelector('[name=remember]')?.checked ?? false,
    };

    const res = await authFetch('/login', data);

    if (res.ok) {
      if (ok) ok.dataset.on = 'true';
      const target = redirectOverride ?? res.data.redirect;
      setTimeout(() => { window.location.href = target; }, 600);
    } else {
      const msg = res.errors?.identifier?.[0] ?? res.message ?? 'Error';
      if (err) {
        err.textContent = msg;
        err.dataset.on = 'true';
      }
      if (btn) setLoading(btn, false);
    }
  });
}

export { open, close };
