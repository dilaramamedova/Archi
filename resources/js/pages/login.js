// Page module for "login". Demo form: no backend, it only reveals the success notice.
export default function init() {
  const form = document.getElementById('loginForm');
  const ok = document.getElementById('loginOk');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    // Same flag register.js writes, so sell.js sees the visitor as signed in.
    try {
      localStorage.setItem('archi-auth', '1');
    } catch (err) {
      /* storage unavailable */
    }
    if (ok) ok.dataset.on = 'true';
    const submit = form.querySelector('[type=submit]');
    if (submit) submit.disabled = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}
init();
