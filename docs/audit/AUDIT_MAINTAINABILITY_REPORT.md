# 🔍 AUDIT MAINTAINABILITY CODEBASE ZAKAT LARAVEL

**Tanggal:** 14 Mei 2026  
**Status:** CRITICAL ISSUES DITEMUKAN  
**Prioritas:** 1=CRITICAL, 2=HIGH, 3=MEDIUM, 4=LOW

---

## 📊 RINGKASAN EKSEKUTIF

| Kategori              | Jumlah       | Severity |
| --------------------- | ------------ | -------- |
| Magic Strings/Numbers | 15+          | CRITICAL |
| Complex Methods       | 12+          | HIGH     |
| Code Duplication      | 8+ patterns  | HIGH     |
| Missing Type Hints    | 20+ methods  | MEDIUM   |
| Timezone Issues       | 12+ files    | CRITICAL |
| Missing Documentation | 25+ methods  | MEDIUM   |
| Validation Scattered  | 4+ locations | HIGH     |

---

## 🔴 PRIORITAS 1: CRITICAL ISSUES

### 1.1 HARDCODED 'Asia/Jakarta' TIMEZONE (12+ Lokasi)

**Severity:** CRITICAL | **Impact:** Go-live nightmare  
**Issue:** Timezone hardcoded di kode, tidak menggunakan `config('app.timezone')`

**Lokasi:**

- [ZakatService.php](ZakatService.php#L178) line 178, 183, 186
- [TransactionHistoryController.php](app/Http/Controllers/Internal/TransactionHistoryController.php#L147) line 147, 148, 204, 230
- [DashboardController.php](app/Http/Controllers/Internal/DashboardController.php#L65) line 65, 85, 96, 105
- [ExportController.php](app/Http/Controllers/Internal/ExportController.php#L28) line 28, 29
- [ZakatTransactionPolicy.php](app/Policies/ZakatTransactionPolicy.php#L35) line 35
- [ReceiptPdf.php](app/Support/ReceiptPdf.php#L58) line 58, 59
- [Muzakki.php](app/Models/Muzakki.php#L34) line 34, 35, 47

**Masalah:**

```php
// ❌ SALAH - di 12+ tempat
return Carbon::parse($input, 'Asia/Jakarta')->startOfMinute();
now('Asia/Jakarta')->subDays(30)
$this->deleted_at->timezone('Asia/Jakarta')
```

**Rekomendasi Perbaikan:**

```php
// ✅ BENAR - buat config
// config/timezone.php atau tambah ke config/app.php
'app_timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),

// Kemudian gunakan di mana-mana:
Carbon::parse($input, config('app_timezone'))
now(config('app_timezone'))
$this->deleted_at->timezone(config('app_timezone'))
```

**File yang Perlu Diubah:**

```
ZakatService.php (3 lokasi)
TransactionHistoryController.php (4 lokasi)
DashboardController.php (4 lokasi)
ExportController.php (2 lokasi)
ZakatTransactionPolicy.php (1 lokasi)
ReceiptPdf.php (2 lokasi)
Muzakki.php (3 lokasi)
GuestSummaryController.php (1 lokasi)
```

---

### 1.2 MAGIC NUMBERS SCATTERED DI CODEBASE

**Severity:** CRITICAL | **Impact:** Configuration tidak fleksibel

**Purge Days (30) - 3 Lokasi:**

- [Muzakki.php](app/Models/Muzakki.php#L31) line 31: `$purgeDays = 30;`
- [TransactionHistoryController.php](app/Http/Controllers/Internal/TransactionHistoryController.php#L125) line 125: `$purgeDays = 30;`
- Tidak ada dokumentasi PURGE_DAYS env

**Lock Timeout (30 detik):**

- [ZakatService.php](app/Services/ZakatService.php#L63) line 63: `$lock = Cache::lock($lockName, 30);`

**Retry Attempts (5):**

- [ZakatService.php](app/Services/ZakatService.php#L11) line 11: `NO_TRANSAKSI_RETRY_ATTEMPTS = 5`

**Cache TTL (300s, 3600s):**

- [DashboardController.php](app/Http/Controllers/Internal/DashboardController.php#L41) line 41: `Cache::remember(..., 300, ...)`
- [DashboardController.php](app/Http/Controllers/Internal/DashboardController.php#L62) line 62: `Cache::remember(..., 3600, ...)`

**Transaction Number Padding (4 digit):**

- [ZakatService.php](app/Services/ZakatService.php#L265) line 265: `str_pad((string) $seq, 4, '0', STR_PAD_LEFT)`

**Default Values (Hardcoded di Controller):**

- [PeriodSettingsController.php](app/Http/Controllers/Internal/PeriodSettingsController.php#L23) line 23-27:

```php
'default_fitrah_cash_per_jiwa' => 50000,
'default_fitrah_beras_per_jiwa' => 2.50,
'default_fidyah_per_hari' => 50000,
'default_fidyah_beras_per_hari' => 0.75,
```

**Rekomendasi:**

```bash
# config/zakat.php (BUAT FILE BARU)
return [
    'purge_days' => env('PURGE_DAYS', 30),
    'lock_timeout_seconds' => env('LOCK_TIMEOUT_SECONDS', 30),
    'retry_attempts' => env('RETRY_ATTEMPTS', 5),
    'transaction_number_padding' => 4,
    'cache_ttl' => [
        'dashboard_normal' => 300,
        'dashboard_offseason' => 3600,
    ],
    'prefix' => [
        'transaction_number' => 'TRX-',
    ],
    'defaults' => [
        'fitrah_cash_per_jiwa' => 50000,
        'fitrah_beras_per_jiwa' => 2.50,
        'fidyah_per_hari' => 50000,
        'fidyah_beras_per_hari' => 0.75,
    ],
];
```

---

### 1.3 COMPLEX METHOD: ZakatService::performSync() (90+ LINES)

**Severity:** CRITICAL | **Impact:** Sulit ditest, bug-prone  
**File:** [ZakatService.php](app/Services/ZakatService.php#L61) lines 61-120

**Masalah:**

- 90+ lines dalam 1 method
- Nested database transactions
- Lock management
- Calculation logic tercampur
- Sulit ditest secara unit

**Kompleksitas:**

```php
// ❌ TERLALU KOMPLEKS
private function performSync(array $data, array $items, int $petugasId, Carbon $waktuTerima, ?string $noTransaksiOverride): array
{
    // Lock acquisition
    // No transaksi generation/validation
    // Muzakki creation
    // Item processing (nested loop)
    // Deletion logic
    // Audit logging
    // ... 90 lines total
}
```

**Rekomendasi Refactor:**

```php
// Pisah ke beberapa method:
1. validateOrGenerateNoTransaksi()
2. createOrUpdateMuzakki()
3. calculateTransactionDeltas()
4. processAndPersistItems()
5. deleteRemovedItems()
6. logAuditEvent()

// performSync hanya orchrestrate:
private function performSync(...): array
{
    $lock = $this->acquireLock($waktuTerima);
    try {
        $noTransaksi = $this->validateOrGenerateNoTransaksi(...);
        $this->createOrUpdateMuzakki($data);
        $deltas = $this->calculateTransactionDeltas($noTransaksi, $data);
        [$results, $newIds] = $this->processAndPersistItems(...);
        $this->deleteRemovedItems(...);
        $this->logAuditEvent(...);
        return $results;
    } finally {
        $lock->release();
    }
}
```

---

### 1.4 COMPLEX METHOD: ExportController::exportDaily() (300+ LINES)

**Severity:** CRITICAL | **Impact:** Tidak sustainable, sulit di-maintain  
**File:** [ExportController.php](app/Http/Controllers/Internal/ExportController.php#L18) lines 18-260+

**Masalah:**

- 300+ lines dalam 1 method
- Raw SQL dengan 30+ lines dan 12 CASE statements
- Styling logic tercampur
- Business logic tercampur
- Sulit ditest

**Raw SQL Query (30+ lines):**

```php
// ❌ TIDAK MAINTAINABLE
$transactions = $this->fetchDailyTransactions($start, $end);
// ... methodnya 30 line raw SQL dengan 12 CASE statements
```

**Rekomendasi:**

```php
// Buat dedicated QueryBuilder atau Repository:
// app/Repositories/DailyExportRepository.php
class DailyExportRepository
{
    public function fetchWithSummaries(Carbon $start, Carbon $end)
    {
        return ZakatTransaction::query()
            ->select([...])
            ->where('status', STATUS_VALID)
            ->whereDateBetween(...)
            ->get();
    }
}

// Pisah styling logic:
// app/Support/ExcelStyler.php
class ExcelStyler
{
    public static function styleHeaders($sheet) { ... }
    public static function styleDataRows($sheet, $range) { ... }
    public static function styleTotals($sheet, $range) { ... }
}

// Method baru jauh lebih ringkas:
public function exportDaily(Request $request)
{
    $request->validate(['date' => 'required|date_format:Y-m-d']);

    $transactions = $this->repo->fetchWithSummaries(...);
    $spreadsheet = $this->createSpreadsheet('Rekap Harian');

    ExcelStyler::styleHeaders($sheet);
    $this->writeTransactionRows($sheet, $transactions);
    ExcelStyler::styleTotals($sheet, ...);

    return $this->downloadSpreadsheet($spreadsheet, ...);
}
```

---

### 1.5 COMPLEX METHOD: DashboardController::show() (145+ LINES)

**Severity:** HIGH | **Impact:** Sulit debug, banyak state  
**File:** [DashboardController.php](app/Http/Controllers/Internal/DashboardController.php#L20) lines 20-145+

**Masalah:**

- 145+ lines dengan complex cache logic
- Off-season detection tercampur dengan chart data logic
- Multiple database queries
- Cache keys tidak konsisten
- Condition logic nested-deep

**Rekomendasi:**

```php
// Buat service class:
// app/Services/DashboardService.php
class DashboardService
{
    public function getPayload(int $year, ?string $metode)
    {
        return Cache::remember("dashboard_rekap_...", 300,
            fn() => RekapBuilder::build($year, $metode)
        );
    }

    public function getOffSeasonData(int $year): array
    {
        return Cache::remember("dashboard_offseason_...", 3600,
            fn() => $this->detectOffSeason($year)
        );
    }

    public function getChartData(int $year, Carbon $end = null, int $days = 14): array
    {
        // Separate chart logic
    }

    public function getLatestTransactions(int $year = null, string $metode = null)
    {
        // Separate transactions query
    }
}

// Controller hanya orchestrate:
public function show(Request $request)
{
    $filters = RekapFilters::fromRequest($request);

    return view('dashboard', [
        'payload' => $this->dashboard->getPayload($filters['year'], $filters['metode']),
        'offSeason' => $this->dashboard->getOffSeasonData($filters['year']),
        'chartData' => $this->dashboard->getChartData(...),
        'transactions' => $this->dashboard->getLatestTransactions(...),
        ...
    ]);
}
```

---

## 🟠 PRIORITAS 2: HIGH ISSUES

### 2.1 CODE DUPLICATION: TRANSACTION FETCHING PATTERN (CRITICAL)

**Severity:** HIGH | **Appear:** 5+ locations  
**Impact:** DRY violation, maintenance nightmare

**Pattern Duplicated:**

```php
// ❌ DUPLICATED di:
// 1. ZakatTransactionController::show() line 64-75
// 2. ZakatTransactionController::receipt() line 98-110
// 3. DashboardController::show() line 45-57
// 4. TransactionHistoryController::index()
// 5. ExportController methods

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

**Rekomendasi:**

```php
// Tambah ke ZakatTransaction model:
public function scopeByTransactionNumber(Builder $query, string $noTransaksi, bool $withTrashed = false)
{
    $query = $query->where('no_transaksi', $noTransaksi)
        ->with(['muzakki' => fn($q) => $withTrashed ? $q->withTrashed() : $q])
        ->orderBy('id', 'asc');

    return $withTrashed ? $query->withTrashed() : $query;
}

// Kemudian gunakan:
$transactions = ZakatTransaction::byTransactionNumber($tx->no_transaksi)->get();
// atau dengan trashed:
$transactions = ZakatTransaction::byTransactionNumber($tx->no_transaksi, withTrashed: true)->get();
```

---

### 2.2 TOTALS CALCULATION DUPLICATION (5+ LOKASI)

**Severity:** HIGH | **Locations:**

- [ZakatTransactionController::show()](app/Http/Controllers/Internal/ZakatTransactionController.php#L70) line 70-73
- [TransactionHistoryController](app/Http/Controllers/Internal/TransactionHistoryController.php) (aggregation)
- [ExportController::writeDailyRows()](app/Http/Controllers/Internal/ExportController.php)
- [ExportController::exportYearly()](app/Http/Controllers/Internal/ExportController.php)
- [RekapBuilder](app/Support/RekapBuilder.php)

**Pattern:**

```php
// ❌ DUPLICATED
$totalUang = $transactions->where('metode', '!=', ZakatTransaction::METHOD_BERAS)->sum('nominal_uang');
$totalTf = $transactions->where('metode', ZakatTransaction::METHOD_UANG)->where('is_transfer', true)->sum('nominal_uang');
$totalCash = $totalUang - $totalTf;
$totalBeras = $transactions->where('metode', ZakatTransaction::METHOD_BERAS)->sum('jumlah_beras_kg');
```

**Rekomendasi:**

```php
// Tambah accessor ke ZakatTransaction atau buat helper:
// app/Support/TransactionSummary.php
class TransactionSummary
{
    public static function calculate(Collection $transactions): array
    {
        return [
            'total_uang' => $transactions->whereNotIn('metode', [METHOD_BERAS])->sum('nominal_uang'),
            'total_transfer' => $transactions->where('metode', METHOD_UANG)->where('is_transfer', true)->sum('nominal_uang'),
            'total_cash' => $totalUang - $totalTf,
            'total_beras' => $transactions->where('metode', METHOD_BERAS)->sum('jumlah_beras_kg'),
        ];
    }
}

// Gunakan:
$summary = TransactionSummary::calculate($transactions);
```

---

### 2.3 VALIDATION LOGIC SCATTERED DI MULTIPLE LOKASI

**Severity:** HIGH | **Locations:**

| Location                                                                                                           | Tipe Validasi               |
| ------------------------------------------------------------------------------------------------------------------ | --------------------------- |
| [StoreZakatTransactionRequest](app/Http/Requests/Internal/StoreZakatTransactionRequest.php)                        | Form rules                  |
| [ZakatService::validateNominalDefaults()](app/Services/ZakatService.php#L204)                                      | Service validation          |
| [ZakatTransactionPolicy::update()](app/Policies/ZakatTransactionPolicy.php#L27)                                    | Policy validation (isToday) |
| [TransactionHistoryController::parseFilters()](app/Http/Controllers/Internal/TransactionHistoryController.php#L79) | Custom validation inline    |

**Masalah:**

- Validation split antara Request, Service, Policy, Controller
- Sulit untuk audit semua validation rules
- Mudah terlewat bila menambah validasi baru

**Rekomendasi:**

```php
// Centralize di FormRequest:
// app/Http/Requests/Internal/StoreZakatTransactionRequest.php

public function rules(): array
{
    return [
        // Existing rules...
        'tahun_zakat' => [...],
        // Tambah validation yang sebelumnya di service:
        'items.*.nominal_uang' => [
            'nullable',
            'numeric',
            'min:1',
            function ($attribute, $value, $fail) {
                // validateNominalDefaults logic dipindah ke sini
            }
        ],
    ];
}

// Authorization logic tetap di Policy/Authorization middleware
// Jangan di FormRequest
```

---

### 2.4 MISSING RETURN TYPE HINTS (20+ METHODS)

**Severity:** MEDIUM | **Examples:**

```php
// ❌ Tanpa return type
public function show(Request $request, int $transaction)  // Should: :View|RedirectResponse
public function index(Request $request)                  // Should: :View
public function edit(Muzakki $muzakki)                  // Should: :View
public function restore(Request $request, $muzakkiId)   // Should: :RedirectResponse

// ✅ Harus dikasih return type
public function show(Request $request, int $transaction): View
public function index(Request $request): View
public function edit(Muzakki $muzakki): View
public function restore(Request $request, $muzakkiId): RedirectResponse
```

**Files to fix:**

- All controller methods in `app/Http/Controllers/Internal/`
- All service methods in `app/Services/`
- Model accessor methods

---

### 2.5 INCONSISTENT PARAMETER TYPING

**Severity:** MEDIUM | **Issues:**

```php
// ❌ TIDAK TYPED
public function restore(Request $request, $muzakkiId)  // int?
public function forceDelete(Request $request, $muzakkiId)  // int?

// ✅ HARUS
public function restore(Request $request, int $muzakkiId): RedirectResponse
public function forceDelete(Request $request, int $muzakkiId): RedirectResponse
```

---

## 🟡 PRIORITAS 3: MEDIUM ISSUES

### 3.1 MISSING DOCUMENTATION (25+ METHODS)

**Severity:** MEDIUM | **Impact:** Hard to understand business logic

**Examples:**

```php
// ❌ No docstring - what does this do?
private function determineIsKhusus(array $data, int $defaultFitrah, int $defaultFidyah, float $defaultBerasKg, float $defaultFidyahBeras): bool
{
    // 15 lines of complex logic with no explanation
}

// ✅ Should be:
/**
 * Determine if transaction is special (khusus) by comparing actual amount
 * against default calculation.
 *
 * @param array $data Item data containing: category, metode, jiwa, hari, nominal_uang, jumlah_beras_kg
 * @param int $defaultFitrah Default fitrah per jiwa in Rupiah
 * @param int $defaultFidyah Default fidyah per hari in Rupiah
 * @param float $defaultBerasKg Default fitrah rice per jiwa in KG
 * @param float $defaultFidyahBeras Default fidyah rice per hari in KG
 * @return bool True if actual amount differs from default calculation
 */
private function determineIsKhusus(array $data, ...): bool
```

**Methods need docs:**

- [ZakatService::performSync()](app/Services/ZakatService.php#L61)
- [ZakatService::processItems()](app/Services/ZakatService.php#L119)
- [ZakatService::executeWithRetry()](app/Services/ZakatService.php#L273)
- All accessor methods in models
- All scope methods in models

---

### 3.2 WEAK VALIDATION: MISSING DELETE REASON VALIDATION

**Severity:** MEDIUM | **Location:**

- [TransactionHistoryController::destroy()](app/Http/Controllers/Internal/TransactionHistoryController.php#L160)
- [MuzakkiController::destroy()](app/Http/Controllers/Internal/MuzakkiController.php#L70)

**Issue:**

```php
// ❌ No validation untuk deleted_reason
public function destroy(Request $request, ZakatTransaction $transaction)
{
    $request->input('deleted_reason')  // Could be null, empty, too long
}
```

**Fix:**

```php
$request->validate([
    'deleted_reason' => 'required|string|max:255|min:10',
]);
```

---

### 3.3 DATABASE-SPECIFIC SQL (SQLite vs MySQL)

**Severity:** MEDIUM | **Location:**

- [ZakatService::generateNoTransaksi()](app/Services/ZakatService.php#L254)

**Issue:**

```php
// ❌ Conditional database logic
$last = ZakatTransaction::withTrashed()
    ->where('no_transaksi', 'like', $prefix . '%')
    ->orderByRaw(
        DB::getDriverName() === 'sqlite'
            ? 'CAST(SUBSTR(no_transaksi, 14) AS INTEGER) DESC'
            : 'CAST(SUBSTRING(no_transaksi, 14) AS UNSIGNED) DESC'
    )
```

**Fix - Move to Scope:**

```php
// Di Model:
public function scopeLastTransactionByPrefix(Builder $query, string $prefix)
{
    return $query
        ->where('no_transaksi', 'like', $prefix . '%')
        ->orderByRaw($this->getSequenceNumberOrderRaw());
}

private function getSequenceNumberOrderRaw(): string
{
    return DB::getDriverName() === 'sqlite'
        ? 'CAST(SUBSTR(no_transaksi, 14) AS INTEGER) DESC'
        : 'CAST(SUBSTRING(no_transaksi, 14) AS UNSIGNED) DESC';
}
```

---

### 3.4 INCONSISTENT NAMING CONVENTIONS

**Severity:** MEDIUM | **Issues:**

| Issue                                                          | Lokasi               | Problem                                     |
| -------------------------------------------------------------- | -------------------- | ------------------------------------------- |
| Variable names: `$tx` vs `$transaction` vs `$zakatTransaction` | Multiple controllers | 3 different names for same model            |
| Snake_case vs camelCase                                        | Everywhere           | pembayar_nama (DB) vs pembayarData (array)  |
| Abbreviations not standardized                                 | Everywhere           | TF, tx, Tx, transaksi, transactionId        |
| Indonesian vs English                                          | Mixed                | metode (Ind) vs method (Eng), muzakki (Ind) |

**Standard:**

```php
// Gunakan consistency:
- Variable untuk model instance: $zakatTransaction (tidak $tx, $transaction)
- Array keys: snake_case (pembayar_nama) untuk DB, camelCase untuk API response
- English method names, Indonesian comments for business logic
```

---

## 🔵 PRIORITAS 4: LOW ISSUES

### 4.1 EVENT/OBSERVER POTENTIAL DUPLICATION

**Severity:** LOW | **Location:**

- [ZakatTransactionObserver](app/Observers/ZakatTransactionObserver.php)
- [Audit::log()](app/Support/Audit.php)

**Issue:** Both create AuditLog entries - check if duplicate

---

### 4.2 INCONSISTENT PARAMETER TYPING IN EVENTS

**Severity:** LOW | **Location:**

- [ZakatTransactionCreated::\_\_construct()](app/Events/ZakatTransactionCreated.php#L25)

```php
// ❌ accepts mixed type
public function __construct($transactions)  // Could be Collection|array|single model

// ✅ Should be:
public function __construct(
    Collection|array|ZakatTransaction $transactions
)
```

---

## 📋 IMPLEMENTATION ROADMAP

### Phase 1: CRITICAL (Week 1-2)

```
1. [ ] Create config/zakat.php dengan semua magic numbers
2. [ ] Extract Timezone ke config constant
3. [ ] Refactor ZakatService::performSync() ke 5-6 methods
4. [ ] Add DailyExportRepository untuk replace raw SQL
5. [ ] Test semua changes dengan existing unit tests
```

### Phase 2: HIGH (Week 3-4)

```
6. [ ] Add model scopes untuk transaction fetching (byTransactionNumber)
7. [ ] Create TransactionSummary helper class
8. [ ] Centralize validation di FormRequest
9. [ ] Extract DashboardService
10. [ ] Add proper return type hints ke all controllers
```

### Phase 3: MEDIUM (Week 5-6)

```
11. [ ] Add docstrings ke complex methods
12. [ ] Add delete_reason validation
13. [ ] Standardize naming conventions
14. [ ] Audit AuditLog duplication in Observer
15. [ ] Add type hints ke Event constructor
```

---

## ✅ QUICK WINS (Bisa dilakukan cepat)

```bash
# 1. Add return types ke controllers (1-2 jam)
# 2. Create config/zakat.php (30 menit)
# 3. Extract timezone ke constant (1-2 jam)
# 4. Add docstrings ke 5 kompleks methods (1-2 jam)
# 5. Add validation ke delete endpoints (30 menit)
```

---

## 🎯 ESTIMATED EFFORT

| Task                         | Hours        | Priority |
| ---------------------------- | ------------ | -------- |
| Config/Timezone extraction   | 3            | P1       |
| ZakatService refactor        | 8            | P1       |
| ExportController refactor    | 10           | P1       |
| DashboardController refactor | 6            | P2       |
| Transaction fetching scope   | 4            | P2       |
| Type hints addition          | 4            | P2       |
| Validation centralization    | 3            | P2       |
| Documentation                | 4            | P3       |
| Testing & QA                 | 8            | ALL      |
| **TOTAL**                    | **50 hours** | -        |

---

## 🚀 NEXT STEPS

1. **Review findings** dengan team lead
2. **Prioritize fixes** berdasarkan impact
3. **Create feature branches** untuk setiap kategori
4. **Write tests** sebelum refactoring
5. **Deploy** ke staging dulu, minimal 2 minggu testing

---

## 📞 CONTACT

Untuk clarification, tanyakan:

- Magic number meanings → Check config/zakat.php
- Complex method logic → Check docstrings (to be added)
- Validation rules → Check StoreZakatTransactionRequest
- Authorization → Check ZakatTransactionPolicy
