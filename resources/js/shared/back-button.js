// Shared "‹ Geri" button (x-cabinet.back): go back in history when the visitor
// came from a same-origin page, otherwise jump to the button's fallback URL so
// the click never does nothing. Delegated, so one listener covers every page.

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-back-button]');
    if (!btn) return;

    let sameOrigin = false;
    try {
        sameOrigin = !!document.referrer && new URL(document.referrer).origin === window.location.origin;
    } catch {
        sameOrigin = false;
    }

    if (sameOrigin && window.history.length > 1) {
        window.history.back();
    } else {
        window.location.assign(btn.dataset.fallback || '/');
    }
});
