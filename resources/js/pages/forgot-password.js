import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';

export default function init() {
  const form = document.getElementById('forgotForm');
  if (!form) return;

  const ok = document.getElementById('forgotOk');
  const btn = form.querySelector('[type=submit]');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);
    if (ok) ok.dataset.on = 'false';
    setLoading(btn, true);

    const res = await authFetch(form.action, {
      email: form.querySelector('[name=email]').value,
    });

    if (res.ok) {
      if (ok) {
        ok.textContent = res.data.message;
        ok.dataset.on = 'true';
      }
      form.reset();
    } else {
      showErrors(form, res.errors, 'forgotErr');
    }
    setLoading(btn, false);
  });
}
