// The cabinet save bar starts in an idle state (data-dirty="false") so it does not greet
// the user with "unsaved changes" before they have touched anything. Each page's own
// setSaved() flips data-dirty on the interactions it knows about (chips, toggles, drag),
// but several pages never wired plain text/select/file fields — those edits would then
// warn nobody. This delegated listener covers every field inside the cabinet body, so the
// warning is driven by real edits regardless of what each page remembered to bind.
export default function initCabinetDirty() {
  const bar = document.querySelector('.cab-save-bar');
  const body = document.querySelector('.cab-body');
  if (!bar || !body) return;

  const msg = bar.querySelector('.msg');
  const unsavedText = msg ? msg.textContent.trim() : '';

  const markDirty = () => {
    if (bar.dataset.dirty === 'true') return;
    bar.dataset.dirty = 'true';
    bar.dataset.saved = 'false';
    if (msg && unsavedText) msg.textContent = unsavedText;
  };

  // `input` covers typing, `change` covers selects, checkboxes and file pickers.
  body.addEventListener('input', markDirty);
  body.addEventListener('change', markDirty);
}
