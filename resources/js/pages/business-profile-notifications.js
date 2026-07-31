// Page module for "business-profile-notifications" — the notification switches and the
// channel chips flip their data-on state (the CSS draws both states). Shared behaviour
// (navbar, cursor) lives in resources/js/shared/.
export default function init() {
  document.querySelectorAll('.bpn-toggle').forEach((toggle) =>
    toggle.addEventListener('click', () => {
      const on = toggle.dataset.on !== 'true';
      toggle.dataset.on = on ? 'true' : 'false';
      toggle.setAttribute('aria-checked', on ? 'true' : 'false');
    })
  );

  document.querySelectorAll('.bpn-chip').forEach((chip) =>
    chip.addEventListener('click', () => {
      chip.dataset.on = chip.dataset.on === 'true' ? 'false' : 'true';
    })
  );
}
init();
