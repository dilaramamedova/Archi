{{-- Business cabinet — showrooms (Figma 1105:23687) --}}
<x-layout page="business-profile-showrooms" :title="__('business-profile-showrooms.title')" bodyClass="bg-gray-soft2">

<div class="bpsh-page">
  <div class="bpsh-edit">

    {{-- header --}}
    <div class="bpsh-head">
      <div class="bpsh-head-left">
        <div class="bpsh-crumbs">
          <p class="c1">{{ __('business-profile-showrooms.crumbs.panel') }}</p>
          <p class="c1">{{ __('business-profile-showrooms.crumbs.sep') }}</p>
          <p class="c2">{{ __('business-profile-showrooms.crumbs.current') }}</p>
        </div>
        <p class="bpsh-title">{{ __('business-profile-showrooms.heading') }}</p>
      </div>
      <div class="bpsh-head-right">
        <div class="bpsh-badge-pub">
          <span class="dot"></span>
          <p>{{ __('business-profile-showrooms.status.published') }}</p>
        </div>
        <div class="bpsh-btn-view"><p>{{ __('business-profile-showrooms.status.view_profile') }}</p></div>
      </div>
    </div>

    <div class="bpsh-body">

      {{-- settings nav --}}
      <div class="bpsh-snav">
        <a class="bpsh-snav-item" data-on="false" href="{{ route('business.profile.company') }}">
          <p class="lbl">{{ __('business-profile-showrooms.nav.company') }}</p>
        </a>
        <a class="bpsh-snav-item" data-on="false" href="{{ route('business.profile.contact') }}">
          <p class="lbl">{{ __('business-profile-showrooms.nav.contact') }}</p>
        </a>
        <a class="bpsh-snav-item" data-on="true" href="{{ route('business.profile.showrooms') }}">
          <p class="lbl">{{ __('business-profile-showrooms.nav.showrooms') }}</p>
          <p class="cnt">{{ __('business-profile-showrooms.nav.showrooms_count') }}</p>
        </a>
        <a class="bpsh-snav-item" data-on="false" href="{{ route('business.profile.products') }}">
          <p class="lbl">{{ __('business-profile-showrooms.nav.products') }}</p>
          <p class="cnt">{{ __('business-profile-showrooms.nav.products_count') }}</p>
        </a>
        <a class="bpsh-snav-item" data-on="false" href="{{ route('business.profile.notifications') }}">
          <p class="lbl">{{ __('business-profile-showrooms.nav.notifications') }}</p>
        </a>
        <a class="bpsh-snav-item" data-on="false" href="{{ route('business.profile.security') }}">
          <p class="lbl">{{ __('business-profile-showrooms.nav.security') }}</p>
        </a>

        <div class="bpsh-snav-progress">
          <div class="top">
            <p class="l">{{ __('business-profile-showrooms.progress.label') }}</p>
            <p class="r">{{ __('business-profile-showrooms.progress.value') }}</p>
          </div>
          <div class="bpsh-snav-bar"><div class="fill w-[184px]"></div></div>
          <p class="note">{{ __('business-profile-showrooms.progress.note') }}</p>
        </div>
      </div>

      {{-- main column --}}
      <div class="bpsh-main">

        <div class="bpsh-card">
          <div class="bpsh-card-head">
            <div class="bpsh-card-head-txt">
              <h2>{{ __('business-profile-showrooms.list.heading') }}</h2>
              <p>{{ __('business-profile-showrooms.list.desc') }}</p>
            </div>
            <div class="bpsh-btn-add"><p>{{ __('business-profile-showrooms.list.add') }}</p></div>
          </div>

          @foreach ([
              ['key' => 'r1', 'state' => 'active'],
              ['key' => 'r2', 'state' => 'active'],
              ['key' => 'r3', 'state' => 'hidden'],
          ] as $room)
            <div class="bpsh-showroom">
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
              <div class="bpsh-sh-badge" data-state="{{ $room['state'] }}">
                <p>{{ __("business-profile-showrooms.state.{$room['state']}") }}</p>
              </div>
              <div class="bpsh-sh-actions">
                <div class="bpsh-btn-edit"><p>{{ __('business-profile-showrooms.list.edit') }}</p></div>
                <div class="bpsh-btn-del"><p>{{ __('business-profile-showrooms.list.delete') }}</p></div>
              </div>
            </div>
          @endforeach
        </div>

        {{-- save bar --}}
        <div class="bpsh-save-bar" data-saved-message="{{ __('business-profile-showrooms.save.saved_alert') }}">
          <div class="bpsh-save-left">
            <span class="dot"></span>
            <p>{{ __('business-profile-showrooms.save.unsaved') }}</p>
          </div>
          <div class="bpsh-save-right">
            <div class="bpsh-btn-cancel"><p>{{ __('business-profile-showrooms.save.cancel') }}</p></div>
            <div class="bpsh-btn-save"><p>{{ __('business-profile-showrooms.save.save') }}</p></div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

</x-layout>
