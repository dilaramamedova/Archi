{{--
  A row of star icons. Pure markup — the size/gap belong to the surrounding rating
  block (.pd-stars, .rev-card .sd, .pcard .rating), which is page/card specific.
  Replaces the `@for ($i = 0; $i < 5; $i++)<img …>@endfor` loops on the product page.

  Example:
    <span class="s"><x-ui.stars /></span>
    <x-ui.stars :count="4" />

  Props:
    count — number of stars (default 5)
    icon  — icon path (default /assets/icon-star-yellow.svg)
--}}
@props([
    'count' => 5,
    'icon' => '/assets/icon-star-yellow.svg',
])
@for ($i = 0; $i < (int) $count; $i++)<img src="{{ $icon }}" alt="">@endfor
