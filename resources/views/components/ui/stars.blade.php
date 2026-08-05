{{--
  A row of star icons with support for filled, half-filled, and empty stars
  based on a numeric rating.

  Example:
    <x-ui.stars :rating="4.5" />
    <x-ui.stars :rating="$avgRating" />
    <x-ui.stars />  {{-- defaults to 0 (all empty) --}}

  Props:
    rating — numeric rating 0–5 (default 0)
    count  — total number of stars (default 5)
    icon   — filled star icon path (default /assets/icon-star-yellow.svg)
--}}
@props([
    'rating' => 0,
    'count' => 5,
    'icon' => '/assets/icon-star-yellow.svg',
])
@php
    $r = floatval($rating);
    $full = (int) floor($r);
    $half = ($r - $full) >= 0.25 && ($r - $full) < 0.75;
    $roundUp = ($r - $full) >= 0.75;
    if ($roundUp) $full++;
    $empty = (int) $count - $full - ($half ? 1 : 0);
@endphp
@for ($i = 0; $i < $full; $i++)<img src="{{ $icon }}" alt="" class="star-full">@endfor
@if ($half)<img src="{{ $icon }}" alt="" class="star-half" style="opacity:.45">@endif
@for ($i = 0; $i < $empty; $i++)<img src="{{ $icon }}" alt="" class="star-empty" style="opacity:.2">@endfor
