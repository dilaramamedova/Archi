<x-layout page="business-profile-security" :title="__('business-profile-security.title')" bodyClass="bg-gray-soft2">

{{-- Business cabinet — security (Figma 1105:23405) --}}
<x-cabinet.shell ns="business-profile-security" active="security" class="bpsec-page">

  {{-- Change password --}}
  <x-cabinet.card gap="gap-4"
      :title="__('business-profile-security.password.title')"
      :desc="__('business-profile-security.password.desc')">
    <div class="cab-field-row">
      <x-cabinet.field :label="__('business-profile-security.password.current_label')">
        <div class="bpsec-input">
          <p class="dots" data-mask="{{ __('business-profile-security.password.mask') }}" data-value="{{ __('business-profile-security.password.value') }}">{{ __('business-profile-security.password.mask') }}</p>
          <button type="button" class="eye" data-on="false" aria-pressed="false">{{ __('business-profile-security.password.eye') }}</button>
        </div>
      </x-cabinet.field>
      <x-cabinet.field :label="__('business-profile-security.password.new_label')">
        <div class="bpsec-input">
          <p class="dots" data-mask="{{ __('business-profile-security.password.mask') }}" data-value="{{ __('business-profile-security.password.value') }}">{{ __('business-profile-security.password.mask') }}</p>
          <button type="button" class="eye" data-on="false" aria-pressed="false">{{ __('business-profile-security.password.eye') }}</button>
        </div>
      </x-cabinet.field>
      <x-cabinet.field :label="__('business-profile-security.password.repeat_label')">
        <div class="bpsec-input">
          <p class="dots" data-mask="{{ __('business-profile-security.password.mask') }}" data-value="{{ __('business-profile-security.password.value') }}">{{ __('business-profile-security.password.mask') }}</p>
          <button type="button" class="eye" data-on="false" aria-pressed="false">{{ __('business-profile-security.password.eye') }}</button>
        </div>
      </x-cabinet.field>
    </div>
    <x-ui.button variant="primary" class="h-11 px-[22px] text-[13px] leading-[normal] font-bold whitespace-nowrap">{{ __('business-profile-security.password.submit') }}</x-ui.button>
  </x-cabinet.card>

  {{-- Two-factor authentication --}}
  <x-cabinet.card gap="gap-4" :title="__('business-profile-security.twofa.title')">
    <div class="bpsec-row">
      <p class="desc">{{ __('business-profile-security.twofa.desc') }}</p>
      <x-ui.toggle :on="true" size="md" tone="ok" :aria-label="__('business-profile-security.twofa.title')" />
    </div>
  </x-cabinet.card>

  {{-- Active sessions --}}
  <x-cabinet.card gap="gap-4" :title="__('business-profile-security.sessions.title')">
    <x-cabinet.row class="justify-between">
      <div class="bpsec-session-info">
        <div class="bpsec-session-title">
          <p>{{ __('business-profile-security.sessions.s1_device') }}</p>
          <x-ui.badge tone="ok" size="xs">{{ __('business-profile-security.sessions.this_device') }}</x-ui.badge>
        </div>
        <p class="bpsec-session-sub">{{ __('business-profile-security.sessions.s1_meta') }}</p>
      </div>
    </x-cabinet.row>
    <x-cabinet.row class="justify-between">
      <div class="bpsec-session-info">
        <div class="bpsec-session-title nogap"><p>{{ __('business-profile-security.sessions.s2_device') }}</p></div>
        <p class="bpsec-session-sub">{{ __('business-profile-security.sessions.s2_meta') }}</p>
      </div>
      <button type="button" class="bpsec-logout">{{ __('business-profile-security.sessions.logout') }}</button>
    </x-cabinet.row>
    <x-cabinet.row class="justify-between">
      <div class="bpsec-session-info">
        <div class="bpsec-session-title nogap"><p>{{ __('business-profile-security.sessions.s3_device') }}</p></div>
        <p class="bpsec-session-sub">{{ __('business-profile-security.sessions.s3_meta') }}</p>
      </div>
      <button type="button" class="bpsec-logout">{{ __('business-profile-security.sessions.logout') }}</button>
    </x-cabinet.row>
  </x-cabinet.card>

  {{-- Danger zone --}}
  <x-cabinet.card class="bpsec-card danger border-danger/50" gap="gap-4"
      :title="__('business-profile-security.danger.title')"
      :desc="__('business-profile-security.danger.desc')">
    <div class="bpsec-row">
      <p class="desc">{{ __('business-profile-security.danger.deactivate_desc') }}</p>
      <x-ui.button variant="danger" class="h-[42px] px-[18px] text-[13px] leading-[normal] font-semibold whitespace-nowrap">{{ __('business-profile-security.danger.deactivate') }}</x-ui.button>
    </div>
  </x-cabinet.card>

  {{-- Save bar --}}
  <x-cabinet.save-bar ns="business-profile-security" />

</x-cabinet.shell>

</x-layout>
