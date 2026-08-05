// Page module for "search" — tab switching navigates via URL query parameters
// so the backend returns the correct filtered results. The initial tab is set
// by Blade from the ?tab= param.
export default function init() {
  const tabs = document.getElementById('srTabs');
  if (!tabs) return;

  const buttons = [...tabs.querySelectorAll('[data-t]')];

  tabs.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-t]');
    if (!btn) return;

    const key = btn.dataset.t;

    // Build URL preserving the search query
    const url = new URL(window.location.href);
    if (key === 'all') {
      url.searchParams.delete('tab');
    } else {
      url.searchParams.set('tab', key);
    }

    window.location.href = url.toString();
  });
}
