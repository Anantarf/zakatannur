<form method="GET" action="{{ route('internal.transactions.index') }}" class="flex w-full flex-col flex-wrap items-center gap-2 sm:flex-row" x-data="{ submitTimeout: null }" @submit.prevent>
    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari nomor transaksi atau nama..." class="ui-input w-full sm:min-w-[240px] sm:flex-[1_1_240px]" @input="clearTimeout(submitTimeout); submitTimeout = setTimeout(() => $el.closest('form').submit(), 400)" @keydown.enter="$el.closest('form').submit()" />

    <div class="relative w-full sm:min-w-[150px] sm:flex-[0_1_150px]">
        <select name="category" class="ui-select w-full">
            <option value="">Semua Kategori</option>
            @foreach ($categories ?? [] as $c)
                <option value="{{ $c }}" @selected(($category ?? '') === $c)>{{ \App\Models\ZakatTransaction::CATEGORY_LABELS[$c] ?? strtoupper($c) }}</option>
            @endforeach
        </select>
    </div>

    <div class="relative w-full sm:min-w-[130px] sm:flex-[0_1_130px]">
        <select name="year" class="ui-select w-full">
            <option value="">Semua Tahun</option>
            @foreach ($years ?? [] as $y)
                <option value="{{ $y }}" @selected((string) ($year ?? '') === (string) $y)>{{ $y }}</option>
            @endforeach
        </select>
    </div>

    <div class="relative w-full sm:min-w-[180px] sm:flex-[0_1_180px]">
        <select name="period_id" class="ui-select w-full">
            <option value="">Semua Periode</option>
            @foreach ($periods ?? [] as $period)
                <option value="{{ $period->id }}" @selected((string) ($periodId ?? '') === (string) $period->id)>
                    {{ $period->display_label }}{{ $period->sequence > 1 ? ' #' . $period->sequence : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="flex w-full flex-none items-center gap-2 sm:w-auto">
        <button type="submit" class="ui-btn ui-btn-secondary flex-1 sm:flex-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            Terapkan
        </button>

        @if (($q ?? null) || ($category ?? null) || ($year ?? null) || ($periodId ?? null))
            <a href="{{ route('internal.transactions.index') }}" class="ui-btn ui-btn-secondary flex-1 text-center sm:flex-none">
                Reset
            </a>
        @endif
    </div>
</form>
