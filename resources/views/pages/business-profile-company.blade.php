{{-- Business cabinet — company details (Figma 1105:22829) --}}
<x-layout page="business-profile-company" :title="t('business-profile-company.title')" bodyClass="bg-gray-soft2">

{{-- This page is the cabinet outlier: it was ported without hover states, so the shell,
     the header/save-bar buttons and the media buttons all pass :hover="false". --}}
<x-cabinet.shell ns="business-profile-company" active="company" :strong="['contact']" :hover="false" class="bpc-page text-ink">

  {{-- logo & cover card --}}
  <x-cabinet.card tag="h3" gap="gap-[18px]"
      :title="t('business-profile-company.media.heading')"
      :desc="t('business-profile-company.media.desc')">

    <div class="bpc-cover">
      <div class="bg">
        <img src="{{ storage_url($profile?->cover_path, '/assets/hero-renovation-before-after.jpg') }}" alt="">
        <div class="overlay"></div>
      </div>
      <div class="bpc-cover-btn"><p>{{ t('business-profile-company.media.change_cover') }}</p></div>
      <input type="file" id="coverInput" accept="image/*" class="hidden" hidden>
    </div>

    <div class="bpc-logo-row">
      <div class="bpc-logo-box">
        @if ($profile?->logo_path)
          <img src="{{ storage_url($profile->logo_path) }}" alt="" class="h-full w-full object-cover">
        @else
          <p>{{ t('business-profile-company.media.logo_initials') }}</p>
        @endif
      </div>
      <input type="file" id="logoInput" accept="image/*" class="hidden" hidden>
      <div class="bpc-logo-info">
        <p>{{ t('business-profile-company.media.logo_hint') }}</p>
        <div class="bpc-logo-btns">
          <x-ui.button variant="outline" :hover="false" data-logo-change class="cab-btn-edit items-start leading-normal">{{ t('business-profile-company.media.change') }}</x-ui.button>
          <x-ui.button variant="ghost" :hover="false" data-logo-delete class="cab-btn-del items-start px-3.5 leading-normal">{{ t('business-profile-company.media.delete') }}</x-ui.button>
        </div>
      </div>
    </div>

  </x-cabinet.card>

  {{-- company card. The heading is 20px here, not the 18px of .cab-card-title, so it
       stays page markup (see business-profile-company.css). --}}
  <x-cabinet.card gap="gap-5" pad="px-7 pt-6 pb-7" class="bpc-card-company">

    <h2>{{ t('business-profile-company.company.heading') }}</h2>
    <p class="desc">{{ t('business-profile-company.company.desc') }}</p>

    <div class="cab-field-row">
      <x-cabinet.field :label="t('business-profile-company.company.legal_name')" for="bpc-legal-name">
        <x-ui.input variant="b2b" id="bpc-legal-name" name="legal_name" :value="$profile->legal_name ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('business-profile-company.company.brand_name')" for="bpc-brand-name">
        <x-ui.input variant="b2b" id="bpc-brand-name" name="brand_name" :value="$profile->brand_name ?? ''" />
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="t('business-profile-company.company.tax_id')" for="bpc-tax-id">
        <x-slot:badge>
          <x-ui.badge tone="green" size="sm" class="px-2 py-[3px] font-medium">{{ t('business-profile-company.company.tax_id_verified') }}</x-ui.badge>
        </x-slot:badge>
        <x-ui.input variant="b2b" id="bpc-tax-id" name="tax_id" :value="$profile->tax_id ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('business-profile-company.company.city')" for="bpc-city">
        <x-ui.input variant="b2b" id="bpc-city" name="city" :value="$profile->city ?? ''" />
      </x-cabinet.field>
    </div>

    <x-cabinet.field full :label="t('business-profile-company.company.address')" for="bpc-address">
      <x-ui.input variant="b2b" id="bpc-address" name="address" :value="$profile->address ?? ''" />
    </x-cabinet.field>

    <x-cabinet.field full :label="t('business-profile-company.company.about')" for="bpc-about">
      <x-ui.textarea variant="b2b" id="bpc-about" name="about" class="h-24 resize-none">{{ $profile->about ?? '' }}</x-ui.textarea>
    </x-cabinet.field>

  </x-cabinet.card>

  <x-cabinet.save-bar ns="business-profile-company" :hover="false" />

</x-cabinet.shell>

</x-layout>
