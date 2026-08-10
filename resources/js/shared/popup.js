// =============================================================================
// archiPopup — global notification / confirm popup (centred modal + dark overlay)
// =============================================================================
//
// Available on EVERY page as `window.archiPopup` (imported by resources/js/app.js)
// and as named ESM exports for page modules:
//
//   import { success, error, info, warning, confirm, show, close } from '../shared/popup.js';
//
// -----------------------------------------------------------------------------
// API — every call returns a Promise that resolves when the popup closes.
// -----------------------------------------------------------------------------
//
// archiPopup.success(message, opts?)   → green check icon,  default title "Uğurlu əməliyyat"
// archiPopup.error(message, opts?)     → red X icon,        default title "Xəta baş verdi"
// archiPopup.info(message, opts?)      → neutral "i" icon,  default title "Məlumat"
// archiPopup.warning(message, opts?)   → amber "!" icon,    default title "Diqqət"
//   All four are sugar for show({ type, message, ...opts }).
//
// archiPopup.show(opts) — the full form. opts:
//   type        'success' | 'error' | 'info' | 'warning'      (default 'info')
//   message     string — plain text; "\n" becomes a line break (always HTML-escaped)
//   title       string — heading text. Omit for the per-type default,
//               pass '' (empty string) or null for NO title at all.
//   buttons     array of button specs (default: one primary "Bağla" that closes):
//                 {
//                   text:      label (required)
//                   variant:   'primary' (yellow) | 'outline' | 'dark' | 'danger'  (default 'primary')
//                   href:      render as a link — navigates on click (onClick still fires first)
//                   onClick:   function called on click (before closing)
//                   value:     what the show() Promise resolves with when this button
//                              closes the popup (default true)
//                   close:     set false to keep the popup open after onClick (default true)
//                   autofocus: receives focus on open (default: the first primary button)
//                 }
//   autoClose   ms until the popup closes itself (default 0 = stays open)
//   dismissible false → no X button, overlay click and Escape do nothing (default true)
//   onClose     function(value) called after the popup fully closes
//   The Promise resolves with the closing button's `value`, or false when
//   dismissed (X / overlay / Escape / autoClose / replaced by a newer popup).
//
// archiPopup.confirm(message, opts?) → Promise<boolean> — native confirm() replacement.
//   opts: { title, confirmText, cancelText, onConfirm, onCancel,
//           type ('warning' by default), danger: true → red confirm button }
//
// archiPopup.close() — programmatically close whatever is open (resolves false).
//
// -----------------------------------------------------------------------------
// Examples for the rollout
// -----------------------------------------------------------------------------
//   archiPopup.success('Məhsul səbətə əlavə olundu', {
//     buttons: [
//       { text: 'Səbətə keç', href: '/cart' },
//       { text: 'Davam et', variant: 'outline' },
//     ],
//   });
//
//   archiPopup.error(data.message || document.body.dataset.errGeneric);
//
//   const ok = await archiPopup.confirm('Bu showroom silinsin?', {
//     confirmText: 'Sil', danger: true,
//   });
//   if (ok) { ...delete... }
//
//   archiPopup.success('Yadda saxlanıldı', { autoClose: 3000 });
//
// -----------------------------------------------------------------------------
// Server-side flash bridging (zero per-page work)
// -----------------------------------------------------------------------------
// The layout (resources/views/components/layout.blade.php) serialises
// session('success') / session('error') / session('warning') / session('info')
// (plus legacy flash_error/flash_success and a validation-$errors summary) into
// <script type="application/json" id="archiFlash">. This module reads it on load
// and shows one popup — priority: error > warning > success > info.
// So a controller can simply `return redirect(...)->with('success', '...')`.
//
// Only ONE popup exists at a time: a new call replaces the current one (whose
// Promise then resolves false). z-index is 10000 — above the login modal (1000),
// the confirm dialog (9998) and the cart checkout modal / toast (9999).
// Texts are localised from <html lang> (az/ru/en) with Azerbaijani fallback.
// =============================================================================

// Tailwind classes live in this object so the templates stay readable; the
// compiler picks them up through the `@source "../js"` rule in app.css.
// Colors/radius/shadow are the design-system tokens from @theme.
const CLS = {
  overlay:
    'fixed inset-0 z-[10000] flex items-center justify-center bg-black/55 p-6 backdrop-blur-[2px] opacity-0 transition-opacity duration-200',
  overlayOn: 'opacity-100',
  dialog:
    'relative w-full max-w-[420px] scale-95 rounded-ds bg-white p-8 text-center opacity-0 shadow-[-6px_6px_28px_rgba(0,0,0,0.22)] transition-[opacity,transform] duration-200',
  dialogOn: 'scale-100 opacity-100',
  // same X button as the login modal / sell success dialog (app.css .ui-modal-close)
  close: 'ui-modal-close',
  iconWrap: 'mx-auto mb-5 flex size-14 items-center justify-center rounded-full',
  icon: {
    success: 'bg-success-soft text-success',
    error: 'bg-error-soft text-error',
    warning: 'bg-warn-soft text-warn',
    info: 'bg-gray-soft text-ink',
  },
  title: 'mb-2 text-xl font-semibold tracking-[-0.2px] text-black/90',
  message: 'text-[15px] leading-[1.5] text-black/55',
  btnRow: 'mt-7 flex flex-wrap justify-center gap-3',
  // geometry shared by every button; tone comes from the design-system ui-btn-* classes
  btnBase: 'ui-btn h-[46px] min-w-0 flex-1 basis-0 px-5 text-sm font-semibold',
  btnVariant: {
    primary: 'ui-btn-primary',
    outline: 'ui-btn-outline',
    dark: 'ui-btn-dark',
    danger: 'ui-btn-danger',
  },
};

// 24x24 stroke icons (stroke = currentColor, tinted by CLS.icon[type])
const ICONS = {
  success: '<path d="M20 6 9 17l-5-5"/>',
  error: '<path d="M18 6 6 18M6 6l12 12"/>',
  warning: '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
  info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-5"/><path d="M12 8h.01"/>',
};

const svg = (type) =>
  `<svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[type] || ICONS.info}</svg>`;

// Default texts, keyed off <html lang>. Values mirror database/seeders/
// TranslationsSeeder.php `common.*` where a key exists (close/cancel/confirm/
// error_generic); the titles are new strings in the same tone.
const I18N = {
  az: {
    successTitle: 'Uğurlu əməliyyat',
    errorTitle: 'Xəta baş verdi',
    infoTitle: 'Məlumat',
    warningTitle: 'Diqqət',
    close: 'Bağla',
    cancel: 'Ləğv et',
    confirm: 'Təsdiq et',
  },
  ru: {
    successTitle: 'Операция выполнена',
    errorTitle: 'Произошла ошибка',
    infoTitle: 'Информация',
    warningTitle: 'Внимание',
    close: 'Закрыть',
    cancel: 'Отмена',
    confirm: 'Подтвердить',
  },
  en: {
    successTitle: 'Success',
    errorTitle: 'An error occurred',
    infoTitle: 'Information',
    warningTitle: 'Attention',
    close: 'Close',
    cancel: 'Cancel',
    confirm: 'Confirm',
  },
};

const T = () => I18N[(document.documentElement.lang || 'az').slice(0, 2)] || I18N.az;

const esc = (s) =>
  String(s == null ? '' : s).replace(
    /[&<>"']/g,
    (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]
  );

// escape first, then turn newlines into <br> so multi-line server messages read well
const nl2br = (s) => esc(s).replace(/\n/g, '<br>');

const TITLE_ID = 'archiPopupTitle';
const MSG_ID = 'archiPopupMsg';
const ANIM_MS = 220; // transition duration + safety margin

// ---------------------------------------------------------------------------
// Single-instance state
// ---------------------------------------------------------------------------
let current = null; // { overlay, dialog, buttons, opts, resolve, lastFocus, autoTimer, onKeydown, prevOverflow }

function show(opts = {}) {
  // a new popup replaces the current one — settle the old Promise as dismissed
  if (current) settle(false, true);

  const t = T();
  const type = CLS.icon[opts.type] ? opts.type : 'info';
  // title: undefined → per-type default; ''/null/false → no title
  const title = opts.title === undefined ? t[type + 'Title'] : opts.title;
  const dismissible = opts.dismissible !== false;

  const buttons =
    Array.isArray(opts.buttons) && opts.buttons.length
      ? opts.buttons
      : [{ text: t.close, variant: 'primary', value: true }];

  const btnHtml = buttons
    .map((b, i) => {
      const cls = `${CLS.btnBase} ${CLS.btnVariant[b.variant] || CLS.btnVariant.primary}`;
      return b.href
        ? `<a href="${esc(b.href)}" class="${cls}" data-hover="true" data-pbtn="${i}">${esc(b.text)}</a>`
        : `<button type="button" class="${cls}" data-hover="true" data-pbtn="${i}">${esc(b.text)}</button>`;
    })
    .join('');

  const overlay = document.createElement('div');
  overlay.id = 'archiPopup';
  overlay.className = CLS.overlay;
  overlay.innerHTML = `
    <div class="${CLS.dialog}" role="alertdialog" aria-modal="true"
         ${title ? `aria-labelledby="${TITLE_ID}"` : ''} aria-describedby="${MSG_ID}">
      ${dismissible ? `<button type="button" class="${CLS.close}" data-pclose aria-label="${esc(t.close)}">&times;</button>` : ''}
      <div class="${CLS.iconWrap} ${CLS.icon[type]}">${svg(type)}</div>
      ${title ? `<h2 id="${TITLE_ID}" class="${CLS.title}">${esc(title)}</h2>` : ''}
      <p id="${MSG_ID}" class="${CLS.message}">${nl2br(opts.message)}</p>
      <div class="${CLS.btnRow}">${btnHtml}</div>
    </div>`;

  const dialog = overlay.firstElementChild;

  current = {
    overlay,
    dialog,
    buttons,
    opts,
    resolve: null,
    lastFocus: document.activeElement,
    autoTimer: null,
    prevOverflow: document.body.style.overflow,
    onKeydown: null,
  };

  // dismiss: overlay click (outside the dialog only)
  if (dismissible) {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) settle(false);
    });
  }

  // button clicks + corner X (delegated)
  overlay.addEventListener('click', (e) => {
    if (e.target.closest('[data-pclose]')) return settle(false);
    const el = e.target.closest('[data-pbtn]');
    if (!el) return;
    const b = buttons[+el.dataset.pbtn];
    if (!b) return;
    if (typeof b.onClick === 'function') b.onClick(e);
    // href buttons navigate natively; still settle so scroll/focus are restored
    // in case navigation is cancelled (e.g. hash link or preventDefault upstream)
    if (b.close !== false) settle(b.value !== undefined ? b.value : true);
  });

  // Escape closes; Tab stays inside the dialog (simple wrap-around trap)
  current.onKeydown = (e) => {
    if (e.key === 'Escape' && dismissible) {
      e.preventDefault();
      // Only the popup should close — keep Escape away from modals underneath
      e.stopPropagation();
      settle(false);
    } else if (e.key === 'Tab') {
      const f = overlay.querySelectorAll('button, a[href]');
      if (!f.length) return;
      const first = f[0];
      const last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  };
  document.addEventListener('keydown', current.onKeydown, true);

  document.body.appendChild(overlay);
  document.body.style.overflow = 'hidden'; // scroll lock, like the login modal

  // fade+scale in on the next frame (transitions are disabled globally by app.css
  // under prefers-reduced-motion, so this degrades to an instant show)
  requestAnimationFrame(() => {
    overlay.classList.add(CLS.overlayOn);
    CLS.dialogOn.split(' ').forEach((c) => dialog.classList.add(c));
    dialog.classList.remove('scale-95', 'opacity-0');
  });

  // focus the requested button, else the first primary one, else the first
  const focusIdx = Math.max(
    0,
    buttons.findIndex((b) => b.autofocus) !== -1
      ? buttons.findIndex((b) => b.autofocus)
      : buttons.findIndex((b) => !b.variant || b.variant === 'primary')
  );
  const focusEl = overlay.querySelector(`[data-pbtn="${focusIdx}"]`) || overlay.querySelector('[data-pbtn]');
  if (focusEl) focusEl.focus();

  if (opts.autoClose > 0) {
    current.autoTimer = setTimeout(() => settle(false), opts.autoClose);
  }

  return new Promise((resolve) => {
    if (current) current.resolve = resolve;
  });
}

// close + resolve. `immediate` skips the exit animation (used when a new popup
// replaces the current one mid-flight).
function settle(value, immediate = false) {
  const c = current;
  if (!c) return;
  current = null;

  if (c.autoTimer) clearTimeout(c.autoTimer);
  document.removeEventListener('keydown', c.onKeydown, true);
  document.body.style.overflow = c.prevOverflow;

  // return focus to where the user was before the popup opened
  if (c.lastFocus && typeof c.lastFocus.focus === 'function' && document.contains(c.lastFocus)) {
    c.lastFocus.focus();
  }

  const remove = () => c.overlay.remove();
  if (immediate) {
    remove();
  } else {
    c.overlay.classList.remove(CLS.overlayOn);
    c.dialog.classList.add('scale-95', 'opacity-0');
    setTimeout(remove, ANIM_MS);
  }

  if (typeof c.opts.onClose === 'function') c.opts.onClose(value);
  if (c.resolve) c.resolve(value);
}

// ---------------------------------------------------------------------------
// Public API
// ---------------------------------------------------------------------------
const success = (message, opts = {}) => show({ ...opts, type: 'success', message });
const error = (message, opts = {}) => show({ ...opts, type: 'error', message });
const info = (message, opts = {}) => show({ ...opts, type: 'info', message });
const warning = (message, opts = {}) => show({ ...opts, type: 'warning', message });

function confirm(message, opts = {}) {
  const t = T();
  return show({
    type: opts.type || 'warning',
    message,
    // native confirm() has no heading — only show one when the caller asks
    title: opts.title !== undefined ? opts.title : '',
    dismissible: true,
    buttons: [
      { text: opts.cancelText || t.cancel, variant: 'outline', value: false },
      {
        text: opts.confirmText || t.confirm,
        variant: opts.danger ? 'danger' : 'primary',
        value: true,
        autofocus: true,
      },
    ],
  }).then((v) => {
    const ok = v === true;
    if (ok && typeof opts.onConfirm === 'function') opts.onConfirm();
    if (!ok && typeof opts.onCancel === 'function') opts.onCancel();
    return ok;
  });
}

const close = () => settle(false);

const api = { show, success, error, info, warning, confirm, close };
window.archiPopup = api;

export { show, success, error, info, warning, confirm, close };
export default api;

// ---------------------------------------------------------------------------
// Session-flash bridge — see the layout's #archiFlash JSON script tag
// ---------------------------------------------------------------------------
function showFlash() {
  const tag = document.getElementById('archiFlash');
  if (!tag) return;
  let flash;
  try {
    flash = JSON.parse(tag.textContent);
  } catch (e) {
    return;
  }
  if (!flash || typeof flash !== 'object') return;
  // one popup at a time — most urgent wins
  const order = ['error', 'warning', 'success', 'info'];
  for (const type of order) {
    if (flash[type]) {
      show({ type, message: String(flash[type]) });
      break;
    }
  }
}

// app.js loads as a deferred module, so the DOM is normally ready already
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', showFlash);
} else {
  showFlash();
}
