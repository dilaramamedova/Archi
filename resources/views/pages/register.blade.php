@php
    // ?role= is resolved server-side so the selected card and the role-specific
    // fields are already correct on first paint (no post-hydration layout jump)
    // and the seller/master fields stay reachable without JS.
    //
    // Five cards, three account roles: Briqadir and Makler are specialists
    // (role=master) whose specialty is resolved from the card — register.js posts
    // role=master + kind=foreman|realtor. See docs/specialist-kinds.md.
    $roleCards = [
        ['role' => 'buyer', 'icon' => 'icon-cart.svg'],
        ['role' => 'seller', 'icon' => 'icon-tower-crane.svg'],
        ['role' => 'master', 'icon' => 'icon-hammer-wrench.svg'],
        ['role' => 'foreman', 'icon' => 'icon-bricks.svg'],
        ['role' => 'realtor', 'icon' => 'icon-house.svg'],
    ];
    $specialistCards = ['master', 'foreman', 'realtor'];
    $r = request()->query('role');
    $role = is_string($r) && in_array($r, array_column($roleCards, 'role'), true) ? $r : 'buyer';
@endphp

<x-layout page="register" :title="t('register.title')">

<section class="bg-gray-soft2 py-14">
  <div class="mx-auto max-w-[760px] px-7">
    <div class="flex flex-col gap-7 border border-black/12 bg-white p-10 shadow-[-4px_4px_4px_rgba(0,0,0,0.05)] max-[640px]:p-6">
      <div class="flex flex-col gap-2">
        <x-ui.eyebrow :label="t('register.head.tag')" />
        <h1 class="text-[32px] font-semibold tracking-[-0.4px] text-black/90 max-[640px]:text-[26px]">{{ t('register.head.title') }}</h1>
        <p class="text-base leading-[1.5] text-black/55">{{ t('register.head.subtitle') }}</p>
      </div>

      {{-- Role selection — the selected state is a data-sel attribute + Tailwind group-data variants. --}}
      <div>
        <div class="mb-3 text-base font-semibold text-black">{{ t('register.roles.label') }}</div>
        {{-- Six-column track: three cards on the first row (span 2), two wider ones on
             the second (span 3). On a phone each card becomes a compact row — icon left,
             text right — so five cards stay one short column with no sideways scroll. --}}
        <div class="grid grid-cols-6 gap-3 max-[640px]:grid-cols-1 max-[640px]:gap-2.5" id="roles">
          @foreach ($roleCards as $card)
            <button type="button" class="group relative flex cursor-pointer flex-col gap-2.5 border border-black/20 p-5 text-left transition-[border-color,background] duration-200 hover:border-black data-[sel=true]:border-yellow data-[sel=true]:bg-[#fffdf0] data-[sel=true]:shadow-[inset_0_0_0_1px_var(--color-yellow)] {{ $loop->index < 3 ? 'col-span-2' : 'col-span-3' }} max-[640px]:col-span-1 max-[640px]:flex-row max-[640px]:items-center max-[640px]:gap-3.5 max-[640px]:py-3.5 max-[640px]:pr-11 max-[640px]:pl-4" data-role="{{ $card['role'] }}" data-sel="{{ $role === $card['role'] ? 'true' : 'false' }}">
              <span class="absolute top-3.5 right-3.5 flex size-5 items-center justify-center rounded-full border border-black/30 opacity-0 transition duration-200 group-data-[sel=true]:border-yellow group-data-[sel=true]:bg-yellow group-data-[sel=true]:opacity-100 max-[640px]:top-1/2 max-[640px]:-translate-y-1/2"><img class="size-3 brightness-0" src="/assets/icon-check-green.svg" alt=""></span>
              <span class="flex size-[46px] shrink-0 items-center justify-center rounded-pill bg-gray-soft group-data-[sel=true]:bg-yellow max-[640px]:size-10"><img class="size-6" src="/assets/{{ $card['icon'] }}" alt=""></span>
              <span class="flex min-w-0 flex-col gap-2.5 max-[640px]:gap-0.5">
                <h4 class="text-base font-semibold text-ink">{{ t('register.roles.'.$card['role'].'.title') }}</h4>
                <p class="text-[13px] leading-[1.35] text-black/55">{{ t('register.roles.'.$card['role'].'.desc') }}</p>
              </span>
            </button>
          @endforeach
        </div>
      </div>

      <form class="flex flex-col gap-[18px]" id="regForm">
        {{-- The CARD that is selected (buyer … realtor); register.js turns foreman /
             realtor into role=master + kind on submit. --}}
        <input type="hidden" name="role" value="{{ $role }}">
        <div class="flex gap-3.5 max-[640px]:flex-col">
          <x-ui.field class="flex-1" :label="t('register.form.first_name_label')">
            <x-ui.input name="first_name" type="text" :placeholder="t('register.form.first_name_placeholder')" required />
          </x-ui.field>
          <x-ui.field class="flex-1" :label="t('register.form.last_name_label')">
            <x-ui.input name="last_name" type="text" :placeholder="t('register.form.last_name_placeholder')" required />
          </x-ui.field>
        </div>

        {{-- Seller only · [&[hidden]]:hidden is required because `flex` beats the UA [hidden] rule. --}}
        <x-ui.field class="[&[hidden]]:hidden" data-for="seller" :hidden="$role !== 'seller'" :label="t('register.form.company_label')">
          <x-ui.input name="company_name" type="text" :placeholder="t('register.form.company_placeholder')" />
        </x-ui.field>

        {{-- Specialists (master / foreman / realtor). data-for is a comma list —
             register.js splits it. Only «Usta / Mütəxəssis» picks a specialty: the
             Briqadir and Makler cards resolve theirs on the server. --}}
        <div class="flex gap-3.5 max-[640px]:flex-col [&[hidden]]:hidden" data-for="{{ implode(',', $specialistCards) }}" @if (! in_array($role, $specialistCards, true)) hidden @endif>
          <x-ui.field class="flex-1 [&[hidden]]:hidden" data-for="master" :hidden="$role !== 'master'" :label="t('register.form.specialization_label')">
            {{-- data-req: register.js keeps `required` on only while the control is shown,
                 so a hidden select never blocks the browser's own validation. --}}
            <x-ui.select name="specialist_specialty_id" data-req :required="$role === 'master'" :placeholder="t('register.form.select_placeholder')" :options="$specialties" />
          </x-ui.field>
          <x-ui.field class="flex-1" :label="t('register.form.city_label')">
            <x-ui.select name="city" data-req :required="in_array($role, $specialistCards, true)" :placeholder="t('register.form.select_placeholder')" :options="\App\Enums\City::options()" />
          </x-ui.field>
        </div>

        <div class="flex gap-3.5 max-[640px]:flex-col">
          <x-ui.field class="flex-1" :label="t('register.form.email_label')">
            <x-ui.input name="email" type="email" :placeholder="t('register.form.email_placeholder')" required />
          </x-ui.field>
          <x-ui.field class="flex-1" :label="t('register.form.phone_label')">
            <x-ui.input name="phone" type="tel" :placeholder="t('register.form.phone_placeholder')" required />
          </x-ui.field>
        </div>
        <div class="flex gap-3.5 max-[640px]:flex-col">
          <x-ui.field class="flex-1" :label="t('register.form.password_label')">
            <x-ui.input name="password" type="password" :placeholder="t('register.form.password_placeholder')" required />
          </x-ui.field>
          <x-ui.field class="flex-1" :label="t('register.form.password_confirm_label')">
            <x-ui.input name="password_confirmation" type="password" :placeholder="t('register.form.password_confirm_placeholder')" required />
          </x-ui.field>
        </div>

        <x-ui.checkbox name="terms" required class="gap-2.5"><span><a class="border-b border-yellow-line" href="/terms">{{ t('register.form.terms_link') }}</a> {{ t('register.form.terms_and') }} <a class="border-b border-yellow-line" href="/privacy">{{ t('register.form.privacy_link') }}</a> {{ t('register.form.terms_agree') }}</span></x-ui.checkbox>

        <x-ui.button variant="primary" type="submit" class="h-[54px] rounded-none text-lg font-semibold hover:brightness-[.93]">{{ t('register.form.submit') }}</x-ui.button>

        <div class="rounded bg-[#f5f7f9] px-4 py-3 text-[13px] leading-[1.5] text-black/50 [&[hidden]]:hidden" data-for="seller,{{ implode(',', $specialistCards) }}" @if ($role === 'buyer') hidden @endif>
          {{ t('register.form.info_note') }}
        </div>

        {{-- The terms/privacy links above stay inline: they sit mid-sentence inside the
             consent label, where a 44px box would break the running text. WCAG 2.5.8
             exempts targets inline in a sentence for exactly this reason. --}}
        <p class="text-center text-[15px] text-black/60">{{ t('register.form.have_account') }} <a class="font-semibold text-ink max-[900px]:inline-flex max-[900px]:min-h-11 max-[900px]:items-center" href="{{ route('login') }}"><span class="border-b-2 border-yellow-line">{{ t('register.form.sign_in') }}</span></a></p>
      </form>
    </div>
  </div>
</section>

</x-layout>
