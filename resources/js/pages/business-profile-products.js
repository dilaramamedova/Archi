// Page module for "business-profile-products" — the visibility switch of each product
// row. The old page shipped an empty handler; the switches were static images there.
export default function init() {
  document.querySelectorAll('.bpp-toggle').forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const on = toggle.dataset.on !== 'true';
      toggle.dataset.on = on ? 'true' : 'false';
      toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  });
}
init();
