{{-- Business cabinet — showrooms (Figma 1105:23687) --}}
<x-layout page="business-profile-showrooms" :title="__('business-profile-showrooms.title')" bodyClass="bg-gray-soft2">

<x-cabinet.shell ns="business-profile-showrooms" active="showrooms" class="bpsh-page text-ink">

  <x-cabinet.card layout="row" gap="gap-4"
      :title="__('business-profile-showrooms.list.heading')"
      :desc="__('business-profile-showrooms.list.desc')">
    <x-slot:action>
      <x-ui.button variant="primary" class="cab-btn-add">{{ __('business-profile-showrooms.list.add') }}</x-ui.button>
    </x-slot:action>

    @foreach ([
        ['key' => 'r1', 'state' => 'active', 'tone' => 'ok'],
        ['key' => 'r2', 'state' => 'active', 'tone' => 'ok'],
        ['key' => 'r3', 'state' => 'hidden', 'tone' => 'muted'],
    ] as $room)
      <x-cabinet.row class="gap-4 p-4">
        <div class="bpsh-sh-icon">
          <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M10 2.5c-2.9 0-5.2 2.3-5.2 5.2 0 3.7 5.2 9.8 5.2 9.8s5.2-6.1 5.2-9.8c0-2.9-2.3-5.2-5.2-5.2Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            <circle cx="10" cy="7.7" r="1.9" stroke="currentColor" stroke-width="1.4"/>
          </svg>
        </div>
        <div class="bpsh-sh-info">
          <b>{{ __("business-profile-showrooms.rooms.{$room['key']}_name") }}</b>
          <p>{{ __("business-profile-showrooms.rooms.{$room['key']}_meta") }}</p>
        </div>
        <x-ui.badge :tone="$room['tone']" size="sm" class="px-[9px]">{{ __("business-profile-showrooms.state.{$room['state']}") }}</x-ui.badge>
        <div class="bpsh-sh-actions">
          <x-ui.button variant="outline" class="cab-btn-edit items-start">{{ __('business-profile-showrooms.list.edit') }}</x-ui.button>
          <x-ui.button variant="ghost" class="cab-btn-del items-start">{{ __('business-profile-showrooms.list.delete') }}</x-ui.button>
        </div>
      </x-cabinet.row>
    @endforeach
  </x-cabinet.card>

  <x-cabinet.save-bar ns="business-profile-showrooms" />

</x-cabinet.shell>

</x-layout>
