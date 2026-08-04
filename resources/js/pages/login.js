import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';

export default function init() {
  const form = document.getElementById('loginForm');
  if (!form) return;

  const ok = document.getElementById('loginOk');
  const btn = form.querySelector('[type=submit]');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);
    if (ok) ok.dataset.on = 'false';
    setLoading(btn, true);

    const data = {
      identifier: form.querySelector('[name=identifier]').value,
      password: form.querySelector('[name=password]').value,
      remember: form.querySelector('[name=remember]')?.checked ?? false,
    };

    const res = await authFetch('/login', data);

    if (res.ok) {
      if (ok) ok.dataset.on = 'true';
      window.scrollTo({ top: 0, behavior: 'smooth' });
      setTimeout(() => { window.location.href = res.data.redirect; }, 600);
    } else {
      showErrors(form, res.errors, 'loginErr');
      setLoading(btn, false);
    }
  });
}
