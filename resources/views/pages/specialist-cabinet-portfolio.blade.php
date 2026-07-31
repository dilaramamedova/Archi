{{--
  Specialist cabinet — Portfolio (Figma 831:11867 / edit-page 831:11904, `scp-` prefix).
  Structurally the business cabinet: the shared <x-cabinet.*> shell renders the header,
  the settings sidebar and the dark save bar; only the tile grid is this page's own
  (ARCHITECTURE.md §4.9.1). The navbar/footer in the Figma frame come from <x-layout>.
--}}
@php
    // The seven specialist-cabinet tabs — copied verbatim across all seven pages.
    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count'],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count'],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'reviews',       'route' => 'specialist.cabinet.reviews',       'count' => 'reviews_count'],
        ['key' => 'notifications', 'route' => 'specialist.cabinet.notifications'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];

    // Portfolio tiles (831:11952 + 831:11975): six photos, then the dashed uploader.
    // The photos are the ones the public profile and specialist-owner already ship.
    $tiles = ['t1' => 'pf1.jpg', 't2' => 'pf2.jpg', 't3' => 'pf3.jpg', 't4' => 'pf4.jpg', 't5' => 'pf5.jpg', 't6' => 'pf6.jpg'];

    // The counter is the profile's real total, not the number of visible tiles.
    $count = 24;
    $max = 30;
@endphp
<x-layout page="specialist-cabinet-portfolio" :title="__('specialist-cabinet-portfolio.title')" bodyClass="bg-gray-soft2">

<x-cabinet.shell
    ns="specialist-cabinet-portfolio"
    active="portfolio"
    :nav-items="$specNav"
    :view-href="route('specialist.owner')"
    progress-fill="w-[168px]"
    class="scp-page">

  {{-- portfolio card (831:11945). data-title-tpl lets the JS renumber the heading
       after a tile is added or removed without hardcoding the string. --}}
  <x-cabinet.card
      layout="row"
      gap="gap-4"
      :title="__('specialist-cabinet-portfolio.list.title', ['count' => $count, 'max' => $max])"
      :desc="__('specialist-cabinet-portfolio.list.summary')"
      data-title-tpl="{{ __('specialist-cabinet-portfolio.list.title', ['count' => '{count}', 'max' => '{max}']) }}">
    <x-slot:action>
      <x-ui.button variant="primary" class="cab-btn-add" data-add>{{ __('specialist-cabinet-portfolio.list.add') }}</x-ui.button>
    </x-slot:action>

    <div class="scp-grid" data-count="{{ $count }}" data-max="{{ $max }}">
      @foreach ($tiles as $key => $img)
        <div class="scp-tile" draggable="true" data-drag="false" data-over="false">
          <img src="/assets/{{ $img }}" alt="{{ __('specialist-cabinet-portfolio.tiles.' . $key) }}">
          {{-- the cover badge always belongs to the first tile, so reordering moves it --}}
          <p class="scp-cover" @if (! $loop->first) hidden @endif>{{ __('specialist-cabinet-portfolio.tile.cover') }}</p>
          <button type="button" class="scp-del" data-del
                  aria-label="{{ __('specialist-cabinet-portfolio.tile.remove_label') }}">{{ __('specialist-cabinet-portfolio.tile.remove') }}</button>
          <p class="scp-cap">{{ __('specialist-cabinet-portfolio.tiles.' . $key) }}</p>
        </div>
      @endforeach

      {{-- dashed uploader slot (831:11986) --}}
      <button type="button" class="scp-add" data-add>
        <span class="ic" aria-hidden="true">{{ __('specialist-cabinet-portfolio.uploader.icon') }}</span>
        <span class="l">{{ __('specialist-cabinet-portfolio.uploader.label') }}</span>
      </button>
    </div>

    {{-- The Figma hint marker (831:11991) is the character U+2195 set in Inter, but the
         Google Fonts build of Inter subsets that codepoint away, so the browser fell back
         to a system font and drew a thinner, narrower arrow in a 5px-wide box instead of
         Inter's 8px one -- which also pulled the hint text 6px left. Drawing the glyph
         inline drops the font dependency and pins the 13x16 advance box Figma uses. --}}
    <p class="scp-hint">
      <svg class="ic" viewBox="0 0 13 16" fill="none" aria-hidden="true" focusable="false">
        <path d="M5 2.53V15.47M1.5 6.03 5 2.53 8.5 6.03M1.5 11.97 5 15.47 8.5 11.97"
              stroke="currentColor" stroke-width="1.05" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span class="t">{{ __('specialist-cabinet-portfolio.hint.text') }}</span>
    </p>

    {{-- both "add" affordances open this picker; new tiles are previewed client-side --}}
    <input type="file" class="sr-only" data-picker multiple accept="image/jpeg,image/png"
           aria-label="{{ __('specialist-cabinet-portfolio.uploader.picker_label') }}">
  </x-cabinet.card>

  <x-cabinet.save-bar ns="specialist-cabinet-portfolio" />

</x-cabinet.shell>

</x-layout>
