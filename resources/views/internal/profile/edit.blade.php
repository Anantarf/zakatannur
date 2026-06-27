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

            <div class="ui-card overflow-hidden shadow-md">
                <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-4 py-3 sm:px-6">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold uppercase text-brand-700">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-700">{{ $user->name }}</div>
                        <div class="text-xs font-medium text-slate-500">{{ '@' . $user->username }}</div>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    <form method="POST" action="{{ route('internal.profile.update') }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <!-- Identitas Login Section -->
                        <div>
                            <h3 class="mb-2 text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">Identitas Login</h3>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="name" :value="__('Nama Lengkap')" class="ui-form-label" />
                                    <x-text-input id="name" name="name" type="text" class="block w-full" :value="old('name', $user->name)" required autofocus maxlength="{{ $nameMax }}" />
                                    <x-input-error class="mt-1" :messages="$errors->get('name')" />
                                </div>

                                <div>
                                    <x-input-label for="username" :value="__('Username')" class="ui-form-label" />
                                    <x-text-input id="username" name="username" type="text" class="block w-full" :value="old('username', $user->username)" required maxlength="{{ $usernameMax }}" />
                                    <x-input-error class="mt-1" :messages="$errors->get('username')" />
                                </div>
                            </div>
                        </div>

                        <!-- Ubah Password Section -->
                        <div>
                            <h3 class="mb-2 text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">Keamanan & Password</h3>
                            <div class="space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="current_password" :value="__('Password Saat Ini')" class="ui-form-label" />
                                        <x-text-input id="current_password" name="current_password" type="password" class="block w-full" autocomplete="current-password" placeholder="Hanya jika ingin mengubah password" />
                                        <x-input-error class="mt-1" :messages="$errors->get('current_password')" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div x-data="{ show: false }">
                                        <x-input-label for="password" :value="__('Password Baru')" class="ui-form-label" />
                                        <div class="relative">
                                            <x-text-input id="password" name="password" ::type="show ? 'text' : 'password'" class="block w-full pr-10" autocomplete="new-password" />
                                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition-colors hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-brand-200 focus:ring-offset-1 rounded-lg">
                                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                            </button>
                                        </div>
                                        <x-input-error class="mt-1" :messages="$errors->get('password')" />
                                    </div>

                                    <div x-data="{ show: false }">
                                        <x-input-label for="password_confirmation" :value="__('Konfirmasi Password')" class="ui-form-label" />
                                        <div class="relative">
                                            <x-text-input id="password_confirmation" name="password_confirmation" ::type="show ? 'text' : 'password'" class="block w-full pr-10" autocomplete="new-password" />
                                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition-colors hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-brand-200 focus:ring-offset-1 rounded-lg">
                                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                            </button>
                                        </div>
                                        <x-input-error class="mt-1" :messages="$errors->get('password_confirmation')" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="ui-btn ui-btn-primary w-full sm:w-auto px-6 py-2.5">
                                {{ __('Simpan Perubahan') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
