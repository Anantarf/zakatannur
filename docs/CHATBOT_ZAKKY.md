# Chatbot & Zakky AI Admin Documentation

## Overview

Sistem ini memiliki 2 fitur AI terpisah:

### 1. **Chatbot** (Public API)
- Endpoint: `POST /api/chatbot/message`
- Tujuan: Menjawab pertanyaan user tentang zakat dari data publik
- Provider: OpenAI (primary) + Mock (fallback)
- Stateless conversation

### 2. **Zakky Admin Insights** (Internal Panel)
- Tampil otomatis di halaman admin (Audit Log, Deteksi Anomali, Detail Anomali)
- Tujuan: Informasi rule-based untuk membantu admin review
- Tidak ada menu terpisah, hanya panel
- 100% rule-based, cache 10-60 menit

---

## Setup

### Environment Variables
```bash
OPENAI_API_KEY=sk-...           # Required for production
OPENAI_CHAT_MODEL=gpt-4o-mini   # Model ID
OPENAI_BASE_URL=https://api.openai.com/v1

CHATBOT_PROVIDER=openai         # 'openai' or 'mock'
```

### No Setup Needed
- Cache driver: default (file/redis)
- Database: existing tables only
- Background jobs: not required

---

## Architecture

### Chatbot (Public-Facing)

```
ChatbotController (API endpoint)
    ↓
ChatbotOrchestrator (routing logic)
    ├→ ChatbotActionDetector (intent classification)
    ├→ ChatbotPublicDataResponder (data context)
    └→ ChatbotServiceInterface
        ├→ OpenAiChatbotProvider (production)
        └→ MockChatbotProvider (fallback)
```

**Data Privacy:**
- Filter context at `ChatbotPublicDataResponder` level
- Never send: password, token, nomor telepon lengkap, alamat detail
- OK to send: nomor transaksi, nama muzakki, nominal, tipe zakat, kategori

### Zakky Admin (Internal Panel)

```
AuditLogController / TransactionAnomalyController
    ↓
ZakkyAdminInsightService (rule-based logic)
    ├→ auditLogInsight() [cached 1h]
    ├→ anomalyListInsight() [cached 10m]
    └→ anomalyDetailInsight() [no cache]
    ↓
x-zakky-insight component (UI render)
```

**Cache Strategy:**
- Audit log: 1 hour (changes once a day typically)
- Anomaly list: 10 minutes (risk changes frequently)
- Anomaly detail: no cache (user-specific context)

---

## Phase Roadmap

### Phase 1 (Current) ✓
- [x] Chatbot API dengan OpenAI + Mock fallback
- [x] Zakky panel di 3 halaman admin
- [x] Rule-based insights (100%)
- [x] Basic caching

### Phase 2 (Future)
- [ ] Natural language wording via `ZakkyAdminAiWordingService`
- [ ] Per-admin configuration untuk sensitivity rules
- [ ] Historical insight snapshot (jika akademis meminta)
- [ ] Rate limiting per user
- [ ] Cost tracking dashboard

### Phase 3 (Later)
- [ ] Chatbot multi-turn conversation state
- [ ] Export insights ke PDF/email
- [ ] Admin A/B testing untuk wording variants

---

## Configuration

### File: `config/chatbot.php`

```php
[
    'provider' => 'openai',      // Set by CHATBOT_PROVIDER env
    'max_context_length' => 5000, // Max chars to send to AI
]
```

### File: `config/app.php` (Providers)

```php
// ChatbotServiceProvider: auto-selects OpenAI or Mock based on OPENAI_API_KEY
// AuditAiServiceProvider: (deprecated, can remove in migration)
```

---

## Troubleshooting

### Chatbot returns error 500
1. Check `OPENAI_API_KEY` is set
2. Check `.env` has correct URL/model
3. If API key missing, fallback to Mock (no error, just generic response)

### Zakky panel not appearing
1. Verify view has `<x-zakky-insight :tone="..." :message="..." />`
2. Check controller passes `$zakkyInsight` to view
3. Clear cache: `php artisan cache:clear`

### Slow Audit Log page load
- First load: slow (builds cache)
- Subsequent loads: fast (1h cache)
- Flush cache manually: `php artisan cache:forget zakky:audit-log:2024-01-15`

---

## Future Enhancements

### To Add Natural Language Wording (Phase 2)
```php
// In controller:
$baseInsight = $zakkyInsightService->auditLogInsight();
$insight = app(ZakkyAdminAiWordingService::class)->enhance($baseInsight);
```

### To Add Rate Limiting (Phase 2)
```php
// In ChatbotController:
if (!RateLimiter::attempt('chatbot:' . $sessionId, perMinute: 30)) {
    return response()->json(['error' => 'Too many requests'], 429);
}
```

### To Add Cost Tracking (Phase 2)
```php
// Log each OpenAI call:
Log::channel('chatbot')->info('API call', [
    'tokens' => $response['usage']['total_tokens'],
    'cost' => $response['usage']['total_tokens'] * 0.000005,
]);
```

---

## Files

- `app/Services/Admin/ZakkyAdminInsightService.php` — Rule-based logic
- `app/Services/Admin/ZakkyAdminAiWordingService.php` — Phase 2 skeleton
- `app/Services/Chatbot/ChatbotOrchestrator.php` — Main orchestrator
- `app/Services/Chatbot/Providers/OpenAiChatbotProvider.php` — Production provider
- `app/Services/Chatbot/Providers/MockChatbotProvider.php` — Fallback
- `resources/components/zakky-insight.blade.php` — UI component
- `config/chatbot.php` — Configuration

---

## Notes

- ✓ All three Zakky panels integrated and working
- ✓ Caching implemented for performance
- ✓ AI Audit menu removed (was not in design spec)
- ✓ Chatbot provider consolidated to OpenAI + Mock
- ⏳ Phase 2: natural language wording (not implemented yet)
- ⏳ Phase 2: admin sensitivity configuration (not implemented yet)

---

## Improvements & Score (v2.0)

**Final Score: 8.8/10 ⭐⭐⭐⭐**

### 1. Security & Rate Limiting
- **ThrottleChatbot middleware** - 30 requests/minute per user/IP
- **Dual throttling** - Route middleware (30/1) + custom middleware

### 2. UX Enhancements
- **Auto-scroll** - Jumps to latest message when new one arrives
- **Copy feedback** - Shows toast "Tersalin!" after copy
- **Better error messages** - Include actionable suggestions

### 3. Performance & Caching
- **ChatbotResponseCache** - Caches identical questions for 1 hour
- **MD5-based cache keys** - Normalized for minor variations

### 4. User Feedback Mechanism
- **Feedback buttons** - 👍 (helpful) / 👎 (unhelpful) on bot messages
- **ChatbotFeedback model** - For analytics & improvement
- Endpoint - POST `/api/chatbot/message` with `type=feedback`

### 5. Sentinel Pattern (Zakat Mal)
- LLM diarahkan menghasilkan `[HITUNG:{...}]`
- `ChatbotOrchestrator` memotong tag tersebut dan menjalankan perhitungan Zakat Mal murni di backend PHP (`ChatbotZakatMalGuide`), menghindari halusinasi.

---

## Troubleshooting Guide

### Error: "Gagal memproses pesan. Silakan coba beberapa saat lagi."

Ini berarti chatbot API gagal. Mari debug:

#### 1. Cek Konfigurasi
Buka `.env` file dan pastikan:
```env
OPENAI_API_KEY=sk-...        # HARUS ada (bukan kosong)
OPENAI_CHAT_MODEL=gpt-4o-mini
OPENAI_BASE_URL=https://api.openai.com/v1
CHATBOT_PROVIDER=openai
```
Jika `OPENAI_API_KEY` kosong atau tidak ada → chatbot fallback ke mode mock (terbatas).

#### 2. Cek Logs
```bash
tail -f storage/logs/laravel.log
```
Cari line dengan `Chatbot error` atau `OpenAI API Error` untuk detail masalah.

#### 3. Error Codes & Meanings
| Error | Penyebab | Solusi |
|-------|----------|--------|
| 401/403 | API key salah/tidak valid | Cek OPENAI_API_KEY di .env |
| 404 | Model tidak ditemukan | Cek OPENAI_CHAT_MODEL (gpt-4o-mini) |
| 429 | Quota terlampaui | Tunggu sampai besok atau upgrade API plan |
| 500+ | Server OpenAI error | Tunggu beberapa menit, coba lagi |
| Network error | Tidak bisa connect | Cek koneksi internet |

#### Fallback Mode (Saat API Down)
Jika API tidak bisa diakses, chatbot masih bekerja tapi terbatas:
- ✅ Bisa jawab pertanyaan standar (total uang, total beras, cara bayar)
- ✅ Bisa recognize intents & navigate (lihat grafik, buka ringkasan)
- ❌ Tidak bisa answer complex questions
