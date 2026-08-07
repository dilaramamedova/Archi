import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';

// Page module for "business-profile-security": real password change (POST /cabinet/password)
// and the danger-zone account deactivation (POST /cabinet/deactivate).
export default function init() {
  // --- Change password ---
  const pwdForm = document.getElementById('passwordForm');
  const pwdErr = document.getElementById('pwdErr');
  const pwdOk = document.getElementById('pwdOk');
  const pwdBtn = document.getElementById('pwdSubmit');

  if (pwdForm) {
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

  // --- Deactivate account ---
  const deactivateBtn = document.getElementById('deactivateBtn');
  const deactivateConfirm = document.getElementById('deactivateConfirm');
  const deactivateConfirmBtn = document.getElementById('deactivateConfirmBtn');
  const deactivateCancelBtn = document.getElementById('deactivateCancelBtn');
  const deactivateErr = document.getElementById('deactivateErr');

  deactivateBtn?.addEventListener('click', () => {
    deactivateConfirm?.classList.remove('hidden');
    deactivateBtn.classList.add('hidden');
  });

  deactivateCancelBtn?.addEventListener('click', () => {
    deactivateConfirm?.classList.add('hidden');
    deactivateBtn?.classList.remove('hidden');
    if (deactivateErr) deactivateErr.dataset.on = 'false';
  });

  deactivateConfirmBtn?.addEventListener('click', async () => {
    if (deactivateErr) deactivateErr.dataset.on = 'false';
    setLoading(deactivateConfirmBtn, true);
    const password = document.getElementById('deactivate-pwd')?.value || '';
    const response = await authFetch('/cabinet/deactivate', { password });
    setLoading(deactivateConfirmBtn, false);

    if (response.ok) {
      window.location.href = response.data.redirect || '/';
      return;
    }

    if (deactivateErr) {
      deactivateErr.textContent = response.errors?.password?.[0] || response.message || 'Error';
      deactivateErr.dataset.on = 'true';
    }
  });
}
