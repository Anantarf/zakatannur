# Database Schema Reference

Dokumen ini merupakan ringkasan skema database utama pada proyek **Zakat Annur**. Dokumen ini dirancang sebagai acuan (*context*) bagi AI Assistant agar tidak perlu menebak-nebak struktur tabel saat mengembangkan fitur.

*(Terakhir diupdate: Juli 2026 berdasarkan file migration.)*

---

## 1. Tabel `users`
Menyimpan data otentikasi dan profil pengguna aplikasi (Petugas / Admin / Super Admin).
- `id` (PK)
- `name` (string)
- `email` (string, unique)
- `password` (string)
- `role` (string) - *admin, super_admin, petugas, dll.*
- `remember_token`
- `timestamps`

## 2. Tabel `muzakki`
Menyimpan data identitas pembayar zakat utama.
- `id` (PK)
- `name` (string, 150) - *Memiliki index*
- `address` (text, nullable)
- `phone` (string, 30, nullable) - *Memiliki index*
- `timestamps`
- `deleted_at` (soft deletes)

## 3. Tabel `zakat_transactions`
Tabel transaksi utama untuk penerimaan Zakat, Infaq, Sedekah, Fidyah, dll.
- `id` (PK)
- `no_transaksi` (string, 30) - *Bisa duplikat jika multiple item dalam 1 struk (lihat revisi migration), atau unique tergantung konfigurasi terbaru*
- `muzakki_id` (FK -> muzakki)
- `pembayar_nama`, `pembayar_no_hp` dsb. *(ditambahkan via alter table)*
- `category` (string, 10) - *zakat_fitrah, zakat_maal, infaq, fidyah, dll.*
- `tahun_zakat` (unsignedInteger)
- `zakat_period_id` (FK -> zakat_periods, nullable) - *ditambahkan via alter table, menggantikan `tahun_zakat` sebagai acuan periode*
- `hijri_year`, `hijri_month` (unsignedSmallInteger/unsignedTinyInteger, nullable) *(ditambahkan via alter table)*
- `metode` (string, 10) - *uang, beras*
- `is_transfer` (boolean)
- `nominal_uang` (unsignedBigInteger, nullable)
- `jumlah_beras_kg` (decimal 10,2, nullable)
- `jiwa` (unsignedInteger, nullable) - *Jumlah jiwa untuk Zakat Fitrah*
- `hari` (unsignedInteger, nullable) - *Jumlah hari untuk Fidyah*
- `is_khusus` (boolean)
- `default_fitrah_cash_per_jiwa_used` (unsignedInteger, nullable)
- `default_fidyah_per_hari_used` (unsignedInteger, nullable)
- `shift` (string, nullable)
- `petugas_id` (FK -> users)
- `keterangan` (text, nullable)
- `status` (string, 10) - *valid, void, dll.*
- `void_reason` (text, nullable)
- `voided_at` (timestamp, nullable)
- `voided_by` (FK -> users, nullable)
- `waktu_terima` (timestamp, nullable)
- `timestamps`
- `deleted_at` (soft deletes, *trash fields*)

## 4. Tabel `annual_settings` *(legacy, digantikan `zakat_periods` — lihat #6)*
Menyimpan konfigurasi tarif zakat fitrah & fidyah yang berlaku per tahun.
- `id` (PK)
- `year` (unsignedInteger, unique)
- `default_fitrah_cash_per_jiwa` (unsignedInteger)
- `default_fidyah_per_hari` (unsignedInteger)
- `beras_per_jiwa` (decimal) *(ditambahkan via alter table)*
- `fidyah_beras_per_hari` (decimal) *(ditambahkan via alter table)*
- `chart_window` dsb. *(ditambahkan via alter table)*
- `timestamps`

## 5. Tabel `transaction_risk_reviews`
Menyimpan hasil deteksi anomali/risiko pada transaksi tertentu.
- `id` (PK)
- `zakat_transaction_id` (FK -> zakat_transactions, unique)
- `group_no_transaksi` (string, 30)
- `risk_level` (string, 20) - *misalnya: warning*
- `risk_score` (unsignedInteger)
- `risk_flags` (json, nullable)
- `reasons` (json, nullable)
- `duplicate_candidates` (json, nullable)
- `review_note` (text, nullable)
- `detector_version` (string, 20)
- `review_status` (string, 30) - *belum_ditinjau, aman, dll.*
- `reviewed_by` (FK -> users, nullable)
- `reviewed_at` (timestamp, nullable)
- `checked_at` (timestamp, nullable)
- `timestamps`

## 6. Tabel `zakat_periods`
Menggantikan `annual_settings` sebagai sumber konfigurasi periode zakat (mendukung multi-periode per tahun, kalender Hijriah, dan nishab emas). `zakat_transactions.zakat_period_id` merujuk ke sini.
- `id` (PK)
- `code` (string, 40, unique) - *misal `ramadan-2026-1`*
- `label` (string, 80)
- `gregorian_year` (unsignedInteger)
- `hijri_year`, `hijri_month` (nullable)
- `sequence` (unsignedTinyInteger, default 1)
- `starts_at`, `ends_at` (date, nullable)
- `default_fitrah_cash_per_jiwa` (unsignedInteger, default 50000)
- `default_fitrah_beras_per_jiwa` (decimal 8,2, default 2.50)
- `default_fidyah_per_hari` (unsignedInteger, default 30000)
- `default_fidyah_beras_per_hari` (decimal 8,2, default 0.75)
- `nishab_gold_gram` (unsignedSmallInteger, default 85) *(ditambahkan via alter table)*
- `gold_price_per_gram` (unsignedBigInteger, default 900000) *(ditambahkan via alter table — ini acuan harga emas tetap yang dipakai `ChatbotZakatMalGuide`)*
- `chart_starts_at`, `chart_ends_at`, `chart_fallback_buffer_days`
- `is_active` (boolean, default false)
- `timestamps`
- unique `(gregorian_year, sequence)`

## 7. Tabel `knowledge_bases`
Basis pengetahuan chatbot Zakky (RAG). Diambil oleh `KnowledgeRetriever`, embedding-nya dicache lewat `chatbot:cache-embeddings` (lihat `KnowledgeEmbeddingsCache`).
- `id` (PK)
- `slug` (string, unique)
- `title` (string)
- `keywords` (json, nullable)
- `answer` (text)
- `source_label` (string, nullable)
- `actions` (json, nullable)
- `is_active` (boolean, default true)
- `timestamps`

## 8. Tabel `ai_chat_logs`
Log setiap pertanyaan & jawaban chatbot, dipakai `ChatbotChatLogger` dan command evaluasi (`chatbot:eval-*`).
- `id` (PK)
- `user_id` (FK -> users, nullable)
- `session_id` (string, 80, nullable)
- `question` (text)
- `question_md5` (string, 32, nullable) - *dedup per sesi, bukan cache respons*
- `intent` (string, 80, nullable)
- `context_summary` (text, nullable)
- `answer` (text)
- `source_type` (string, 40, nullable)
- `sentiment` (string, nullable)
- `confidence_source` (enum: knowledge, calculation, ai, fallback, nullable)
- `model` (string, nullable)
- `prompt_tokens`, `cached_tokens`, `completion_tokens`, `total_tokens` (unsignedInteger, nullable)
- `estimated_cost_usd` (decimal 12,8, nullable)
- `timestamps`
- unique `(session_id, question_md5)`, index `(session_id, created_at)`, index `created_at`

## 9. Tabel `chatbot_feedbacks`
Feedback 👍/👎 dari tombol di UI chat.
- `id` (PK)
- `session_id` (string, nullable, index)
- `message` (text)
- `rating` (enum: helpful, unhelpful, index)
- `ip_address` (ipAddress, nullable)
- `timestamps`

## 10. Tabel `ai_audit_summaries`
Ringkasan audit AI periodik (dibuat manual oleh admin, bukan otomatis dari chatbot Zakky).
- `id` (PK)
- `generated_by` (FK -> users)
- `date_from`, `date_to` (date)
- `total_activities`, `sensitive_activities_count` (unsignedInteger, default 0)
- `summary`, `recommendation` (text)
- `context_snapshot` (json, nullable)
- `timestamps`

## 11. Tabel `app_settings`
Key-value store untuk konfigurasi aplikasi (misal `active_zakat_period_id`).
- `id` (PK)
- `key` (string, 100, unique)
- `value` (text, nullable)
- `timestamps`

## 12. Tabel `audit_logs`
Log aktivitas admin/petugas di luar chatbot (CRUD transaksi, dsb).
- `id` (PK)
- `actor_user_id` (FK -> users, nullable)
- `action` (string, 100)
- `subject_type` (string, 150, nullable), `subject_id` (unsignedBigInteger, nullable)
- `metadata` (json, nullable)
- `ip` (string, 45, nullable), `user_agent` (text, nullable)
- `timestamps`

---
> **Catatan untuk AI**: Jika perlu membuat join, pastikan relasi `muzakki_id` dari `zakat_transactions` tidak asumsikan data muzakki selalu utuh, perhatikan `softDeletes`. Selalu rujuk kolom di dokumen ini sebelum menulis query raw/Eloquent. Untuk fitur baru yang butuh konfigurasi periode, pakai `zakat_periods`, bukan `annual_settings` (legacy).
