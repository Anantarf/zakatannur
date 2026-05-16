# 📝 REFACTORING CHECKLIST & IMPLEMENTATION GUIDE

## NEW FILES TO CREATE

### 1. `config/zakat.php` (NEW)

**Purpose:** Centralize all Zakat-specific configuration  
**Size:** ~50 lines  
**Copy from:** Examples in AUDIT_REFACTORING_EXAMPLES.md (Contoh 2)

```bash
php artisan make:config zakat
```

Then add content from the guide.

---

### 2. `app/Repositories/DailyExportRepository.php` (NEW)

**Purpose:** Extract database queries from ExportController  
**Methods:**

- `fetchWithSummaries(Carbon $start, Carbon $end)`
- `fetchYearlyWithSummaries(int $year)`

**Size:** ~100 lines  
**Replaces:** The 30-line raw SQL in ExportController::fetchDailyTransactions()

---

### 3. `app/Support/ExcelStyler.php` (NEW)

**Purpose:** Extract Excel styling logic from ExportController  
**Methods:**

- `styleHeaders(Worksheet $sheet, string $title)`
- `styleDataRows(Worksheet $sheet, string $range)`
- `styleTotalRow(Worksheet $sheet, int $rowIdx)`
- `styleSummarySection(Worksheet $sheet, int $baseRow)`

**Size:** ~80 lines

---

### 4. `app/Services/DashboardService.php` (NEW)

**Purpose:** Extract dashboard logic from DashboardController  
**Methods:**

- `getPayload(int $year, ?string $metode): array`
- `getOffSeasonData(int $year): array`
- `getChartData(int $year, ?Carbon $end, int $days): array`
- `getLatestTransactions(?int $year, ?string $metode): Collection`

**Size:** ~120 lines  
**Replaces:** The 145-line method in DashboardController::show()

---

### 5. `app/Support/TransactionSummary.php` (NEW)

**Purpose:** Centralize transaction totals calculation  
**Methods:**

- `calculate(Collection $transactions): array`
- `calculateUang(Collection $transactions): array`
- `calculateBeras(Collection $transactions): float`

**Size:** ~50 lines  
**Replaces:** Duplicated calculations in 5 files

---

## FILES TO MODIFY

### 🟡 PRIORITY: CRITICAL

#### 1. `config/app.php`

**Change:** Update timezone configuration

```php
// FROM:
'timezone' => env('APP_TIMEZONE', 'UTC'),

// TO:
'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
```

**Size:** 1 line change

---

#### 2. `.env` and `.env.example`

**Add:**

```bash
# Application Timezone
APP_TIMEZONE=Asia/Jakarta

# Zakat Service Configuration
TRANSACTION_PREFIX=TRX-
TRANSACTION_NUMBER_PADDING=4
TRANSACTION_RETRY_ATTEMPTS=5
PURGE_DAYS=30
LOCK_TIMEOUT_SECONDS=30
DASHBOARD_CACHE_TTL=300
DASHBOARD_OFFSEASON_CACHE_TTL=3600
DEFAULT_FITRAH_CASH=50000
DEFAULT_FITRAH_BERAS=2.50
DEFAULT_FIDYAH_CASH=50000
DEFAULT_FIDYAH_BERAS=0.75
```

---

#### 3. `app/Services/ZakatService.php` (90 lines to refactor)

**Changes:**

1. Remove `private const NO_TRANSAKSI_RETRY_ATTEMPTS = 5;`
2. In `executeWithRetry()`: Use `config('zakat.transaction.retry_attempts')`
3. In `performSync()`: Use `config('zakat.cache.lock_timeout_seconds')`
4. In `generateNoTransaksi()`: Use config for prefix and padding
5. In `parseWaktuTerima()`: Use `config('app.timezone')`

**Refactor into separate methods:**

- Extract `validateOrGenerateNoTransaksi()`
- Extract `createOrUpdateMuzakki()`
- Extract `calculateTransactionDeltas()`
- Extract `deleteRemovedItems()`
- Extract `logAuditEvent()`

**Impact:** ~30 file changes, test all thoroughly

---

#### 4. `app/Models/ZakatTransaction.php` (Add scopes)

**Add scopes:**

```php
public function scopeByTransactionNumber(...)
public function scopeValid()  // where('status', STATUS_VALID)
```

**Update locations that use:**

- `where('status', ZakatTransaction::STATUS_VALID)` → `valid()`
- Repeated transaction fetching → `byTransactionNumber()`

---

### 🟠 PRIORITY: HIGH

#### 5. `app/Http/Controllers/Internal/ZakatTransactionController.php`

**Changes:**

1. Add return types to all methods
2. Replace transaction fetching with `byTransactionNumber()` scope
3. Use `TransactionSummary::calculate()` instead of inline calculation

**Methods to update:**

- `create()` → `: View`
- `store()` → `: RedirectResponse`
- `show()` → `: View`
- `receipt()` → `: Response`
- `edit()` → `: View`
- `update()` → `: RedirectResponse`

---

#### 6. `app/Http/Controllers/Internal/DashboardController.php`

**Changes:**

1. Inject DashboardService
2. Extract logic to service methods
3. Simplify show() to ~50 lines
4. Use config() for timezone

**Before:** 145 lines  
**After:** 50 lines

---

#### 7. `app/Http/Controllers/Internal/TransactionHistoryController.php`

**Changes:**

1. Add return types to all methods
2. Use config() for purge_days
3. Use model scopes for queries
4. Validate deleted_reason

---

#### 8. `app/Http/Controllers/Internal/ExportController.php` (300 lines)

**Changes:**

1. Inject DailyExportRepository
2. Inject ExcelStyler
3. Replace fetchDailyTransactions() with repository call
4. Replace styling code with ExcelStyler class
5. Reduce exportDaily() from 300 lines to ~80 lines

---

#### 9. `app/Http/Controllers/Internal/MuzakkiController.php`

**Changes:**

1. Add type hints to parameters: `int $muzakkiId`
2. Add return types to all methods
3. Use config() for timezone

---

### 🟡 PRIORITY: MEDIUM

#### 10. `app/Models/Muzakki.php`

**Changes:**

1. Use `config('zakat.retention.purge_days')`
2. Use `config('app.timezone')`

---

#### 11. `app/Policies/ZakatTransactionPolicy.php`

**Changes:**

1. Use `config('app.timezone')`
2. Add docstrings

---

#### 12. `app/Support/ReceiptPdf.php`

**Changes:**

1. Use `config('app.timezone')`
2. Add type hints

---

#### 13. `app/Support/Audit.php`

**Changes:**

1. Add proper type hints to `log()` method

---

#### 14. `app/Observers/ZakatTransactionObserver.php`

**Changes:**

1. Add docstrings
2. Check for audit log duplication

---

#### 15. `app/Http/Requests/Internal/StoreZakatTransactionRequest.php`

**Changes:**

1. Add validation for delete reasons in other methods
2. Centralize validation rules

---

## ALL CONTROLLER FILES TO ADD RETURN TYPES

**Files in `app/Http/Controllers/Internal/`:**

- [ ] AuditLogController.php
- [ ] DashboardController.php
- [ ] ExportController.php
- [ ] MustahikController.php
- [ ] MuzakkiController.php
- [ ] PeriodSettingsController.php
- [ ] ProfileController.php
- [ ] TemplateController.php
- [ ] TransactionHistoryController.php
- [ ] UserManagementController.php
- [ ] ZakatTransactionController.php

**Time:** ~2-3 hours total

---

## ALL FILES REFERENCING 'Asia/Jakarta'

These files need timezone change:

1. [ ] `app/Services/ZakatService.php` (3 occurrences)
2. [ ] `app/Http/Controllers/Internal/TransactionHistoryController.php` (4 occurrences)
3. [ ] `app/Http/Controllers/Internal/DashboardController.php` (4 occurrences)
4. [ ] `app/Http/Controllers/Internal/ExportController.php` (2 occurrences)
5. [ ] `app/Policies/ZakatTransactionPolicy.php` (1 occurrence)
6. [ ] `app/Support/ReceiptPdf.php` (2 occurrences)
7. [ ] `app/Models/Muzakki.php` (3 occurrences)
8. [ ] `app/Http/Controllers/Guest/GuestSummaryController.php` (1 occurrence)

**Total:** 20 occurrences to replace  
**Time:** 45 minutes with find/replace

---

## TESTING CHECKLIST

After each change, run:

```bash
# Unit tests
php artisan test

# Check for syntax errors
php artisan tinker

# Manual testing
- Create transaction
- Edit transaction
- Export daily/yearly
- Check timezone display
- Verify cache works
- Verify locks work
```

---

## GIT WORKFLOW

Suggested branch structure:

```bash
# Create feature branches
git checkout -b feature/config-refactor
git checkout -b feature/timezone-extraction
git checkout -b feature/return-types
git checkout -b feature/complex-method-refactor
git checkout -b feature/duplication-extraction

# After testing, merge to develop
git merge --no-ff feature/config-refactor

# Then to main for release
git merge --no-ff develop
git tag v2.1.0
```

---

## ROLLBACK PLAN

If issues arise:

```bash
# Keep previous version tagged
git tag v2.0.1  # Current production

# If need to rollback during go-live
git checkout v2.0.1
git revert [commits]

# Or reset to before refactoring
git revert [merge-commit]
```

---

## DOCUMENTATION UPDATES NEEDED

1. **README.md** - Add configuration section
2. **docs/ARCHITECTURE.md** - Update DashboardService, Repository patterns
3. **docs/DEPLOYMENT.md** - Add APP_TIMEZONE to env variables
4. **docs/CONFIGURATION.md** - Document config/zakat.php

---

## SIGN-OFF

**Refactoring Ready When:**

- [ ] All files identified
- [ ] Team agrees on timeline
- [ ] Test environment ready
- [ ] Backup created
- [ ] Deploy steps documented

**Approval by:** ******\_\_\_\_******  
**Date:** ******\_\_\_\_******
