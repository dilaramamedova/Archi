// Page module for "specialist-cabinet-portfolio": the interactions the Figma frame shows
// on the tile grid — remove a tile, drag tiles to reorder (the first tile is always the
// cover), add photos through either "add" affordance — plus the shared save-bar contract
// (.cab-save-bar / .msg / [data-save] / [data-cancel]). No upload happens: new tiles are
// object-URL previews before upload. Shared behaviour lives in resources/js/shared/.
import { success, error } from '../shared/popup.js';

export default function init() {
  const grid = document.querySelector('.scp-grid');
  if (!grid) return;

  const bar = document.querySelector('.cab-save-bar');
  const msg = bar ? bar.querySelector('.msg') : null;
  const unsavedText = msg ? msg.textContent : '';
  const savedText = bar ? (bar.dataset.savedMessage || '').trim() : '';

  // `saved` only swaps the message text and the dot color — the box keeps its size.
  const setSaved = (on) => {
    if (!bar) return;
    bar.dataset.saved = on ? 'true' : 'false';
    // setSaved is only ever called in response to a real edit, so a false here means dirty.
    bar.dataset.dirty = on ? 'false' : 'true';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };

  const card = document.querySelector('[data-title-tpl]');
  const titleEl = card ? card.querySelector('.cab-card-title') : null;
  const max = Number(grid.dataset.max) || 0;
  // The heading counts the whole portfolio (24), not the tiles on screen, so it is
  // tracked separately and only follows add/remove.
  let count = Number(grid.dataset.count) || 0;
  const newFiles = new WeakMap();

  const tiles = () => Array.from(grid.querySelectorAll('.scp-tile'));

  const paint = () => {
    if (titleEl && card.dataset.titleTpl) {
      titleEl.textContent = card.dataset.titleTpl
        .replace('{count}', String(count))
        .replace('{max}', String(max));
    }
    tiles().forEach((tile, i) => {
      const cover = tile.querySelector('.scp-cover');
      if (cover) cover.hidden = i !== 0;
    });
  };

  grid.addEventListener('click', (e) => {
    const del = e.target.closest('[data-del]');
    if (!del) return;
    const tile = del.closest('.scp-tile');
    if (!tile) return;
    const img = tile.querySelector('img');
    if (img && img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);
    tile.remove();
    count = Math.max(0, count - 1);
    paint();
    setSaved(false);
  });

  // Reorder with native drag and drop: the dragged tile is inserted before or after the
  // tile it is dropped on, depending on which way it travelled.
  let dragged = null;
  const clearOver = () => tiles().forEach((t) => (t.dataset.over = 'false'));

  grid.addEventListener('dragstart', (e) => {
    const tile = e.target.closest('.scp-tile');
    if (!tile) return;
    dragged = tile;
    tile.dataset.drag = 'true';
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', '');
    }
  });

  grid.addEventListener('dragend', () => {
    if (dragged) dragged.dataset.drag = 'false';
    clearOver();
    dragged = null;
  });

  grid.addEventListener('dragover', (e) => {
    const tile = e.target.closest('.scp-tile');
    if (!dragged || !tile || tile === dragged) return;
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    tiles().forEach((t) => (t.dataset.over = t === tile ? 'true' : 'false'));
  });

  grid.addEventListener('drop', (e) => {
    const tile = e.target.closest('.scp-tile');
    if (!dragged || !tile || tile === dragged) return;
    e.preventDefault();
    const forward = dragged.compareDocumentPosition(tile) & Node.DOCUMENT_POSITION_FOLLOWING;
    tile.insertAdjacentElement(forward ? 'afterend' : 'beforebegin', dragged);
    clearOver();
    paint();
    setSaved(false);
  });

  // Both the header button and the dashed slot open the same picker.
  const picker = document.querySelector('[data-picker]');
  document.querySelectorAll('[data-add]').forEach((btn) =>
    btn.addEventListener('click', () => picker && picker.click())
  );

  if (picker) {
    picker.addEventListener('change', () => {
      const slot = grid.querySelector('.scp-add');
      const template = document.getElementById('portfolioTileTemplate');
      if (!slot || !template) return;

      Array.from(picker.files || []).forEach((file) => {
        if (count >= max) return;
        const tile = template.content.firstElementChild.cloneNode(true);
        tile.dataset.drag = 'false';
        tile.dataset.over = 'false';
        const img = tile.querySelector('img');
        if (img) {
          img.src = URL.createObjectURL(file);
          img.alt = file.name;
        }
        const cap = tile.querySelector('.scp-cap');
        if (cap) cap.textContent = file.name;
        newFiles.set(tile, file);
        grid.insertBefore(tile, slot);
        count += 1;
      });

      picker.value = '';
      paint();
      setSaved(false);
    });
  }

  const saveBtn = bar ? bar.querySelector('[data-save]') : null;
  const cancelBtn = bar ? bar.querySelector('[data-cancel]') : null;
  if (saveBtn) saveBtn.addEventListener('click', async () => {
    saveBtn.disabled = true;
    const formData = new FormData();
    const items = [];
    let fileIndex = 0;

    tiles().forEach((tile) => {
      const item = {
        id: tile.dataset.id ? Number(tile.dataset.id) : null,
        title: tile.querySelector('.scp-cap')?.textContent.trim() || null,
      };
      const file = newFiles.get(tile);
      if (file) {
        item.file_index = fileIndex;
        formData.append(`images[${fileIndex}]`, file);
        fileIndex += 1;
      }
      items.push(item);
    });

    formData.append('items', JSON.stringify(items));

    try {
      const res = await fetch('/specialist/cabinet/portfolio', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: formData,
      });
      const data = await res.json();
      if (res.ok) {
        setSaved(true);
        // The reload waits for the popup so the confirmation stays readable.
        success(data.message, { autoClose: 1800 }).then(() => window.location.reload());
      } else {
        error(data.message || Object.values(data.errors || {}).flat().join('. '));
      }
    } finally {
      saveBtn.disabled = false;
    }
  });
  if (cancelBtn) cancelBtn.addEventListener('click', () => window.location.reload());

  paint();
}
