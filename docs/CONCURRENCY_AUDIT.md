# Backend Concurrency & Rate Limiting Audit Report

**Date:** June 24, 2026  
**Status:** 9 High/Medium issues identified, 4 Critical fixes recommended  
**Overall Risk:** MEDIUM (fixable with targeted changes)

---

## 🔴 CRITICAL ISSUES (Must Fix)

### 1. TransactionNumberGenerator - Race Condition (CRITICAL)
**File:** `app/Services/Transactions/TransactionNumberGenerator.php`  
**Severity:** CRITICAL  
**Impact:** Duplicate transaction numbers (violates unique constraint)

**Problem:**
```php
$last = ZakatTransaction::withTrashed()
    ->where('no_transaksi', 'like', $prefix . '%')
    ->orderByRaw(SqlDialect::transactionNumberOrderExpression())
    ->orderByDesc('id')
    ->value('no_transaksi');  // ← Two concurrent requests read same value

$sequence = ($last && preg_match('/(\d{4})$/', $last, $matches)) ? (int) $matches[1] + 1 : 1;
return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);  // ← Same number generated
```

**Risk Scenario:** 
- Request A & B both call `generate()` concurrently
- Both read `no_transaksi = "TRX-20260624-0005"`
- Both compute sequence = 0006
- Both return `"TRX-20260624-0006"`
- Unique constraint violation when second insert attempted

**Fix:** Use database lock
```php
$last = ZakatTransaction::withTrashed()
    ->where('no_transaksi', 'like', $prefix . '%')
    ->lockForUpdate()  // ← Prevents concurrent reads
    ->orderByRaw(SqlDialect::transactionNumberOrderExpression())
    ->orderByDesc('id')
    ->value('no_transaksi');
```

**Alternative:** Use sequence table with AUTO_INCREMENT

---

### 2. TransactionReviewAssistantService::syncForTransactions - firstOrNew Race (HIGH)
**File:** `app/Services/Transactions/TransactionReviewAssistantService.php`  
**Severity:** HIGH  
**Impact:** Duplicate risk review records, unique constraint violation

**Problem:**
```php
$review = TransactionRiskReview::query()->firstOrNew([
    'zakat_transaction_id' => $transaction->id,  // ← NOT wrapped in lock
]);
// Between firstOrNew() and save():
// Another request can also find "not found", both create new record
$review->fill([...]);
$review->save();  // ← Unique constraint violation!
```

**Fix:** Use `updateOrCreate()` instead
```php
$review = TransactionRiskReview::query()->updateOrCreate(
    ['zakat_transaction_id' => $transaction->id],  // ← Atomic operation
    [
        'risk_level' => $analysis->riskLevel,
        'score' => $analysis->score,
        // ... other fields
    ]
);
```

---

### 3. TransactionGroupLifecycleService::restoreGroup - TOCTOU (MEDIUM)
**File:** `app/Services/Transactions/TransactionGroupLifecycleService.php`  
**Severity:** MEDIUM  
**Impact:** Restore fails with unclear error when concurrent transaction created

**Problem:**
```php
// Check OUTSIDE transaction
$hasActiveCollision = ZakatTransaction::where('no_transaksi', $noTransaksi)
    ->whereNull('deleted_at')
    ->exists();  // ← Check happens here

if ($hasActiveCollision) {
    return ['restored' => false, ...];
}

DB::transaction(function () use ($noTransaksi, ...) {
    // Between check and transaction start, another request can create
    // transaction with same no_transaksi, causing unique constraint error!
    ZakatTransaction::onlyTrashed()
        ->where('no_transaksi', $noTransaksi)
        ->restore();
});
```

**Fix:** Move check inside transaction
```php
DB::transaction(function () use ($transaction, ...) {
    $transaction = ZakatTransaction::withTrashed()
        ->where('id', $transaction->id)
        ->lockForUpdate()  // ← Lock the record being restored
        ->first();

    if ($transaction->deleted_at === null) {
        return ['restored' => false, 'reason' => 'Already active'];
    }

    $hasActiveCollision = ZakatTransaction::where('no_transaksi', $transaction->no_transaksi)
        ->whereNull('deleted_at')
        ->lockForUpdate()  // ← Lock check for concurrent creates
        ->exists();

    if ($hasActiveCollision) {
        return ['restored' => false, 'reason' => 'Collision with active'];
    }

    $transaction->restore();
    return ['restored' => true];
});
```

---

## 🟠 HIGH ISSUES (Should Fix)

### 4. MuzakkiMergeService::mergeInto - Validation Outside Transaction (MEDIUM)
**File:** `app/Services/Muzakki/MuzakkiMergeService.php`  
**Severity:** MEDIUM  
**Impact:** Merge fails when models deleted mid-operation

**Current:**
```php
public function mergeInto(Muzakki $target, Muzakki $duplicate): array
{
    if ($target->is($duplicate)) {  // ← Check OUTSIDE transaction
        throw new InvalidArgumentException('...');
    }

    return DB::transaction(function () use ($target, $duplicate) {
        // $target or $duplicate could be deleted here
        $movedTransactions = ZakatTransaction::withTrashed()
            ->where('muzakki_id', $duplicate->id)
            ->update(['muzakki_id' => $target->id]);

        $duplicate->delete();
    });
}
```

**Fix:**
```php
return DB::transaction(function () use ($target, $duplicate) {
    // Reload with lock to ensure not deleted
    $target = Muzakki::lockForUpdate()->find($target->id);
    $duplicate = Muzakki::lockForUpdate()->find($duplicate->id);

    if (!$target || !$duplicate) {
        throw new MuzakkiNotFoundException('Muzakki was deleted');
    }

    if ($target->is($duplicate)) {
        throw new InvalidArgumentException('Cannot merge same Muzakki');
    }

    // ... proceed with merge
});
```

---

### 5. AiChatLog Create Without Transaction (MEDIUM)
**File:** `app/Services/Chatbot/ChatbotOrchestrator.php`  
**Severity:** MEDIUM  
**Impact:** Duplicate chat log records under concurrent load

**Current:**
```php
private function saveChatLog(string $question, ?string $intent, string $sourceType, string $answer, ?string $sessionId, ?string $sentiment = null): void
{
    try {
        AiChatLog::create([  // ← No transaction wrapping
            'session_id' => $sessionId,
            'question' => $question,
            // ... fields
        ]);
    } catch (Throwable $e) {
        Log::warning('Failed to save AI chat log.', ['message' => $e->getMessage()]);
    }
}
```

**Issue:** Same message sent twice concurrently → duplicate log entries

**Fix:**
```php
private function saveChatLog(...): void
{
    try {
        AiChatLog::updateOrCreate(
            [
                'session_id' => $sessionId,
                'question' => md5($question),  // ← Use hash as idempotency key
            ],
            [
                'question' => $question,
                'intent' => $intent,
                'source_type' => $sourceType,
                'answer' => $answer,
                'sentiment' => $sentiment,
            ]
        );
    } catch (Throwable $e) {
        Log::warning('Failed to save AI chat log.', ['message' => $e->getMessage()]);
    }
}
```

Requires migration: add unique index on `(session_id, question_md5)`

---

### 6. ChatbotFeedback Create Without Idempotency (MEDIUM)
**File:** `app/Http/Controllers/Api/ChatbotController.php`  
**Severity:** MEDIUM  
**Impact:** Duplicate feedback records, skewed ratings

**Current:**
```php
private function handleFeedback(Request $request)
{
    try {
        \App\Models\ChatbotFeedback::create([  // ← No idempotency check
            'session_id' => $request->input('session_id'),
            'message' => $request->input('message'),
            'rating' => $request->input('feedback'),
            'ip_address' => $request->ip(),
        ]);
        return response()->json(['status' => 'success']);
    } catch (\Throwable $e) {
        Log::warning('Failed to save feedback', ['error' => $e->getMessage()]);
        return response()->json(['status' => 'success']);  // ← Hides error!
    }
}
```

**Fix:**
```php
private function handleFeedback(Request $request)
{
    try {
        $feedback = \App\Models\ChatbotFeedback::updateOrCreate(
            [
                'session_id' => $request->input('session_id'),
                'message' => $request->input('message'),  // or hash for uniqueness
            ],
            [
                'rating' => $request->input('feedback'),
                'ip_address' => $request->ip(),
            ]
        );
        return response()->json(['status' => 'success', 'id' => $feedback->id]);
    } catch (\Throwable $e) {
        Log::error('Failed to save feedback', ['error' => $e->getMessage()]);  // Log error, don't hide
        return response()->json(
            ['status' => 'error', 'message' => 'Feedback save failed'],
            500
        );
    }
}
```

---

## 🟡 RATE LIMITING ISSUES

### 7. Dual Rate Limiting Middleware Redundancy (MEDIUM)
**File:** `routes/api.php` (lines 26-30)  
**Severity:** MEDIUM  
**Issue:** Both `throttle:30,1` and `ThrottleChatbot` middleware applied

**Current:**
```php
Route::post('/chatbot/message', [\App\Http\Controllers\Api\ChatbotController::class, 'chat'])
    ->middleware(['throttle:30,1', \App\Http\Middleware\ThrottleChatbot::class]);

Route::post('/chatbot/stream', [ChatbotStreamController::class, 'stream'])
    ->middleware(['throttle:30,1', \App\Http\Middleware\ThrottleChatbot::class]);
```

**Problems:**
1. Both middleware execute, adding latency
2. Two different cache keys used (RateLimiter vs ThrottleChatbot)
3. On multi-server, file cache driver doesn't sync (commented in code already)
4. Different behavior: throttle is per-IP, ThrottleChatbot is per-user OR per-IP

**Fix - Option A (Recommended):**
```php
// Remove custom middleware, use Laravel's throttle with auth/guest variants
Route::post('/chatbot/message', [...])
    ->middleware('throttle:guest,30,1|auth,60,1');  // Auth users get 60/min

Route::post('/chatbot/stream', [...])
    ->middleware('throttle:guest,10,1|auth,20,1');  // Streaming is resource-intensive
```

**Fix - Option B (If custom logic needed):**
```php
// Remove throttle:30,1, keep only ThrottleChatbot
Route::post('/chatbot/message', [...])
    ->middleware(\App\Http\Middleware\ThrottleChatbot::class);

// Enhance ThrottleChatbot to add X-RateLimit headers
```

---

### 8. Cache Driver for Rate Limiting (MEDIUM)
**File:** `config/cache.php` (default = 'file')  
**Severity:** MEDIUM for multi-server deployments  
**Impact:** Rate limiting ineffective across multiple servers

**Current:** File-based cache (noted in code comments)

**Fix for distributed systems:**
```bash
# .env
CACHE_DRIVER=redis  # Use Redis instead of file
# Requires: php-redis extension, Redis server running
```

**Check:** `app/Services/ZakatService.php` lines 159-161 already warn about this
```php
// NOTE: Cache::lock only serializes within a single host for file/database drivers.
// For multi-host deployments, set CACHE_DRIVER=redis in .env
```

---

## 🟡 DEADLOCK RISKS

### 9. Transaction Ordering Vulnerability in ZakatService (MEDIUM)
**File:** `app/Services/ZakatService.php` (lines 155-208)  
**Severity:** MEDIUM  
**Impact:** Rare deadlocks under concurrent sync operations

**Current:**
```php
Cache::lock("zakat_sync_lock:{$period->id}", 30)->block(30, function () use (...) {
    $existingTotals = $this->getExistingTransactionTotals($period);  // Read
    $this->deleteRemovedTransactions($period, $upsertData);  // Delete
    
    foreach ($upsertData as $muzakkiId => $items) {
        // Update Muzakki, create/update transactions
        $muzakki = Muzakki::find($muzakkiId);
        // Multiple queries in different order than other requests
    }
});
```

**Risk:** If two concurrent requests lock in different query order, deadlock possible.

**Fix: Add explicit row locks**
```php
Cache::lock("zakat_sync_lock:{$period->id}", 30)->block(30, function () use (...) {
    DB::transaction(function () {
        // Lock all Muzakki first, in consistent order
        $muzakkiIds = array_keys($upsertData);
        Muzakki::whereIn('id', $muzakkiIds)
            ->orderBy('id')  // ← Consistent order
            ->lockForUpdate()
            ->get();

        $existingTotals = $this->getExistingTransactionTotals($period);
        $this->deleteRemovedTransactions($period, $upsertData);

        foreach ($upsertData as $muzakkiId => $items) {
            // Muzakki already locked, safe to update
            $this->processItems(...);
        }
    });
});
```

---

### 10. Cascading Deletes Complexity (LOW-MEDIUM)
**Files:** Various migrations  
**Severity:** LOW-MEDIUM  
**Issue:** Foreign key cascading deletes can cause lock ordering conflicts

**Current:**
```php
$table->foreignId('zakat_transaction_id')
    ->constrained('zakat_transactions')
    ->cascadeOnDelete();  // ← Can trigger locks in reverse order
```

**Fix: Use restrictOnDelete to fail fast**
```php
$table->foreignId('zakat_transaction_id')
    ->constrained('zakat_transactions')
    ->restrictOnDelete();  // ← Fail explicitly if referenced
```

Then implement explicit cleanup service if needed.

---

## 📊 RISK MATRIX

| Issue | Severity | Likelihood | Impact | Fix Effort | Status |
|-------|----------|-----------|--------|-----------|--------|
| TransactionNumberGenerator race | CRITICAL | HIGH | Duplicate numbers | LOW | Needs fix |
| TransactionReviewAssistantService | HIGH | HIGH | Unique constraint | LOW | Needs fix |
| RestoreGroup TOCTOU | MEDIUM | MEDIUM | Unclear error | MEDIUM | Needs fix |
| MuzakkiMerge validation | MEDIUM | LOW | Merge fails | MEDIUM | Needs fix |
| AiChatLog duplicate | MEDIUM | MEDIUM | Analytics skewed | MEDIUM | Needs fix |
| ChatbotFeedback duplicate | MEDIUM | MEDIUM | Ratings wrong | LOW | Needs fix |
| Dual rate limiting | MEDIUM | HIGH | Config confusion | LOW | Needs fix |
| Cache driver | MEDIUM | HIGH (multi-server) | Bypass possible | LOW | Configuration |
| Transaction ordering | MEDIUM | LOW | Rare deadlock | MEDIUM | Testing needed |
| Cascading deletes | LOW-MEDIUM | LOW | Rare deadlock | MEDIUM | Documentation |

---

## ✅ RECOMMENDATIONS (Priority Order)

### Immediate (This Sprint)
- [ ] Fix TransactionNumberGenerator with `lockForUpdate()`
- [ ] Replace `firstOrNew()` with `updateOrCreate()` in TransactionReviewAssistantService
- [ ] Remove duplicate rate limiting middleware
- [ ] Move collision check inside transaction in RestoreGroup

### Short-term (Next Sprint)
- [ ] Add idempotency to AiChatLog and ChatbotFeedback
- [ ] Implement explicit locking in MuzakkiMergeService
- [ ] Configure Redis for cache driver (if multi-server)
- [ ] Load test concurrent transaction operations

### Long-term
- [ ] Implement distributed tracing for lock contention monitoring
- [ ] Create deadlock test suite
- [ ] Document lock ordering conventions
- [ ] Consider event sourcing for high-concurrency operations

---

## 🧪 Testing Checklist

```bash
# Load test concurrent requests
php artisan tinker
> // Simulate 50 concurrent transaction creates
> for ($i = 0; $i < 50; $i++) {
>     exec('php artisan test tests/Concurrent/CreateTransactionTest.php &');
> }

# Check for deadlock errors
tail -f storage/logs/laravel.log | grep -i deadlock

# Verify rate limiting
curl -i http://localhost:8000/api/chatbot/message -X POST \
  -H "Content-Type: application/json" \
  -d '{"message":"test"}' \
  # Repeat 31+ times in 1 minute, should see 429 after 30

# Check for duplicate records
SELECT COUNT(*) as total, 
       COUNT(DISTINCT session_id, question) as unique 
FROM ai_chat_logs 
WHERE created_at > NOW() - INTERVAL 1 HOUR;
# Should be equal if no duplicates
```

---

**Generated:** 2026-06-24  
**Auditor:** Claude Code  
**Next Review:** After fixes applied
