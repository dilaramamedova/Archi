import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';
import popup from '../shared/popup.js';

export default function init() {
  const form = document.getElementById('loginForm');
  if (!form) return;

  const btn = form.querySelector('[type=submit]');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);
    setLoading(btn, true);

    const data = {
      identifier: form.querySelector('[name=identifier]').value,
      password: form.querySelector('[name=password]').value,
      remember: form.querySelector('[name=remember]')?.checked ?? false,
    };

    const res = await authFetch('/login', data);

    if (res.ok) {
      // Redirect when the popup closes — autoClose and manual close both resolve.
      popup.success(form.dataset.success, { autoClose: 1800 }).then(() => {
        window.location.href = res.data.redirect;
      });
    } else {
      showErrors(form, res.errors, true);
      setLoading(btn, false);
    }
  });
}
