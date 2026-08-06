// Business inventory: per-row stock update via mini-modal.

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

export default function () {
  const modal = document.getElementById('stockModal');
  const input = document.getElementById('stockInput');
  const save = document.getElementById('stockSave');
  const cancel = document.getElementById('stockCancel');
  if (!modal || !input || !save) return;

  let productId = null;

  document.querySelectorAll('[data-add-stock]').forEach((btn) => {
    btn.addEventListener('click', () => {
      productId = btn.dataset.productId;
      input.value = btn.dataset.current || '0';
      modal.style.display = 'flex';
      input.focus();
      input.select();
    });
  });

  function close() {
    modal.style.display = 'none';
    productId = null;
  }

  cancel?.addEventListener('click', close);
  modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

  save.addEventListener('click', async () => {
    if (!productId) return;
    save.disabled = true;

    try {
      const res = await fetch(`/business/products/${productId}/stock`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf(),
          'Accept': 'application/json',
        },
        body: JSON.stringify({ stock: Number(input.value) }),
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok && data.success) {
        location.reload();
      } else {
        window.ARCHI?.toast?.show({ type: 'error', title: data.message || 'Xəta baş verdi' });
        save.disabled = false;
      }
    } catch {
      save.disabled = false;
    }
  });
}
