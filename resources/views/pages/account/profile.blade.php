{{-- User account / profile page --}}
@php
    $initials = mb_strtoupper(mb_substr($user->first_name ?? $user->name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1));
@endphp
<x-layout page="account-profile" :title="__('account.title', [], 'Hesabım')">

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
      <a href="{{ route('account') }}" class="border-b-2 border-yellow px-4 pb-3 text-sm font-semibold text-ink">{{ __('account.nav.profile', [], 'Profil') }}</a>
      <a href="{{ route('account.orders') }}" class="border-b-2 border-transparent px-4 pb-3 text-sm font-medium text-black/50 hover:text-ink">{{ __('account.nav.orders', [], 'Sifarişlər') }}</a>
      <a href="{{ route('wishlist') }}" class="border-b-2 border-transparent px-4 pb-3 text-sm font-medium text-black/50 hover:text-ink">{{ __('account.nav.wishlist', [], 'Seçilmişlər') }}</a>
    </div>

    @if (session('success'))
      <div class="mb-6 border border-green/20 bg-green/5 px-5 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    {{-- profile edit form --}}
    <form method="POST" action="{{ route('account.update') }}" class="border border-black/12 bg-white p-8 shadow-[-4px_4px_4px_rgba(0,0,0,0.05)]">
      @csrf
      @method('PUT')

      <h2 class="mb-6 text-lg font-semibold text-ink">{{ __('account.personal_info', [], 'Şəxsi məlumatlar') }}</h2>

      <div class="flex flex-col gap-5">
        <div class="flex gap-4 max-[560px]:flex-col">
          <div class="flex flex-1 flex-col gap-1.5">
            <label class="text-sm font-medium text-black/70" for="first_name">{{ __('account.first_name', [], 'Ad') }}</label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                   class="border border-black/15 px-4 py-3 text-[15px] outline-none transition focus:border-black/40" required>
            @error('first_name') <p class="text-xs text-red">{{ $message }}</p> @enderror
          </div>
          <div class="flex flex-1 flex-col gap-1.5">
            <label class="text-sm font-medium text-black/70" for="last_name">{{ __('account.last_name', [], 'Soyad') }}</label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                   class="border border-black/15 px-4 py-3 text-[15px] outline-none transition focus:border-black/40">
            @error('last_name') <p class="text-xs text-red">{{ $message }}</p> @enderror
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium text-black/70" for="email">{{ __('account.email', [], 'E-poçt') }}</label>
          <input type="email" id="email" value="{{ $user->email }}" disabled
                 class="border border-black/15 bg-gray-soft px-4 py-3 text-[15px] text-black/50 outline-none">
          <p class="text-xs text-black/40">{{ __('account.email_hint', [], 'E-poçt dəyişdirilə bilməz') }}</p>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium text-black/70" for="phone">{{ __('account.phone', [], 'Telefon') }}</label>
          <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                 class="border border-black/15 px-4 py-3 text-[15px] outline-none transition focus:border-black/40"
                 placeholder="+994 XX XXX XX XX">
          @error('phone') <p class="text-xs text-red">{{ $message }}</p> @enderror
        </div>
      </div>

      <div class="mt-7 flex items-center justify-end border-t border-black/10 pt-6">
        <button type="submit" class="bg-yellow px-8 py-3 text-base font-semibold text-ink transition hover:brightness-[.93]">{{ __('account.save', [], 'Yadda saxla') }}</button>
      </div>
    </form>

  </div>
</section>

</x-layout>
