{{-- Specialist cabinet — notifications tab (Figma 831:12996). --}}
@php
    // The seven specialist cabinet rows — identical on all seven pages (ARCHITECTURE.md 4.9.1).
    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count'],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count'],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'reviews',       'route' => 'specialist.cabinet.reviews',       'count' => 'reviews_count'],
        ['key' => 'notifications', 'route' => 'specialist.cabinet.notifications'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];

    // type key => switch state. Only "platform news" ships OFF in the frame.
    $types = [
        'request' => true,
        'message' => true,
        'review' => true,
        'booking' => true,
        'platform' => false,
        'weekly' => true,
    ];

    // channel key => selected. E-mail is the only unselected chip in the frame.
    $channels = [
        'push' => true,
        'sms' => true,
        'email' => false,
        'whatsapp' => true,
    ];

    $ns = 'specialist-cabinet-notifications';
@endphp
<x-layout page="specialist-cabinet-notifications" :title="__('specialist-cabinet-notifications.title')" bodyClass="bg-gray-soft2">

{{-- Header, settings sidebar and the two-column body come from the cabinet shell. --}}
<x-cabinet.shell ns="specialist-cabinet-notifications" active="notifications" :nav-items="$specNav"
                 :view-href="route('specialist.owner')" progress-fill="w-[168px]"
                 class="scn-page text-ink">

  {{-- notification types --}}
  <x-cabinet.card class="scn-card" tag="h3"
      :title="__('specialist-cabinet-notifications.types.heading')"
      :desc="__('specialist-cabinet-notifications.types.desc')">

    @foreach ($types as $type => $on)
      @php $label = __($ns . '.types.' . $type . '_title'); @endphp
      <div class="scn-row">
        <div class="txt">
          <p class="t">{{ $label }}</p>
          <p class="s">{{ __($ns . '.types.' . $type . '_desc') }}</p>
        </div>
        <x-ui.toggle size="md" :on="$on" :aria-label="$label" />
      </div>
    @endforeach

  </x-cabinet.card>

  {{-- notification channels — the frame has no description under this heading --}}
  <x-cabinet.card class="scn-card" tag="h3"
      :title="__('specialist-cabinet-notifications.channels.heading')">
    <div class="scn-chips">
      @foreach ($channels as $channel => $on)
        <x-ui.chip size="md" :on="$on" :label="__($ns . '.channels.' . $channel)" />
      @endforeach
    </div>
  </x-cabinet.card>

  <x-cabinet.save-bar ns="specialist-cabinet-notifications" />

</x-cabinet.shell>

</x-layout>
