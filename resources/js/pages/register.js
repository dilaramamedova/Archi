// Page module for "register": role selection (data-sel) + role-specific fields (data-for).
// The initial role (?role=, default buyer) is rendered server-side by the Blade view,
// so this module only reacts to clicks.
export default function init() {
  const roles = document.querySelectorAll('#roles [data-role]');
  const form = document.getElementById('regForm');
  if (!roles.length || !form) return;

  const fields = form.querySelectorAll('[data-for]');

  const pick = (role) => {
    roles.forEach((r) => {
      r.dataset.sel = String(r.dataset.role === role);
    });
    fields.forEach((el) => {
      el.hidden = el.dataset.for !== role;
    });
  };

  roles.forEach((r) => r.addEventListener('click', () => pick(r.dataset.role)));

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    try {
      localStorage.setItem('archi-auth', '1');
    } catch (err) {
      /* storage unavailable */
    }
    document.getElementById('okMsg')?.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

init();
