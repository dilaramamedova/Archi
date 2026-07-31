<x-layout page="specialist-cabinet-security" :title="__('specialist-cabinet-security.title')" bodyClass="bg-gray-soft2">

{{-- Specialist cabinet — security (Figma 831:13282). Same shell as the business cabinet
     (ARCHITECTURE.md §4.9.1): only the sidebar rows and the "view profile" target differ. --}}
@php
    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count'],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count'],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'reviews',       'route' => 'specialist.cabinet.reviews',       'count' => 'reviews_count'],
        ['key' => 'notifications', 'route' => 'specialist.cabinet.notifications'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];
@endphp
<x-cabinet.shell ns="specialist-cabinet-security" active="security" class="spsec-page"
                 :nav-items="$specNav"
                 :view-href="route('specialist.owner')"
                 progress-fill="w-[168px]">

  {{-- Change password --}}
  <x-cabinet.card gap="gap-4"
      :title="__('specialist-cabinet-security.password.title')"
      :desc="__('specialist-cabinet-security.password.desc')">
    <div class="cab-field-row">
      <x-cabinet.field tag="p" :label="__('specialist-cabinet-security.password.current_label')">
        <div class="spsec-input">
          <p class="dots" data-mask="{{ __('specialist-cabinet-security.password.mask') }}" data-value="{{ __('specialist-cabinet-security.password.value') }}">{{ __('specialist-cabinet-security.password.mask') }}</p>
          <button type="button" class="eye" data-on="false" aria-pressed="false" aria-label="{{ __('specialist-cabinet-security.password.eye_label') }}">{{ __('specialist-cabinet-security.password.eye') }}</button>
        </div>
      </x-cabinet.field>
      <x-cabinet.field tag="p" :label="__('specialist-cabinet-security.password.new_label')">
        <div class="spsec-input">
          <p class="dots" data-mask="{{ __('specialist-cabinet-security.password.mask') }}" data-value="{{ __('specialist-cabinet-security.password.value') }}">{{ __('specialist-cabinet-security.password.mask') }}</p>
          <button type="button" class="eye" data-on="false" aria-pressed="false" aria-label="{{ __('specialist-cabinet-security.password.eye_label') }}">{{ __('specialist-cabinet-security.password.eye') }}</button>
        </div>
      </x-cabinet.field>
      <x-cabinet.field tag="p" :label="__('specialist-cabinet-security.password.repeat_label')">
        <div class="spsec-input">
          <p class="dots" data-mask="{{ __('specialist-cabinet-security.password.mask') }}" data-value="{{ __('specialist-cabinet-security.password.value') }}">{{ __('specialist-cabinet-security.password.mask') }}</p>
          <button type="button" class="eye" data-on="false" aria-pressed="false" aria-label="{{ __('specialist-cabinet-security.password.eye_label') }}">{{ __('specialist-cabinet-security.password.eye') }}</button>
        </div>
      </x-cabinet.field>
    </div>
    <x-ui.button variant="primary" class="h-11 px-[22px] text-[13px] leading-[normal] font-bold whitespace-nowrap">{{ __('specialist-cabinet-security.password.submit') }}</x-ui.button>
  </x-cabinet.card>

  {{-- Two-factor authentication (OFF in the specialist frame) --}}
  <x-cabinet.card gap="gap-4" :title="__('specialist-cabinet-security.twofa.title')">
    <div class="spsec-row">
      <p class="desc">{{ __('specialist-cabinet-security.twofa.desc') }}</p>
      <x-ui.toggle :on="false" size="md" tone="ok" :aria-label="__('specialist-cabinet-security.twofa.title')" />
    </div>
  </x-cabinet.card>

  {{-- Active sessions --}}
  <x-cabinet.card gap="gap-4" :title="__('specialist-cabinet-security.sessions.title')">
    <x-cabinet.row class="justify-between">
      <div class="spsec-session-info">
        <div class="spsec-session-title">
          <p>{{ __('specialist-cabinet-security.sessions.s1_device') }}</p>
          <x-ui.badge tone="ok" size="xs">{{ __('specialist-cabinet-security.sessions.this_device') }}</x-ui.badge>
        </div>
        <p class="spsec-session-sub">{{ __('specialist-cabinet-security.sessions.s1_meta') }}</p>
      </div>
    </x-cabinet.row>
    <x-cabinet.row class="justify-between">
      <div class="spsec-session-info">
        <div class="spsec-session-title nogap"><p>{{ __('specialist-cabinet-security.sessions.s2_device') }}</p></div>
        <p class="spsec-session-sub">{{ __('specialist-cabinet-security.sessions.s2_meta') }}</p>
      </div>
      <button type="button" class="spsec-logout">{{ __('specialist-cabinet-security.sessions.logout') }}</button>
    </x-cabinet.row>
  </x-cabinet.card>

  {{-- Danger zone --}}
  <x-cabinet.card class="spsec-card danger border-danger/50" gap="gap-4"
      :title="__('specialist-cabinet-security.danger.title')"
      :desc="__('specialist-cabinet-security.danger.desc')">
    <div class="spsec-row">
      <p class="desc">{{ __('specialist-cabinet-security.danger.deactivate_desc') }}</p>
      <x-ui.button variant="danger" class="h-[42px] px-[18px] text-[13px] leading-[normal] font-semibold whitespace-nowrap">{{ __('specialist-cabinet-security.danger.deactivate') }}</x-ui.button>
    </div>
  </x-cabinet.card>

  {{-- Save bar --}}
  <x-cabinet.save-bar ns="specialist-cabinet-security" />

</x-cabinet.shell>

</x-layout>
