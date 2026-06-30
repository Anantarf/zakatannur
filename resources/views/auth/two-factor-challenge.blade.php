<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="flex justify-center mb-6 mt-4">
        <div class="w-32 h-32 rounded-full bg-white shadow-md border border-brand-100 p-2 flex items-center justify-center">
            <img src="{{ asset('images/logo_zakatannur.png') }}" alt="Logo Zakat Annur" class="w-full h-full object-contain">
        </div>
    </div>

    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold tracking-[-0.01em] text-slate-900">Verifikasi 2FA</h2>
        <p class="mt-2 text-sm text-slate-500 font-medium">Masukkan kode 6 digit dari Google Authenticator</p>
    </div>

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <div>
            <x-input-label for="code" :value="__('Kode Autentikasi')" />
            <x-text-input id="code" class="block mt-1 w-full tracking-widest text-center text-xl"
                type="text" name="code" inputmode="numeric" maxlength="6"
                autofocus autocomplete="one-time-code" placeholder="000000" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                Verifikasi
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
