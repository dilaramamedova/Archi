// Business orders list + order detail: status transition buttons.
// Shared by data-page "business-orders" and "business-order-detail".

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

async function changeStatus(orderId, next) {
  const res = await fetch(`/business/orders/${orderId}/status`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf(),
      'Accept': 'application/json',
    },
    body: JSON.stringify({ status: next }),
  });
  const data = await res.json().catch(() => ({}));

  if (res.ok && data.success) {
    location.reload();
  } else {
    archiPopup.error(data.message || document.body.dataset.errGeneric);
  }
}

export default function () {
  document.querySelectorAll('[data-order-status]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const { orderId, next } = btn.dataset;

      if (btn.dataset.confirm === 'true') {
        const ok = await archiPopup.confirm(
          document.querySelector('[data-cancel-confirm-body]')?.dataset.cancelConfirmBody || btn.textContent.trim() + '?',
          { title: btn.textContent.trim() }
        );
        if (!ok) return;
      }

      btn.disabled = true;
      await changeStatus(orderId, next);
      btn.disabled = false;
    });
  });
}
