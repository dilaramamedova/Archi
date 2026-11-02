{{--
  Stroke icons for the B2B pages (directory board + company profile). Inline SVG so
  each one takes `currentColor` (verified-badge green, featured star, muted contacts
  are plain CSS). Emits `.b2-ic`; size/colour come from the page CSS.

  @include('pages.partials.b2b-icon', ['name' => 'building', 'class' => 'size-5'])
--}}
@php
    $paths = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'sliders' => '<path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'dash' => '<path d="M6 12h12"/>',
        'reset' => '<path d="M4 12a8 8 0 1 0 2.4-5.7L4 8.5"/><path d="M4 4v4.5h4.5"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'star' => '<path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.1 5.9-.9Z"/>',
        'building' => '<path d="M4 21V6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v15M14 21V10h4a2 2 0 0 1 2 2v9M4 21h18M7.5 8h3M7.5 12h3M7.5 16h3"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.8 5.7 3.8 9S14.5 18.5 12 21c-2.5-2.5-3.8-5.7-3.8-9S9.5 5.5 12 3Z"/>',
        'pin' => '<path d="M19 10c0 5.5-7 11-7 11s-7-5.5-7-11a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'badge-check' => '<path d="m9 12 2 2 4-4"/><path d="M12 3l2.1 1.5 2.5-.2 1 2.4 2.1 1.4-.6 2.5.6 2.5-2.1 1.4-1 2.4-2.5-.2L12 21l-2.1-1.5-2.5.2-1-2.4-2.1-1.4.6-2.5-.6-2.5 2.1-1.4 1-2.4 2.5.2Z"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/>',
        'box' => '<path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'tag' => '<path d="M3 11.5V5a2 2 0 0 1 2-2h6.5L21 12.5 12.5 21 3 11.5Z"/><circle cx="8" cy="8" r="1.6"/>',
        'layers' => '<path d="M12 3 2 8l10 5 10-5-10-5ZM2 16l10 5 10-5M2 12l10 5 10-5"/>',
        'languages' => '<path d="M3 5h9M7.5 3v2M10 5c0 4.5-3 8-6.5 9.5M5 9c0 2.5 2.5 5 6 6.5M13 20l4-9 4 9M14.5 17h5"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/>',
        'phone' => '<path d="M21 16.5v3a1.5 1.5 0 0 1-1.6 1.5A18 18 0 0 1 3 4.6 1.5 1.5 0 0 1 4.5 3h3a1.5 1.5 0 0 1 1.5 1.3c.1.9.4 1.8.7 2.6a1.5 1.5 0 0 1-.4 1.6L8 9.8a15 15 0 0 0 6.2 6.2l1.3-1.3a1.5 1.5 0 0 1 1.6-.4c.8.3 1.7.6 2.6.7a1.5 1.5 0 0 1 1.3 1.5Z"/>',
        'link' => '<path d="M9.5 14.5 14.5 9.5M8 12l-2 2a3.5 3.5 0 0 0 5 5l2-2M16 12l2-2a3.5 3.5 0 0 0-5-5l-2 2"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'copy' => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4.5A1.5 1.5 0 0 1 3 13.5v-9A1.5 1.5 0 0 1 4.5 3h9A1.5 1.5 0 0 1 15 4.5V5"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/>',
        'whatsapp' => '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7L4 20Z"/><path d="M9 9.2c0-.4.3-.6.6-.6h.6c.2 0 .4.1.5.4l.5 1.2c.1.2 0 .4-.1.6l-.4.4a.4.4 0 0 0-.1.5 5 5 0 0 0 2.3 2.3c.2.1.4 0 .5-.1l.4-.5c.1-.2.4-.2.6-.1l1.2.5c.2.1.4.3.4.5v.6c0 .4-.3.7-.7.8-2.8.4-6.5-3.3-6.1-6.1Z" fill="currentColor" stroke="none"/>',
        'telegram' => '<path d="M21 5 3 12l5 1.8L18 7l-8 8.5V20l3-3 3.5 2.5L21 5Z"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1.1" fill="currentColor" stroke="none"/>',
        'linkedin' => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 17v-7" />',
        'facebook' => '<path d="M14 7h2V4h-2.5A3.5 3.5 0 0 0 10 7.5V10H8v3h2v8h3v-8h2.2l.8-3H13V7.6c0-.4.3-.6.7-.6Z"/>',
    ];
@endphp
<svg class="b2-ic {{ $class ?? '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $stroke ?? '1.8' }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $paths[$name] ?? '' !!}</svg>
