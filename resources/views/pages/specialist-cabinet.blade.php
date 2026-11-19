{{-- Specialist cabinet — personal info edit (Figma 831:11186).
     The specialist cabinet reuses the business cabinet shell (ARCHITECTURE.md §4.9.1):
     same header, same 264px sidebar, same cards, same dark save bar — only the nav rows
     and the "view profile" target differ. --}}
@php
    $portfolioCount = $profile ? $profile->portfolioItems()->count() : 0;
    $servicesCount = $profile ? $profile->services()->count() : 0;

    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count', 'count_value' => $portfolioCount],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count', 'count_value' => $servicesCount],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];

    $initials = mb_strtoupper(mb_substr($user->first_name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1));
    $aboutMax = t('specialist-cabinet.main.about_max');
    $about = $profile->about ?? '';
    $skills = $profile->skills ?? [];
@endphp
<x-layout page="specialist-cabinet" :title="t('specialist-cabinet.title')" bodyClass="bg-gray-soft2">

<x-cabinet.shell ns="specialist-cabinet" active="main" :nav-items="$specNav"
                 :view-href="route('specialist.owner')" progress-fill="w-[168px]"
                 class="sc-page text-ink">

  {{-- profile photo --}}
  <x-cabinet.card gap="gap-[18px]"
      :title="t('specialist-cabinet.photo.heading')"
      :desc="t('specialist-cabinet.photo.desc')">

    <div class="sc-photo">
      <div class="sc-avatar" data-avatar data-initials="{{ $initials }}">
        @if ($profile?->avatar_path)
          <img class="size-full rounded-full object-cover" src="{{ storage_url($profile->avatar_path) }}" alt="{{ $user->name }}">
        @else
          <p>{{ $initials }}</p>
        @endif
      </div>
      <div class="sc-photo-info">
        <p class="hint">{{ t('specialist-cabinet.photo.hint') }}</p>
        <div class="sc-photo-btns">
          <x-ui.button variant="outline" type="button" class="cab-btn-edit" data-avatar-change>{{ t('specialist-cabinet.photo.change') }}</x-ui.button>
          <x-ui.button variant="ghost" type="button" class="cab-btn-del" data-avatar-delete>{{ t('specialist-cabinet.photo.delete') }}</x-ui.button>
          <input type="file" class="sr-only" data-avatar-picker accept="image/jpeg,image/png,image/webp">
        </div>
      </div>
    </div>

  </x-cabinet.card>

  {{-- personal details --}}
  <x-cabinet.card gap="gap-[18px]" :title="t('specialist-cabinet.main.heading')">
    <div class="cab-field-row">
      <x-cabinet.field :label="t('specialist-cabinet.main.first_name')" for="sc-first-name">
        <x-ui.input variant="b2b" id="sc-first-name" name="first_name" class="sc-input"
                    :value="$user->first_name" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('specialist-cabinet.main.last_name')" for="sc-last-name">
        <x-ui.input variant="b2b" id="sc-last-name" name="last_name" class="sc-input"
                    :value="$user->last_name" />
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="t('specialist-cabinet.main.craft')" for="sc-craft">
        <div class="sc-select">
          <x-ui.select variant="b2b" id="sc-craft" name="specialist_specialty_id" class="sc-input sc-select-control"
                       :options="$specialties"
                       :value="$profile->specialist_specialty_id ?? ''" />
          <img class="car" src="/assets/icon-chevron-down-small.svg" alt="">
        </div>
      </x-cabinet.field>
      <x-cabinet.field :label="t('specialist-cabinet.main.experience')" for="sc-experience">
        <x-ui.input variant="b2b" id="sc-experience" name="experience_years" class="sc-input" inputmode="numeric"
                    :value="$profile->experience_years ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('specialist-cabinet.main.city')" for="sc-city">
        <div class="sc-select">
          <x-ui.select variant="b2b" id="sc-city" name="city" class="sc-input sc-select-control"
                       :placeholder="t('register.form.select_placeholder')"
                       :options="\App\Enums\City::options()"
                       {{-- options are keyed by slug, so the selected value must be a slug too --}}
                       :value="\App\Enums\City::resolve($profile->city ?? '')?->value" />
          <img class="car" src="/assets/icon-chevron-down-small.svg" alt="">
        </div>
      </x-cabinet.field>
    </div>

    <div class="cab-field-row">
      <x-cabinet.field :label="t('specialist-cabinet.main.phone')" for="sc-phone">
        <x-ui.input variant="b2b" type="tel" id="sc-phone" name="phone" class="sc-input"
                    :value="$profile->phone ?? $user->phone" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('specialist-cabinet.main.whatsapp')" for="sc-whatsapp">
        <x-ui.input variant="b2b" type="tel" id="sc-whatsapp" name="whatsapp" class="sc-input"
                    :value="$profile->whatsapp ?? ''" />
      </x-cabinet.field>
    </div>

    <x-cabinet.field full :label="t('specialist-cabinet.main.about')" for="sc-about">
      <x-ui.textarea variant="b2b" id="sc-about" name="about" class="sc-textarea"
                     maxlength="{{ $aboutMax }}">{{ $about }}</x-ui.textarea>
      <p class="sc-counter" data-count-for="sc-about" data-max="{{ $aboutMax }}">{{ mb_strlen($about) }} / {{ $aboutMax }}</p>
    </x-cabinet.field>

  </x-cabinet.card>

  {{-- Briqadir / Makler blocks (docs/specialist-kinds.md). Both are rendered and the one
       matching the selected specialty is shown: specialist-cabinet.js swaps them when the
       specialty select changes kind, and only the visible block is posted. The server
       keeps only the keys of the saved specialty's kind either way. --}}
  @php
      $kindValue = $kind?->value;
      $fd = $kindValue === 'foreman' ? $details : \App\Support\SpecialistDetails::normalize(\App\Enums\SpecialistCategory::Foreman, []);
      $rd = $kindValue === 'realtor' ? $details : \App\Support\SpecialistDetails::normalize(\App\Enums\SpecialistCategory::Realtor, []);
  @endphp
  <div class="hidden" data-specialty-kinds='@json($specialtyKinds)'></div>

  <x-cabinet.card gap="gap-[18px]" class="sc-kind [&[hidden]]:hidden" data-kind-block="foreman" :hidden="$kindValue !== 'foreman'"
      :title="t('specialist-cabinet.crew.heading')"
      :desc="t('specialist-cabinet.crew.desc')">

    <div class="cab-field-row">
      <x-cabinet.field :label="t('specialist-cabinet.crew.size')" for="sc-crew-size">
        <x-ui.input variant="b2b" type="number" min="1" max="{{ \App\Support\SpecialistDetails::MAX_CREW_SIZE }}" id="sc-crew-size"
                    name="details[crew_size]" class="sc-input" inputmode="numeric"
                    :value="$fd['crew_size'] ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('specialist-cabinet.crew.full_projects_label')" tag="p">
        <label class="sc-opt sc-opt-wide">
          <input type="checkbox" name="details[takes_full_projects]" @checked($fd['takes_full_projects'])>
          <span>{{ t('specialist-cabinet.crew.full_projects') }}</span>
        </label>
      </x-cabinet.field>
    </div>

    <x-cabinet.field full :label="t('specialist-cabinet.crew.trades')" tag="p">
      <div class="sc-opts" data-multi="crew_trades">
        @foreach ($tradeOptions as $tradeId => $tradeName)
          <label class="sc-opt">
            <input type="checkbox" value="{{ $tradeId }}" @checked(in_array($tradeId, $fd['crew_trades'], true))>
            <span>{{ $tradeName }}</span>
          </label>
        @endforeach
      </div>
    </x-cabinet.field>

    <x-cabinet.field full :label="t('specialist-cabinet.crew.members')" tag="p">
      <p class="sc-hint">{{ t('specialist-cabinet.crew.members_hint', ['max' => \App\Support\SpecialistDetails::MAX_CREW_MEMBERS]) }}</p>
      <div class="sc-crew" data-crew data-max="{{ \App\Support\SpecialistDetails::MAX_CREW_MEMBERS }}">
        @foreach ($fd['crew_members'] as $member)
          <div class="sc-crew-row" data-crew-row>
            <x-ui.input variant="b2b" class="sc-input" data-f="name" maxlength="100"
                        :placeholder="t('specialist-cabinet.crew.member_name')" :aria-label="t('specialist-cabinet.crew.member_name')"
                        :value="$member['name']" />
            <div class="sc-select">
              <x-ui.select variant="b2b" class="sc-input sc-select-control" data-f="specialty_id"
                           :aria-label="t('specialist-cabinet.crew.member_specialty')"
                           :placeholder="t('specialist-cabinet.crew.member_specialty')"
                           :options="$tradeOptions" :value="$member['specialty_id']" />
              <img class="car" src="/assets/icon-chevron-down-small.svg" alt="">
            </div>
            <x-ui.input variant="b2b" type="number" min="0" max="80" class="sc-input" data-f="experience_years" inputmode="numeric"
                        :placeholder="t('specialist-cabinet.crew.member_years_ph')" :aria-label="t('specialist-cabinet.crew.member_years')"
                        :value="$member['experience_years']" />
            <button type="button" class="sc-crew-x" data-crew-remove aria-label="{{ t('specialist-cabinet.crew.member_remove') }}">&#10005;</button>
          </div>
        @endforeach
      </div>
      <button type="button" class="sc-skill-add" data-crew-add>{{ t('specialist-cabinet.crew.member_add') }}</button>
      <template data-crew-template>
        <div class="sc-crew-row" data-crew-row>
          <x-ui.input variant="b2b" class="sc-input" data-f="name" maxlength="100"
                      :placeholder="t('specialist-cabinet.crew.member_name')" :aria-label="t('specialist-cabinet.crew.member_name')" />
          <div class="sc-select">
            <x-ui.select variant="b2b" class="sc-input sc-select-control" data-f="specialty_id"
                         :aria-label="t('specialist-cabinet.crew.member_specialty')"
                         :placeholder="t('specialist-cabinet.crew.member_specialty')"
                         :options="$tradeOptions" />
            <img class="car" src="/assets/icon-chevron-down-small.svg" alt="">
          </div>
          <x-ui.input variant="b2b" type="number" min="0" max="80" class="sc-input" data-f="experience_years" inputmode="numeric"
                      :placeholder="t('specialist-cabinet.crew.member_years_ph')" :aria-label="t('specialist-cabinet.crew.member_years')" />
          <button type="button" class="sc-crew-x" data-crew-remove aria-label="{{ t('specialist-cabinet.crew.member_remove') }}">&#10005;</button>
        </div>
      </template>
    </x-cabinet.field>

  </x-cabinet.card>

  <x-cabinet.card gap="gap-[18px]" class="sc-kind [&[hidden]]:hidden" data-kind-block="realtor" :hidden="$kindValue !== 'realtor'"
      :title="t('specialist-cabinet.realtor.heading')"
      :desc="t('specialist-cabinet.realtor.desc')">

    <div class="cab-field-row">
      <x-cabinet.field :label="t('specialist-cabinet.realtor.agency')" for="sc-agency">
        <x-ui.input variant="b2b" id="sc-agency" name="details[agency_name]" class="sc-input" maxlength="150"
                    :placeholder="t('specialist-cabinet.realtor.agency_placeholder')"
                    :value="$rd['agency_name'] ?? ''" />
      </x-cabinet.field>
      <x-cabinet.field :label="t('specialist-cabinet.realtor.license')" for="sc-license">
        <x-ui.input variant="b2b" id="sc-license" name="details[license_no]" class="sc-input" maxlength="60"
                    :value="$rd['license_no'] ?? ''" />
      </x-cabinet.field>
    </div>

    <x-cabinet.field full :label="t('specialist-cabinet.realtor.deal_types').' *'" tag="p">
      <div class="sc-opts" data-multi="deal_types">
        @foreach ($dealTypeOptions as $value => $label)
          <label class="sc-opt">
            <input type="checkbox" value="{{ $value }}" @checked(in_array($value, $rd['deal_types'], true))>
            <span>{{ $label }}</span>
          </label>
        @endforeach
      </div>
    </x-cabinet.field>

    <x-cabinet.field full :label="t('specialist-cabinet.realtor.property_types')" tag="p">
      <div class="sc-opts" data-multi="property_types">
        @foreach ($propertyTypeOptions as $value => $label)
          <label class="sc-opt">
            <input type="checkbox" value="{{ $value }}" @checked(in_array($value, $rd['property_types'], true))>
            <span>{{ $label }}</span>
          </label>
        @endforeach
      </div>
    </x-cabinet.field>

    <x-cabinet.field full :label="t('specialist-cabinet.realtor.service_areas')" for="sc-areas">
      <x-ui.input variant="b2b" id="sc-areas" name="details[service_areas]" class="sc-input" maxlength="300"
                  :placeholder="t('specialist-cabinet.realtor.service_areas_placeholder')"
                  :value="$rd['service_areas'] ?? ''" />
    </x-cabinet.field>

  </x-cabinet.card>

  {{-- skills --}}
  <x-cabinet.card gap="gap-[18px]"
      :title="t('specialist-cabinet.skills.heading')"
      :desc="t('specialist-cabinet.skills.desc')">

    <div class="sc-skills">
      @foreach ($skills as $skill)
        <span class="sc-skill">
          <span class="lbl">{{ $skill }}</span>
          <button class="x" type="button" data-skill-remove
                  aria-label="{{ t('specialist-cabinet.skills.remove') }}">&#10005;</button>
        </span>
      @endforeach

      <input class="sc-skill-input" type="text" data-skill-input hidden
             maxlength="32" aria-label="{{ t('specialist-cabinet.skills.add') }}"
             placeholder="{{ t('specialist-cabinet.skills.placeholder') }}">
      <button class="sc-skill-add" type="button" data-skill-add>{{ t('specialist-cabinet.skills.add') }}</button>

      <template data-skill-template>
        <span class="sc-skill">
          <span class="lbl"></span>
          <button class="x" type="button" data-skill-remove
                  aria-label="{{ t('specialist-cabinet.skills.remove') }}">&#10005;</button>
        </span>
      </template>
    </div>

  </x-cabinet.card>

  <x-cabinet.save-bar ns="specialist-cabinet" />

</x-cabinet.shell>

</x-layout>
