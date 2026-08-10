// Shared auth utilities — CSRF-aware fetch, error rendering, field error display.
import popup from './popup.js';

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export async function authFetch(url, data) {
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: JSON.stringify(data),
  });
  const json = await res.json();
  if (!res.ok) return { ok: false, status: res.status, errors: json.errors ?? {}, message: json.message ?? '' };
  return { ok: true, data: json };
}

export function clearErrors(form) {
  form.querySelectorAll('.field-error').forEach(el => el.remove());
  form.querySelectorAll('.ui-control-error, .ui-control-b2b-error').forEach(el => {
    el.classList.remove('ui-control-error', 'ui-control-b2b-error');
  });
}

// Pass a truthy `showGeneral` to surface the first error in the global popup
// (replaces the old inline general-alert element); field errors stay inline.
export function showErrors(form, errors, showGeneral) {
  clearErrors(form);

  const firstField = Object.keys(errors)[0];
  if (showGeneral && firstField) {
    popup.error(errors[firstField][0]);
  }

  for (const [field, messages] of Object.entries(errors)) {
    const input = form.querySelector(`[name="${field}"]`);
    if (!input) continue;
    input.classList.add('ui-control-error');
    const errEl = document.createElement('p');
    errEl.className = 'field-error';
    errEl.textContent = messages[0];
    input.closest('.ui-field')?.appendChild(errEl) ?? input.parentNode.appendChild(errEl);
  }
}

export function setLoading(btn, loading) {
  btn.disabled = loading;
  if (loading) {
    btn.dataset.origText = btn.textContent;
    btn.textContent = '...';
  } else if (btn.dataset.origText) {
    btn.textContent = btn.dataset.origText;
  }
}
