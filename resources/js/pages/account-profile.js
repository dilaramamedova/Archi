import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';

// Page module for "account-profile" — the personal-info form posts classically;
// the security card changes the password via POST /cabinet/password (shared by all roles).
export default function init() {
  const pwdForm = document.getElementById('passwordForm');
  if (!pwdForm) return;

  const pwdErr = document.getElementById('pwdErr');
  const pwdOk = document.getElementById('pwdOk');
  const pwdBtn = document.getElementById('pwdSubmit');

  pwdForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (pwdErr) pwdErr.dataset.on = 'false';
    if (pwdOk) pwdOk.dataset.on = 'false';
    clearErrors(pwdForm);
    setLoading(pwdBtn, true);

    const res = await authFetch('/cabinet/password', {
      current_password: pwdForm.querySelector('[name="current_password"]').value,
      password: pwdForm.querySelector('[name="password"]').value,
      password_confirmation: pwdForm.querySelector('[name="password_confirmation"]').value,
    });

    setLoading(pwdBtn, false);

    if (res.ok) {
      if (pwdOk) {
        pwdOk.textContent = res.data.message;
        pwdOk.dataset.on = 'true';
      }
      pwdForm.reset();
    } else {
      showErrors(pwdForm, res.errors, 'pwdErr');
    }
  });
}
