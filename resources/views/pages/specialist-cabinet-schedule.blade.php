{{-- Specialist cabinet — work schedule tab (Figma 831:12428). --}}
@php
    // The seven specialist cabinet rows — identical on all seven pages (ARCHITECTURE.md 4.9.1).
    $portfolioCount = $profile ? $profile->portfolioItems()->count() : 0;
    $servicesCount = $profile ? $profile->services()->count() : 0;

    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count', 'count_value' => $portfolioCount],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count', 'count_value' => $servicesCount],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];

    // Build schedule from DB data ($schedules passed from route). day_of_week: 1=Monday..7=Sunday.
    $dayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $schedulesKeyed = $schedules->keyBy('day_of_week');
    $days = [];
    foreach ($dayNames as $i => $dayName) {
        $dow = $i + 1;
        $sched = $schedulesKeyed->get($dow);
        if ($sched) {
            $days[$dayName] = [
                ! $sched->is_day_off,
                $sched->start_time ? \Carbon\Carbon::parse($sched->start_time)->format('H:i') : '09:00',
                $sched->end_time ? \Carbon\Carbon::parse($sched->end_time)->format('H:i') : '18:00',
            ];
        } else {
            // Default: weekdays open 09-18, weekends closed
            $days[$dayName] = [$dow <= 5, '09:00', '18:00'];
        }
    }
    $availableSlots = $profile->available_slots ?? 0;
    $isOnVacation = $profile->is_on_vacation ?? false;

    $ns = 'specialist-cabinet-schedule';
@endphp
<x-layout page="specialist-cabinet-schedule" :title="t('specialist-cabinet-schedule.title')" bodyClass="bg-gray-soft2">

{{-- Header, settings sidebar and the two-column body come from the cabinet shell. --}}
<x-cabinet.shell ns="specialist-cabinet-schedule" active="schedule" :nav-items="$specNav"
                 :view-href="route('specialist.owner')" progress-fill="w-[168px]"
                 class="sch-page text-ink">

  {{-- weekly schedule --}}
  <x-cabinet.card gap="gap-[14px]"
      :title="t('specialist-cabinet-schedule.week.heading')"
      :desc="t('specialist-cabinet-schedule.week.desc')">

    <x-ui.alert tone="error" id="scheduleErr" />
    <x-ui.alert tone="ok" id="scheduleOk" />

    @foreach ($days as $day => [$open, $start, $end])
      @php $label = t($ns . '.days.' . $day); @endphp
      <x-cabinet.row class="sch-day" data-day="{{ $loop->iteration }}" data-on="{{ $open ? 'true' : 'false' }}">
        <x-ui.toggle size="sm" :on="$open" :aria-label="$label" />
        <p class="lbl">{{ $label }}</p>
        <div class="times">
          <input type="time" class="sch-time" value="{{ $start }}"
                 aria-label="{{ $label }} — {{ t($ns . '.week.start') }}" @disabled(! $open)>
          <input type="time" class="sch-time" value="{{ $end }}"
                 aria-label="{{ $label }} — {{ t($ns . '.week.end') }}" @disabled(! $open)>
        </div>
        <p class="off">{{ t('specialist-cabinet-schedule.week.day_off') }}</p>
      </x-cabinet.row>
    @endforeach

  </x-cabinet.card>

  {{-- free slots left this week --}}
  <x-cabinet.card gap="gap-[14px]"
      :title="t('specialist-cabinet-schedule.slots.heading')"
      :desc="t('specialist-cabinet-schedule.slots.desc')">
    <div class="sch-slots">
      <button type="button" class="sch-step" data-step="-1"
              aria-label="{{ t('specialist-cabinet-schedule.slots.decrease') }}">&minus;</button>
      <p class="sch-slots-val" data-slots aria-live="polite">{{ $availableSlots }}</p>
      <button type="button" class="sch-step" data-step="1"
              aria-label="{{ t('specialist-cabinet-schedule.slots.increase') }}">+</button>
      <p class="sch-slots-unit">{{ t('specialist-cabinet-schedule.slots.unit') }}</p>
    </div>
  </x-cabinet.card>

  {{-- vacation mode --}}
  <x-cabinet.card gap="gap-[14px]" :title="t('specialist-cabinet-schedule.vacation.heading')">
    <div class="sch-vacation">
      <p class="txt">{{ t('specialist-cabinet-schedule.vacation.desc') }}</p>
      <x-ui.toggle size="md" :on="$isOnVacation"
                   :aria-label="t('specialist-cabinet-schedule.vacation.heading')" />
    </div>
  </x-cabinet.card>

  <x-cabinet.save-bar ns="specialist-cabinet-schedule" />

</x-cabinet.shell>

</x-layout>
