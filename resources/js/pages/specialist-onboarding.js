// Page module for "specialist-onboarding".
// The banner CTA stays on the page: it scrolls the checklist into view and points at the
// first unfinished step instead of navigating anywhere.
const CUE_MS = 1600;

export default function init() {
  const cta = document.querySelector('[data-complete]');
  const checklist = document.querySelector('.onb-checklist');
  if (!cta || !checklist) return;

  const steps = Array.from(checklist.querySelectorAll('[data-step]'));
  const next = steps.find((s) => s.dataset.done !== 'true');
  let cueTimer = 0;

  cta.addEventListener('click', () => {
    checklist.scrollIntoView({ behavior: 'smooth', block: 'start' });
    if (!next) return;

    next.dataset.cue = 'true';
    window.clearTimeout(cueTimer);
    cueTimer = window.setTimeout(() => {
      next.dataset.cue = 'false';
    }, CUE_MS);

    const action = next.querySelector('.ui-btn');
    if (action) action.focus({ preventScroll: true });
  });
}
