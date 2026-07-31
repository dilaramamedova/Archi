// Page module for "specialist-cabinet-reviews": the two interactions the Figma frame
// shows — the filter tabs above the list, and the reply button that opens an
// inline composer and turns the review into the answered state (the yellow-ruled
// answer block of the first card). Nothing is posted anywhere: the reply is
// rendered client-side only. Shared behaviour (navbar, cursor) lives in resources/js/shared/.
export default function init() {
  const root = document.querySelector('.scr-page');
  if (!root) return;

  const tabs = Array.from(root.querySelectorAll('.scr-tab'));
  const reviews = Array.from(root.querySelectorAll('.scr-rev'));
  const empty = root.querySelector('.scr-empty');
  const more = root.querySelector('.scr-more');

  const matches = (rev, filter) => {
    const rating = Number(rev.dataset.rating) || 0;
    if (filter === 'unanswered') return rev.dataset.replied !== 'true';
    if (filter === 'five') return rating === 5;
    if (filter === 'low') return rating > 0 && rating <= 3;
    return true;
  };

  const activeFilter = () => {
    const on = tabs.find((t) => t.dataset.on === 'true');
    return on ? on.dataset.filter : 'all';
  };

  const paint = () => {
    const filter = activeFilter();
    let shown = 0;
    reviews.forEach((rev) => {
      const on = matches(rev, filter);
      rev.hidden = !on;
      if (on) shown += 1;
    });
    if (empty) empty.hidden = shown > 0;
    // "load more" pages the full list; a narrowed list is already complete on screen
    if (more) more.hidden = filter !== 'all' || shown === 0;
  };

  tabs.forEach((tab) =>
    tab.addEventListener('click', () => {
      tabs.forEach((t) => {
        const on = t === tab;
        t.dataset.on = on ? 'true' : 'false';
        t.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      paint();
    })
  );

  root.addEventListener('click', (e) => {
    const rev = e.target.closest('.scr-rev');
    if (!rev) return;

    const btn = rev.querySelector('[data-reply]');
    const compose = rev.querySelector('.scr-compose');
    const reply = rev.querySelector('.scr-reply');
    const field = compose ? compose.querySelector('textarea') : null;

    if (e.target.closest('[data-reply]')) {
      if (btn) btn.hidden = true;
      if (compose) compose.hidden = false;
      if (field) field.focus();
      return;
    }

    if (e.target.closest('[data-cancel]')) {
      if (field) field.value = '';
      if (compose) compose.hidden = true;
      if (btn) btn.hidden = false;
      return;
    }

    if (e.target.closest('[data-send]')) {
      const text = field ? field.value.trim() : '';
      if (!text) {
        if (field) field.focus();
        return;
      }
      if (reply) {
        const body = reply.querySelector('.tx');
        if (body) body.textContent = text;
        reply.hidden = false;
      }
      if (field) field.value = '';
      if (compose) compose.hidden = true;
      // the answered review must leave the "unanswered" filter immediately
      rev.dataset.replied = 'true';
      paint();
    }
  });

  paint();
}
