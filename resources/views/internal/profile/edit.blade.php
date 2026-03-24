<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            {{ __('Pengaturan Akun') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('internal.profile.update') }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        {{-- Name --}}
                        <div>
                            <x-input-label for="name" :value="__('Nama (Maks. 10 Karakter)')" class="mb-1.5" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500" :value="old('name', $user->name)" required autofocus maxlength="10" />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            <p class="mt-1 text-[10px] text-gray-400">Gunakan nama singkat agar tampilan menu tetap rapi.</p>
                        </div>

                        {{-- Username --}}
                        <div>
                            <x-input-label for="username" :value="__('Username')" class="mb-1.5" />
                            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500" :value="old('username', $user->username)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('username')" />
                        </div>

                        <hr class="border-gray-100">

                        <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                            <h3 class="text-xs font-bold text-amber-800 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                Ubah Password
                            </h3>
                            <p class="text-xs text-amber-700 mb-4 italic">Kosongkan jika tidak ingin mengubah password.</p>

                            <div class="space-y-4">
                                {{-- Current Password --}}
                                <div>
                                    <x-input-label for="current_password" :value="__('Password Saat Ini')" />
                                    <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500" autocomplete="current-password" />
                                    <x-input-error class="mt-2" :messages="$errors->get('current_password')" />
                                    <p class="mt-1 text-[10px] text-gray-400">Wajib diisi jika ingin mengubah password.</p>
                                </div>

                                {{-- Password --}}
                                <div x-data="{ show: false }">
                                    <x-input-label for="password" :value="__('Password Baru')" />
                                    <div class="relative">
                                        <x-text-input id="password" name="password" ::type="show ? 'text' : 'password'" class="mt-1 block w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 pr-10" autocomplete="new-password" />
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 transition-colors">
                                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                        </button>
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                                </div>

                                {{-- Confirm Password --}}
                                <div x-data="{ show: false }">
                                    <x-input-label for="password_confirmation" :value="__('Konfirmasi Password Baru')" />
                                    <div class="relative">
                                        <x-text-input id="password_confirmation" name="password_confirmation" ::type="show ? 'text' : 'password'" class="mt-1 block w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 pr-10" autocomplete="new-password" />
                                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 transition-colors">
                                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                        </button>
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4 mt-8 pt-4 border-t border-gray-50">
                            <x-primary-button class="bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-100 px-8 py-3 rounded-xl transition-all h-auto">
                                {{ __('Simpan Perubahan') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
