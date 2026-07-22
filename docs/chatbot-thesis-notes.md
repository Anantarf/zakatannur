# Catatan Skripsi Chatbot Zakky

## Yang Dibuktikan Program

Bagian ini dapat dibuktikan dari test otomatis dan command evaluasi:

1. Retrieval RAG diuji lewat `php artisan test tests/Feature/ChatbotKnowledgeRetrievalEvalTest.php`.
2. Evaluasi live dengan API asli diuji lewat `php artisan chatbot:eval-rag`.
3. Dataset evaluasi mencakup 40 kasus positif dan 20 kasus negatif.
4. Log biaya/token tersimpan di `ai_chat_logs`: model, prompt token, completion token, total token, dan estimasi biaya USD.
5. Routing 3 model diuji otomatis: Luna untuk pertanyaan ringan, Terra untuk default, Sol untuk konsultasi/hitung zakat kompleks.

## Yang Perlu Diupayakan Di Luar Program

Bagian ini tidak perlu dipaksakan menjadi fitur aplikasi:

1. Evaluasi user nyata oleh dosen, panitia, atau responden.
2. Skor manual akurasi jawaban 1-5.
3. Skor manual kemudahan dipahami 1-5.
4. Catatan evaluator untuk jawaban yang kurang jelas.
5. Kesimpulan kualitatif dari hasil wawancara atau observasi.

## Format Evaluasi Manual

Gunakan tabel sederhana:

| No | Pertanyaan | Jawaban Chatbot | Akurasi 1-5 | Mudah Dipahami 1-5 | Catatan |
|----|------------|-----------------|-------------|---------------------|---------|
| 1 | | | | | |

Target realistis untuk skripsi S1: 30-50 pertanyaan manual.
