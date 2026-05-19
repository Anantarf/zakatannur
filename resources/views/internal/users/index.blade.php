<x-app-layout>
    @php
        $currentUser = auth()->user();
        $totalUsers = method_exists($users, 'total') ? $users->total() : count($users);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Manajemen Pengguna
                </h2>
                <p class="ui-page-title-copy">Kelola akses petugas dan admin tanpa membingungkan operator.</p>
            </div>
            <a href="{{ route('internal.users.create') }}" class="ui-btn ui-btn-primary w-full px-4 py-3 sm:w-auto sm:py-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Pengguna
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="ui-alert ui-alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="ui-alert ui-alert-error">
                    <div class="w-full">
                        <div class="ui-alert-title text-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Periksa:
                        </div>
                        <ul class="list-disc pl-10 space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
                    <div class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Total Akun</div>
                    <div class="mt-1 text-2xl font-black text-emerald-950">{{ $totalUsers }}</div>
                </div>
                <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4">
                    <div class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Akses Anda</div>
                    <div class="mt-1 text-lg font-black text-blue-950">{{ $roleLabels[$currentUser->role] ?? ucfirst($currentUser->role) }}</div>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                    <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Catatan</div>
                    <div class="mt-1 text-sm font-semibold text-slate-700">Admin hanya boleh mengelola petugas.</div>
                </div>
            </div>

            <div class="ui-card overflow-hidden shadow-md">
                <div class="ui-card-header ui-card-header-neutral">
                    <div class="ui-section-accent h-6 w-2"></div>
                    <div>
                        <h3 class="ui-card-header-title">Daftar Pengguna</h3>
                        <p class="text-xs text-slate-500">Role ditampilkan dengan label operasional agar mudah dipahami.</p>
                    </div>
                </div>
                <div class="space-y-3 p-4 md:hidden">
                    @if (count($users) > 0)
                        @foreach ($users as $u)
                            <article class="ui-mobile-card">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold leading-tight text-gray-800">{{ $u->name }}</div>
                                        <div class="mt-1 font-mono text-xs text-gray-500">{{ $u->username }}</div>
                                    </div>
                                    <span class="ui-role-badge {{ $u->role === 'super_admin' ? 'ui-role-badge-super' : ($u->role === 'admin' ? 'ui-role-badge-admin' : 'ui-role-badge-staff') }}">
                                        {{ $roleLabels[$u->role] ?? ucfirst($u->role) }}
                                    </span>
                                </div>

                                <div class="mt-4">
                                    @if($currentUser->canManageUser($u))
                                        <a class="ui-btn ui-btn-secondary w-full px-4 py-3 text-sm text-blue-600 hover:text-blue-800" href="{{ route('internal.users.edit', ['user' => $u->id]) }}">
                                            Ubah Pengguna
                                        </a>
                                    @else
                                        <div class="rounded-xl border border-dashed border-gray-200 px-4 py-3 text-center text-xs font-bold text-gray-300">
                                            Tidak ada aksi
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    @else
                        <div class="ui-empty-state">
                            <div class="flex flex-col items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span class="ui-empty-state-copy">Belum ada pengguna.</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="hidden overflow-x-auto w-full md:block">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                <th class="px-6 py-4">Nama</th>
                                <th class="px-6 py-4">Username</th>
                                <th class="px-6 py-4">Role</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @if (count($users) > 0)
                                @foreach ($users as $u)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-800">{{ $u->name }}</td>
                                    <td class="px-6 py-4 text-gray-500 font-mono text-xs">{{ $u->username }}</td>
                                    <td class="px-6 py-4">
                                        <span class="ui-role-badge {{ $u->role === 'super_admin' ? 'ui-role-badge-super' : ($u->role === 'admin' ? 'ui-role-badge-admin' : 'ui-role-badge-staff') }}">
                                            {{ $roleLabels[$u->role] ?? ucfirst($u->role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        @if($currentUser->canManageUser($u))
                                            <a class="ui-btn ui-btn-secondary px-3 py-2 text-xs text-blue-700" href="{{ route('internal.users.edit', ['user' => $u->id]) }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Ubah
                                            </a>
                                        @else
                                            <span class="inline-flex rounded-full border border-dashed border-gray-200 px-3 py-1 text-xs font-bold text-gray-300">Tidak tersedia</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-200 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            <span class="text-sm font-medium text-gray-400">Belum ada pengguna.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if ($users->hasPages())
                    <div class="px-6 py-4 border-t border-gray-50">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
