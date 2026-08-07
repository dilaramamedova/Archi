@php
    // ?role= is resolved server-side so the selected card and the role-specific
    // fields are already correct on first paint (no post-hydration layout jump)
    // and the seller/master fields stay reachable without JS.
    $r = request()->query('role');
    $role = is_string($r) && in_array($r, ['buyer', 'seller', 'master'], true) ? $r : 'buyer';
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

      <x-ui.alert tone="ok" id="okMsg">{{ t('register.pending_message') }}</x-ui.alert>
      <x-ui.alert tone="error" id="regErr"></x-ui.alert>

      {{-- Role selection — the selected state is a data-sel attribute + Tailwind group-data variants. --}}
      <div>
        <div class="mb-3 text-base font-semibold text-black">{{ t('register.roles.label') }}</div>
        <div class="grid grid-cols-3 gap-3 max-[640px]:grid-cols-1" id="roles">
          <button type="button" class="group relative flex cursor-pointer flex-col gap-2.5 border border-black/20 p-5 text-left transition-[border-color,background] duration-200 hover:border-black data-[sel=true]:border-yellow data-[sel=true]:bg-[#fffdf0] data-[sel=true]:shadow-[inset_0_0_0_1px_var(--color-yellow)]" data-role="buyer" data-sel="{{ $role === 'buyer' ? 'true' : 'false' }}">
            <span class="absolute top-3.5 right-3.5 flex size-5 items-center justify-center rounded-full border border-black/30 opacity-0 transition duration-200 group-data-[sel=true]:border-yellow group-data-[sel=true]:bg-yellow group-data-[sel=true]:opacity-100"><img class="size-3 brightness-0" src="/assets/icon-check-green.svg" alt=""></span>
            <span class="flex size-[46px] items-center justify-center rounded-pill bg-gray-soft group-data-[sel=true]:bg-yellow"><img class="size-6" src="/assets/icon-cart.svg" alt=""></span>
            <h4 class="text-base font-semibold text-ink">{{ t('register.roles.buyer.title') }}</h4>
            <p class="text-[13px] leading-[1.35] text-black/55">{{ t('register.roles.buyer.desc') }}</p>
          </button>
          <button type="button" class="group relative flex cursor-pointer flex-col gap-2.5 border border-black/20 p-5 text-left transition-[border-color,background] duration-200 hover:border-black data-[sel=true]:border-yellow data-[sel=true]:bg-[#fffdf0] data-[sel=true]:shadow-[inset_0_0_0_1px_var(--color-yellow)]" data-role="seller" data-sel="{{ $role === 'seller' ? 'true' : 'false' }}">
            <span class="absolute top-3.5 right-3.5 flex size-5 items-center justify-center rounded-full border border-black/30 opacity-0 transition duration-200 group-data-[sel=true]:border-yellow group-data-[sel=true]:bg-yellow group-data-[sel=true]:opacity-100"><img class="size-3 brightness-0" src="/assets/icon-check-green.svg" alt=""></span>
            <span class="flex size-[46px] items-center justify-center rounded-pill bg-gray-soft group-data-[sel=true]:bg-yellow"><img class="size-6" src="/assets/icon-tower-crane.svg" alt=""></span>
            <h4 class="text-base font-semibold text-ink">{{ t('register.roles.seller.title') }}</h4>
            <p class="text-[13px] leading-[1.35] text-black/55">{{ t('register.roles.seller.desc') }}</p>
          </button>
          <button type="button" class="group relative flex cursor-pointer flex-col gap-2.5 border border-black/20 p-5 text-left transition-[border-color,background] duration-200 hover:border-black data-[sel=true]:border-yellow data-[sel=true]:bg-[#fffdf0] data-[sel=true]:shadow-[inset_0_0_0_1px_var(--color-yellow)]" data-role="master" data-sel="{{ $role === 'master' ? 'true' : 'false' }}">
            <span class="absolute top-3.5 right-3.5 flex size-5 items-center justify-center rounded-full border border-black/30 opacity-0 transition duration-200 group-data-[sel=true]:border-yellow group-data-[sel=true]:bg-yellow group-data-[sel=true]:opacity-100"><img class="size-3 brightness-0" src="/assets/icon-check-green.svg" alt=""></span>
            <span class="flex size-[46px] items-center justify-center rounded-pill bg-gray-soft group-data-[sel=true]:bg-yellow"><img class="size-6" src="/assets/icon-hammer-wrench.svg" alt=""></span>
            <h4 class="text-base font-semibold text-ink">{{ t('register.roles.master.title') }}</h4>
            <p class="text-[13px] leading-[1.35] text-black/55">{{ t('register.roles.master.desc') }}</p>
          </button>
        </div>
      </div>

      <form class="flex flex-col gap-[18px]" id="regForm">
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

        {{-- Master only --}}
        <div class="flex gap-3.5 max-[640px]:flex-col [&[hidden]]:hidden" data-for="master" @if ($role !== 'master') hidden @endif>
          <x-ui.field class="flex-1" :label="t('register.form.specialization_label')">
            <x-ui.select name="specialist_specialty_id" :placeholder="t('register.form.select_placeholder')" :options="$specialties" />
          </x-ui.field>
          <x-ui.field class="flex-1" :label="t('register.form.city_label')">
            <x-ui.select name="city" :placeholder="t('register.form.select_placeholder')" :options="t('register.cities')" />
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

        <div class="rounded bg-[#f5f7f9] px-4 py-3 text-[13px] leading-[1.5] text-black/50 [&[hidden]]:hidden" data-for="seller,master" @if ($role === 'buyer') hidden @endif>
          {{ t('register.form.info_note') }}
        </div>

        <p class="text-center text-[15px] text-black/60">{{ t('register.form.have_account') }} <a class="border-b-2 border-yellow-line font-semibold text-ink" href="{{ route('login') }}">{{ t('register.form.sign_in') }}</a></p>
      </form>
    </div>
  </div>
</section>

</x-layout>
