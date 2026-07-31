{{-- Business cabinet — notifications tab --}}
<x-layout page="business-profile-notifications" :title="__('business-profile-notifications.title')" bodyClass="bg-gray-soft2">

{{-- Header, settings sidebar and the two-column body come from the cabinet shell. --}}
<x-cabinet.shell ns="business-profile-notifications" active="notifications" class="bpn-page text-ink">

  {{-- notification types — the card head is flush here (gap-0), see the page CSS --}}
  <x-cabinet.card class="bpn-card" tag="h3" gap="gap-0"
      :title="__('business-profile-notifications.types.heading')"
      :desc="__('business-profile-notifications.types.desc')">
    <div class="bpn-spacer"></div>

    @foreach (['order', 'reviews', 'stock', 'report'] as $type)
      <div class="bpn-row">
        <div class="txt">
          <p class="t">{{ __('business-profile-notifications.types.' . $type . '_title') }}</p>
          <p class="s">{{ __('business-profile-notifications.types.' . $type . '_desc') }}</p>
        </div>
        <x-ui.toggle size="md" :on="true" :aria-label="__('business-profile-notifications.types.' . $type . '_title')" />
      </div>
    @endforeach
  </x-cabinet.card>

  {{-- notification channels --}}
  <x-cabinet.card tag="h3" gap="gap-1"
      :title="__('business-profile-notifications.channels.heading')"
      :desc="__('business-profile-notifications.channels.desc')">
    <div class="bpn-chips">
      @foreach ([['email', true], ['sms', false], ['push', true], ['telegram', false]] as [$channel, $on])
        <x-ui.chip size="md" :on="$on" :label="__('business-profile-notifications.channels.' . $channel)" />
      @endforeach
    </div>
  </x-cabinet.card>

  <x-cabinet.save-bar ns="business-profile-notifications" />

</x-cabinet.shell>

</x-layout>
