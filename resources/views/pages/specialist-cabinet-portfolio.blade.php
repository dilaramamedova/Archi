{{--
  Specialist cabinet — Portfolio (Figma 831:11867 / edit-page 831:11904, `scp-` prefix).
  Structurally the business cabinet: the shared <x-cabinet.*> shell renders the header,
  the settings sidebar and the dark save bar; only the tile grid is this page's own
  (ARCHITECTURE.md §4.9.1). The navbar/footer in the Figma frame come from <x-layout>.
--}}
@php
    // The seven specialist-cabinet tabs — copied verbatim across all seven pages.
    $portfolioCount = $portfolioItems->count();
    $servicesCount = $profile ? $profile->services()->count() : 0;

    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count', 'count_value' => $portfolioCount],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count', 'count_value' => $servicesCount],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];

    // Portfolio items loaded from DB via route closure.
    // $portfolioItems and $maxPortfolio are passed from the route.
    $count = $portfolioItems->count();
    $max = $maxPortfolio;
@endphp
<x-layout page="specialist-cabinet-portfolio" :title="t('specialist-cabinet-portfolio.title')" bodyClass="bg-gray-soft2">

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
      :title="t('specialist-cabinet-portfolio.list.title', ['count' => $count, 'max' => $max])"
      :desc="t('specialist-cabinet-portfolio.list.summary')"
      data-title-tpl="{{ t('specialist-cabinet-portfolio.list.title', ['count' => '{count}', 'max' => '{max}']) }}">
    <x-slot:action>
      <x-ui.button variant="primary" class="cab-btn-add" data-add>{{ t('specialist-cabinet-portfolio.list.add') }}</x-ui.button>
    </x-slot:action>

    <x-ui.alert tone="error" id="portfolioErr" />
    <x-ui.alert tone="ok" id="portfolioOk" />

    <div class="scp-grid" data-count="{{ $count }}" data-max="{{ $max }}">
      @foreach ($portfolioItems as $item)
        <div class="scp-tile" draggable="true" data-drag="false" data-over="false" data-id="{{ $item->id }}">
          <img src="{{ storage_url($item->image_path) }}" alt="{{ $item->title ?? t('specialist-cabinet-portfolio.tile.untitled') }}">
          <p class="scp-status" data-status="{{ $item->status->value }}">{{ $item->status->label() }}</p>
          <p class="scp-cover" @if (! $item->is_cover) hidden @endif>{{ t('specialist-cabinet-portfolio.tile.cover') }}</p>
          <button type="button" class="scp-del" data-del
                  aria-label="{{ t('specialist-cabinet-portfolio.tile.remove_label') }}">{{ t('specialist-cabinet-portfolio.tile.remove') }}</button>
          <p class="scp-cap" contenteditable="true">{{ $item->title ?? '' }}</p>
        </div>
      @endforeach

      {{-- dashed uploader slot (831:11986) --}}
      <button type="button" class="scp-add" data-add>
        <span class="ic" aria-hidden="true">{{ t('specialist-cabinet-portfolio.uploader.icon') }}</span>
        <span class="l">{{ t('specialist-cabinet-portfolio.uploader.label') }}</span>
      </button>
    </div>

    <template id="portfolioTileTemplate">
      <div class="scp-tile" draggable="true" data-drag="false" data-over="false">
        <img src="" alt="">
        <p class="scp-status" data-status="pending">{{ t('specialist-cabinet-portfolio.tile.status_pending') }}</p>
        <p class="scp-cover" hidden>{{ t('specialist-cabinet-portfolio.tile.cover') }}</p>
        <button type="button" class="scp-del" data-del
                aria-label="{{ t('specialist-cabinet-portfolio.tile.remove_label') }}">{{ t('specialist-cabinet-portfolio.tile.remove') }}</button>
        <p class="scp-cap" contenteditable="true"></p>
      </div>
    </template>

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
      <span class="t">{{ t('specialist-cabinet-portfolio.hint.text') }}</span>
    </p>

    {{-- both "add" affordances open this picker; new tiles are previewed client-side --}}
    <input type="file" class="sr-only" data-picker multiple accept="image/jpeg,image/png,image/webp"
           aria-label="{{ t('specialist-cabinet-portfolio.uploader.picker_label') }}">
  </x-cabinet.card>

  <x-cabinet.save-bar ns="specialist-cabinet-portfolio" />

</x-cabinet.shell>

</x-layout>
