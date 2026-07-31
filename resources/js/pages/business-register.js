// Page module for "business-register": role selection (data-sel), design default = business.
export default function init() {
  const roles = document.querySelectorAll('[data-role]');
  if (!roles.length) return;

  roles.forEach((r) =>
    r.addEventListener('click', () => {
      roles.forEach((x) => (x.dataset.sel = 'false'));
      r.dataset.sel = 'true';
    })
  );
}

init();
