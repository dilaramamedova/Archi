// Page module for "search" — tab filtering. The button carries data-t, the result
// block data-sec; the initial selection (?tab=) is already rendered by Blade.
export default function init() {
  const tabs = document.getElementById('srTabs');
  if (!tabs) return;

  const buttons = [...tabs.querySelectorAll('[data-t]')];
  const sections = [...document.querySelectorAll('[data-sec]')];

  tabs.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-t]');
    if (!btn) return;

    buttons.forEach((b) => (b.dataset.on = 'false'));
    btn.dataset.on = 'true';

    const key = btn.dataset.t;
    sections.forEach((s) => (s.hidden = !(key === 'all' || s.dataset.sec === key)));
  });
}
