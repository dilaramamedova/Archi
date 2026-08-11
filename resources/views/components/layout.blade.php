{{--
  Main layout (anonymous Blade component).

  Usage:
    <x-layout page="catalog" :title="t('catalog.title')">
        ... page markup ...
    </x-layout>

  Props:
    page      — data-page value; resources/js/app.js uses it to pick the page JS
                module, so it must equal the page slug
    title     — <title> text (defaults to t('common.site_name'))
    bodyClass — extra class on <body> (rarely needed)
    footer    — render <x-footer/>? Default true. Only business-register passes false:
                its Figma frame (Navbar 140 + auth-page 1160) has no footer, and the
                reference page is the single one of the 23 without a footer mount.

  Optional named slot:
    <x-slot:head> ... </x-slot:head>   → appended to the end of <head>
--}}
@props([
    'page' => 'home',
    'title' => null,
    'bodyClass' => '',
    'footer' => true,
])
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? t('common.site_name') }}</title>
<link rel="icon" href="/assets/logo-archi-black.png" type="image/png">

{{-- Fonts: Inter (whole site) + Manrope (business pages, --font-b2b) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
{{ $head ?? '' }}
</head>
<body
    class="{{ $bodyClass }}"
    data-page="{{ $page }}"
    @auth data-user="{{ auth()->id() }}" @endauth
    data-cur-product="{{ t('common.go_to_product') }}"
    data-cur-details="{{ t('common.view_details') }}"
    data-err-generic="{{ t('common.error_generic') }}"
    data-err-network="{{ t('common.error_network') }}"
>

<x-navbar :headerMenu="$headerMenu" :megaCatalog="$megaCatalog" :megaSpecialists="$megaSpecialists" :megaBlog="$megaBlog" :megaBlogMenu="$megaBlogMenu" />

{{ $slot }}

@if ($footer)
<x-footer :footerMenu="$footerMenu" :footerLegal="$footerLegal" :socialLinks="$socialLinks" />
@endif

{{-- Shared login modal — opened by the navbar "sign in" link; /login is the no-JS fallback --}}
<x-login-modal />

{{-- Session flash → popup (window.archiPopup, resources/js/shared/popup.js).
     Any redirect()->with('success'|'error'|'warning'|'info', …) — plus the legacy
     flash_error/flash_success keys and a validation-$errors summary — surfaces as
     a centred popup on the next page load with zero per-page work. --}}
@php
    $archiFlash = array_filter([
        'success' => session('success') ?? session('flash_success'),
        'error'   => session('error') ?? session('flash_error'),
        'warning' => session('warning'),
        'info'    => session('info'),
    ]);
    if (! isset($archiFlash['error']) && isset($errors) && $errors->any()) {
        $archiFlash['error'] = implode("\n", $errors->all());
    }
@endphp
@if ($archiFlash)
<script type="application/json" id="archiFlash">@json($archiFlash)</script>
@endif

</body>
</html>
