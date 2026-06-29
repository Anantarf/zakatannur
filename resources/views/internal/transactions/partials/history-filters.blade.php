@php
    $categoriesList = [];
    foreach ($categories ?? [] as $c) {
        $categoriesList[$c] = \App\Models\ZakatTransaction::CATEGORY_LABELS[$c] ?? strtoupper($c);
    }
    
    $yearsList = [];
    foreach ($years ?? [] as $y) {
        $yearsList[$y] = $y;
    }

    $periodsList = [];
    foreach ($periods ?? [] as $period) {
        $periodsList[$period->id] = $period->display_label . ($period->sequence > 1 ? ' #' . $period->sequence : '');
    }
@endphp

<form method="GET" action="{{ route('internal.transactions.index') }}" class="flex w-full flex-col items-end gap-3 sm:flex-row sm:items-center"
      x-data="{
          submitTimeout: null,
          submitForm() {
              const url = new URL($el.action);
              const formData = new FormData($el);
              const params = new URLSearchParams();
              for (const [key, value] of formData.entries()) {
                  if (value) params.append(key, value);
              }
              url.search = params.toString();
              if (window.swup) {
                  window.swup.navigate(url.href);
              } else {
                  window.location.href = url.href;
              }
          }
      }"
      @submit.prevent="submitForm()">
    
    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari nomor transaksi atau nama..." class="ui-input w-full sm:flex-[1_1_auto]"
           @input="clearTimeout(submitTimeout); submitTimeout = setTimeout(() => submitForm(), 400)"
           @keydown.enter.prevent="submitForm()" />

    <div class="relative w-full sm:min-w-[170px] sm:w-auto sm:flex-none">
        <x-ui-select-custom name="category" :options="$categoriesList" :value="$category ?? ''" placeholder="Semua Kategori" @change="submitForm()" />
    </div>

    <div class="relative w-full sm:min-w-[140px] sm:w-auto sm:flex-none">
        <x-ui-select-custom name="year" :options="$yearsList" :value="$year ?? ''" placeholder="Semua Tahun" @change="submitForm()" />
    </div>

    <div class="relative w-full sm:min-w-[160px] sm:w-auto sm:flex-none">
        <x-ui-select-custom name="period_id" :options="$periodsList" :value="$periodId ?? ''" placeholder="Semua Periode" @change="submitForm()" />
    </div>

    @if (($q ?? null) || ($category ?? null) || ($year ?? null) || ($periodId ?? null))
        <a href="{{ route('internal.transactions.index') }}" class="ui-btn ui-btn-secondary sm:flex-none" @click.prevent="window.swup ? window.swup.navigate($el.href) : window.location.href = $el.href">
            Reset
        </a>
    @endif
</form>
