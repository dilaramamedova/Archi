// Page module for "business-profile-security" — the eye icon of a password field and the
// 2FA switch flip their data-on state (the CSS reacts to it). Same behaviour as the old
// inline script, which toggled a data attribute on the masked value.
export default function init() {
  document.querySelectorAll('.bpsec-input .eye').forEach((eye) =>
    eye.addEventListener('click', () => {
      const on = eye.dataset.on !== 'true';
      eye.dataset.on = String(on);
      const dots = eye.parentElement.querySelector('.dots');
      if (dots) dots.dataset.shown = String(on);
    })
  );

  document.querySelectorAll('.bpsec-toggle').forEach((toggle) =>
    toggle.addEventListener('click', () => {
      const on = toggle.dataset.on !== 'true';
      toggle.dataset.on = String(on);
      toggle.setAttribute('aria-checked', String(on));
    })
  );
}
init();
