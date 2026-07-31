<x-layout page="business-profile-contact" :title="__('business-profile-contact.title')" bodyClass="bg-gray-soft2">

{{-- Business cabinet — contact details (Figma 1105:23103) --}}
<x-cabinet.shell ns="business-profile-contact" active="contact" :hover="false" class="bpco-page">

  {{-- Contact card --}}
  <x-cabinet.card gap="gap-[18px]"
      :title="__('business-profile-contact.contact.title')"
      :desc="__('business-profile-contact.contact.desc')">

    <div class="cab-field-row">
      <x-cabinet.field :label="__('business-profile-contact.contact.person_label')" for="bpco-contact-person">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-person" :value="__('business-profile-contact.contact.person_value')" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('business-profile-contact.contact.role_label')" for="bpco-contact-role">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-role" :value="__('business-profile-contact.contact.role_value')" />
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="__('business-profile-contact.contact.phone_label')" for="bpco-contact-phone">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-phone" :value="__('business-profile-contact.contact.phone_value')" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('business-profile-contact.contact.whatsapp_label')" for="bpco-contact-whatsapp">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-whatsapp" :value="__('business-profile-contact.contact.whatsapp_value')" />
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="__('business-profile-contact.contact.telegram_label')" for="bpco-contact-telegram">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-telegram" :value="__('business-profile-contact.contact.telegram_value')" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('business-profile-contact.contact.email_label')" for="bpco-contact-email">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-email" :value="__('business-profile-contact.contact.email_value')" />
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="__('business-profile-contact.contact.website_label')" for="bpco-contact-website">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-website" :value="__('business-profile-contact.contact.website_value')" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('business-profile-contact.contact.hours_label')" for="bpco-contact-hours">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-hours" :value="__('business-profile-contact.contact.hours_value')" />
      </x-cabinet.field>
    </div>

  </x-cabinet.card>

  {{-- Social card --}}
  <x-cabinet.card gap="gap-[18px]" :title="__('business-profile-contact.social.title')">
    <div class="cab-field-row">
      <x-cabinet.field :label="__('business-profile-contact.social.instagram_label')" for="bpco-social-instagram">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-social-instagram" :value="__('business-profile-contact.social.instagram_value')" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('business-profile-contact.social.linkedin_label')" for="bpco-social-linkedin">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-social-linkedin" :value="__('business-profile-contact.social.linkedin_value')" />
      </x-cabinet.field>
      <x-cabinet.field :label="__('business-profile-contact.social.facebook_label')" for="bpco-social-facebook">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-social-facebook" :value="__('business-profile-contact.social.facebook_value')" />
      </x-cabinet.field>
    </div>
  </x-cabinet.card>

  {{-- Languages card --}}
  <x-cabinet.card gap="gap-[18px]" :title="__('business-profile-contact.languages.title')">
    <div class="flex w-full flex-wrap items-start content-start gap-2 overflow-hidden">
      <x-ui.chip size="sm" tick="svg" :on="true" :label="__('business-profile-contact.languages.az')" />
      <x-ui.chip size="sm" tick="svg" :on="true" :label="__('business-profile-contact.languages.ru')" />
      <x-ui.chip size="sm" tick="svg" :on="true" :label="__('business-profile-contact.languages.en')" />
      <x-ui.chip size="sm" tick="svg" :on="false" :label="__('business-profile-contact.languages.tr')" />
      <x-ui.chip size="sm" tick="svg" :on="false" :label="__('business-profile-contact.languages.other')" />
    </div>
  </x-cabinet.card>

  <x-cabinet.save-bar ns="business-profile-contact" :hover="false" />

</x-cabinet.shell>

</x-layout>
