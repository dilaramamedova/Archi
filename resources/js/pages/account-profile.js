import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';
import { success } from '../shared/popup.js';

// Page module for "account-profile" — the personal-info form posts classically;
// the security card changes the password via POST /cabinet/password (shared by all roles).
export default function init() {
  const pwdForm = document.getElementById('passwordForm');
  if (!pwdForm) return;

  const pwdBtn = document.getElementById('pwdSubmit');

  pwdForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(pwdForm);
    setLoading(pwdBtn, true);

    const res = await authFetch('/cabinet/password', {
      current_password: pwdForm.querySelector('[name="current_password"]').value,
      password: pwdForm.querySelector('[name="password"]').value,
      password_confirmation: pwdForm.querySelector('[name="password_confirmation"]').value,
    });

    setLoading(pwdBtn, false);

    if (res.ok) {
      success(res.data.message);
      pwdForm.reset();
    } else {
      // Per-field messages stay inline; showErrors pops the general part itself.
      showErrors(pwdForm, res.errors, true);
    }
  });
}
