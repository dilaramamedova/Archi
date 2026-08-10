{{-- Business cabinet — showrooms (Figma 1105:23687) --}}
<x-layout page="business-profile-showrooms" :title="t('business-profile-showrooms.title')" bodyClass="bg-gray-soft2">

@php
  // Translate, falling back to a literal while a key is still missing from the
  // translations table (t() echoes the key back otherwise).
  $tOr = function (string $key, string $fallback, array $replace = []) {
      $v = t($key, $replace);
      return is_string($v) && $v !== $key ? $v : strtr($fallback, array_combine(
          array_map(fn ($k) => ':'.$k, array_keys($replace)),
          array_map('strval', array_values($replace)),
      ));
  };

  $showroomCount = $showrooms->count();
  $businessNav = [
    ['key' => 'orders', 'route' => 'business.orders'],
    ['key' => 'company', 'route' => 'business.profile.company'],
    ['key' => 'contact', 'route' => 'business.profile.contact'],
    ['key' => 'showrooms', 'route' => 'business.profile.showrooms', 'count' => 'showrooms_count', 'count_value' => $showroomCount],
    ['key' => 'products', 'route' => 'business.profile.products', 'count' => 'products_count', 'count_value' => auth()->user()->products()->count()],
    ['key' => 'inventory', 'route' => 'business.inventory'],
    ['key' => 'security', 'route' => 'business.profile.security'],
  ];
@endphp
<x-cabinet.shell ns="business-profile-showrooms" active="showrooms" class="bpsh-page text-ink" :nav-items="$businessNav">

  <x-cabinet.card layout="row" gap="gap-4"
      :title="t('business-profile-showrooms.nav.showrooms') . ' (' . number_format($showroomCount) . ')'"
      :desc="t('business-profile-showrooms.list.desc')">
    <x-slot:action>
      <x-ui.button variant="primary" class="cab-btn-add">{{ t('business-profile-showrooms.list.add') }}</x-ui.button>
    </x-slot:action>

    @forelse ($showrooms as $showroom)
      @php($meta = implode(' · ', array_filter([$showroom->address, $showroom->city, $showroom->phone, $showroom->work_hours])))
      <x-cabinet.row class="gap-4 p-4"
          :data-showroom-id="$showroom->id"
          :data-name="$showroom->name"
          :data-address="$showroom->address"
          {{-- the modal's city <select> is keyed by slug, so hand the JS a slug --}}
          :data-city="\App\Enums\City::resolve($showroom->city)?->value"
          :data-phone="$showroom->phone"
          :data-hours="$showroom->work_hours"
          :data-status="$showroom->status"
          :data-delete-confirm="$tOr('business-profile-showrooms.list.delete_confirm', '«:name» şourumu silinsin?', ['name' => $showroom->name])">
        <div class="bpsh-sh-icon">
          <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M10 2.5c-2.9 0-5.2 2.3-5.2 5.2 0 3.7 5.2 9.8 5.2 9.8s5.2-6.1 5.2-9.8c0-2.9-2.3-5.2-5.2-5.2Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            <circle cx="10" cy="7.7" r="1.9" stroke="currentColor" stroke-width="1.4"/>
          </svg>
        </div>
        <div class="bpsh-sh-info">
          <b>{{ $showroom->name }}</b>
          <p>{{ $meta }}</p>
        </div>
        <x-ui.badge :tone="$showroom->status === 'active' ? 'ok' : 'muted'" size="sm" class="px-[9px]">{{ t("business-profile-showrooms.state.{$showroom->status}") }}</x-ui.badge>
        <div class="bpsh-sh-actions">
          <x-ui.button variant="outline" data-edit class="cab-btn-edit items-start">{{ t('business-profile-showrooms.list.edit') }}</x-ui.button>
          <x-ui.button variant="ghost" data-delete class="cab-btn-del items-start">{{ t('business-profile-showrooms.list.delete') }}</x-ui.button>
        </div>
      </x-cabinet.row>
    @empty
      <div class="w-full py-8 text-center text-sm text-black/50">{{ t('business-profile-showrooms.empty') }}</div>
    @endforelse
  </x-cabinet.card>

  {{-- Showroom create/edit modal --}}
  <div id="showroomModal" class="fixed inset-0 z-[9999] hidden items-center justify-center overflow-y-auto bg-black/40 p-4" hidden>
    <div class="my-auto max-h-[calc(100vh-2rem)] w-full max-w-md overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
      <h3 class="mb-4 text-lg font-semibold" data-modal-title
          data-add-title="{{ t('business-profile-showrooms.modal.add_title') }}"
          data-edit-title="{{ t('business-profile-showrooms.modal.edit_title') }}">{{ t('business-profile-showrooms.modal.add_title') }}</h3>
      <div class="flex flex-col gap-3">
        <label class="flex flex-col gap-1 text-sm font-medium">
          {{ t('business-profile-showrooms.modal.name') }}
          <x-ui.input variant="b2b" name="name" id="showroomName" />
        </label>
        <label class="flex flex-col gap-1 text-sm font-medium">
          {{ t('business-profile-showrooms.modal.address') }}
          <x-ui.input variant="b2b" name="address" id="showroomAddress" />
        </label>
        <label class="flex flex-col gap-1 text-sm font-medium">
          {{ t('business-profile-showrooms.modal.city') }}
          <x-ui.select variant="b2b" name="city" id="showroomCity"
                       :placeholder="t('register.form.select_placeholder')"
                       :options="\App\Enums\City::options()" />
        </label>
        <label class="flex flex-col gap-1 text-sm font-medium">
          {{ t('business-profile-showrooms.modal.phone') }}
          <x-ui.input variant="b2b" name="phone" id="showroomPhone" />
        </label>
        <label class="flex flex-col gap-1 text-sm font-medium">
          {{ t('business-profile-showrooms.modal.work_hours') }}
          <x-ui.input variant="b2b" name="work_hours" id="showroomHours" />
        </label>
        <label class="flex flex-col gap-1 text-sm font-medium">
          {{ t('business-profile-showrooms.modal.status') }}
          <select name="status" id="showroomStatus" class="ui-control-b2b h-11 px-3.5">
            <option value="active">{{ t('business-profile-showrooms.state.active') }}</option>
            <option value="hidden">{{ t('business-profile-showrooms.state.hidden') }}</option>
          </select>
        </label>
      </div>
      <div class="mt-5 flex justify-end gap-2">
        <x-ui.button variant="ghost" data-modal-cancel class="cab-btn-del items-start">{{ t('business-profile-showrooms.save.cancel') }}</x-ui.button>
        <x-ui.button variant="primary" data-modal-save class="cab-btn-add">{{ t('business-profile-showrooms.save.save') }}</x-ui.button>
      </div>
    </div>
  </div>

  <x-cabinet.save-bar ns="business-profile-showrooms" />

</x-cabinet.shell>

</x-layout>
