// Page module for "blog-article": the share bar next to the author line.
// Labels and the article title come from Blade via data-* — never hardcoded here.
export default function init() {
  const bar = document.querySelector('[data-share-bar]');
  if (!bar) return;

  const status = bar.querySelector('[data-share-status]');
  const copiedLabel = bar.dataset.copiedLabel || '';
  const title = bar.dataset.shareTitle || document.title;
  let timer;

  const announce = (text) => {
    if (!status) return;
    status.textContent = text;
    clearTimeout(timer);
    timer = setTimeout(() => { status.textContent = ''; }, 2000);
  };

  const copyLink = (url) => {
    if (!navigator.clipboard) return;
    navigator.clipboard.writeText(url).then(() => announce(copiedLabel)).catch(() => {});
  };

  const popup = (href) => window.open(href, '_blank', 'noopener,noreferrer');

  bar.querySelectorAll('[data-share]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const url = window.location.href;
      switch (btn.dataset.share) {
        case 'facebook':
          popup(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`);
          break;
        case 'whatsapp':
          popup(`https://api.whatsapp.com/send?text=${encodeURIComponent(`${title} ${url}`)}`);
          break;
        // Instagram has no web share endpoint — hand it to the OS share sheet.
        case 'native':
          if (navigator.share) navigator.share({ title, url }).catch(() => {});
          else copyLink(url);
          break;
        default:
          copyLink(url);
      }
    });
  });
}
