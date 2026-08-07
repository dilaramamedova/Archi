{{-- Business cabinet — notifications tab --}}
<x-layout page="business-profile-notifications" :title="t('business-profile-notifications.title')" bodyClass="bg-gray-soft2">

{{-- Header, settings sidebar and the two-column body come from the cabinet shell. --}}
@php
  // notification_settings is array-cast on SellerProfile
  $settings = ($profile?->notification_settings ?? null) ?: [
      'orders' => true, 'reviews' => true, 'stock' => true, 'report' => false, 'channels' => ['email'],
  ];
@endphp

<x-cabinet.shell ns="business-profile-notifications" active="notifications" class="bpn-page text-ink">

  {{-- notification types — the card head is flush here (gap-0), see the page CSS --}}
  <x-cabinet.card class="bpn-card" tag="h3" gap="gap-0"
      :title="t('business-profile-notifications.types.heading')"
      :desc="t('business-profile-notifications.types.desc')">
    <div class="bpn-spacer"></div>

    @foreach (['order' => 'orders', 'reviews' => 'reviews', 'stock' => 'stock', 'report' => 'report'] as $type => $setting)
      <div class="bpn-row">
        <div class="txt">
          <p class="t">{{ t('business-profile-notifications.types.' . $type . '_title') }}</p>
          <p class="s">{{ t('business-profile-notifications.types.' . $type . '_desc') }}</p>
        </div>
        <x-ui.toggle size="md" :data-setting="$setting" :on="(bool) ($settings[$setting] ?? false)" :aria-label="t('business-profile-notifications.types.' . $type . '_title')" />
      </div>
    @endforeach
  </x-cabinet.card>

  {{-- notification channels --}}
  <x-cabinet.card tag="h3" gap="gap-1"
      :title="t('business-profile-notifications.channels.heading')"
      :desc="t('business-profile-notifications.channels.desc')">
    <div class="bpn-chips">
      @foreach (['email', 'sms', 'push', 'telegram'] as $channel)
        <x-ui.chip size="md" :data-channel="$channel" :on="in_array($channel, $settings['channels'] ?? [])" :label="t('business-profile-notifications.channels.' . $channel)" />
      @endforeach
    </div>
  </x-cabinet.card>

  <x-cabinet.save-bar ns="business-profile-notifications" />

</x-cabinet.shell>

</x-layout>
