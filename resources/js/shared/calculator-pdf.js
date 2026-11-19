// «PDF yüklə» for both calculators (TZ №2 19.11.2026, §39).
//
// The server renders a PDF once and then serves it from disk. While it is still being
// rendered it answers 202 (or 503 when a render failed) with Retry-After; this module
// polls every 2 s for up to 15 s, keeps a loading state on the button the whole time,
// and only then tells the visitor «PDF hazırlanmadı, yenidən cəhd edin». The file is
// fetched rather than navigated to, so a failed first request can never again end in
// a blank nothing.

import popup from './popup.js';

const RETRY_EVERY_MS = 2000;
const GIVE_UP_AFTER_MS = 15000;

/** Statuses that mean «not yet» rather than «no». */
const RETRYABLE = new Set([202, 429, 502, 503, 504]);

const sleep = (ms) => new Promise((resolve) => { setTimeout(resolve, ms); });

/** «attachment; filename="archi-smeta-2026-11-19-1a2b3c4d.pdf"» → the name. */
function filenameOf(response, fallback) {
  const header = response.headers.get('Content-Disposition') || '';
  const match = header.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);

  return match ? decodeURIComponent(match[1]) : fallback;
}

function save(blob, filename) {
  const href = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = href;
  link.download = filename;
  link.rel = 'noopener';
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(href), 60000);
}

/**
 * Put a button into its loading state; returns the function that restores it.
 *
 * @param {HTMLButtonElement|null} button
 * @param {string} loadingText
 */
export function busy(button, loadingText) {
  if (! button) return () => {};

  const label = button.textContent;
  button.disabled = true;
  button.classList.add('is-loading');
  button.setAttribute('aria-busy', 'true');
  if (loadingText) button.textContent = loadingText;

  return () => {
    button.disabled = false;
    button.classList.remove('is-loading');
    button.removeAttribute('aria-busy');
    button.textContent = label;
  };
}

/**
 * Fetch the PDF at `url` and hand it to the browser as a download.
 *
 * @param {string} url
 * @param {{failedText?: string, filename?: string}} options
 * @returns {Promise<boolean>} true when the file was downloaded
 */
export async function downloadPdf(url, { failedText = '', filename = 'archi.pdf' } = {}) {
  const deadline = Date.now() + GIVE_UP_AFTER_MS;

  for (;;) {
    let retry = false;

    try {
      const response = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/pdf' },
        cache: 'no-store',
      });
      const type = response.headers.get('Content-Type') || '';

      if (response.status === 200 && type.includes('pdf')) {
        save(await response.blob(), filenameOf(response, filename));

        return true;
      }

      retry = RETRYABLE.has(response.status);
    } catch {
      // A dropped connection is worth another try, like a 503.
      retry = true;
    }

    if (! retry || Date.now() + RETRY_EVERY_MS > deadline) break;

    await sleep(RETRY_EVERY_MS);
  }

  if (failedText) popup.info(failedText);

  return false;
}
