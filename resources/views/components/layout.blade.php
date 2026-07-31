{{--
  Main layout (anonymous Blade component).

  Usage:
    <x-layout page="catalog" :title="__('catalog.meta_title')">
        ... page markup ...
    </x-layout>

  Props:
    page      — data-page value; resources/js/app.js uses it to pick the page JS
                module, so it must equal the page slug
    title     — <title> text (defaults to __('common.site_name'))
    bodyClass — extra class on <body> (rarely needed)

  Optional named slot:
    <x-slot:head> ... </x-slot:head>   → appended to the end of <head>
--}}
@props([
    'page' => 'home',
    'title' => null,
    'bodyClass' => '',
])
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title ?? __('common.site_name') }}</title>

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
    data-cur-product="{{ __('common.go_to_product') }}"
    data-cur-details="{{ __('common.view_details') }}"
>

<x-navbar />

{{ $slot }}

<x-footer />

</body>
</html>
