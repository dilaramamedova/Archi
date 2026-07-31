<x-layout page="register" :title="__('register.title')">

<section class="bg-gray-soft2 py-14">
  <div class="mx-auto max-w-[760px] px-7">
    <div class="flex flex-col gap-7 border border-black/12 bg-white p-10 shadow-[-4px_4px_4px_rgba(0,0,0,0.05)] max-[640px]:p-6">
      <div class="flex flex-col gap-2">
        <div class="flex items-center gap-3">
          <span class="h-1 w-8 rounded-[2px] bg-yellow"></span>
          <p class="text-[13px] font-medium tracking-[1.4px] text-black/55 uppercase">{{ __('register.head.tag') }}</p>
        </div>
        <h1 class="text-[32px] font-semibold tracking-[-0.4px] text-black/90 max-[640px]:text-[26px]">{{ __('register.head.title') }}</h1>
        <p class="text-base leading-[1.5] text-black/55">{{ __('register.head.subtitle') }}</p>
      </div>

      <div class="hidden border border-green bg-[#eafce9] px-4 py-3.5 text-[15px] text-[#0a7a14]" id="okMsg">{{ __('register.success') }}</div>

      {{-- Role selection — the selected state is a data-sel attribute + Tailwind group-data variants. --}}
      <div>
        <div class="mb-3 text-base font-semibold text-black">{{ __('register.roles.label') }}</div>
        <div class="grid grid-cols-3 gap-3 max-[640px]:grid-cols-1" id="roles">
          <button type="button" class="group relative flex cursor-pointer flex-col gap-2.5 border border-black/20 p-5 text-left transition-[border-color,background] duration-200 hover:border-black data-[sel=true]:border-yellow data-[sel=true]:bg-[#fffdf0] data-[sel=true]:shadow-[inset_0_0_0_1px_var(--color-yellow)]" data-role="buyer">
            <span class="absolute top-3.5 right-3.5 flex size-5 items-center justify-center rounded-full border border-black/30 opacity-0 transition duration-200 group-data-[sel=true]:border-yellow group-data-[sel=true]:bg-yellow group-data-[sel=true]:opacity-100"><img class="size-3 brightness-0" src="/assets/ic-check.svg" alt=""></span>
            <span class="flex size-[46px] items-center justify-center rounded-pill bg-gray-soft group-data-[sel=true]:bg-yellow"><img class="size-6" src="/assets/ic-cart.svg" alt=""></span>
            <h4 class="text-base font-semibold text-ink">{{ __('register.roles.buyer.title') }}</h4>
            <p class="text-[13px] leading-[1.35] text-black/55">{{ __('register.roles.buyer.desc') }}</p>
          </button>
          <button type="button" class="group relative flex cursor-pointer flex-col gap-2.5 border border-black/20 p-5 text-left transition-[border-color,background] duration-200 hover:border-black data-[sel=true]:border-yellow data-[sel=true]:bg-[#fffdf0] data-[sel=true]:shadow-[inset_0_0_0_1px_var(--color-yellow)]" data-role="seller">
            <span class="absolute top-3.5 right-3.5 flex size-5 items-center justify-center rounded-full border border-black/30 opacity-0 transition duration-200 group-data-[sel=true]:border-yellow group-data-[sel=true]:bg-yellow group-data-[sel=true]:opacity-100"><img class="size-3 brightness-0" src="/assets/ic-check.svg" alt=""></span>
            <span class="flex size-[46px] items-center justify-center rounded-pill bg-gray-soft group-data-[sel=true]:bg-yellow"><img class="size-6" src="/assets/spec-ic-sirket.svg" alt=""></span>
            <h4 class="text-base font-semibold text-ink">{{ __('register.roles.seller.title') }}</h4>
            <p class="text-[13px] leading-[1.35] text-black/55">{{ __('register.roles.seller.desc') }}</p>
          </button>
          <button type="button" class="group relative flex cursor-pointer flex-col gap-2.5 border border-black/20 p-5 text-left transition-[border-color,background] duration-200 hover:border-black data-[sel=true]:border-yellow data-[sel=true]:bg-[#fffdf0] data-[sel=true]:shadow-[inset_0_0_0_1px_var(--color-yellow)]" data-role="master">
            <span class="absolute top-3.5 right-3.5 flex size-5 items-center justify-center rounded-full border border-black/30 opacity-0 transition duration-200 group-data-[sel=true]:border-yellow group-data-[sel=true]:bg-yellow group-data-[sel=true]:opacity-100"><img class="size-3 brightness-0" src="/assets/ic-check.svg" alt=""></span>
            <span class="flex size-[46px] items-center justify-center rounded-pill bg-gray-soft group-data-[sel=true]:bg-yellow"><img class="size-6" src="/assets/spec-ic-usta.svg" alt=""></span>
            <h4 class="text-base font-semibold text-ink">{{ __('register.roles.master.title') }}</h4>
            <p class="text-[13px] leading-[1.35] text-black/55">{{ __('register.roles.master.desc') }}</p>
          </button>
        </div>
      </div>

      <form class="flex flex-col gap-[18px]" id="regForm">
        <div class="flex gap-3.5 max-[640px]:flex-col">
          <div class="flex flex-1 flex-col gap-2">
            <label class="text-sm font-medium text-black/70">{{ __('register.form.first_name_label') }}</label>
            <input class="border border-black/20 bg-white px-4 py-3.5 text-base text-black outline-none transition-[border-color] duration-200 placeholder:text-black/40 focus:border-black" type="text" placeholder="{{ __('register.form.first_name_placeholder') }}" required>
          </div>
          <div class="flex flex-1 flex-col gap-2">
            <label class="text-sm font-medium text-black/70">{{ __('register.form.last_name_label') }}</label>
            <input class="border border-black/20 bg-white px-4 py-3.5 text-base text-black outline-none transition-[border-color] duration-200 placeholder:text-black/40 focus:border-black" type="text" placeholder="{{ __('register.form.last_name_placeholder') }}" required>
          </div>
        </div>

        {{-- Seller only · [&[hidden]]:hidden is required because `flex` beats the UA [hidden] rule. --}}
        <div class="flex flex-col gap-2 [&[hidden]]:hidden" data-for="seller" hidden>
          <label class="text-sm font-medium text-black/70">{{ __('register.form.company_label') }}</label>
          <input class="border border-black/20 bg-white px-4 py-3.5 text-base text-black outline-none transition-[border-color] duration-200 placeholder:text-black/40 focus:border-black" type="text" placeholder="{{ __('register.form.company_placeholder') }}">
        </div>

        {{-- Master only --}}
        <div class="flex gap-3.5 max-[640px]:flex-col [&[hidden]]:hidden" data-for="master" hidden>
          <div class="flex flex-1 flex-col gap-2">
            <label class="text-sm font-medium text-black/70">{{ __('register.form.specialization_label') }}</label>
            <select class="border border-black/20 bg-white px-4 py-3.5 text-base text-black outline-none transition-[border-color] duration-200 focus:border-black">
              <option value="">{{ __('register.form.select_placeholder') }}</option>
              @foreach (__('register.specializations') as $specialization)
                <option>{{ $specialization }}</option>
              @endforeach
            </select>
          </div>
          <div class="flex flex-1 flex-col gap-2">
            <label class="text-sm font-medium text-black/70">{{ __('register.form.city_label') }}</label>
            <select class="border border-black/20 bg-white px-4 py-3.5 text-base text-black outline-none transition-[border-color] duration-200 focus:border-black">
              <option value="">{{ __('register.form.select_placeholder') }}</option>
              @foreach (__('register.cities') as $city)
                <option>{{ $city }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="flex gap-3.5 max-[640px]:flex-col">
          <div class="flex flex-1 flex-col gap-2">
            <label class="text-sm font-medium text-black/70">{{ __('register.form.email_label') }}</label>
            <input class="border border-black/20 bg-white px-4 py-3.5 text-base text-black outline-none transition-[border-color] duration-200 placeholder:text-black/40 focus:border-black" type="email" placeholder="{{ __('register.form.email_placeholder') }}" required>
          </div>
          <div class="flex flex-1 flex-col gap-2">
            <label class="text-sm font-medium text-black/70">{{ __('register.form.phone_label') }}</label>
            <input class="border border-black/20 bg-white px-4 py-3.5 text-base text-black outline-none transition-[border-color] duration-200 placeholder:text-black/40 focus:border-black" type="tel" placeholder="{{ __('register.form.phone_placeholder') }}" required>
          </div>
        </div>
        <div class="flex gap-3.5 max-[640px]:flex-col">
          <div class="flex flex-1 flex-col gap-2">
            <label class="text-sm font-medium text-black/70">{{ __('register.form.password_label') }}</label>
            <input class="border border-black/20 bg-white px-4 py-3.5 text-base text-black outline-none transition-[border-color] duration-200 placeholder:text-black/40 focus:border-black" type="password" placeholder="{{ __('register.form.password_placeholder') }}" required>
          </div>
          <div class="flex flex-1 flex-col gap-2">
            <label class="text-sm font-medium text-black/70">{{ __('register.form.password_confirm_label') }}</label>
            <input class="border border-black/20 bg-white px-4 py-3.5 text-base text-black outline-none transition-[border-color] duration-200 placeholder:text-black/40 focus:border-black" type="password" placeholder="{{ __('register.form.password_confirm_placeholder') }}" required>
          </div>
        </div>

        <label class="flex items-center gap-2.5 text-sm text-black/70"><input class="size-[18px] accent-yellow" type="checkbox" required> <span><a class="border-b border-yellow-line" href="#">{{ __('register.form.terms_link') }}</a> {{ __('register.form.terms_and') }} <a class="border-b border-yellow-line" href="#">{{ __('register.form.privacy_link') }}</a> {{ __('register.form.terms_agree') }}</span></label>

        <button class="h-[54px] cursor-pointer border-none bg-yellow text-lg font-semibold text-ink transition-[filter] duration-200 hover:brightness-[.93]" type="submit">{{ __('register.form.submit') }}</button>
        <p class="text-center text-[15px] text-black/60">{{ __('register.form.have_account') }} <a class="border-b-2 border-yellow-line font-semibold text-ink" href="{{ route('login') }}">{{ __('register.form.sign_in') }}</a></p>
      </form>
    </div>
  </div>
</section>

</x-layout>
