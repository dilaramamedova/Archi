import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';

const RULES = {
  ruleLen: (v) => v.length >= 8,
  ruleNum: (v) => /\d/.test(v),
  ruleUpper: (v) => /[A-ZÇƏĞİÖŞÜ]/.test(v),
};

export default function init() {
  const form = document.getElementById('resetForm');
  if (!form) return;

  const ok = document.getElementById('resetOk');
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
    if (ok) ok.dataset.on = 'false';
    setLoading(btn, true);

    const res = await authFetch(form.action, {
      token: form.querySelector('[name=token]').value,
      email: form.querySelector('[name=email]').value,
      password: password.value,
      password_confirmation: form.querySelector('[name=password_confirmation]').value,
    });

    if (res.ok) {
      if (ok) {
        ok.textContent = res.data.message;
        ok.dataset.on = 'true';
      }
      setTimeout(() => { window.location.href = res.data.redirect; }, 600);
    } else {
      showErrors(form, res.errors, 'resetErr');
      setLoading(btn, false);
    }
  });
}
