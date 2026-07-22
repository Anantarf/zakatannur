# Catatan Skripsi Chatbot Zakky

## Yang Dibuktikan Program

Bagian ini dapat dibuktikan dari test otomatis dan command evaluasi:

1. Retrieval RAG diuji lewat `php artisan test tests/Feature/ChatbotKnowledgeRetrievalEvalTest.php`.
2. Evaluasi live dengan API asli diuji lewat `php artisan chatbot:eval-rag`.
3. Dataset evaluasi mencakup 40 kasus positif dan 20 kasus negatif.
4. Log biaya/token tersimpan di `ai_chat_logs`: model, prompt token, completion token, total token, dan estimasi biaya USD.
5. Routing 3 model diuji otomatis: Luna untuk pertanyaan ringan, Terra untuk default, Sol untuk konsultasi/hitung zakat kompleks.
6. Evaluasi perilaku multi-turn deterministik diuji lewat `php artisan chatbot:eval-behavior`.
7. Evaluasi kualitas konsultan berbasis rubric disiapkan lewat `php artisan chatbot:eval-behavior-rubric`.

## Yang Perlu Diupayakan Di Luar Program

Bagian ini tidak perlu dipaksakan menjadi fitur aplikasi:

1. Evaluasi user nyata oleh dosen, panitia, atau responden.
2. Skor manual akurasi jawaban 1-5.
3. Skor manual kemudahan dipahami 1-5.
4. Catatan evaluator untuk jawaban yang kurang jelas.
5. Kesimpulan kualitatif dari hasil wawancara atau observasi.

## Evaluasi Behavior Konsultan

Evaluasi behavior dibagi menjadi dua:

1. **Boolean behavior evaluation**
   - Command: `php artisan chatbot:eval-behavior`.
   - Tujuan: memastikan perilaku yang bisa dinilai benar/salah, misalnya tidak menebak angka, tidak langsung menginterogasi data, tetap ingat konteks, dan menghitung saat data sudah lengkap.

2. **Rubric behavior evaluation**
   - Command: `php artisan chatbot:eval-behavior-rubric`.
   - Versi tabel Markdown: `php artisan chatbot:eval-behavior-rubric --markdown`.
   - Tujuan: menilai kualitas yang lebih manusiawi, seperti empati natural, tidak menghakimi, kejelasan langkah, panjang jawaban, tidak defensif, menjaga konteks, dan tone panitia masjid.

Rubric memakai skor 1-5:

| Skor | Makna |
|------|-------|
| 1 | Buruk |
| 2 | Kurang |
| 3 | Cukup |
| 4 | Baik |
| 5 | Sangat baik |

Target realistis:

- Rata-rata keseluruhan minimal 4.0/5.
- Tidak ada aspek utama di bawah 3.
- Jawaban dengan skor rendah menjadi dasar perbaikan prompt atau knowledge base.

## Format Evaluasi Manual

Gunakan tabel sederhana:

| No | Pertanyaan | Jawaban Chatbot | Akurasi 1-5 | Mudah Dipahami 1-5 | Catatan |
|----|------------|-----------------|-------------|---------------------|---------|
| 1 | | | | | |

Target realistis untuk skripsi S1: 30-50 pertanyaan manual.
