{{--
  Breadcrumb trail. One shape for every page that has one (catalog · product ·
  specialists · specialist · cart · calculator · blog · the six cabinet pages).

  Example:
    <x-ui.breadcrumbs :items="[
        ['label' => __('common.home'), 'href' => route('home')],
        ['label' => __('common.catalog'), 'href' => '#'],
        ['label' => __('catalog.crumb_current')],
    ]" />

  Cabinet form (no links, a written separator):
    <x-ui.breadcrumbs :sep="__('business-profile-company.crumbs.sep')" :items="[
        ['label' => __('business-profile-company.crumbs.panel')],
        ['label' => __('business-profile-company.crumbs.current')],
    ]" />

  Props:
    items — array of ['label' => string, 'href' => ?string]. The LAST item renders as
            the current page (.cur) and is never a link.
    sep   — separator glyph between items (default "/")
--}}
@props([
    'items' => [],
    'sep' => '/',
])
<nav {{ $attributes->merge(['class' => 'ui-crumbs']) }}>
  @foreach ($items as $i => $item)
    @php
        $last = $i === array_key_last($items);
        $href = $item['href'] ?? null;
    @endphp
    @if ($i > 0)<span class="sep">{{ $sep }}</span>@endif
    @if (! $last && $href)
      <a href="{{ $href }}">{{ $item['label'] }}</a>
    @elseif ($last)
      <span class="cur">{{ $item['label'] }}</span>
    @else
      <span>{{ $item['label'] }}</span>
    @endif
  @endforeach
  {{ $slot }}
</nav>
