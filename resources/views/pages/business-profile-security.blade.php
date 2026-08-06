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

  {{-- Danger zone --}}
  <x-cabinet.card class="bpsec-card danger border-danger/50" gap="gap-4"
      :title="__('business-profile-security.danger.title')"
      :desc="__('business-profile-security.danger.desc')">
    <div class="bpsec-row">
      <p class="desc">{{ __('business-profile-security.danger.deactivate_desc') }}</p>
      <x-ui.button variant="danger" id="deactivateBtn" class="h-[42px] px-[18px] text-[13px] leading-[normal] font-semibold whitespace-nowrap">{{ __('business-profile-security.danger.deactivate') }}</x-ui.button>
    </div>
    <div id="deactivateConfirm" class="mt-4 hidden rounded-[12px] border border-red bg-[#fef2f2] p-4">
      <p class="mb-3 text-[14px] text-[#b91c1c]">{{ __('specialist-cabinet-security.danger.confirm_text') }}</p>
      <x-cabinet.field :label="__('specialist-cabinet-security.danger.password_label')" for="deactivate-pwd">
        <x-ui.input variant="b2b" type="password" id="deactivate-pwd" name="deactivate_password" autocomplete="current-password" />
      </x-cabinet.field>
      <x-ui.alert tone="error" id="deactivateErr" class="mt-3" />
      <div class="mt-3 flex gap-3">
        <x-ui.button variant="danger" id="deactivateConfirmBtn" class="h-[42px] px-[18px] text-[13px] font-semibold">{{ __('specialist-cabinet-security.danger.confirm_deactivate') }}</x-ui.button>
        <x-ui.button variant="outline" id="deactivateCancelBtn" class="h-[42px] px-[18px] text-[13px] font-semibold">{{ __('common.cancel') }}</x-ui.button>
      </div>
    </div>
  </x-cabinet.card>

  {{-- Save bar --}}
  <x-cabinet.save-bar ns="business-profile-security" />

</x-cabinet.shell>

</x-layout>
