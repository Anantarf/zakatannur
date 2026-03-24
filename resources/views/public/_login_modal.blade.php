<div x-show="openLogin" style="display: none;" class="relative z-[150]" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div x-show="openLogin" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

    {{-- Dialog --}}
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="openLogin" @click.away="openLogin = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100">
                
                {{-- Close Button --}}
                <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                    <button type="button" @click="openLogin = false" class="rounded-full bg-white p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all">
                        <span class="sr-only">Tutup</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-8 sm:p-10">
                    <div class="text-center mb-8">
                        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-white mb-4 border border-emerald-100 shadow-sm overflow-hidden p-1">
                            <img src="/images/logo_zakatannur.png" alt="Logo Zakat Annur" class="w-full h-full object-contain">
                        </div>
                        <h2 class="text-2xl font-black tracking-tight text-gray-900">Masuk ke Sistem</h2>
                        <p class="mt-2 text-sm text-gray-500 font-medium">Silakan masuk dengan akun panitia Anda</p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 rounded-xl bg-red-50 p-4 border border-red-100 text-sm font-medium text-red-600 flex items-start gap-3">
                            <svg class="h-5 w-5 text-red-500 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5" 
                        x-init="$watch('openLogin', value => { if(value) setTimeout(() => $refs.username.focus(), 100) })">
                        @csrf
                        <div>
                            <label for="username" class="block text-sm font-bold text-gray-700 mb-1">Nama Pengguna</label>
                            <input id="username" type="text" name="username" x-ref="username" value="{{ old('username') }}" required autocomplete="username" class="block w-full rounded-xl border-gray-200 px-4 py-3 text-gray-900 placeholder-gray-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all font-medium" placeholder="Masukkan username">
                        </div>

                        <div x-data="{ showPass: false }">
                            <label for="password" class="block text-sm font-bold text-gray-700 mb-1">Kata Sandi</label>
                            <div class="relative">
                                <input id="password" :type="showPass ? 'text' : 'password'" name="password" required class="block w-full rounded-xl border-gray-200 px-4 py-3 pr-12 text-gray-900 placeholder-gray-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all font-medium" placeholder="••••••••">
                                <button type="button" @click="showPass = !showPass" aria-label="Tampilkan kata sandi" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg x-show="!showPass" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="showPass" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <label for="remember_me" class="flex items-center gap-2 cursor-pointer">
                                <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                                <span class="text-sm font-medium text-gray-600">Ingat Saya</span>
                            </label>
                        </div>

                        <button type="submit" class="flex w-full justify-center rounded-xl bg-emerald-600 px-4 py-3.5 text-sm font-bold text-white shadow-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all">
                            Masuk Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
