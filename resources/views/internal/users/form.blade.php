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

    <div class="py-4 sm:py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="ui-alert ui-alert-success mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <x-form-errors />

            <div class="ui-card overflow-hidden shadow-md">
                @if ($user)
                    <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-4 py-3 sm:px-6">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold uppercase text-brand-700">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-700">{{ $user->name }}</div>
                            <div class="text-xs font-medium text-slate-500">{{ '@' . $user->username }} &middot; {{ $roleLabels[$user->role] ?? ucfirst($user->role) }}</div>
                        </div>
                    </div>
                @endif

                <div class="p-4 sm:p-5">
                    <form method="POST" action="{{ $user ? route('internal.users.update', ['user' => data_get($user, 'id')]) : route('internal.users.store') }}" class="space-y-4">
                        @csrf
                        @if ($user)
                            @method('PATCH')
                        @endif

                        <!-- Identitas Pengguna Section -->
                        <div>
                            <h3 class="mb-2 text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">Identitas Pengguna</h3>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="ui-form-label" for="name">Nama Lengkap</label>
                                    <input id="name" name="name" type="text" value="{{ old('name', data_get($user, 'name', '')) }}" maxlength="{{ $nameMax }}" class="ui-input w-full block" required />
                                    <x-input-error class="mt-1" :messages="$errors->get('name')" />
                                </div>

                                <div>
                                    <label class="ui-form-label" for="username">Username</label>
                                    <input id="username" name="username" type="text" value="{{ old('username', data_get($user, 'username', '')) }}" maxlength="{{ $usernameMax }}" class="ui-input w-full block" required />
                                    <x-input-error class="mt-1" :messages="$errors->get('username')" />
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2">
                                <div>
                                    <label class="ui-form-label" for="role">Role</label>
                                    @php
                                        $roleOpts = [];
                                        foreach ($allowedRoles as $r) {
                                            $roleOpts[$r] = $roleLabels[$r] ?? ucfirst($r);
                                        }
                                    @endphp
                                    <x-ui-select-custom name="role" :options="$roleOpts" :value="old('role', data_get($user, 'role', ''))" required />
                                    <x-input-error class="mt-1" :messages="$errors->get('role')" />
                                </div>
                            </div>
                        </div>

                        <!-- Keamanan & Password Section -->
                        <div>
                            <h3 class="mb-2 text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">Keamanan & Password</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2">
                                <div>
                                    <x-password-input
                                        name="password"
                                        :label="'Kata Sandi' . ($user ? ' (Opsional)' : '')"
                                        hint="{{ $user ? 'Kosongkan jika tidak ingin ganti.' : '' }}"
                                        :required="!$user" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col-reverse sm:flex-row items-center justify-between gap-3 pt-2">
                            <div class="w-full sm:w-auto">
                                @if ($user && auth()->id() !== $user->id && auth()->user()->canManageUser($user))
                                    <button type="button" class="ui-btn bg-white text-red-600 border-red-200 hover:bg-red-50 hover:border-red-300 w-full sm:w-auto" onclick="if(confirm('Apakah Anda yakin ingin menghapus pengguna ini?')) document.getElementById('delete-form').submit();">
                                        Hapus Pengguna
                                    </button>
                                @endif
                            </div>
                            <div class="flex flex-col-reverse sm:flex-row gap-3 w-full sm:w-auto">
                                <a href="{{ route('internal.users.index') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto text-center">Batal</a>
                                <button type="submit" class="ui-btn ui-btn-primary w-full sm:w-auto">
                                    Simpan
                                </button>
                            </div>
                        </div>
                    </form>

                    @if ($user && auth()->id() !== $user->id)
                        <form id="delete-form" action="{{ route('internal.users.destroy', ['user' => $user->id]) }}" method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
