@php
  // Translate, falling back to a literal while a key is still missing from the
  // translations table (t() echoes the key back otherwise).
  $tOr = function (string $key, string $fallback) {
      $v = t($key);
      return is_string($v) && $v !== $key ? $v : $fallback;
  };
@endphp
<x-layout page="business-profile-security" :title="t('business-profile-security.title')" bodyClass="bg-gray-soft2">

{{-- Business cabinet — security (Figma 1105:23405) --}}
<x-cabinet.shell ns="business-profile-security" active="security" class="bpsec-page">

  {{-- Change password --}}
  <x-cabinet.card gap="gap-4"
      :title="t('business-profile-security.password.title')"
      :desc="t('business-profile-security.password.desc')">
    <form id="passwordForm" autocomplete="off">
      <div class="cab-field-row">
        <x-cabinet.field :label="t('business-profile-security.password.current_label')" for="bpsec-current">
          <x-ui.input variant="b2b" type="password" id="bpsec-current" name="current_password" autocomplete="current-password" />
        </x-cabinet.field>
        <x-cabinet.field :label="t('business-profile-security.password.new_label')" for="bpsec-new">
          <x-ui.input variant="b2b" type="password" id="bpsec-new" name="password" autocomplete="new-password" />
        </x-cabinet.field>
        <x-cabinet.field :label="t('business-profile-security.password.repeat_label')" for="bpsec-confirm">
          <x-ui.input variant="b2b" type="password" id="bpsec-confirm" name="password_confirmation" autocomplete="new-password" />
        </x-cabinet.field>
      </div>
      <x-ui.button variant="primary" type="submit" id="pwdSubmit"
        class="mt-2 h-11 px-[22px] text-[13px] leading-[normal] font-bold whitespace-nowrap">{{ t('business-profile-security.password.submit') }}</x-ui.button>
    </form>
  </x-cabinet.card>

  {{-- Danger zone --}}
  <x-cabinet.card class="bpsec-card danger border-danger/50" gap="gap-4"
      :title="t('business-profile-security.danger.title')"
      :desc="t('business-profile-security.danger.desc')">
    <div class="bpsec-row">
      <p class="desc">{{ t('business-profile-security.danger.deactivate_desc') }}</p>
      <x-ui.button variant="danger" id="deactivateBtn" class="h-[42px] px-[18px] text-[13px] leading-[normal] font-semibold whitespace-nowrap">{{ t('business-profile-security.danger.deactivate') }}</x-ui.button>
    </div>
    <div id="deactivateConfirm" class="mt-4 hidden rounded-[12px] border border-red bg-[#fef2f2] p-4">
      <p class="mb-3 text-[14px] text-[#b91c1c]">{{ t('specialist-cabinet-security.danger.confirm_text') }}</p>
      <x-cabinet.field :label="t('specialist-cabinet-security.danger.password_label')" for="deactivate-pwd">
        <x-ui.input variant="b2b" type="password" id="deactivate-pwd" name="deactivate_password" autocomplete="current-password" />
      </x-cabinet.field>
      <div class="mt-3 flex gap-3">
        <x-ui.button variant="danger" id="deactivateConfirmBtn" class="h-[42px] px-[18px] text-[13px] font-semibold"
          :data-l-confirm="$tOr('business-profile-security.danger.confirm_question', 'Hesab deaktiv edilsin? Bu əməliyyat geri qaytarıla bilməz.')"
          data-l-deactivated="{{ t('security.deactivated') }}">{{ t('specialist-cabinet-security.danger.confirm_deactivate') }}</x-ui.button>
        <x-ui.button variant="outline" id="deactivateCancelBtn" class="h-[42px] px-[18px] text-[13px] font-semibold">{{ t('common.cancel') }}</x-ui.button>
      </div>
    </div>
  </x-cabinet.card>

</x-cabinet.shell>

</x-layout>
