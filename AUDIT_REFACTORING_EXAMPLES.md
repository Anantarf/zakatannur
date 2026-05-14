# CONTOH REFACTORING - BEFORE & AFTER

## Contoh 1: Timezone Extraction

### ❌ BEFORE - Hardcoded Timezone (Current Code)

**File:** `app/Services/ZakatService.php`

```php
private function parseWaktuTerima(?string $input, ?string $noTransaksiOverride = null): Carbon
{
    if ($input) {
        return Carbon::parse($input, 'Asia/Jakarta')->startOfMinute();  // ❌ HARDCODED
    }

    if ($noTransaksiOverride) {
        $existing = ZakatTransaction::where('no_transaksi', $noTransaksiOverride)->value('waktu_terima');
        if ($existing) return Carbon::parse($existing, 'Asia/Jakarta')->startOfMinute();  // ❌ HARDCODED
    }

    return now('Asia/Jakarta')->startOfMinute();  // ❌ HARDCODED
}
```

**File:** `app/Http/Controllers/Internal/DashboardController.php`

```php
$chartStart = now('Asia/Jakarta')->subDays($activeDays)->startOfDay();  // ❌ HARDCODED
$endBoundary = $chartEnd ?? now('Asia/Jakarta')->endOfDay();  // ❌ HARDCODED
```

**File:** `app/Models/Muzakki.php`

```php
public function getDaysLeftAttribute(): ?int
{
    if (!$this->deleted_at) return null;

    $purgeDays = 30;  // ❌ HARDCODED
    $deletedAt = $this->deleted_at->timezone('Asia/Jakarta');  // ❌ HARDCODED
    return max(0, $purgeDays - (int) $deletedAt->startOfDay()->diffInDays(now('Asia/Jakarta')->startOfDay()));  // ❌ HARDCODED
}
```

### ✅ AFTER - Using Config

**Step 1: Update `config/app.php`**

```php
return [
    // ... existing config ...
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),  // ✅ Configurable
];
```

**Step 2: Update `.env` file**

```bash
APP_TIMEZONE=Asia/Jakarta  # ✅ Configurable per environment
```

**File:** `app/Services/ZakatService.php` - FIXED

```php
private function parseWaktuTerima(?string $input, ?string $noTransaksiOverride = null): Carbon
{
    $timezone = config('app.timezone');  // ✅ From config

    if ($input) {
        return Carbon::parse($input, $timezone)->startOfMinute();  // ✅ Uses config
    }

    if ($noTransaksiOverride) {
        $existing = ZakatTransaction::where('no_transaksi', $noTransaksiOverride)->value('waktu_terima');
        if ($existing) return Carbon::parse($existing, $timezone)->startOfMinute();  // ✅ Uses config
    }

    return now($timezone)->startOfMinute();  // ✅ Uses config
}
```

**File:** `app/Http/Controllers/Internal/DashboardController.php` - FIXED

```php
$timezone = config('app.timezone');  // ✅ Define once
$chartStart = now($timezone)->subDays($activeDays)->startOfDay();  // ✅ Uses config
$endBoundary = $chartEnd ?? now($timezone)->endOfDay();  // ✅ Uses config
```

**File:** `app/Models/Muzakki.php` - FIXED

```php
public function getDaysLeftAttribute(): ?int
{
    if (!$this->deleted_at) return null;

    $purgeDays = config('zakat.purge_days', 30);  // ✅ Configurable with default
    $timezone = config('app.timezone');  // ✅ From config
    $deletedAt = $this->deleted_at->timezone($timezone);  // ✅ Uses config
    return max(0, $purgeDays - (int) $deletedAt->startOfDay()->diffInDays(now($timezone)->startOfDay()));  // ✅ All from config
}
```

---

## Contoh 2: Magic Numbers Configuration

### ❌ BEFORE - Scattered Magic Numbers

**File:** `app/Models/Muzakki.php` line 31

```php
$purgeDays = 30;  // ❌ Magic number, not configurable
```

**File:** `app/Services/ZakatService.php` line 11

```php
private const NO_TRANSAKSI_RETRY_ATTEMPTS = 5;  // ❌ Hard to change
```

**File:** `app/Services/ZakatService.php` line 63

```php
$lock = Cache::lock($lockName, 30);  // ❌ 30 second timeout - hardcoded
```

**File:** `app/Services/ZakatService.php` line 265

```php
return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);  // ❌ 4 digit padding
```

**File:** `app/Http/Controllers/Internal/DashboardController.php` line 41

```php
$payload = Cache::remember("dashboard_rekap_{$yearKey}_{$metodeKey}", 300, ...);  // ❌ 300 seconds
```

### ✅ AFTER - Centralized Configuration

**Step 1: Create `config/zakat.php`**

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Transaction Number Configuration
    |--------------------------------------------------------------------------
    */
    'transaction' => [
        'prefix' => env('TRANSACTION_PREFIX', 'TRX-'),
        'number_padding' => env('TRANSACTION_NUMBER_PADDING', 4),
        'retry_attempts' => env('TRANSACTION_RETRY_ATTEMPTS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention Configuration
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'purge_days' => env('PURGE_DAYS', 30),  // Days before hard-delete soft-deleted items
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock & Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'lock_timeout_seconds' => env('LOCK_TIMEOUT_SECONDS', 30),
        'dashboard_ttl_seconds' => env('DASHBOARD_CACHE_TTL', 300),  // Normal season
        'dashboard_offseason_ttl_seconds' => env('DASHBOARD_OFFSEASON_CACHE_TTL', 3600),  // Off-season
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values (Annual Settings Defaults)
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'fitrah_cash_per_jiwa' => env('DEFAULT_FITRAH_CASH', 50000),
        'fitrah_beras_per_jiwa' => env('DEFAULT_FITRAH_BERAS', 2.50),
        'fidyah_per_hari' => env('DEFAULT_FIDYAH_CASH', 50000),
        'fidyah_beras_per_hari' => env('DEFAULT_FIDYAH_BERAS', 0.75),
    ],
];
```

**Step 2: Update `.env`**

```bash
# Transaction Configuration
TRANSACTION_PREFIX=TRX-
TRANSACTION_NUMBER_PADDING=4
TRANSACTION_RETRY_ATTEMPTS=5

# Retention
PURGE_DAYS=30

# Cache & Locking
LOCK_TIMEOUT_SECONDS=30
DASHBOARD_CACHE_TTL=300
DASHBOARD_OFFSEASON_CACHE_TTL=3600

# Defaults
DEFAULT_FITRAH_CASH=50000
DEFAULT_FITRAH_BERAS=2.50
DEFAULT_FIDYAH_CASH=50000
DEFAULT_FIDYAH_BERAS=0.75
```

**File:** `app/Models/Muzakki.php` - FIXED

```php
public function getDaysLeftAttribute(): ?int
{
    if (!$this->deleted_at) return null;

    $purgeDays = config('zakat.retention.purge_days');  // ✅ From config
    $timezone = config('app.timezone');
    $deletedAt = $this->deleted_at->timezone($timezone);
    return max(0, $purgeDays - (int) $deletedAt->startOfDay()->diffInDays(now($timezone)->startOfDay()));
}
```

**File:** `app/Services/ZakatService.php` - FIXED

```php
// No const needed, use config instead:
// Remove: private const NO_TRANSAKSI_RETRY_ATTEMPTS = 5;

private function executeWithRetry(\Closure $callback)
{
    $attempts = 0;
    $maxAttempts = config('zakat.transaction.retry_attempts');  // ✅ From config

    while ($attempts < $maxAttempts) {
        $attempts++;
        try {
            return DB::transaction($callback);
        } catch (QueryException $e) {
            if ($e->getCode() === '40001' || $e->errorInfo[1] === 1213) continue;
            throw $e;
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Nomor Transaksi') && str_contains($e->getMessage(), 'sudah terpakai')) {
                continue;
            }
            throw $e;
        }
    }
    throw new \RuntimeException("Gagal memproses transaksi setelah beberapa kali percobaan...");
}

private function performSync(array $data, ...): array
{
    $lockName = 'sync_tx_' . $waktuTerima->format('Ymd');
    $lockTimeout = config('zakat.cache.lock_timeout_seconds');  // ✅ From config
    $lock = Cache::lock($lockName, $lockTimeout);  // ✅ Uses config

    // ... rest of method
}

private function generateNoTransaksi(Carbon $time): string
{
    $prefix = config('zakat.transaction.prefix') . $time->format('Ymd') . '-';  // ✅ Uses config
    $last = ZakatTransaction::withTrashed()
        ->where('no_transaksi', 'like', $prefix . '%')
        ->orderByRaw(...)
        ->value('no_transaksi');

    $seq = ($last && preg_match('/(\d{4})$/', $last, $matches))
        ? (int) $matches[1] + 1
        : 1;

    $padding = config('zakat.transaction.number_padding');  // ✅ From config
    return $prefix . str_pad((string) $seq, $padding, '0', STR_PAD_LEFT);
}
```

**File:** `app/Http/Controllers/Internal/DashboardController.php` - FIXED

```php
$cacheTtl = config('zakat.cache.dashboard_ttl_seconds');  // ✅ From config
$payload = Cache::remember(
    "dashboard_rekap_{$yearKey}_{$metodeKey}",
    $cacheTtl,  // ✅ Uses config
    fn() => RekapBuilder::build($year, $metode)
);

// For off-season:
$offSeasonCacheTtl = config('zakat.cache.dashboard_offseason_ttl_seconds');  // ✅ From config
$offSeasonData = Cache::remember(
    $offSeasonCacheKey,
    $offSeasonCacheTtl,  // ✅ Uses config
    function () use ($activeYear) { ... }
);
```

---

## Contoh 3: Adding Return Type Hints

### ❌ BEFORE - No Return Types

**File:** `app/Http/Controllers/Internal/ZakatTransactionController.php`

```php
public function create(Request $request)  // ❌ No return type
{
    return view('internal.transactions.create', [...]);
}

public function store(StoreZakatTransactionRequest $request, \App\Services\ZakatService $service)  // ❌ No return type
{
    $results = $service->storeTransaction($data, $request->user()->id);
    return redirect()->route('internal.transactions.show', ...)->with(...);
}

public function show(Request $request, int $transaction)  // ❌ No return type
{
    return view('internal.transactions.show', [...]);
}
```

### ✅ AFTER - With Return Types

**File:** `app/Http/Controllers/Internal/ZakatTransactionController.php` - FIXED

```php
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

public function create(Request $request): View  // ✅ Return type added
{
    return view('internal.transactions.create', [...]);
}

public function store(
    StoreZakatTransactionRequest $request,
    \App\Services\ZakatService $service
): RedirectResponse  // ✅ Return type added
{
    $results = $service->storeTransaction($data, $request->user()->id);
    return redirect()->route('internal.transactions.show', ...)->with(...);
}

public function show(Request $request, int $transaction): View  // ✅ Return type added
{
    return view('internal.transactions.show', [...]);
}
```

---

## Contoh 4: Extracting Duplication - Transaction Fetching

### ❌ BEFORE - Duplicated in 5 Places

**File:** `app/Http/Controllers/Internal/ZakatTransactionController.php` line 64

```php
$transactions = ZakatTransaction::query()
    ->with(['muzakki' => fn($q) => $q->withTrashed()])
    ->where('no_transaksi', $tx->no_transaksi)
    ->orderBy('id', 'asc')
    ->get();

if ($transactions->isEmpty()) {
    $transactions = ZakatTransaction::withTrashed()
        ->with(['muzakki' => fn($q) => $q->withTrashed()])
        ->where('no_transaksi', $tx->no_transaksi)
        ->get();
}
```

**File:** `app/Http/Controllers/Internal/ZakatTransactionController.php` line 98

```php
$transactions = ZakatTransaction::query()
    ->with(['muzakki' => fn($q) => $q->withTrashed()])
    ->where('no_transaksi', $tx->no_transaksi)
    ->where('status', ZakatTransaction::STATUS_VALID)
    ->orderBy('id', 'asc')
    ->get();

if ($transactions->isEmpty()) {
    $transactions = ZakatTransaction::withTrashed()
        ->with(['muzakki' => fn($q) => $q->withTrashed()])
        ->where('no_transaksi', $tx->no_transaksi)
        ->where('status', ZakatTransaction::STATUS_VALID)
        ->get();
}
```

### ✅ AFTER - Single Scope in Model

**File:** `app/Models/ZakatTransaction.php` - ADD SCOPE

```php
/**
 * Get transactions by transaction number, optionally including trashed records.
 * Falls back to withTrashed if no active records exist.
 */
public function scopeByTransactionNumber(
    Builder $query,
    string $noTransaksi,
    bool $includeInvalid = false,
    bool $fallbackToTrashed = true
): Builder
{
    $query = $query
        ->with(['muzakki' => fn($q) => $q->withTrashed()])
        ->where('no_transaksi', $noTransaksi)
        ->orderBy('id', 'asc');

    if (!$includeInvalid) {
        $query->where('status', self::STATUS_VALID);
    }

    // Fallback to trashed if no active records
    if ($fallbackToTrashed && $query->doesntExist()) {
        $query = self::withTrashed()
            ->with(['muzakki' => fn($q) => $q->withTrashed()])
            ->where('no_transaksi', $noTransaksi)
            ->orderBy('id', 'asc');

        if (!$includeInvalid) {
            $query->where('status', self::STATUS_VALID);
        }
    }

    return $query;
}
```

**File:** `app/Http/Controllers/Internal/ZakatTransactionController.php` - REFACTORED

```php
public function show(Request $request, int $transaction): View
{
    $tx = ZakatTransaction::withTrashed()->findOrFail($transaction);

    // ✅ MUCH CLEANER - uses scope
    $transactions = ZakatTransaction::byTransactionNumber($tx->no_transaksi)->get();

    // ... rest of method
}

public function receipt(Request $request, int $transaction)
{
    $user = $request->user();
    $tx = ZakatTransaction::withTrashed()->findOrFail($transaction);

    // ✅ MUCH CLEANER - uses scope with valid filter
    $transactions = ZakatTransaction::byTransactionNumber(
        $tx->no_transaksi,
        includeInvalid: false  // Only STATUS_VALID
    )->get();

    // ... rest of method
}
```

---

## Kesimpulan

Dengan 4 contoh di atas, Anda bisa melihat:

1. **Timezone:** Replace hardcoded strings dengan config()
2. **Magic Numbers:** Create config/zakat.php dan gunakan config()
3. **Type Hints:** Add return types ke semua methods
4. **Duplication:** Extract ke model scopes

Ini adalah pattern yang dipakai untuk fixing semua issues dalam audit report.
