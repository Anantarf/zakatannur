<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1 text-center sm:text-left">
                <h2 class="ui-page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-page-title-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.88 6.196 9 9 0 015.12 17.804z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Profil Muzakki
                </h2>
                <p class="ui-page-title-copy">Ringkasan relasi, kontribusi, dan riwayat transaksi.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('internal.muzakki.edit', ['muzakki' => $muzakki->id]) }}" class="ui-btn ui-btn-primary">Ubah Data</a>
                <a href="{{ route('internal.muzakki.index') }}" class="ui-btn ui-btn-secondary">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="ui-alert ui-alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="ui-alert-icon text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            @endif

            <x-form-errors />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_1.35fr]">
                <section class="ui-card overflow-hidden">
                    <div class="ui-card-header ui-card-header-emerald">
                        <svg xmlns="http://www.w3.org/2000/svg" class="ui-card-header-icon text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <h3 class="ui-card-header-title text-emerald-900">Identitas</h3>
                    </div>
                    <div class="space-y-5 p-5 sm:p-6">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Nama</p>
                            <p class="mt-1 text-2xl font-black text-slate-950">{{ $muzakki->name }}</p>
                        </div>
                        <div class="grid grid-cols-1 gap-3 text-sm">
                            <div class="ui-card-muted px-4 py-3">
                                <p class="text-xs font-bold text-slate-500">No HP</p>
                                <p class="mt-1 font-bold text-slate-800">{{ $muzakki->phone ?: '-' }}</p>
                            </div>
                            <div class="ui-card-muted px-4 py-3">
                                <p class="text-xs font-bold text-slate-500">Alamat</p>
                                <p class="mt-1 leading-relaxed text-slate-800">{{ $muzakki->address ?: '-' }}</p>
                            </div>
                        </div>

                        @php
                            $segmentTone = [
                                'success' => 'border-emerald-100 bg-emerald-50 text-emerald-800',
                                'info' => 'border-sky-100 bg-sky-50 text-sky-800',
                                'warning' => 'border-amber-100 bg-amber-50 text-amber-800',
                                'muted' => 'border-slate-100 bg-slate-50 text-slate-700',
                            ][$summary['segment']['tone'] ?? 'muted'] ?? 'border-slate-100 bg-slate-50 text-slate-700';
                        @endphp
                        <div class="rounded-2xl border px-4 py-3 {{ $segmentTone }}">
                            <p class="text-xs font-black uppercase tracking-[0.16em]">Segmentasi</p>
                            <p class="mt-1 text-lg font-black">{{ $summary['segment']['label'] }}</p>
                            <p class="mt-1 text-sm opacity-80">{{ $summary['segment']['description'] }}</p>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui-stat-card title="Total Uang" :value="$summary['total_uang_display']" description="Akumulasi transaksi valid." />
                    <x-ui-stat-card title="Total Beras" :value="$summary['total_beras_display']" tone="info" description="Akumulasi transaksi beras." />
                    <x-ui-stat-card title="Jumlah Transaksi" :value="number_format($summary['transaction_count'], 0, ',', '.')" tone="muted" description="Transaksi valid terkait muzakki ini." />
                    <x-ui-stat-card title="Terakhir Transaksi" :value="$summary['last_transaction_at']" tone="warning" description="Tanggal transaksi valid terbaru." />
                </section>
            </div>

            @if ($possible_duplicates->isNotEmpty())
                <section class="ui-alert ui-alert-warning">
                    <div class="w-full">
                        <p class="font-black text-amber-900">Kemungkinan data ganda</p>
                        <p class="mt-1 text-sm text-amber-800">Ada muzakki lain dengan nama atau nomor HP yang mirip. Belum digabung otomatis agar riwayat tetap aman.</p>
                        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                            @foreach ($possible_duplicates as $duplicate)
                                <div class="rounded-2xl border border-amber-200 bg-white p-3">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <a href="{{ route('internal.muzakki.show', ['muzakki' => $duplicate->id]) }}" class="text-sm font-black text-amber-900 hover:text-amber-700">
                                                {{ $duplicate->name }}
                                            </a>
                                            <p class="text-xs text-amber-700">{{ $duplicate->phone ?: '-' }}</p>
                                        </div>
                                        @if (auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin'], true))
                                            <form method="POST" action="{{ route('internal.muzakki.merge', ['muzakki' => $muzakki->id]) }}" class="space-y-2 sm:min-w-[220px]">
                                                @csrf
                                                <input type="hidden" name="duplicate_id" value="{{ $duplicate->id }}">
                                                <input type="text" name="confirm_name" class="ui-input w-full px-3 py-2 text-xs" placeholder="Ketik: {{ $muzakki->name }}" aria-label="Konfirmasi nama target merge">
                                                <button type="submit" class="ui-btn ui-btn-danger w-full px-3 py-2 text-xs">
                                                    Gabungkan ke Profil Ini
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            <section class="ui-card overflow-hidden">
                <div class="ui-card-header ui-card-header-neutral">
                    <h3 class="ui-card-header-title text-slate-900">Ringkasan Per Periode</h3>
                </div>
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-400">
                            <tr>
                                <th class="px-6 py-4">Periode</th>
                                <th class="px-6 py-4">Transaksi</th>
                                <th class="px-6 py-4">Uang</th>
                                <th class="px-6 py-4">Beras</th>
                                <th class="px-6 py-4">Terakhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($periods as $period)
                                <tr class="transition-colors hover:bg-slate-50/70">
                                    <td class="px-6 py-4 font-bold text-slate-800">{{ $period['label'] }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $period['count'] }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $period['total_uang_display'] }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $period['total_beras_display'] }}</td>
                                    <td class="px-6 py-4 text-slate-500">{{ $period['last_at'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm font-bold text-slate-400">Belum ada riwayat periode.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="space-y-3 p-4 md:hidden">
                    @forelse ($periods as $period)
                        <article class="ui-mobile-card">
                            <p class="font-black text-slate-900">{{ $period['label'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $period['count'] }} transaksi - terakhir {{ $period['last_at'] }}</p>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div class="rounded-xl bg-emerald-50 px-3 py-2 font-bold text-emerald-800">{{ $period['total_uang_display'] }}</div>
                                <div class="rounded-xl bg-sky-50 px-3 py-2 font-bold text-sky-800">{{ $period['total_beras_display'] }}</div>
                            </div>
                        </article>
                    @empty
                        <div class="ui-empty-state-box">
                            <p class="ui-empty-state-copy">Belum ada riwayat periode.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="ui-card overflow-hidden">
                <div class="ui-card-header ui-card-header-emerald">
                    <h3 class="ui-card-header-title text-emerald-900">10 Transaksi Terakhir</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recent_transactions as $tx)
                        <a href="{{ route('internal.transactions.show', ['transaction' => $tx->id]) }}" class="block px-5 py-4 transition hover:bg-slate-50 sm:px-6">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-black text-slate-900">{{ $tx->no_transaksi }}</p>
                                    <p class="text-xs text-slate-500">{{ $tx->zakatPeriod?->display_label ?? $tx->tahun_zakat }} - {{ $tx->category_label }} - {{ $tx->metode_label }}</p>
                                </div>
                                <div class="text-sm font-bold text-slate-700">
                                    {{ $tx->metode === \App\Models\ZakatTransaction::METHOD_BERAS ? $tx->jumlah_beras_kg_display : $tx->nominal_uang_display }}
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="ui-empty-state">
                            <p class="ui-empty-state-copy">Belum ada transaksi valid untuk muzakki ini.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
