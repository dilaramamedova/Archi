<x-layout page="specialist-cabinet-security" :title="t('specialist-cabinet-security.title')" bodyClass="bg-gray-soft2">

{{-- Specialist cabinet — security (Figma 831:13282). Same shell as the business cabinet
     (ARCHITECTURE.md §4.9.1): only the sidebar rows and the "view profile" target differ. --}}
@php
    $user = auth()->user();
    $profile = $user->specialistProfile;
    $portfolioCount = $profile ? $profile->portfolioItems()->count() : 0;
    $servicesCount = $profile ? $profile->services()->count() : 0;

    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count', 'count_value' => $portfolioCount],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count', 'count_value' => $servicesCount],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];
@endphp
<x-cabinet.shell ns="specialist-cabinet-security" active="security" class="spsec-page"
                 :nav-items="$specNav"
                 :view-href="route('specialist.owner')"
                 progress-fill="w-[168px]">

  {{-- Change password --}}
  <x-cabinet.card gap="gap-4"
      :title="t('specialist-cabinet-security.password.title')"
      :desc="t('specialist-cabinet-security.password.desc')">
    <form id="passwordForm" autocomplete="off">
      <div class="cab-field-row">
        <x-cabinet.field :label="t('specialist-cabinet-security.password.current_label')" for="spsec-current">
          <x-ui.input variant="b2b" type="password" id="spsec-current" name="current_password" autocomplete="current-password" />
        </x-cabinet.field>
        <x-cabinet.field :label="t('specialist-cabinet-security.password.new_label')" for="spsec-new">
          <x-ui.input variant="b2b" type="password" id="spsec-new" name="password" autocomplete="new-password" />
        </x-cabinet.field>
        <x-cabinet.field :label="t('specialist-cabinet-security.password.repeat_label')" for="spsec-confirm">
          <x-ui.input variant="b2b" type="password" id="spsec-confirm" name="password_confirmation" autocomplete="new-password" />
        </x-cabinet.field>
      </div>
      <x-ui.button variant="primary" type="submit" id="pwdSubmit"
        class="h-11 px-[22px] text-[13px] leading-[normal] font-bold whitespace-nowrap mt-2">{{ t('specialist-cabinet-security.password.submit') }}</x-ui.button>
    </form>
  </x-cabinet.card>

  {{-- Danger zone --}}
  <x-cabinet.card class="spsec-card danger border-danger/50" gap="gap-4"
      :title="t('specialist-cabinet-security.danger.title')"
      :desc="t('specialist-cabinet-security.danger.desc')">
    <div class="spsec-row">
      <p class="desc">{{ t('specialist-cabinet-security.danger.deactivate_desc') }}</p>
      <x-ui.button variant="danger" id="deactivateBtn"
        class="h-[42px] px-[18px] text-[13px] leading-[normal] font-semibold whitespace-nowrap">{{ t('specialist-cabinet-security.danger.deactivate') }}</x-ui.button>
    </div>

    {{-- Deactivation confirmation (hidden) --}}
    <div id="deactivateConfirm" class="hidden mt-4 p-4 bg-[#fef2f2] border border-red rounded-[12px]">
      <p class="text-[14px] text-[#b91c1c] mb-3">{{ t('specialist-cabinet-security.danger.confirm_text') }}</p>
      <x-cabinet.field :label="t('specialist-cabinet-security.danger.password_label')" for="deactivate-pwd">
        <x-ui.input variant="b2b" type="password" id="deactivate-pwd" name="deactivate_password" />
      </x-cabinet.field>
      <div class="flex gap-3 mt-3">
        <x-ui.button variant="danger" id="deactivateConfirmBtn"
          class="h-[42px] px-[18px] text-[13px] leading-[normal] font-semibold">{{ t('specialist-cabinet-security.danger.confirm_deactivate') }}</x-ui.button>
        <x-ui.button variant="outline" id="deactivateCancelBtn"
          class="h-[42px] px-[18px] text-[13px] leading-[normal] font-semibold">{{ t('specialist-cabinet-security.danger.cancel') }}</x-ui.button>
      </div>
    </div>
  </x-cabinet.card>

  {{-- i18n anchors for the active-sessions list and the deactivate popup
       rendered by specialist-cabinet-security.js --}}
  <span hidden data-this-device>{{ t('specialist-cabinet-security.sessions.this_device') }}</span>
  <span hidden data-logout-text>{{ t('specialist-cabinet-security.sessions.logout') }}</span>
  <span hidden data-load-error>{{ t('specialist-cabinet-security.sessions.load_error') }}</span>
  <span hidden data-deactivated-text>{{ t('security.deactivated') }}</span>

</x-cabinet.shell>

</x-layout>
