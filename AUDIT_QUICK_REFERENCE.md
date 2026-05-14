# 🚨 CRITICAL ISSUES - QUICK REFERENCE

## 1️⃣ TIMEZONE HARDCODING (Paling Urgent!)

**Impact:** Will break on timezone config change  
**Files:** 12+ files with `'Asia/Jakarta'` hardcoded

```bash
# SEARCH & FIX:
grep -r "Asia/Jakarta" app/
grep -r "now('Asia/Jakarta')" app/

# REPLACE WITH:
config('app_timezone')  # Default: 'Asia/Jakarta' tapi configurable
```

**Minutes to fix:** 30-45 minutes (with testing)

---

## 2️⃣ MAGIC NUMBERS SCATTERED

| Value      | Location                                      | Type      | Fix                                       |
| ---------- | --------------------------------------------- | --------- | ----------------------------------------- |
| 30 days    | Muzakki.php, TransactionHistoryController.php | Purge     | config('zakat.purge_days')                |
| 5 retries  | ZakatService.php                              | Retry     | config('zakat.retry_attempts')            |
| 30 seconds | ZakatService.php                              | Lock      | config('zakat.lock_timeout_seconds')      |
| 300/3600   | DashboardController.php                       | Cache TTL | config('zakat.cache_ttl')                 |
| 'TRX-'     | ZakatService.php                              | Prefix    | config('zakat.prefix.transaction_number') |

**Create:** `config/zakat.php` (15 minutes)

---

## 3️⃣ COMPLEX METHODS NEEDING REFACTOR

### ZakatService::performSync() (90 lines)

```php
// Split into:
1. validateOrGenerateNoTransaksi() - 20 lines
2. createOrUpdateMuzakki() - 10 lines
3. calculateDeltas() - 20 lines
4. processItems() - already exists
5. deleteRemovedItems() - 10 lines
6. logAudit() - 5 lines

// Time: 4-6 hours
```

### ExportController::exportDaily() (300 lines)

```php
// Create:
- DailyExportRepository (handles queries)
- ExcelStyler (handles formatting)
- Refactor method to 50 lines

// Time: 8-10 hours
```

### DashboardController::show() (145 lines)

```php
// Create: DashboardService
// Extract:
- getPayload()
- getOffSeasonData()
- getChartData()
- getLatestTransactions()

// Time: 5-6 hours
```

---

## 4️⃣ CODE DUPLICATION - TOP 3

### A. Transaction Fetching (5 copies)

```php
ZakatTransaction::query()
    ->with(['muzakki' => fn($q) => $q->withTrashed()])
    ->where('no_transaksi', $noTransaksi)
    ->orderBy('id', 'asc')
    ->get();
```

**Fix:** Add scope `byTransactionNumber()` to model (30 minutes)

### B. Totals Calculation (5 copies)

```php
TransactionSummary::calculate($transactions);
// Returns: total_uang, total_tf, total_cash, total_beras
```

**Fix:** Create TransactionSummary class (1 hour)

### C. Status Filter (15 copies)

```php
where('status', ZakatTransaction::STATUS_VALID)
```

**Fix:** Add scope `valid()` to model (15 minutes)

---

## 5️⃣ MISSING TYPE HINTS

**All controller methods need return type:**

```php
// ❌ Before
public function show(Request $request, int $transaction)

// ✅ After
public function show(Request $request, int $transaction): View
```

**Time:** 2-3 hours (all controllers)

---

## 📊 EFFORT vs IMPACT

| Issue                     | Time | Impact | Do First? |
| ------------------------- | ---- | ------ | --------- |
| Timezone extraction       | 0.5h | HIGH   | ✅ YES    |
| Magic numbers config      | 0.5h | HIGH   | ✅ YES    |
| Type hints                | 3h   | MEDIUM | ⏭ 2nd    |
| ZakatService refactor     | 6h   | HIGH   | ⏭ 2nd    |
| ExportController refactor | 10h  | MEDIUM | 3rd       |
| Duplication fixes         | 3h   | MEDIUM | 3rd       |

**Total Critical:** ~7 hours → Do in 1-2 days

---

## 🎯 TESTING STRATEGY

```bash
# Before any refactoring:
1. Run existing tests → Baseline
2. Add tests for complex methods
3. Refactor one method at a time
4. Re-run tests after each change
5. Create regression tests for edge cases
```

---

## 🔧 IMPLEMENTATION ORDER

```
Day 1 (Morning):
□ Create config/zakat.php
□ Replace all hardcoded values with config()
□ Replace all 'Asia/Jakarta' with config('app_timezone')
□ Run tests

Day 1 (Afternoon):
□ Add return type hints to controllers
□ Add docstrings to 5 complex methods
□ Run tests

Day 2+:
□ Refactor ZakatService methods
□ Extract DashboardService
□ Refactor ExportController
```

---

## ⚠️ GO-LIVE CHECKLIST ADDITIONS

Before deploying to production:

```bash
# Timezone
[ ] APP_TIMEZONE env var set correctly in .env
[ ] config/app.php uses env('APP_TIMEZONE', ...)
[ ] All Carbon::parse() uses config('app_timezone')

# Configuration
[ ] config/zakat.php exists with all values
[ ] All hardcoded magic numbers removed from code
[ ] No environment-specific logic in code (all in config)

# Code Quality
[ ] All controller methods have return types
[ ] No methods > 100 lines
[ ] No raw SQL without comments explaining it
[ ] All complex logic has docstrings
```

---

## 📝 FILES TO CREATE/MODIFY

### New Files:

- `config/zakat.php` - All configuration
- `app/Repositories/DailyExportRepository.php` - Query extraction
- `app/Support/ExcelStyler.php` - Styling logic
- `app/Services/DashboardService.php` - Dashboard logic
- `app/Support/TransactionSummary.php` - Summary calculations

### Files to Modify:

- All in `app/Http/Controllers/Internal/` - Add return types
- `app/Services/ZakatService.php` - Refactor methods
- `app/Models/ZakatTransaction.php` - Add scopes
- `app/Http/Controllers/Internal/ExportController.php` - Major refactor

---

## 🎓 LESSONS FOR FUTURE

1. **Never hardcode configuration** → Use config files from day 1
2. **Keep methods < 50 lines** → Easier to understand and test
3. **Use model scopes** for repeated query patterns
4. **Centralize business logic** → Don't spread across controllers/services/policies
5. **Document complex algorithms** → Future you will thank present you
6. **Type hint everything** → Makes refactoring safer
7. **DRY principle** → Extract duplication immediately

---

Generated: 14 May 2026 | Time Cost: ~50 hours to fix | Not fixing: ~250 hours tech debt accumulation/year
