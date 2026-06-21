@php
    /** @var \App\Models\User|null $user */
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    {{ $user ? 'Ubah Pengguna' : 'Tambah Pengguna' }}
                </h2>
                <p class="ui-page-title-copy">{{ $user ? 'Perbarui identitas, role, atau password pengguna.' : 'Tambahkan akun baru sesuai kewenangan role Anda.' }}</p>
            </div>
            <a href="{{ route('internal.users.index') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
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

            <x-form-errors />

            <div class="ui-card overflow-hidden">
                @if ($user)
                    <div class="ui-inline-note border-b border-brand-100/70 rounded-none">
                        <div class="ui-label text-brand-700">Akun yang Diedit</div>
                        <div class="ui-metric-value mt-1 text-lg text-slate-900">{{ $user->name }}</div>
                        <div class="mt-0.5 text-sm font-semibold text-brand-700">{{ '@' . $user->username }} - {{ $roleLabels[$user->role] ?? ucfirst($user->role) }}</div>
                    </div>
                @endif
                <div class="ui-card-header ui-card-header-emerald">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-card-header-icon text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <h3 class="ui-card-header-title text-brand-900">Data Pengguna</h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ $user ? route('internal.users.update', ['user' => data_get($user, 'id')]) : route('internal.users.store') }}" class="space-y-5">
                        @csrf
                        @if ($user)
                            @method('PATCH')
                        @endif

                        <div>
                            <label class="ui-form-label" for="name">Nama</label>
                            <input id="name" name="name" type="text" value="{{ old('name', data_get($user, 'name', '')) }}" maxlength="{{ $nameMax }}" class="ui-input w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            <p class="mt-1 text-xs text-slate-500">Maksimal {{ $nameMax }} karakter.</p>
                        </div>

                        <div>
                            <label class="ui-form-label" for="username">Username</label>
                            <input id="username" name="username" type="text" value="{{ old('username', data_get($user, 'username', '')) }}" maxlength="{{ $usernameMax }}" class="ui-input w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('username')" />
                            <p class="mt-1 text-xs text-slate-500">Gunakan huruf, angka, atau garis bawah tanpa spasi.</p>
                        </div>

                        <div>
                            <label class="ui-form-label" for="role">Role</label>
                            <select id="role" name="role" class="ui-select w-full" required>
                                @foreach ($allowedRoles as $r)
                                    <option value="{{ $r }}" @selected(old('role', data_get($user, 'role', '')) === $r)>{{ $roleLabels[$r] ?? ucfirst($r) }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('role')" />
                            @if (empty($allowedRoles))
                                <p class="mt-1 text-xs text-slate-500 not-italic">Role tidak tersedia untuk akun ini.</p>
                            @else
                                <p class="mt-1 text-xs text-slate-500">Pilih role sesuai tanggung jawab operasional pengguna.</p>
                            @endif
                        </div>

                        <x-password-input
                            name="password"
                            :label="'Kata Sandi' . ($user ? ' (opsional, isi jika ingin ganti)' : '')"
                            hint="Minimal {{ $passwordMin }} karakter. {{ $user ? 'Kosongkan jika tidak ingin mengganti password.' : '' }}"
                            :required="!$user" />

                        <div class="sticky bottom-3 z-10 -mx-2 rounded-2xl border border-brand-100 bg-white/90 p-3 shadow-xl shadow-slate-900/10 backdrop-blur sm:static sm:mx-0 sm:flex sm:items-center sm:justify-between sm:shadow-none sm:backdrop-blur-0">
                            <p class="mb-3 hidden text-xs font-semibold text-slate-500 sm:mb-0 sm:block">Pastikan role sesuai wewenang pengguna.</p>
                            <div class="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center">
                                <a href="{{ route('internal.users.index') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto">Batal</a>
                                <button type="submit" class="ui-btn ui-btn-primary w-full px-6 py-3 sm:w-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                Simpan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
