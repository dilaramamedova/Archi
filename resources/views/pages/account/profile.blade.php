{{-- User account / profile page --}}
@php
    $initials = mb_strtoupper(mb_substr($user->first_name ?? $user->name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1));
@endphp
<x-layout page="account-profile" :title="t('account.title')">

<section class="bg-gray-soft2 min-h-[60vh] py-12">
  <div class="mx-auto max-w-[800px] px-7">

    {{-- header --}}
    <div class="mb-8 flex items-center gap-5">
      <div class="flex size-16 items-center justify-center rounded-full bg-yellow text-xl font-semibold text-ink">{{ $initials }}</div>
      <div>
        <h1 class="text-2xl font-semibold text-ink">{{ $user->first_name }} {{ $user->last_name }}</h1>
        <p class="text-sm text-black/50">{{ $user->email }}</p>
      </div>
    </div>

    {{-- nav tabs --}}
    <div class="mb-8 flex gap-4 border-b border-black/10">
      <a href="{{ route('account') }}" class="border-b-2 border-yellow px-4 pb-3 text-sm font-semibold text-ink">{{ t('account.nav.profile') }}</a>
      <a href="{{ route('account.orders') }}" class="border-b-2 border-transparent px-4 pb-3 text-sm font-medium text-black/50 hover:text-ink">{{ t('account.nav.orders') }}</a>
      <a href="{{ route('wishlist') }}" class="border-b-2 border-transparent px-4 pb-3 text-sm font-medium text-black/50 hover:text-ink">{{ t('account.nav.wishlist') }}</a>
    </div>

    {{-- profile edit form --}}
    <form method="POST" action="{{ route('account.update') }}" class="border border-black/12 bg-white p-8 shadow-[-4px_4px_4px_rgba(0,0,0,0.05)]">
      @csrf
      @method('PUT')

      <h2 class="mb-6 text-lg font-semibold text-ink">{{ t('account.personal_info') }}</h2>

      <div class="flex flex-col gap-5">
        <div class="flex gap-4 max-[560px]:flex-col">
          <div class="flex flex-1 flex-col gap-1.5">
            <label class="text-sm font-medium text-black/70" for="first_name">{{ t('account.first_name') }}</label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                   class="border border-black/15 px-4 py-3 text-[15px] outline-none transition focus:border-black/40" required>
            @error('first_name') <p class="text-xs text-red">{{ $message }}</p> @enderror
          </div>
          <div class="flex flex-1 flex-col gap-1.5">
            <label class="text-sm font-medium text-black/70" for="last_name">{{ t('account.last_name') }}</label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                   class="border border-black/15 px-4 py-3 text-[15px] outline-none transition focus:border-black/40">
            @error('last_name') <p class="text-xs text-red">{{ $message }}</p> @enderror
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium text-black/70" for="email">{{ t('account.email') }}</label>
          <input type="email" id="email" value="{{ $user->email }}" disabled
                 class="border border-black/15 bg-gray-soft px-4 py-3 text-[15px] text-black/50 outline-none">
          <p class="text-xs text-black/40">{{ t('account.email_hint') }}</p>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium text-black/70" for="phone">{{ t('account.phone') }}</label>
          <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                 class="border border-black/15 px-4 py-3 text-[15px] outline-none transition focus:border-black/40"
                 placeholder="+994 XX XXX XX XX">
          @error('phone') <p class="text-xs text-red">{{ $message }}</p> @enderror
        </div>
      </div>

      <div class="mt-7 flex items-center justify-end border-t border-black/10 pt-6">
        <button type="submit" class="bg-yellow px-8 py-3 text-base font-semibold text-ink transition hover:brightness-[.93]">{{ t('account.save') }}</button>
      </div>
    </form>

    {{-- security: change password (POST /cabinet/password, shared by all roles) --}}
    <form id="passwordForm" autocomplete="off" class="mt-8 border border-black/12 bg-white p-8 shadow-[-4px_4px_4px_rgba(0,0,0,0.05)]">
      <h2 class="mb-6 text-lg font-semibold text-ink">{{ t('account.security.title') }}</h2>

      <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium text-black/70" for="current_password">{{ t('account.security.current') }}</label>
          <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                 class="border border-black/15 px-4 py-3 text-[15px] outline-none transition focus:border-black/40">
        </div>
        <div class="flex gap-4 max-[560px]:flex-col">
          <div class="flex flex-1 flex-col gap-1.5">
            <label class="text-sm font-medium text-black/70" for="password">{{ t('account.security.new') }}</label>
            <input type="password" id="password" name="password" autocomplete="new-password"
                   class="border border-black/15 px-4 py-3 text-[15px] outline-none transition focus:border-black/40">
          </div>
          <div class="flex flex-1 flex-col gap-1.5">
            <label class="text-sm font-medium text-black/70" for="password_confirmation">{{ t('account.security.repeat') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                   class="border border-black/15 px-4 py-3 text-[15px] outline-none transition focus:border-black/40">
          </div>
        </div>
      </div>

      <div class="mt-7 flex items-center justify-end border-t border-black/10 pt-6">
        <button type="submit" id="pwdSubmit" class="bg-yellow px-8 py-3 text-base font-semibold text-ink transition hover:brightness-[.93]">{{ t('account.security.save') }}</button>
      </div>
    </form>

  </div>
</section>

</x-layout>
