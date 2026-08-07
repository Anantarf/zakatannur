# Pemetaan Test ke KF-01 sampai KF-09 AI Assistant Zakky

Sumber:

- `hasil-pengujian/01-fungsional/php-artisan-test-final.txt`
- `hasil-pengujian/01-fungsional/php-artisan-test-list.txt`
- Acuan alur AI Assistant: `docs/ACUAN.md` bagian 6.1-6.3

Status eksekusi suite penuh terbaru: `326 passed`, exit code `0` (2026-08-07, setelah perbaikan 9 bug intent-routing/redaksi-privasi/guardrail chatbot - lihat `docs/chatbot-dokumentasi-skripsi.md` bagian 10.19-10.23).

Catatan koreksi: KF-01 sampai KF-09 pada Bab III dipakai untuk kebutuhan khusus AI Assistant Zakky. Karena itu, test autentikasi, transaksi umum, data muzakki, dashboard admin, export, dan template surat tidak dipetakan sebagai bukti utama KF Zakky. Test tersebut tetap menjadi bukti pendukung stabilitas aplikasi, tetapi bukan bukti fungsi AI Assistant.

| Kode Fungsi | Kebutuhan Fungsional AI Assistant Zakky | Bukti Test / Evaluasi Aktual | Jumlah Bukti | Status |
|---|---|---|---:|---|
| KF-01 | Sistem menerima pertanyaan pengguna melalui endpoint/widget chatbot dan mengembalikan payload jawaban. | `ChatbotApiTest::test_chatbot_returns_success_payload`, validasi payload/error API, `ThrottleChatbotTest`, `RateLimitingTest` | 6 | Lulus |
| KF-02 | Sistem menentukan jalur pertanyaan, seperti sapaan, kalkulasi cepat, data publik, pengetahuan, atau jalur AI. | `ChatbotActionDetectorTest`, routing model di `ChatbotApiTest`, hasil performa `PERF-01` sampai `PERF-04` | 37 | Lulus |
| KF-03 | Sistem melakukan retrieval basis pengetahuan dan menolak pertanyaan tanpa konteks relevan. | `ChatbotKnowledgeRetrievalEvalTest`, `KnowledgeBaseSeederSmokeTest`, `chatbot:eval-rag`, konversi retrieval Bab III | 64 kasus evaluasi/test | Lulus |
| KF-04 | Sistem menjaga konteks percakapan multi-turn, termasuk koreksi angka, lanjutan konsultasi, dan interupsi konsep. | `ChatbotConversationContextTest`, `ChatbotBehaviorDatasetTest`, `ChatbotApiTest::test_formatted_rupiah_nominal_survives_into_next_turn_context`, `chatbot:eval-behavior` | 29 skenario/test | Lulus |
| KF-05 | Sistem melakukan kalkulasi deterministik untuk fitrah, fidyah, dan zakat mal dalam cakupan yang didukung. | `ChatbotCalculatorServiceTest`, `ChatbotSentinelParserTest`, tabel kalkulasi `CALC-01` sampai `CALC-10` | 22 kasus/test | Lulus |
| KF-06 | Sistem menerapkan guardrail keamanan respons, termasuk penolakan topik luar cakupan, manipulasi instruksi, data sensitif, dan batas kewenangan. | `ChatbotGuardrailVerifierTest`, `ChatbotSafetyClassifierTest`, `ChatbotStreamParserTest::test_guardrail_violation_stops_streaming`, tabel keamanan `SEC-01` sampai `SEC-06`, `chatbot:eval-safety` | 25 kasus/test + 167 kasus classifier | Lulus dengan keterbatasan classifier |
| KF-07 | Sistem menyusun respons yang aman dan layak dibaca, termasuk sitasi, aksi frontend, streaming, dan kualitas respons konsultatif. | `ChatbotCitationTest`, `ChatbotResponseTest`, `ChatbotStreamParserTest`, `chatbot:eval-behavior-rubric`, rubrik kualitas jawaban | 23 test + 12 skenario rubrik | Lulus |
| KF-08 | Sistem mencatat log, token, model, source, dan diagnostik untuk penelusuran jalur chatbot. | `ChatbotDiagnosticsSummaryTest`, test logging di `ChatbotApiTest`, `AiChatLog` pada pengukuran performa | 5 | Lulus |
| KF-09 | Sistem menjawab informasi publik penerimaan zakat ketika intent mengarah ke ringkasan, grafik, total, atau status data publik. | `ChatbotApiTest` untuk public data, `PublicSummaryApiTest`, pengukuran performa `PERF-02` | 11 | Lulus |

## Ringkasan Cakupan

| Kelompok | Jumlah |
|---|---:|
| Total test suite aplikasi | 326 |
| Test/unit/feature yang relevan langsung dengan KF Zakky | 156 |
| Skenario evaluasi retrieval Bab III | 61 |
| Skenario evaluasi perilaku multi-turn | 19 |
| Skenario rubrik kualitas jawaban | 12 |
| Skenario keamanan respons aktual | 6 |
| Kasus classifier keamanan | 167 |

Interpretasi: `326 passed` menunjukkan repository stabil secara umum pada revisi terbaru. Untuk Bab IV AI Assistant Zakky, bukti utama adalah subset test chatbot ditambah evaluator retrieval, perilaku, kualitas jawaban, keamanan, kalkulasi, dan performa yang dipetakan pada KF-01 sampai KF-09 di atas.
