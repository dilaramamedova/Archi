import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';
import popup from '../shared/popup.js';

export default function init() {
  const form = document.getElementById('forgotForm');
  if (!form) return;

  const btn = form.querySelector('[type=submit]');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);
    setLoading(btn, true);

    try {
      const res = await authFetch(form.action, {
        email: form.querySelector('[name=email]').value,
      });

      if (res.ok) {
        form.reset();
        popup.success(res.data.message || btn.dataset.labelSuccess);
        return;
      }

      showErrors(form, res.errors);
      const firstError = Object.values(res.errors)[0]?.[0];
      popup.error(firstError || res.message || btn.dataset.labelError);
    } catch {
      popup.error(btn.dataset.labelError);
    } finally {
      setLoading(btn, false);
    }
  });
}
