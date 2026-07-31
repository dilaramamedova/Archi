// Page module for "business-profile" — app.js imports it dynamically only when
// <body data-page="business-profile"> is rendered. Shared behaviour (navbar, cursor) lives in
// resources/js/shared/ — do not duplicate it here.
//
// The public storefront is a static read-only view: the old biznes-profil.html carried no
// page script beyond the logo->home handler, which the shared navbar now owns. The one
// piece of state on the page (the product-card carousel dots) is rendered server-side and
// has no interaction, so there is nothing to wire up here.
export default function init() {}

init();
