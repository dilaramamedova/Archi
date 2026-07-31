{{-- Specialist cabinet — work schedule tab (Figma 831:12428). --}}
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

    // day key => [open, start, end]. Sunday ships closed, so its row shows the "day off"
    // note instead of the two time fields — same markup, driven by data-on.
    $days = [
        'monday'    => [true,  '09:00', '19:00'],
        'tuesday'   => [true,  '09:00', '19:00'],
        'wednesday' => [true,  '09:00', '19:00'],
        'thursday'  => [true,  '09:00', '19:00'],
        'friday'    => [true,  '09:00', '17:00'],
        'saturday'  => [true,  '10:00', '15:00'],
        'sunday'    => [false, '10:00', '15:00'],
    ];

    $ns = 'specialist-cabinet-schedule';
@endphp
<x-layout page="specialist-cabinet-schedule" :title="__('specialist-cabinet-schedule.title')" bodyClass="bg-gray-soft2">

{{-- Header, settings sidebar and the two-column body come from the cabinet shell. --}}
<x-cabinet.shell ns="specialist-cabinet-schedule" active="schedule" :nav-items="$specNav"
                 :view-href="route('specialist.owner')" progress-fill="w-[168px]"
                 class="sch-page text-ink">

  {{-- weekly schedule --}}
  <x-cabinet.card gap="gap-[14px]"
      :title="__('specialist-cabinet-schedule.week.heading')"
      :desc="__('specialist-cabinet-schedule.week.desc')">

    @foreach ($days as $day => [$open, $start, $end])
      @php $label = __($ns . '.days.' . $day); @endphp
      <x-cabinet.row class="sch-day" data-on="{{ $open ? 'true' : 'false' }}">
        <x-ui.toggle size="sm" :on="$open" :aria-label="$label" />
        <p class="lbl">{{ $label }}</p>
        <div class="times">
          <input type="text" class="sch-time" value="{{ $start }}" inputmode="numeric"
                 aria-label="{{ $label }} — {{ __($ns . '.week.start') }}" @disabled(! $open)>
          <input type="text" class="sch-time" value="{{ $end }}" inputmode="numeric"
                 aria-label="{{ $label }} — {{ __($ns . '.week.end') }}" @disabled(! $open)>
        </div>
        <p class="off">{{ __('specialist-cabinet-schedule.week.day_off') }}</p>
      </x-cabinet.row>
    @endforeach

  </x-cabinet.card>

  {{-- free slots left this week --}}
  <x-cabinet.card gap="gap-[14px]"
      :title="__('specialist-cabinet-schedule.slots.heading')"
      :desc="__('specialist-cabinet-schedule.slots.desc')">
    <div class="sch-slots">
      <button type="button" class="sch-step" data-step="-1"
              aria-label="{{ __('specialist-cabinet-schedule.slots.decrease') }}">&minus;</button>
      <p class="sch-slots-val" data-slots aria-live="polite">3</p>
      <button type="button" class="sch-step" data-step="1"
              aria-label="{{ __('specialist-cabinet-schedule.slots.increase') }}">+</button>
      <p class="sch-slots-unit">{{ __('specialist-cabinet-schedule.slots.unit') }}</p>
    </div>
  </x-cabinet.card>

  {{-- vacation mode --}}
  <x-cabinet.card gap="gap-[14px]" :title="__('specialist-cabinet-schedule.vacation.heading')">
    <div class="sch-vacation">
      <p class="txt">{{ __('specialist-cabinet-schedule.vacation.desc') }}</p>
      <x-ui.toggle size="md" :on="false"
                   :aria-label="__('specialist-cabinet-schedule.vacation.heading')" />
    </div>
  </x-cabinet.card>

  <x-cabinet.save-bar ns="specialist-cabinet-schedule" />

</x-cabinet.shell>

</x-layout>
