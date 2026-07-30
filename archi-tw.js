/* =============================================================================
   ARCHİ — Tailwind bazası (build step YOXDUR)

   Necə işləyir
   ------------
   Tailwind v4-ün browser build-i yalnız sənəddəki `<style type="text/tailwindcss">`
   elementlərini oxuyur — nə `<link>` görür, nə də xarici faylı `@import` edə bilir
   (mənbədə birbaşa belədir: «The browser build does not support @import for ...»).
   Ona görə tema 23 səhifədə təkrarlanmasın deyə buradan bir dəfə inject olunur.

   Səhifədə istifadə qaydası — `<head>` içində, bu SIRA ilə:

     <script src="archi-tw.js"></script>
     <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

   Sıra vacibdir: bu fayl klassik skriptdir, yəni sinxron işləyir və `<style>`-ı
   Tailwind compiler-i başlamamışdan əvvəl DOM-a qoyur.

   Utilities qəsdən LAYER-SİZ import olunur
   ----------------------------------------
   CSS cascade qaydası: layer-ə salınmamış bəyanlar HƏMİŞƏ layer-li bəyanları
   üstələyir. `archi.css` layer-siz olduğu üçün `@layer utilities` içindəki
   Tailwind utility-ləri onun `body{background:#fff}` kimi element qaydalarına
   uduzurdu. Layer-siz import edildikdə isə müqayisə adi specificity ilə gedir —
   `.bg-gray-soft2` (0,1,0) > `body` (0,0,1) — və utility qazanır.
   Köçürmə bitib `archi.css` silindikdən sonra `layer(utilities)` geri qoyula bilər.

   Preflight qəsdən yüklənmir
   --------------------------
   `archi.css`-in öz reset-i var (`*{margin:0;padding:0;box-sizing:border-box}`,
   `a{text-decoration:none}`, `img{display:block}`). Tailwind preflight-ı bunun
   üstünə düşsəydi `h1`–`h6`-nın ölçüsünü `inherit`-ə salıb hələ çevrilməmiş
   səhifələrin başlıqlarını sındıracaqdı. Ona görə yalnız theme + utilities
   import olunur. Köçürmə bitib `archi.css` silinəndə preflight-a keçmək olar.

   Rəng/kölgə qeydi
   ----------------
   Şəffaflıqlı tokenlər (`rgba(0,0,0,.5)` və s.) burada AYRICA token deyil —
   Tailwind-in opacity modifikatoru ilə yazılır:
     rgba(0,0,0,.9)  → text-black/90     (köhnə --text-primary)
     rgba(0,0,0,.7)  → text-black/70     (köhnə --text-secondary)
     rgba(0,0,0,.5)  → text-black/50     (köhnə --text-muted)
     rgba(0,0,0,.4)  → text-black/40     (köhnə --text-faint)
     rgba(0,0,0,.1)  → border-black/10   (köhnə --border)
     rgba(0,0,0,.14) → border-black/14   (köhnə --border-strong)
     rgba(0,0,0,.35) → border-black/35   (köhnə --border-hover)
   `--black:#111111` isə #000 DEYİL, ona görə ayrıca token kimi qalır: `ink`.
   Boşluq şkalası (4/8/12/16/20/24/28/32/40/48) Tailwind-in standart şkalası ilə
   üst-üstə düşür (p-1…p-12) — ayrıca spacing tokeni lazım deyil.
   ============================================================================= */
(function () {
  "use strict";

  var CSS = `
@import "tailwindcss/theme.css" layer(theme);
@import "tailwindcss/utilities.css";

@theme {
  /* şrift */
  --font-sans: 'Inter', sans-serif;
  --font-b2b: 'Manrope', sans-serif;

  /* brend */
  --color-yellow: #fdfe00;
  --color-yellow-line: #ffe600;
  --color-sel-bg: #fffde0;

  /* baza — ARCHİ-nin "qarası" #111111-dir, #000 deyil */
  --color-ink: #111111;
  --color-off-white: #efefef;

  /* vəziyyət */
  --color-green: #00a613;
  --color-red: #d3524c;
  --color-star: #ffe600;

  /* boz fonlar */
  --color-gray-soft: #f3f5f7;
  --color-gray-soft2: #f5f7f9;

  /* radius — Figma DS: 4 / 8 / pill */
  --radius-ds: 4px;
  --radius-ds-md: 8px;
  --radius-pill: 100px;
}

/* =============================================================================
   PAYLAŞILAN KART KOMPONENTLƏRİ — archi.css-dən köçürülüb (@apply ilə)

   Bunlar 5 səhifədə birdən istifadə olunur: index · product · catalog · search · blog
   (cəmi 19 istifadə yeri). Ona görə utility-ləri hər səhifədə təkrarlamaq yerinə
   burada BİR DƏFƏ komponent class-ı kimi təyin olunur — paylaşılan komponent üçün
   Tailwind-in standart yolu budur.

   `@layer components` istifadə olunur ki, layer sırası belə olsun:
     theme < components < layer-siz (utilities + səhifənin öz <style>-ı)
   Yəni utility-lər və səhifənin öz qaydaları komponenti həmişə üstələyə bilir
   (məs. search.html-də `.sr .grid4{gap:12px}` işləməyə davam edir).
   ============================================================================= */
@layer components {

  /* ---- şəbəkə ---- */
  .grid4     { @apply flex gap-2; }
  .blog-grid { @apply flex gap-2; }

  /* ---- bölmə başlığı (index · catalog · specialists · blog) ---- */
  .section   { @apply py-10; }
  .sec-head  { @apply mb-5 flex items-end justify-between; }
  .sec-tag   { @apply mb-4 flex items-center gap-2.5; }
  .sec-tag .line { @apply h-0.5 w-8 bg-yellow; }
  .sec-tag p { @apply text-sm font-medium text-black/55 uppercase; }
  .sec-title { @apply text-[40px] font-semibold text-black; }
  /* alt-xətt: sarı zolaq (::before) + hover-də sağa açılan tünd zolaq (::after).
     Qeyd: `scale-x-*` müasir `scale` xassəsini yazır, `transition-transform` isə
     v4-də `transform, translate, scale, rotate`-u əhatə edir — yoxlanıldı. */
  .sec-more  { @apply relative flex items-center gap-2.5 pb-1
                      before:absolute before:inset-x-0 before:bottom-0 before:h-0.5 before:bg-yellow-line before:content-['']
                      after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:origin-left after:scale-x-0 after:bg-ink/70 after:transition-transform after:duration-[350ms] after:content-['']
                      hover:after:scale-x-100; }
  .sec-more p   { @apply text-base font-medium text-[#141414] uppercase; }
  .sec-more img { @apply size-6; }

  /* ---- məhsul kartı ---- */
  .pcard { @apply relative flex flex-1 flex-col gap-3 overflow-hidden rounded-ds border border-black/10 bg-white p-3; }
  .pcard .ph { @apply relative h-[316px] overflow-hidden bg-gray-soft; }
  .pcard .ph > img.prod { @apply absolute top-1/2 left-1/2 h-[90%] w-auto -translate-x-1/2 -translate-y-1/2; }
  .pcard .heart { @apply absolute top-0 right-0 flex rounded-[37px] bg-white p-2; }
  .pcard .heart img { @apply size-5; }
  .pcard .badges { @apply absolute top-0 left-0 flex gap-1; }
  .pcard .badges .b { @apply rounded-ds border border-black/10 bg-white px-2 py-1 font-sans text-sm text-black; }
  .pcard .dots { @apply absolute bottom-1.5 left-1/2 flex -translate-x-1/2 items-center gap-0.5; }
  .pcard .dots i { @apply size-1.5 rounded-[17px] bg-black/30; }
  .pcard .dots i.on { @apply w-[18px] bg-black/15; }
  .pcard .rating { @apply flex items-center gap-1; }
  .pcard .rating img { @apply size-5; }
  .pcard .rating p { @apply font-sans text-sm font-medium text-black; }
  .pcard .rating p span { @apply text-black/30; }
  .pcard .cat { @apply text-xs font-medium text-black/50 uppercase; }
  .pcard .name { @apply text-base font-medium text-black; }
  .pcard .price { @apply flex items-end gap-3; }
  .pcard .price .now { @apply text-xl font-medium text-black; }
  .pcard .price .old { @apply text-sm font-medium text-black/30 line-through; }
  .pcard .price .off { @apply rounded-ds bg-red/20 px-1 py-0.5 font-sans text-[10px] font-bold text-red; }

  /* ---- custom kursor (archi.js `.hascur` / `.cursing` ilə idarə edir) ---- */
  .hascur:hover { @apply cursor-none; }
  .prod-cursor {
    @apply pointer-events-none absolute top-0 left-0 z-40 flex size-[110px] items-center justify-center rounded-full border border-black bg-black p-2.5 text-center text-base leading-[1.25] font-medium text-white opacity-0;
    transform: translate(-50%, -50%) scale(.4);
    transition: opacity .25s ease, transform .25s ease;
  }
  .cursing .prod-cursor { @apply opacity-100; transform: translate(-50%, -50%) scale(1); }
  .prod-cursor span { @apply block max-w-[84px]; }

  /* ---- mütəxəssis kartı ---- */
  .scard { @apply flex flex-1 flex-col gap-5 border border-black/10 bg-white px-3 pt-3 pb-5; }
  .scard .top { @apply relative flex h-[201px] flex-col items-center justify-center gap-3 overflow-hidden; }
  .scard .heart { @apply absolute top-0 right-0 flex rounded-[37px] bg-white p-2; }
  .scard .heart img { @apply size-5; }
  .scard .badges { @apply absolute top-0 left-0 flex gap-1; }
  .scard .badges .b { @apply flex items-center gap-1 rounded-ds border border-black/10 bg-white px-2 py-1 text-sm text-black; }
  .scard .badges .b img { @apply size-5; }
  .scard .badges .b.ok { @apply text-green; }
  .scard .avatar { @apply mt-3.5 flex size-[60px] items-center justify-center rounded-[51px] bg-white; }
  .scard .avatar img { @apply size-[30px]; }
  .scard .role { @apply text-xl font-medium text-black; }
  .scard .rating { @apply flex items-center gap-1; }
  .scard .rating img { @apply size-5; }
  .scard .rating p { @apply font-sans text-sm font-medium text-black; }
  .scard .rating p span { @apply text-black/40; }
  .scard .name { @apply text-base font-medium text-black/80; }
  .scard .meta { @apply mt-3 flex gap-3; }
  .scard .meta span { @apply text-base font-medium text-black/50; }

  /* ---- bloq kartı ---- */
  /* flex-1 + .read{mt-auto} — başlıq 1 və ya 2 sətir olsun, «Oxu →» kartın altında
     eyni yerdə dayanır (kartlar sətir daxilində onsuz da eyni hündürlükdədir) */
  .post { @apply flex flex-1 flex-col border border-[#e4e2db] bg-white; }
  .post .ph { @apply h-[228px] overflow-hidden bg-[#f5f4f0]; }
  .post .ph img { @apply size-full object-cover; }
  .post .body { @apply flex flex-1 flex-col gap-2 p-5; }
  .post .time { @apply text-xs text-[#5c5c57]; }
  .post h3 { @apply text-[21px] font-medium tracking-[-0.21px] text-[#141414]; }
  .post .ex { @apply mb-2 line-clamp-2 text-sm leading-[1.25] text-[#5c5c57]; }
  .post .read { @apply mt-auto self-start border-b-2 border-yellow-line pb-px text-sm font-semibold text-[#141414]; }
}
`;

  var style = document.createElement("style");
  style.setAttribute("type", "text/tailwindcss");
  style.textContent = CSS;
  (document.head || document.documentElement).appendChild(style);
})();
