// Business products list: visibility toggles persist via the API; the status
// filter is server-rendered (GET), so no client-side row filtering remains.

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

export default function () {
  document.querySelectorAll('[data-visibility-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', async () => {
      const id = toggle.dataset.productId;
      if (!id) return;

      const res = await fetch(`/business/products/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok && data.success) {
        toggle.dataset.on = data.is_visible ? 'true' : 'false';
        toggle.setAttribute('aria-pressed', data.is_visible ? 'true' : 'false');
      } else {
        archiPopup.error(data.message || document.body.dataset.errGeneric);
      }
    });
  });
}
