{{-- Specialist cabinet — personal info edit (Figma 831:11186).
     The specialist cabinet reuses the business cabinet shell (ARCHITECTURE.md §4.9.1):
     same header, same 264px sidebar, same cards, same dark save bar — only the nav rows
     and the "view profile" target differ. --}}
@php
    $portfolioCount = $profile ? $profile->portfolioItems()->count() : 0;
    $servicesCount = $profile ? $profile->services()->count() : 0;

    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count', 'count_value' => $portfolioCount],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count', 'count_value' => $servicesCount],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'reviews',       'route' => 'specialist.cabinet.reviews',       'count' => 'reviews_count'],
        ['key' => 'notifications', 'route' => 'specialist.cabinet.notifications'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];

    $initials = mb_strtoupper(mb_substr($user->first_name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1));
    $aboutMax = __('specialist-cabinet.main.about_max');
    $about = $profile->about ?? '';
    $skills = $profile->skills ?? [];
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
      <div class="sc-avatar"><p>{{ $initials }}</p></div>
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
    <x-ui.alert tone="error" id="profileErr" class="mb-2" />
    <x-ui.alert tone="ok" id="profileOk" class="mb-2" />

    <div class="cab-field-row">
      <x-cabinet.field :label="__('specialist-cabinet.main.first_name')" for="sc-first-name">
        <x-ui.input variant="b2b" id="sc-first-name" name="first_name" class="sc-input"
                    :value="$user->first_name" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('specialist-cabinet.main.last_name')" for="sc-last-name">
        <x-ui.input variant="b2b" id="sc-last-name" name="last_name" class="sc-input"
                    :value="$user->last_name" />
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="__('specialist-cabinet.main.craft')" for="sc-craft">
        <div class="sc-select">
          <x-ui.select variant="b2b" id="sc-craft" name="craft" class="sc-input sc-select-control"
                       :options="__('specialist-cabinet.main.craft_options')"
                       :value="$profile->craft ?? ''" />
          <img class="car" src="/assets/icon-chevron-down-small.svg" alt="">
        </div>
      </x-cabinet.field>
      <x-cabinet.field :label="__('specialist-cabinet.main.experience')" for="sc-experience">
        <x-ui.input variant="b2b" id="sc-experience" name="experience_years" class="sc-input" inputmode="numeric"
                    :value="$profile->experience_years ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('specialist-cabinet.main.city')" for="sc-city">
        <div class="sc-select">
          <x-ui.select variant="b2b" id="sc-city" name="city" class="sc-input sc-select-control"
                       :options="__('specialist-cabinet.main.city_options')"
                       :value="$profile->city ?? ''" />
          <img class="car" src="/assets/icon-chevron-down-small.svg" alt="">
        </div>
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="__('specialist-cabinet.main.phone')" for="sc-phone">
        <x-ui.input variant="b2b" type="tel" id="sc-phone" name="phone" class="sc-input"
                    :value="$profile->phone ?? $user->phone" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('specialist-cabinet.main.whatsapp')" for="sc-whatsapp">
        <x-ui.input variant="b2b" type="tel" id="sc-whatsapp" name="whatsapp" class="sc-input"
                    :value="$profile->whatsapp ?? ''" />
      </x-cabinet.field>
    </div>

    <x-cabinet.field full :label="__('specialist-cabinet.main.about')" for="sc-about">
      <x-ui.textarea variant="b2b" id="sc-about" name="about" class="sc-textarea"
                     maxlength="{{ $aboutMax }}">{{ $about }}</x-ui.textarea>
      <p class="sc-counter" data-count-for="sc-about" data-max="{{ $aboutMax }}">{{ mb_strlen($about) }} / {{ $aboutMax }}</p>
    </x-cabinet.field>

  </x-cabinet.card>

  {{-- skills --}}
  <x-cabinet.card gap="gap-[18px]"
      :title="__('specialist-cabinet.skills.heading')"
      :desc="__('specialist-cabinet.skills.desc')">

    <div class="sc-skills">
      @foreach ($skills as $skill)
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
