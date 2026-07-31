{{-- Specialist cabinet — personal info edit (Figma 831:11186).
     The specialist cabinet reuses the business cabinet shell (ARCHITECTURE.md §4.9.1):
     same header, same 264px sidebar, same cards, same dark save bar — only the nav rows
     and the "view profile" target differ. --}}
@php
    // Verbatim across all seven specialist-cabinet-* pages; only `active` differs.
    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count'],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count'],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'reviews',       'route' => 'specialist.cabinet.reviews',       'count' => 'reviews_count'],
        ['key' => 'notifications', 'route' => 'specialist.cabinet.notifications'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];

    $about = __('specialist-cabinet.main.about_value');
    $aboutMax = __('specialist-cabinet.main.about_max');
@endphp
<x-layout page="specialist-cabinet" :title="__('specialist-cabinet.title')" bodyClass="bg-gray-soft2">

<x-cabinet.shell ns="specialist-cabinet" active="main" :nav-items="$specNav"
                 :view-href="route('specialist.owner')" progress-fill="w-[168px]"
                 class="sc-page text-ink">

  {{-- profile photo --}}
  <x-cabinet.card gap="gap-[18px]"
      :title="__('specialist-cabinet.photo.heading')"
      :desc="__('specialist-cabinet.photo.desc')">

    <div class="sc-photo">
      <div class="sc-avatar"><p>{{ __('specialist-cabinet.photo.initials') }}</p></div>
      <div class="sc-photo-info">
        <p class="hint">{{ __('specialist-cabinet.photo.hint') }}</p>
        <div class="sc-photo-btns">
          <x-ui.button variant="outline" class="cab-btn-edit">{{ __('specialist-cabinet.photo.change') }}</x-ui.button>
          <x-ui.button variant="ghost" class="cab-btn-del">{{ __('specialist-cabinet.photo.delete') }}</x-ui.button>
        </div>
      </div>
    </div>

  </x-cabinet.card>

  {{-- personal details --}}
  <x-cabinet.card gap="gap-[18px]" :title="__('specialist-cabinet.main.heading')">

    <div class="cab-field-row">
      <x-cabinet.field :label="__('specialist-cabinet.main.first_name')" for="sc-first-name">
        <x-ui.input variant="b2b" id="sc-first-name" class="sc-input"
                    :value="__('specialist-cabinet.main.first_name_value')" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('specialist-cabinet.main.last_name')" for="sc-last-name">
        <x-ui.input variant="b2b" id="sc-last-name" class="sc-input"
                    :value="__('specialist-cabinet.main.last_name_value')" />
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="__('specialist-cabinet.main.craft')" for="sc-craft">
        <div class="sc-select">
          <x-ui.select variant="b2b" id="sc-craft" class="sc-input sc-select-control"
                       :options="__('specialist-cabinet.main.craft_options')" />
          <img class="car" src="/assets/ic-chevron-sm.svg" alt="">
        </div>
      </x-cabinet.field>
      <x-cabinet.field :label="__('specialist-cabinet.main.experience')" for="sc-experience">
        <x-ui.input variant="b2b" id="sc-experience" class="sc-input" inputmode="numeric"
                    :value="__('specialist-cabinet.main.experience_value')" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('specialist-cabinet.main.city')" for="sc-city">
        <div class="sc-select">
          <x-ui.select variant="b2b" id="sc-city" class="sc-input sc-select-control"
                       :options="__('specialist-cabinet.main.city_options')" />
          <img class="car" src="/assets/ic-chevron-sm.svg" alt="">
        </div>
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="__('specialist-cabinet.main.phone')" for="sc-phone">
        <x-ui.input variant="b2b" type="tel" id="sc-phone" class="sc-input"
                    :value="__('specialist-cabinet.main.phone_value')" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('specialist-cabinet.main.whatsapp')" for="sc-whatsapp">
        <x-ui.input variant="b2b" type="tel" id="sc-whatsapp" class="sc-input"
                    :value="__('specialist-cabinet.main.whatsapp_value')" />
      </x-cabinet.field>
    </div>

    <x-cabinet.field full :label="__('specialist-cabinet.main.about')" for="sc-about">
      <x-ui.textarea variant="b2b" id="sc-about" class="sc-textarea"
                     maxlength="{{ $aboutMax }}">{{ $about }}</x-ui.textarea>
      <p class="sc-counter" data-count-for="sc-about" data-max="{{ $aboutMax }}">{{ mb_strlen($about) }} / {{ $aboutMax }}</p>
    </x-cabinet.field>

  </x-cabinet.card>

  {{-- skills --}}
  <x-cabinet.card gap="gap-[18px]"
      :title="__('specialist-cabinet.skills.heading')"
      :desc="__('specialist-cabinet.skills.desc')">

    <div class="sc-skills">
      @foreach (__('specialist-cabinet.skills.items') as $skill)
        <span class="sc-skill">
          <span class="lbl">{{ $skill }}</span>
          <button class="x" type="button" data-skill-remove
                  aria-label="{{ __('specialist-cabinet.skills.remove') }}">&#10005;</button>
        </span>
      @endforeach

      <input class="sc-skill-input" type="text" data-skill-input hidden
             maxlength="32" aria-label="{{ __('specialist-cabinet.skills.add') }}"
             placeholder="{{ __('specialist-cabinet.skills.placeholder') }}">
      <button class="sc-skill-add" type="button" data-skill-add>{{ __('specialist-cabinet.skills.add') }}</button>

      {{-- cloned by the page JS, so no chip markup or text lives in the module --}}
      <template data-skill-template>
        <span class="sc-skill">
          <span class="lbl"></span>
          <button class="x" type="button" data-skill-remove
                  aria-label="{{ __('specialist-cabinet.skills.remove') }}">&#10005;</button>
        </span>
      </template>
    </div>

  </x-cabinet.card>

  <x-cabinet.save-bar ns="specialist-cabinet" />

</x-cabinet.shell>

</x-layout>
