import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';

// Page module for "business-profile-security": real password change (POST /cabinet/password)
// and the danger-zone account deactivation (POST /cabinet/deactivate).
export default function init() {
  // --- Change password ---
  const pwdForm = document.getElementById('passwordForm');
  const pwdBtn = document.getElementById('pwdSubmit');

  if (pwdForm) {
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
        archiPopup.success(res.data.message);
        pwdForm.reset();
      } else {
        // per-field errors render inline; the general part is surfaced by showErrors
        showErrors(pwdForm, res.errors, true);
      }
    });
  }

  // --- Deactivate account ---
  const deactivateBtn = document.getElementById('deactivateBtn');
  const deactivateConfirm = document.getElementById('deactivateConfirm');
  const deactivateConfirmBtn = document.getElementById('deactivateConfirmBtn');
  const deactivateCancelBtn = document.getElementById('deactivateCancelBtn');

  deactivateBtn?.addEventListener('click', () => {
    deactivateConfirm?.classList.remove('hidden');
    deactivateBtn.classList.add('hidden');
  });

  deactivateCancelBtn?.addEventListener('click', () => {
    deactivateConfirm?.classList.add('hidden');
    deactivateBtn?.classList.remove('hidden');
  });

  deactivateConfirmBtn?.addEventListener('click', async () => {
    // The inline panel already asks for the password; guard the POST with one
    // last explicit confirm, since deactivation logs the account out.
    const sure = await archiPopup.confirm(deactivateConfirmBtn.dataset.lConfirm, {
      confirmText: deactivateConfirmBtn.textContent.trim(),
      danger: true,
    });
    if (!sure) return;

    setLoading(deactivateConfirmBtn, true);
    const password = document.getElementById('deactivate-pwd')?.value || '';
    const response = await authFetch('/cabinet/deactivate', { password });
    setLoading(deactivateConfirmBtn, false);

    if (response.ok) {
      archiPopup
        .success(deactivateConfirmBtn.dataset.lDeactivated, { autoClose: 1800 })
        .then(() => (window.location.href = response.data.redirect || '/'));
      return;
    }

    archiPopup.error(response.errors?.password?.[0] || response.message || document.body.dataset.errGeneric);
  });
}
