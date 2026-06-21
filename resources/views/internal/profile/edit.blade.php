<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1 text-center sm:text-left">
            <h2 class="ui-page-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ __('Pengaturan Akun') }}
            </h2>
            <p class="ui-page-title-copy">Perbarui identitas login dan password akun yang sedang digunakan.</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="ui-alert ui-alert-success mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <div class="ui-card overflow-hidden shadow-md">
                <div class="ui-inline-note rounded-none border-b border-brand-100/70 sm:px-8">
                    <div class="ui-label text-brand-700">Akun Aktif</div>
                    <div class="ui-metric-value mt-1 text-lg text-slate-900">{{ $user->name }}</div>
                    <div class="mt-0.5 text-sm font-semibold text-brand-700">{{ '@' . $user->username }}</div>
                </div>
                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('internal.profile.update') }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div class="ui-settings-panel ui-settings-panel-muted">
                            <div class="ui-settings-section-head">
                                <span class="h-5 w-1 rounded-full bg-brand-500"></span>
                                <div>
                                    <h3 class="ui-settings-section-title">Identitas Login</h3>
                                    <p class="ui-settings-section-copy">Informasi ini tampil di menu dan dipakai saat login.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="name" :value="__('Nama Tampilan')" class="ui-form-label" />
                                    <x-text-input id="name" name="name" type="text" class="block w-full" :value="old('name', $user->name)" required autofocus maxlength="{{ $nameMax }}" />
                                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                                    <p class="mt-1 text-xs text-slate-500">Maksimal {{ $nameMax }} karakter.</p>
                                </div>

                                <div>
                                    <x-input-label for="username" :value="__('Username')" class="ui-form-label" />
                                    <x-text-input id="username" name="username" type="text" class="block w-full" :value="old('username', $user->username)" required maxlength="{{ $usernameMax }}" />
                                    <x-input-error class="mt-2" :messages="$errors->get('username')" />
                                    <p class="mt-1 text-xs text-slate-500">Huruf, angka, atau garis bawah.</p>
                                </div>
                            </div>
                        </div>

                        <div class="ui-settings-panel ui-settings-panel-amber">
                            <div class="ui-settings-section-head">
                                <span class="h-5 w-1 rounded-full bg-amber-500"></span>
                                <div>
                                    <h3 class="ui-settings-section-title text-amber-900">Ubah Password</h3>
                                    <p class="text-xs text-amber-700">Kosongkan bagian ini jika tidak ingin mengubah password.</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="current_password" :value="__('Password Saat Ini')" class="ui-form-label" />
                                    <x-text-input id="current_password" name="current_password" type="password" class="block w-full" autocomplete="current-password" />
                                    <x-input-error class="mt-2" :messages="$errors->get('current_password')" />
                                    <p class="mt-1 text-[10px] text-slate-400">Wajib diisi jika ingin mengubah password.</p>
                                </div>

                                <div x-data="{ show: false }">
                                    <x-input-label for="password" :value="__('Password Baru')" class="ui-form-label" />
                                    <div class="relative">
                                        <x-text-input id="password" name="password" ::type="show ? 'text' : 'password'" class="block w-full pr-10" autocomplete="new-password" />
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition-colors hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-brand-200 focus:ring-offset-1 rounded-lg">
                                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                        </button>
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                                    <p class="mt-1 text-xs text-slate-500">Minimal {{ $passwordMin }} karakter.</p>
                                </div>

                                <div x-data="{ show: false }">
                                    <x-input-label for="password_confirmation" :value="__('Konfirmasi Password Baru')" class="ui-form-label" />
                                    <div class="relative">
                                        <x-text-input id="password_confirmation" name="password_confirmation" ::type="show ? 'text' : 'password'" class="block w-full pr-10" autocomplete="new-password" />
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition-colors hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-brand-200 focus:ring-offset-1 rounded-lg">
                                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                        </button>
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
                                </div>
                            </div>
                        </div>

                        <div class="ui-settings-panel ui-settings-panel-muted sm:flex sm:items-center sm:justify-between">
                            <p class="mb-3 text-xs font-semibold text-slate-500 sm:mb-0">Simpan hanya jika ada perubahan akun.</p>
                            <button type="submit" class="ui-btn ui-btn-primary w-full px-8 py-3 sm:w-auto">
                                {{ __('Simpan Perubahan') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
