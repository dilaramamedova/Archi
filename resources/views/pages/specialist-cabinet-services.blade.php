{{-- Specialist cabinet — services & prices (Figma 831:12139) --}}
<x-layout page="specialist-cabinet-services" :title="__('specialist-cabinet-services.title')" bodyClass="bg-gray-soft2">

@php
    // The seven specialist-cabinet tabs — identical on every specialist-cabinet-* page
    // (ARCHITECTURE.md 4.9.1); only `active` differs.
    $portfolioCount = $profile ? $profile->portfolioItems()->count() : 0;
    $servicesCount = $services->count();

    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count', 'count_value' => $portfolioCount],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count', 'count_value' => $servicesCount],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];

    // Services loaded from DB via route closure ($services collection passed from route).
    // Price units for the <select>; the first one is the design's default.
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

    <x-ui.alert tone="error" id="servicesErr" />
    <x-ui.alert tone="ok" id="servicesOk" />

    <div class="scs-list">
      @foreach ($services as $service)
        <x-cabinet.row data-id="{{ $service->id }}">
          {{-- The ⠿ glyph is presentational, so it lives in the CSS, not in a lang file. --}}
          <button type="button" class="scs-grip" data-grip aria-label="{{ __('specialist-cabinet-services.list.reorder') }}"></button>

          <div class="scs-info">
            <p class="n" contenteditable="true">{{ $service->name }}</p>
            <p class="d" contenteditable="true">{{ $service->description ?? '' }}</p>
          </div>

          <input type="text" inputmode="decimal" class="scs-price" value="{{ $service->price ? number_format($service->price, 0) : '' }}"
                 aria-label="{{ __('specialist-cabinet-services.list.price_label') }}">

          <select class="scs-unit" aria-label="{{ __('specialist-cabinet-services.list.unit_label') }}">
            @foreach ($units as $unit)
              <option value="{{ $unit }}" @selected(($service->unit ?? 'sqm') === $unit)>{{ __('specialist-cabinet-services.units.' . $unit) }}</option>
            @endforeach
          </select>

          <x-ui.toggle
              :on="$service->is_active"
              size="sm"
              tone="ok"
              :aria-label="__('specialist-cabinet-services.list.toggle')" />

          <x-ui.button variant="ghost" class="scs-del" data-del>{{ __('specialist-cabinet-services.list.delete') }}</x-ui.button>
        </x-cabinet.row>
      @endforeach
    </div>

    <template id="specialistServiceRowTemplate">
      <x-cabinet.row>
        <button type="button" class="scs-grip" data-grip aria-label="{{ __('specialist-cabinet-services.list.reorder') }}"></button>
        <div class="scs-info">
          <p class="n" contenteditable="true"></p>
          <p class="d" contenteditable="true"></p>
        </div>
        <input type="text" inputmode="decimal" class="scs-price" value=""
               aria-label="{{ __('specialist-cabinet-services.list.price_label') }}">
        <select class="scs-unit" aria-label="{{ __('specialist-cabinet-services.list.unit_label') }}">
          @foreach ($units as $unit)
            <option value="{{ $unit }}">{{ __('specialist-cabinet-services.units.' . $unit) }}</option>
          @endforeach
        </select>
        <x-ui.toggle :on="false" size="sm" tone="ok"
                     :aria-label="__('specialist-cabinet-services.list.toggle')" />
        <x-ui.button variant="ghost" class="scs-del" data-del>{{ __('specialist-cabinet-services.list.delete') }}</x-ui.button>
      </x-cabinet.row>
    </template>

    <p class="scs-hint">{{ __('specialist-cabinet-services.list.hint') }}</p>
  </x-cabinet.card>

  <x-cabinet.save-bar ns="specialist-cabinet-services" />

</x-cabinet.shell>

</x-layout>
