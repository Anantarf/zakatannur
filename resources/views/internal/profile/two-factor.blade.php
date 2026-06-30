<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1 text-center sm:text-left">
            <h2 class="ui-page-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Keamanan Dua Langkah (2FA)
            </h2>
            <p class="ui-page-title-copy">Tambah lapisan keamanan dengan Google Authenticator.</p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="ui-alert ui-alert-success mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <div class="ui-card overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/50 px-4 py-3 sm:px-6">
                    <h3 class="text-sm font-semibold text-slate-700">Status 2FA</h3>
                </div>
                <div class="p-4 sm:p-6 space-y-4">
                    @if ($user->hasTwoFactorEnabled())
                        <div class="flex items-center gap-3 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            2FA aktif — akun kamu dilindungi dengan Google Authenticator.
                        </div>

                        <form method="POST" action="{{ route('internal.profile.two-factor.disable') }}">
                            @csrf
                            <div class="space-y-3">
                                <div>
                                    <x-input-label for="password" value="Masukkan password untuk nonaktifkan 2FA" />
                                    <x-text-input id="password" type="password" name="password" class="block mt-1 w-full max-w-sm" />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>
                                <button type="submit" class="ui-btn ui-btn-danger px-4 py-2 text-sm">
                                    Nonaktifkan 2FA
                                </button>
                            </div>
                        </form>

                    @elseif ($user->two_factor_secret && !$user->two_factor_confirmed_at)
                        {{-- Secret generated, awaiting confirmation --}}
                        <p class="text-sm text-slate-600">Scan QR code ini dengan Google Authenticator, lalu masukkan kode 6 digit untuk mengaktifkan.</p>

                        @if ($qrCodeUrl)
                            <div class="flex justify-center py-4">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" alt="QR Code 2FA" class="rounded-lg border border-slate-200 shadow-sm" width="200" height="200">
                            </div>
                        @endif

                        <form method="POST" action="{{ route('internal.profile.two-factor.confirm') }}">
                            @csrf
                            <div class="space-y-3">
                                <div>
                                    <x-input-label for="code" value="Kode dari Google Authenticator" />
                                    <x-text-input id="code" type="text" name="code" inputmode="numeric" maxlength="6"
                                        class="block mt-1 w-full max-w-xs tracking-widest text-center text-xl"
                                        placeholder="000000" autofocus />
                                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                                </div>
                                <button type="submit" class="ui-btn ui-btn-primary px-4 py-2 text-sm">
                                    Konfirmasi & Aktifkan
                                </button>
                            </div>
                        </form>

                    @else
                        <div class="flex items-center gap-3 text-sm text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            2FA belum aktif. Aktifkan untuk keamanan tambahan.
                        </div>

                        <form method="POST" action="{{ route('internal.profile.two-factor.enable') }}">
                            @csrf
                            <button type="submit" class="ui-btn ui-btn-primary px-4 py-2 text-sm">
                                Aktifkan 2FA
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="text-center">
                <a href="{{ route('internal.profile.edit') }}" class="text-sm text-brand-600 hover:underline">← Kembali ke Pengaturan Akun</a>
            </div>
        </div>
    </div>
</x-app-layout>
