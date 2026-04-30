<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight flex items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Dashboard Pengelolaan
            </h2>
            <div class="hidden sm:block"></div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-8">
            

            <!-- Daily Trend Chart -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-5 sm:w-2 sm:h-6 bg-emerald-500 rounded-full"></div>
                        <h3 class="font-bold text-sm sm:text-base text-gray-800">Grafik Transaksi Zakat</h3>
                    </div>
                    
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center justify-end gap-2 w-full sm:w-auto">
                        {{-- Keep other filters --}}
                        @if(request('year')) <input type="hidden" name="year" value="{{ request('year') }}"> @endif
                        @if(request('metode')) <input type="hidden" name="metode" value="{{ request('metode') }}"> @endif
                        
                        <select name="days" onchange="this.form.submit()" class="appearance-none rounded-lg border-gray-200 bg-gray-50 pl-3 pr-8 py-1.5 text-[11px] sm:text-xs font-black text-gray-500 uppercase tracking-widest focus:border-emerald-500 focus:ring-emerald-500 transition-all cursor-pointer">
                            <option value="7" @selected($activeDays == 7)>7 Hari</option>
                            <option value="14" @selected($activeDays == 14)>14 Hari</option>
                            <option value="30" @selected($activeDays == 30)>30 Hari</option>
                        </select>
                    </form>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="relative h-[200px] sm:h-[250px] w-full">
                        <canvas id="dailyTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Rekap Table Section -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-5 sm:w-2 sm:h-6 bg-emerald-500 rounded-full"></div>
                        <h3 class="font-bold text-sm sm:text-base text-gray-800">Rekapitulasi Zakat</h3>
                    </div>

                    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                        <!-- Filter Tahun -->
                        <div class="relative w-full sm:w-auto sm:min-w-[120px]">
                            <select name="year" onchange="this.form.submit()" class="w-full appearance-none rounded-lg border-gray-200 bg-gray-50 pl-3 pr-8 py-2 text-sm font-bold text-gray-600 focus:border-emerald-500 focus:ring-emerald-500 transition-all cursor-pointer">
                                <option value="">Semua Waktu</option>
                                @foreach ($years ?? [] as $y)
                                    <option value="{{ $y }}" @selected((string) $year === (string) $y)>Tahun {{ $y }}</option>
                                @endforeach
                            </select>

                        </div>

                        <!-- Filter Bentuk Zakat -->
                        <div class="relative w-full sm:w-auto sm:min-w-[140px]">
                            <select name="metode" onchange="this.form.submit()" class="w-full appearance-none rounded-lg border-gray-200 bg-gray-50 pl-3 pr-8 py-2 text-sm font-bold text-gray-600 focus:border-emerald-500 focus:ring-emerald-500 transition-all cursor-pointer">
                                <option value="">Semua Bentuk</option>
                                @foreach ($methods ?? [] as $m)
                                    <option value="{{ $m }}" @selected((string) $metode === (string) $m)>{{ \App\Models\ZakatTransaction::METHOD_LABELS[$m] ?? strtoupper($m) }}</option>
                                @endforeach
                            </select>

                        </div>

                        @if(request('days')) <input type="hidden" name="days" value="{{ request('days') }}"> @endif
                        
                        @if($year || $metode)
                            <a href="{{ route('dashboard') }}" class="flex items-center justify-center p-1.5 text-gray-400 hover:text-emerald-600 rounded-lg transition-all" title="Reset Filters">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                    </form>
                </div>
                <div class="p-0 overflow-x-auto w-full">
                    @include('internal.dashboard._rekap_table')
                </div>
            </div>

            <!-- Latest Transactions Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-5 sm:w-2 sm:h-6 bg-blue-500 rounded-full"></div>
                        <h3 class="font-bold text-sm sm:text-base text-gray-800">10 Transaksi Terakhir</h3>
                    </div>
                    <a href="{{ route('internal.transactions.index', ['year' => $activeYear]) }}" class="text-xs sm:text-sm font-bold text-blue-600 hover:text-blue-700 transition-colors flex items-center gap-1">
                        Lengkap
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
                <div class="p-0 overflow-x-auto">
                    @include('internal.transactions._latest_table')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    function initChart() {
        try {
            if (typeof Chart === 'undefined') {
                setTimeout(initChart, 100);
                return;
            }
            const canvas = document.getElementById('dailyTrendChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 250);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
            gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartData['labels'] ?? []) !!},
                    datasets: [{
                        label: 'Jumlah Transaksi',
                        data: {!! json_encode($chartData['values'] ?? []) !!},
                        borderColor: '#10b981',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            padding: 12,
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 12 },
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.raw + ' Transaksi';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                stepSize: 1,
                                color: '#64748b',
                                font: { size: 11, weight: '600' }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#64748b',
                                font: { size: 11, weight: '600' }
                            }
                        }
                    }
                }
            });
        } catch (e) {
            console.error("Chart Error: ", e);
            alert("Gagal memuat grafik: " + e.message);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChart);
    } else {
        initChart();
    }
})();
</script>
@endpush
