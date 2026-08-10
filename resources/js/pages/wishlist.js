/**
 * Wishlist page JS — handles removing items via the toggle API.
 */

import popup from '../shared/popup.js';

function csrf() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export default function wishlist() {
  const rows = document.querySelectorAll('.wl-row');

  rows.forEach((row) => {
    const removeBtn = row.querySelector('.wl-remove');
    if (!removeBtn) return;

    removeBtn.addEventListener('click', async () => {
      const productId = row.dataset.productId;
      if (!productId) return;

      // Optimistic UI — fade out immediately
      row.style.opacity = '0.4';
      row.style.pointerEvents = 'none';

      try {
        const res = await fetch('/api/wishlist/toggle', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf(),
          },
          body: JSON.stringify({ product_id: +productId }),
        });

        if (res.ok) {
          row.style.transition = 'all 0.3s ease';
          row.style.maxHeight = row.scrollHeight + 'px';
          // Force reflow
          row.offsetHeight;
          row.style.maxHeight = '0';
          row.style.padding = '0';
          row.style.marginBottom = '0';
          row.style.overflow = 'hidden';
          row.style.borderWidth = '0';

          setTimeout(() => {
            row.remove();

            // If no rows left, reload to show empty state
            const remaining = document.querySelectorAll('.wl-row');
            if (remaining.length === 0) {
              window.location.reload();
            }
          }, 300);
        } else {
          // Revert on failure and tell the user why nothing was removed
          row.style.opacity = '';
          row.style.pointerEvents = '';
          popup.error(document.body.dataset.errGeneric);
        }
      } catch {
        row.style.opacity = '';
        row.style.pointerEvents = '';
        popup.error(document.body.dataset.errNetwork || document.body.dataset.errGeneric);
      }
    });
  });
}
