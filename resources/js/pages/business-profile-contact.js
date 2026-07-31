// Page module for "business-profile-contact" — communication language chips toggle
// between the selected and unselected state (data-on drives the CSS).
export default function init() {
  document.querySelectorAll('.bpco-lchip').forEach((chip) =>
    chip.addEventListener('click', () => {
      chip.dataset.on = chip.dataset.on === 'true' ? 'false' : 'true';
    })
  );
}
init();
