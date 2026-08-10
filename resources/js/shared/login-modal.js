import { authFetch, setLoading } from './auth.js';
import popup from './popup.js';

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

document.querySelectorAll('[data-login]').forEach((el) =>
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
  const btn = form?.querySelector('[type=submit]');

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (btn) setLoading(btn, true);

    const data = {
      identifier: form.querySelector('[name=identifier]').value,
      password: form.querySelector('[name=password]').value,
      remember: form.querySelector('[name=remember]')?.checked ?? false,
    };

    const res = await authFetch('/login', data);

    if (res.ok) {
      const target = redirectOverride ?? res.data.redirect;
      // Redirect when the popup closes — autoClose and manual close both resolve.
      popup.success(form.dataset.success, { autoClose: 1800 }).then(() => {
        window.location.href = target;
      });
    } else {
      popup.error(res.errors?.identifier?.[0] ?? res.message ?? 'Error');
      if (btn) setLoading(btn, false);
    }
  });
}

export { open, close };
