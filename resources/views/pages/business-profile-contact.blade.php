<x-layout page="business-profile-contact" :title="t('business-profile-contact.title')" bodyClass="bg-gray-soft2">

{{-- Business cabinet — contact details (Figma 1105:23103) --}}
<x-cabinet.shell ns="business-profile-contact" active="contact" :hover="false" class="bpco-page">

  {{-- Contact card --}}
  <x-cabinet.card gap="gap-[18px]"
      :title="t('business-profile-contact.contact.title')"
      :desc="t('business-profile-contact.contact.desc')">

    <div class="cab-field-row">
      <x-cabinet.field :label="t('business-profile-contact.contact.person_label')" for="bpco-contact-person">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-person" name="contact_person" :value="$profile->contact_person ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('business-profile-contact.contact.role_label')" for="bpco-contact-role">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-role" name="contact_role" :value="$profile->contact_role ?? ''" />
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="t('business-profile-contact.contact.phone_label')" for="bpco-contact-phone">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-phone" name="contact_phone" :value="$profile->contact_phone ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('business-profile-contact.contact.whatsapp_label')" for="bpco-contact-whatsapp">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-whatsapp" name="whatsapp" :value="$profile->whatsapp ?? ''" />
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="t('business-profile-contact.contact.telegram_label')" for="bpco-contact-telegram">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-telegram" name="telegram" :value="$profile->telegram ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('business-profile-contact.contact.email_label')" for="bpco-contact-email">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-email" name="contact_email" :value="$profile->contact_email ?? ''" />
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="t('business-profile-contact.contact.website_label')" for="bpco-contact-website">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-website" name="website" :value="$profile->website ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('business-profile-contact.contact.hours_label')" for="bpco-contact-hours">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-contact-hours" name="work_hours" :value="$profile->work_hours ?? ''" />
      </x-cabinet.field>
    </div>

  </x-cabinet.card>

  {{-- Social card --}}
  <x-cabinet.card gap="gap-[18px]" :title="t('business-profile-contact.social.title')">
    <div class="cab-field-row">
      <x-cabinet.field :label="t('business-profile-contact.social.instagram_label')" for="bpco-social-instagram">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-social-instagram" name="instagram" :value="$profile->instagram ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('business-profile-contact.social.linkedin_label')" for="bpco-social-linkedin">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-social-linkedin" name="linkedin" :value="$profile->linkedin ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('business-profile-contact.social.facebook_label')" for="bpco-social-facebook">
        <x-ui.input variant="b2b" class="h-11 px-3.5 py-0 font-semibold" id="bpco-social-facebook" name="facebook" :value="$profile->facebook ?? ''" />
      </x-cabinet.field>
    </div>
  </x-cabinet.card>

  {{-- Languages card --}}
  <x-cabinet.card gap="gap-[18px]" :title="t('business-profile-contact.languages.title')">
    @php($langs = $profile->languages ?? ['az', 'ru', 'en'])
    <div class="flex w-full flex-wrap items-start content-start gap-2 overflow-hidden">
      @foreach (['az', 'ru', 'en', 'tr', 'other'] as $lang)
        <x-ui.chip size="sm" tick="svg" :data-lang="$lang" :on="in_array($lang, $langs)" :label="t('business-profile-contact.languages.' . $lang)" />
      @endforeach
    </div>
  </x-cabinet.card>

  <x-cabinet.save-bar ns="business-profile-contact" :hover="false" />

</x-cabinet.shell>

</x-layout>
