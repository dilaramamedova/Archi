// Page module for "specialist" — ported from the inline <script> of the old
// specialist.html. Handles the favourite heart toggle, calculator button,
// and the "send message" modal.

const HEART_ON = '/assets/icon-heart-rounded.svg';
const HEART_OFF = '/assets/icon-heart-pointed.svg';

function initFavourite() {
  const fav = document.getElementById('ppFav');
  const icon = fav && fav.querySelector('img');
  if (!fav || !icon) return;

  fav.addEventListener('click', () => {
    const on = fav.dataset.on !== 'true';
    fav.dataset.on = on ? 'true' : 'false';
    fav.setAttribute('aria-pressed', on ? 'true' : 'false');
    icon.src = on ? HEART_ON : HEART_OFF;
  });
}

function initContactPhone() {
  const btn = document.getElementById('ppContact');
  const link = document.getElementById('ppContactPhone');
  if (!btn || !link) return;
  btn.addEventListener('click', () => {
    const phone = (btn.dataset.phone || '').trim();
    link.textContent = phone || btn.dataset.noPhone || '';
    link.hidden = false;
    if (phone) link.href = `tel:${phone.replace(/[^+\d]/g, '')}`;
  });
}

function initMessageModal() {
  const btn = document.getElementById('ppMsg');
  if (!btn) return;

  const modal = document.getElementById('msgModal');
  if (!modal) return;

  const form = document.getElementById('msgForm');
  const successEl = document.getElementById('msgSuccess');
  const errorEl = document.getElementById('msgError');

  function openModal() {
    modal.dataset.on = 'true';
    modal.classList.add('animate-[fadeIn_0.2s_ease]');
    document.body.dataset.lmLock = 'true';
    // Focus the first input
    const firstInput = form.querySelector('input:not([type=hidden]), textarea');
    if (firstInput) setTimeout(() => firstInput.focus(), 60);
  }

  function closeModal() {
    if (modal.dataset.on !== 'true') return;
    modal.dataset.on = 'false';
    modal.classList.remove('animate-[fadeIn_0.2s_ease]');
    delete document.body.dataset.lmLock;
    btn.focus();
  }

  // Open modal on button click
  btn.addEventListener('click', (e) => {
    e.preventDefault();
    // Reset form state
    if (successEl) successEl.classList.add('hidden');
    if (errorEl) errorEl.classList.add('hidden');
    form.reset();
    openModal();
  });

  // Close handlers
  const closeBtn = modal.querySelector('[data-modal-close]');
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
  });

  // Form submit — send message via mailto fallback
  // (A full messaging system would require a backend endpoint; for now, we use
  // a simple approach: collect the data and open a mailto link or show a success
  // state indicating the message was "sent". In a real deployment, this would POST
  // to an API endpoint that stores the message or sends an email notification.)
  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const specialistName = btn.dataset.specialistName || '';
    const specialistId = btn.dataset.specialistId || '';
    const messageText = document.getElementById('msgText')?.value?.trim();

    if (!messageText) return;

    // Collect contact info for guest users
    const nameInput = document.getElementById('msgName');
    const phoneInput = document.getElementById('msgPhone');
    const senderName = nameInput?.value?.trim() || '';
    const senderPhone = phoneInput?.value?.trim() || '';

    // Build mailto link as a pragmatic fallback. Labels come from the form's
    // data-l-* attributes, rendered via t() in the Blade.
    const L = form.dataset;
    const subject = encodeURIComponent((L.lSubject || '') + ' ' + specialistName);
    const body = encodeURIComponent(
      messageText +
      (senderName ? '\n\n' + (L.lName || '') + ' ' + senderName : '') +
      (senderPhone ? '\n' + (L.lPhone || '') + ' ' + senderPhone : '') +
      '\n\n' + (L.lSpecId || '') + ' ' + specialistId
    );

    // Try to open mailto (works on desktop, may not on all mobile)
    window.location.href = 'mailto:?subject=' + subject + '&body=' + body;

    // Show success state regardless
    if (successEl) successEl.classList.remove('hidden');
    if (errorEl) errorEl.classList.add('hidden');

    // Disable submit
    const submitBtn = document.getElementById('msgSubmit');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = submitBtn.textContent.replace(/.$/, '') + ' ✓';
    }

    // Close modal after a short delay
    setTimeout(closeModal, 2000);
  });
}

export default function init() {
  initFavourite();
  initContactPhone();
  initMessageModal();
}
