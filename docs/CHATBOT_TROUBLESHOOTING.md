# Chatbot Troubleshooting Guide

## Error: "Gagal memproses pesan. Silakan coba beberapa saat lagi."

Ini berarti chatbot API gagal. Mari debug:

### ✅ Step 1: Cek Konfigurasi

Buka `.env` file dan pastikan:

```env
OPENAI_API_KEY=sk-...        # HARUS ada (bukan kosong)
OPENAI_CHAT_MODEL=gpt-4o-mini
OPENAI_BASE_URL=https://api.openai.com/v1
CHATBOT_PROVIDER=openai
```

Jika `OPENAI_API_KEY` kosong atau tidak ada → chatbot fallback ke mode mock (terbatas).

### ✅ Step 2: Cek Logs

Buka file log untuk detail error:

```bash
# Buka file:
storage/logs/laravel.log

# Atau tail real-time:
tail -f storage/logs/laravel.log
```

Cari line dengan `Chatbot error` atau `OpenAI API Error` untuk detail masalah.

### ✅ Step 3: Diagnostic Checklist

| Item | Check | Status |
|------|-------|--------|
| API Key | Apakah `OPENAI_API_KEY` di .env tidak kosong? | ✓/✗ |
| Model | Apakah `OPENAI_CHAT_MODEL=gpt-4o-mini`? | ✓/✗ |
| Base URL | Apakah `OPENAI_BASE_URL=https://api.openai.com/v1`? | ✓/✗ |
| Internet | Apakah bisa connect ke api.openai.com? | ✓/✗ |
| API Quota | Apakah API key masih punya quota? | ✓/✗ |

---

## Error Codes & Meanings

| Error | Penyebab | Solusi |
|-------|----------|--------|
| 401/403 | API key salah/tidak valid | Cek OPENAI_API_KEY di .env |
| 404 | Model tidak ditemukan | Cek OPENAI_CHAT_MODEL (gpt-4o-mini) |
| 429 | Quota terlampaui | Tunggu sampai besok atau upgrade API plan |
| 500+ | Server OpenAI error | Tunggu beberapa menit, coba lagi |
| Network error | Tidak bisa connect | Cek koneksi internet |

---

## Fallback Mode (Saat API Down)

Jika API tidak bisa diakses, chatbot masih bekerja tapi terbatas:
- ✅ Bisa jawab pertanyaan standar (total uang, total beras, cara bayar)
- ✅ Bisa recognize intents & navigate (lihat grafik, buka ringkasan)
- ❌ Tidak bisa answer complex questions

Pesan user akan melihat: "Layanan asisten sedang mengalami kendala..."

---

## Testing Chatbot

### 1. Test dengan Mode Mock (tanpa API)

Hapus atau kosongkan `OPENAI_API_KEY` di .env:

```env
OPENAI_API_KEY=          # Kosong = gunakan Mock provider
```

Restart app, chatbot akan gunakan mock responses (tidak real AI).

### 2. Test API Connectivity

Buka terminal dan test koneksi:

```bash
curl -X POST https://api.openai.com/v1/chat/completions \
  -H "Authorization: Bearer sk-..." \
  -H "Content-Type: application/json" \
  -d '{
    "model": "gpt-4o-mini",
    "messages": [{"role": "user", "content": "test"}]
  }'
```

Jika error 401/403 → API key salah.
Jika error 429 → Quota habis.
Jika timeout → Internet/firewall issue.

### 3. Check Logs in Real-Time

```bash
tail -100f storage/logs/laravel.log | grep -i chatbot
```

---

## Common Issues & Fixes

### Issue: "Konfigurasi belum lengkap"

**Penyebab:** `OPENAI_API_KEY` tidak di-set.

**Fix:**
1. Ambil API key dari https://platform.openai.com/api-keys
2. Paste ke `.env`: `OPENAI_API_KEY=sk-...`
3. Save file, restart app
4. Clear cache: `php artisan cache:clear`

### Issue: "Model tidak ditemukan"

**Penyebab:** Model name salah di .env.

**Fix:**
```env
OPENAI_CHAT_MODEL=gpt-4o-mini    # Jangan pakai gpt-3.5-turbo atau lainnya
```

### Issue: "Kuota penggunaan habis"

**Penyebab:** API plan sudah max requests.

**Fix:**
1. Buka https://platform.openai.com/account/billing/overview
2. Check usage & upgrade jika perlu
3. Wait sampai reset cycle (monthly)
4. Coba lagi esok

### Issue: "Koneksi ke asisten gagal"

**Penyebab:** Network issue.

**Fix:**
1. Ping test: `ping api.openai.com`
2. Check firewall/proxy settings
3. Restart app: `php artisan serve`
4. Check ISP/internet connection

---

## Development: Enable Debug Mode

Untuk debugging lebih detail, enable debug di `.env`:

```env
APP_DEBUG=true        # Show full error traces
LOG_LEVEL=debug       # Verbose logging
```

Restart app, coba chatbot lagi, check `storage/logs/laravel.log` untuk detail stack trace.

---

## When Everything Fails

1. **Check logs first**: `storage/logs/laravel.log` (cari line "Chatbot" atau "OpenAI")
2. **Test API key directly**: Gunakan curl command di atas
3. **Check environment**: `OPENAI_API_KEY`, `OPENAI_CHAT_MODEL`, `OPENAI_BASE_URL`
4. **Restart app**: `php artisan cache:clear && php artisan serve`
5. **Contact OpenAI support**: Jika API key valid tapi masih error

---

## Healthy Chatbot Indicators

✅ Chatbot berfungsi baik jika:
- Pertanyaan di-response dalam < 5 detik
- Messages muncul dengan timestamp
- Tidak ada error di logs
- Copy button bekerja
- Clear chat button bekerja

---

## Questions?

Cek file:
- `docs/CHATBOT_ZAKKY.md` — Architecture & setup
- `.env.example` — Configuration template
- `app/Services/Chatbot/` — Implementation details
