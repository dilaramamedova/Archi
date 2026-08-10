import { authFetch, clearErrors, showErrors, setLoading } from '../shared/auth.js';
import popup from '../shared/popup.js';

export default function init() {
  const roles = document.querySelectorAll('#roles [data-role]');
  const form = document.getElementById('regForm');
  if (!roles.length || !form) return;

  const fields = form.querySelectorAll('[data-for]');
  const roleInput = form.querySelector('[name=role]');

  const pick = (role) => {
    roles.forEach((r) => {
      r.dataset.sel = String(r.dataset.role === role);
    });
    fields.forEach((el) => {
      el.hidden = el.dataset.for !== role;
    });
    if (roleInput) roleInput.value = role;
  };

  roles.forEach((r) => r.addEventListener('click', () => pick(r.dataset.role)));

  const btn = form.querySelector('[type=submit]');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);
    setLoading(btn, true);

    const data = {
      first_name: form.querySelector('[name=first_name]').value,
      last_name: form.querySelector('[name=last_name]').value,
      email: form.querySelector('[name=email]').value,
      phone: form.querySelector('[name=phone]').value,
      password: form.querySelector('[name=password]').value,
      password_confirmation: form.querySelector('[name=password_confirmation]').value,
      role: roleInput.value,
      terms: form.querySelector('[name=terms]')?.checked ? true : false,
    };

    const role = roleInput.value;
    if (role === 'seller') {
      data.company_name = form.querySelector('[name=company_name]')?.value ?? '';
    }
    if (role === 'master') {
      const specialtyId = form.querySelector('[name=specialist_specialty_id]')?.value ?? '';
      data.specialist_specialty_id = specialtyId === '' ? null : Number(specialtyId);
      data.city = form.querySelector('[name=city]')?.value ?? '';
    }

    const res = await authFetch('/register', data);

    if (res.ok) {
      // Buyers are signed in straight away — send them on when the popup closes
      // (autoClose and manual close both resolve the promise).
      if (res.data.redirect && role === 'buyer') {
        popup.success(res.data.message, { autoClose: 1800 }).then(() => {
          window.location.href = res.data.redirect;
        });
        return;
      }
      // Sellers/masters wait for approval — no redirect, just the pending message.
      form.reset();
      pick('buyer');
      popup.success(res.data.message);
    } else {
      showErrors(form, res.errors, true);
    }
    setLoading(btn, false);
  });
}
