{{-- Specialist cabinet — services & prices (Figma 831:12139) --}}
<x-layout page="specialist-cabinet-services" :title="__('specialist-cabinet-services.title')" bodyClass="bg-gray-soft2">

@php
    // The seven specialist-cabinet tabs — identical on every specialist-cabinet-* page
    // (ARCHITECTURE.md 4.9.1); only `active` differs.
    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count'],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count'],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'reviews',       'route' => 'specialist.cabinet.reviews',       'count' => 'reviews_count'],
        ['key' => 'notifications', 'route' => 'specialist.cabinet.notifications'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];

    // Non-text row data: the price and the visibility switch. Every label is a lang key.
    $services = [
        ['key' => 'tile_floor',  'price' => '25', 'on' => true],
        ['key' => 'mosaic',      'price' => '35', 'on' => true],
        ['key' => 'waterproof',  'price' => '18', 'on' => true],
        ['key' => 'demolition',  'price' => '8',  'on' => false],
    ];

    // Price units of the <select>; the first one is the design's default.
    $units = ['sqm', 'hour', 'piece', 'linear'];
@endphp

{{-- Header, settings sidebar and the two-column body come from the cabinet shell. --}}
<x-cabinet.shell
    ns="specialist-cabinet-services"
    active="services"
    :nav-items="$specNav"
    :view-href="route('specialist.owner')"
    progress-fill="w-[168px]"
    class="scs-page">

  <x-cabinet.card
      layout="row"
      gap="gap-3.5"
      :title="__('specialist-cabinet-services.list.title', ['count' => count($services)])"
      :desc="__('specialist-cabinet-services.list.summary')"
      data-title-tpl="{{ __('specialist-cabinet-services.list.title', ['count' => ':n']) }}"
      data-new-name="{{ __('specialist-cabinet-services.list.new_name') }}"
      data-new-desc="{{ __('specialist-cabinet-services.list.new_desc') }}">
    <x-slot:action>
      <x-ui.button variant="primary" class="cab-btn-add" data-add>{{ __('specialist-cabinet-services.list.add') }}</x-ui.button>
    </x-slot:action>

    <div class="scs-list">
      @foreach ($services as $service)
        <x-cabinet.row>
          {{-- The ⠿ glyph is presentational, so it lives in the CSS, not in a lang file. --}}
          <button type="button" class="scs-grip" data-grip aria-label="{{ __('specialist-cabinet-services.list.reorder') }}"></button>

          <div class="scs-info">
            <p class="n">{{ __('specialist-cabinet-services.services.' . $service['key'] . '.name') }}</p>
            <p class="d">{{ __('specialist-cabinet-services.services.' . $service['key'] . '.desc') }}</p>
          </div>

          <input type="text" inputmode="decimal" class="scs-price" value="{{ $service['price'] }}"
                 aria-label="{{ __('specialist-cabinet-services.list.price_label') }}">

          <select class="scs-unit" aria-label="{{ __('specialist-cabinet-services.list.unit_label') }}">
            @foreach ($units as $unit)
              <option>{{ __('specialist-cabinet-services.units.' . $unit) }}</option>
            @endforeach
          </select>

          <x-ui.toggle
              :on="$service['on']"
              size="sm"
              tone="ok"
              :aria-label="__('specialist-cabinet-services.list.toggle')" />

          <x-ui.button variant="ghost" class="scs-del" data-del>{{ __('specialist-cabinet-services.list.delete') }}</x-ui.button>
        </x-cabinet.row>
      @endforeach
    </div>

    <p class="scs-hint">{{ __('specialist-cabinet-services.list.hint') }}</p>
  </x-cabinet.card>

  <x-cabinet.save-bar ns="specialist-cabinet-services" />

</x-cabinet.shell>

</x-layout>
