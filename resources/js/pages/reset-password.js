import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';
import popup from '../shared/popup.js';

const RULES = {
  ruleLen: (v) => v.length >= 8,
  ruleNum: (v) => /\d/.test(v),
  ruleUpper: (v) => /[A-ZÇƏĞİÖŞÜ]/.test(v),
};

export default function init() {
  const form = document.getElementById('resetForm');
  if (!form) return;

  const btn = form.querySelector('[type=submit]');
  const password = form.querySelector('[name=password]');

  // Live checklist under the password field — mirrors the server-side rules.
  password?.addEventListener('input', () => {
    for (const [id, test] of Object.entries(RULES)) {
      const row = document.getElementById(id);
      if (row) row.dataset.on = String(test(password.value));
    }
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);
    setLoading(btn, true);

    const res = await authFetch(form.action, {
      token: form.querySelector('[name=token]').value,
      email: form.querySelector('[name=email]').value,
      password: password.value,
      password_confirmation: form.querySelector('[name=password_confirmation]').value,
    });

    if (res.ok) {
      // Redirect when the popup closes — autoClose and manual close both resolve.
      popup.success(res.data.message, { autoClose: 1800 }).then(() => {
        window.location.href = res.data.redirect;
      });
    } else {
      showErrors(form, res.errors, true);
      setLoading(btn, false);
    }
  });
}
