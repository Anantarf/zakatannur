<form method="GET" action="{{ route('internal.transactions.index') }}" class="flex w-full flex-col items-stretch gap-2 sm:flex-row sm:flex-wrap sm:items-center xl:justify-end">
    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari nomor transaksi atau nama..." class="ui-input w-full sm:min-w-[260px] sm:flex-[1_1_280px] xl:max-w-[320px]" />

    <div class="relative w-full sm:min-w-[170px] sm:flex-[1_1_170px] xl:max-w-[190px]">
        <select name="category" class="ui-select w-full appearance-none pr-10">
            <option value="">Semua Kategori</option>
            @foreach ($categories ?? [] as $c)
                <option value="{{ $c }}" @selected(($category ?? '') === $c)>{{ \App\Models\ZakatTransaction::CATEGORY_LABELS[$c] ?? strtoupper($c) }}</option>
            @endforeach
        </select>
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </div>
    </div>

    <div class="relative w-full sm:min-w-[140px] sm:flex-[0.8_1_140px] xl:max-w-[150px]">
        <select name="year" class="ui-select w-full appearance-none pr-10">
            <option value="">Semua Tahun</option>
            @foreach ($years ?? [] as $y)
                <option value="{{ $y }}" @selected((string) ($year ?? '') === (string) $y)>{{ $y }}</option>
            @endforeach
        </select>
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </div>
    </div>

    <div class="relative w-full sm:min-w-[210px] sm:flex-[1_1_210px] xl:max-w-[240px]">
        <select name="period_id" class="ui-select w-full appearance-none pr-10">
            <option value="">Semua Periode</option>
            @foreach ($periods ?? [] as $period)
                <option value="{{ $period->id }}" @selected((string) ($periodId ?? '') === (string) $period->id)>
                    {{ $period->display_label }}{{ $period->sequence > 1 ? ' #' . $period->sequence : '' }}
                </option>
            @endforeach
        </select>
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </div>
    </div>

    <button type="submit" class="ui-btn ui-btn-primary w-full sm:w-auto sm:flex-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        Terapkan
    </button>

    @if (($q ?? null) || ($category ?? null) || ($year ?? null) || ($periodId ?? null))
        <a href="{{ route('internal.transactions.index') }}" class="ui-btn ui-btn-secondary w-full sm:w-auto sm:flex-none">
            Reset
        </a>
    @endif
</form>
