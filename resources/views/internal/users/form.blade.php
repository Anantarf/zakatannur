@php
    /** @var \App\Models\User|null $user */
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight flex items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                {{ $user ? 'Ubah Pengguna' : 'Tambah Pengguna' }}
            </h2>
            <a href="{{ route('internal.users.index') }}" class="inline-flex justify-center items-center gap-2 rounded-xl bg-white border border-gray-100 px-4 py-3 sm:py-2 text-sm font-bold text-gray-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all w-full sm:w-auto shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <x-form-errors />

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-emerald-50 px-6 py-4 border-b border-emerald-100 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <h3 class="font-bold text-emerald-900">Data Pengguna</h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ $user ? route('internal.users.update', ['user' => data_get($user, 'id')]) : route('internal.users.store') }}" class="space-y-5">
                        @csrf
                        @if ($user)
                            @method('PATCH')
                        @endif

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1" for="name">Nama</label>
                            <input id="name" name="name" type="text" value="{{ old('name', data_get($user, 'name', '')) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" required />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1" for="username">Username</label>
                            <input id="username" name="username" type="text" value="{{ old('username', data_get($user, 'username', '')) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" required />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1" for="role">Role</label>
                            <select id="role" name="role" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" required>
                                @foreach ($allowedRoles as $r)
                                    <option value="{{ $r }}" @selected(old('role', data_get($user, 'role', '')) === $r)>{{ $r }}</option>
                                @endforeach
                            </select>
                            @if (empty($allowedRoles))
                                <p class="mt-1 text-xs text-gray-500 italic">Role tidak tersedia untuk akun ini.</p>
                            @endif
                        </div>

                        <x-password-input
                            name="password"
                            :label="'Kata Sandi' . ($user ? ' (opsional, isi jika ingin ganti)' : '')"
                            hint="Minimal 8 karakter."
                            :required="!$user" />

                        <div class="pt-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <button type="submit" class="inline-flex justify-center items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-md hover:bg-emerald-700 transition-all w-full sm:w-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                Simpan
                            </button>
                            <a href="{{ route('internal.users.index') }}" class="px-4 py-3 sm:py-2 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors w-full sm:w-auto text-center border sm:border-0 border-gray-200 rounded-xl sm:rounded-none">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
