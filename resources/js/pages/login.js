// Page module for "login". Demo form: no backend, it only reveals the success notice.
export default function init() {
  const form = document.getElementById('loginForm');
  const ok = document.getElementById('loginOk');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (ok) ok.dataset.on = 'true';
  });
}
init();
