// Page module for "business-profile-company" — real saves against the business profile
// backend: company fields PUT to /business/profile/company, logo/cover upload/delete
// against /business/profile/logo and /business/profile/cover. The save bar keeps the
// shared contract (.cab-save-bar / .msg / [data-save] / [data-cancel]).
export default function init() {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  const bar = document.querySelector('.cab-save-bar');
  const msg = bar ? bar.querySelector('.msg') : null;
  const unsavedText = msg ? msg.textContent : '';
  const savedText = bar ? (bar.dataset.savedMessage || '').trim() : '';

  const setSaved = (on) => {
    if (!bar) return;
    bar.dataset.saved = on ? 'true' : 'false';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };

  // ---- company fields save -------------------------------------------------
  const saveBtn = bar?.querySelector('[data-save]');
  const cancelBtn = bar?.querySelector('[data-cancel]');

  if (saveBtn) {
    saveBtn.addEventListener('click', async () => {
      const payload = {};
      ['legal_name', 'brand_name', 'tax_id', 'city', 'address', 'about'].forEach((name) => {
        const field = document.querySelector(`[name="${name}"]`);
        if (field) payload[name] = field.value;
      });

      try {
        const res = await fetch('/business/profile/company', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.success) setSaved(true);
        else window.alert(data.message || 'Error');
      } catch {
        window.alert('Network error');
      }
    });
  }
  if (cancelBtn) {
    cancelBtn.addEventListener('click', () => window.location.reload());
  }

  // ---- media upload / delete ----------------------------------------------
  const upload = async (url, field, file) => {
    const form = new FormData();
    form.append(field, file);
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: form,
    });
    return res.json().catch(() => ({}));
  };

  const destroy = async (url) => {
    const res = await fetch(url, {
      method: 'DELETE',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    });
    return res.json().catch(() => ({}));
  };

  // Cover
  const coverInput = document.getElementById('coverInput');
  const coverBtn = document.querySelector('.bpc-cover-btn');
  const coverImg = document.querySelector('.bpc-cover .bg img');

  if (coverBtn && coverInput) {
    coverBtn.addEventListener('click', () => coverInput.click());
    coverInput.addEventListener('change', async () => {
      const file = coverInput.files && coverInput.files[0];
      if (!file) return;
      const data = await upload('/business/profile/cover', 'cover', file);
      if (data.success && data.url && coverImg) coverImg.src = data.url;
      else if (!data.success) window.alert(data.message || 'Error');
      coverInput.value = '';
    });
  }

  // Logo
  const logoInput = document.getElementById('logoInput');
  const logoBox = document.querySelector('.bpc-logo-box');
  const logoChangeBtn = document.querySelector('[data-logo-change]');
  const logoDeleteBtn = document.querySelector('[data-logo-delete]');

  const setLogo = (url) => {
    if (!logoBox) return;
    let img = logoBox.querySelector('img');
    if (url) {
      if (!img) {
        img = document.createElement('img');
        img.alt = '';
        img.className = 'h-full w-full object-cover';
        logoBox.innerHTML = '';
        logoBox.appendChild(img);
      }
      img.src = url;
    }
  };

  if (logoChangeBtn && logoInput) {
    logoChangeBtn.addEventListener('click', () => logoInput.click());
    logoInput.addEventListener('change', async () => {
      const file = logoInput.files && logoInput.files[0];
      if (!file) return;
      const data = await upload('/business/profile/logo', 'logo', file);
      if (data.success && data.url) setLogo(data.url);
      else if (!data.success) window.alert(data.message || 'Error');
      logoInput.value = '';
    });
  }

  if (logoDeleteBtn) {
    logoDeleteBtn.addEventListener('click', async () => {
      const data = await destroy('/business/profile/logo');
      if (data.success) window.location.reload();
      else window.alert(data.message || 'Error');
    });
  }
}
