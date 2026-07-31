{{-- Business cabinet — company details (Figma 1105:22829) --}}
<x-layout page="business-profile-company" :title="__('business-profile-company.title')" bodyClass="bg-gray-soft2">

{{-- This page is the cabinet outlier: it was ported without hover states, so the shell,
     the header/save-bar buttons and the media buttons all pass :hover="false". --}}
<x-cabinet.shell ns="business-profile-company" active="company" :strong="['contact']" :hover="false" class="bpc-page text-ink">

  {{-- logo & cover card --}}
  <x-cabinet.card tag="h3" gap="gap-[18px]"
      :title="__('business-profile-company.media.heading')"
      :desc="__('business-profile-company.media.desc')">

    <div class="bpc-cover">
      <div class="bg">
        <img src="/assets/hero-renovation-before-after.jpg" alt="">
        <div class="overlay"></div>
      </div>
      <div class="bpc-cover-btn"><p>{{ __('business-profile-company.media.change_cover') }}</p></div>
    </div>

    <div class="bpc-logo-row">
      <div class="bpc-logo-box"><p>{{ __('business-profile-company.media.logo_initials') }}</p></div>
      <div class="bpc-logo-info">
        <p>{{ __('business-profile-company.media.logo_hint') }}</p>
        <div class="bpc-logo-btns">
          <x-ui.button variant="outline" :hover="false" class="cab-btn-edit items-start leading-normal">{{ __('business-profile-company.media.change') }}</x-ui.button>
          <x-ui.button variant="ghost" :hover="false" class="cab-btn-del items-start px-3.5 leading-normal">{{ __('business-profile-company.media.delete') }}</x-ui.button>
        </div>
      </div>
    </div>

  </x-cabinet.card>

  {{-- company card. The heading is 20px here, not the 18px of .cab-card-title, so it
       stays page markup (see business-profile-company.css). --}}
  <x-cabinet.card gap="gap-5" pad="px-7 pt-6 pb-7" class="bpc-card-company">

    <h2>{{ __('business-profile-company.company.heading') }}</h2>
    <p class="desc">{{ __('business-profile-company.company.desc') }}</p>

    <div class="cab-field-row">
      <x-cabinet.field :label="__('business-profile-company.company.legal_name')" for="bpc-legal-name">
        <x-ui.input variant="b2b" id="bpc-legal-name" :value="__('business-profile-company.company.legal_name_value')" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('business-profile-company.company.brand_name')" for="bpc-brand-name">
        <x-ui.input variant="b2b" id="bpc-brand-name" :value="__('business-profile-company.company.brand_name_value')" />
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="__('business-profile-company.company.tax_id')" for="bpc-tax-id">
        <x-slot:badge>
          <x-ui.badge tone="green" size="sm" class="px-2 py-[3px] font-medium">{{ __('business-profile-company.company.tax_id_verified') }}</x-ui.badge>
        </x-slot:badge>
        <x-ui.input variant="b2b" id="bpc-tax-id" :value="__('business-profile-company.company.tax_id_value')" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('business-profile-company.company.city')" for="bpc-city">
        <x-ui.input variant="b2b" id="bpc-city" :value="__('business-profile-company.company.city_value')" />
      </x-cabinet.field>
    </div>

    <x-cabinet.field full :label="__('business-profile-company.company.address')" for="bpc-address">
      <x-ui.input variant="b2b" id="bpc-address" :value="__('business-profile-company.company.address_value')" />
    </x-cabinet.field>

    <x-cabinet.field full :label="__('business-profile-company.company.about')" for="bpc-about">
      <x-ui.textarea variant="b2b" id="bpc-about" class="h-24 resize-none">{{ __('business-profile-company.company.about_value') }}</x-ui.textarea>
    </x-cabinet.field>

  </x-cabinet.card>

  <x-cabinet.save-bar ns="business-profile-company" :hover="false" />

</x-cabinet.shell>

</x-layout>
