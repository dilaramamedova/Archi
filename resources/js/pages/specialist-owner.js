// Page module for "specialist-owner". The only stateful control in the frame is the
// "Yeni sifarişlərə açığam" switch in the owner banner (831:11538).
export default function init() {
  const avail = document.querySelector('[data-sel="availability"]');
  if (!avail) return;

  avail.addEventListener('click', () => {
    const on = avail.dataset.on !== 'true';
    avail.dataset.on = on ? 'true' : 'false';
    avail.setAttribute('aria-pressed', on ? 'true' : 'false');
  });
}
