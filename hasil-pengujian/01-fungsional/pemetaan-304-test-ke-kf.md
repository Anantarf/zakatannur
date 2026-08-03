# Pemetaan 304 Test ke KF-01 sampai KF-09

Sumber:

- `hasil-pengujian/01-fungsional/php-artisan-test-final.txt`
- `hasil-pengujian/01-fungsional/php-artisan-test-list.txt`

Status eksekusi: `304 passed`, exit code `0`.

| Kode Fungsi | Area Pengujian | Kelas Test Aktual | Jumlah Test | Status |
|---|---|---|---:|---|
| KF-01 | Autentikasi, otorisasi, pengguna, dan fitur enterprise | Auth\AuthenticationTest, Auth\PasswordConfirmationTest, Auth\PasswordUpdateTest, Auth\RegistrationTest, RbacGatesTest, UserManagementTest, EnterpriseFeatureTest | 27 | Lulus |
| KF-02 | Transaksi zakat, validasi transaksi, audit, risiko, riwayat, trash, kuitansi | AuditLogTest, DuplicateTransactionDetectorTest, InternalTransactionTest, ReceiptPrintTest, TransactionTest, TransactionConcurrencyTest, TransactionHistoryFilterTest, TransactionTrashTest, TransactionAnomalyDetectorTest, TransactionRiskAnalyzerTest, TransactionRiskReviewTest, TransactionRiskReviewBackfillCommandTest | 102 | Lulus |
| KF-03 | Data muzakki dan autocomplete pembayar | MuzakkiCrudTest, AutocompleteServiceTest | 8 | Lulus |
| KF-04 | Dashboard dan ringkasan publik | DashboardTest, PublicSummaryApiTest | 12 | Lulus |
| KF-05 | Periode zakat, tarif/default tahunan, dan migrasi metodologi | PeriodSettingsTest, MigrationPolicyTest, SyncBrutoMethodologyKbContentMigrationTest | 14 | Lulus |
| KF-06 | Chatbot: API, dialog, kalkulasi, konteks, sitasi, stream, sentinel, throttle | ChatbotActionDetectorTest, ChatbotApiTest, ChatbotBehaviorDatasetTest, ChatbotCalculatorServiceTest, ChatbotCitationTest, ChatbotConversationContextTest, ChatbotDiagnosticsSummaryTest, ChatbotResponseTest, ChatbotSentimentDetectorTest, ChatbotSentinelParserTest, ChatbotStreamParserTest, ThrottleChatbotTest | 113 | Lulus |
| KF-07 | Retrieval dan basis pengetahuan chatbot | ChatbotKnowledgeRetrievalEvalTest, KnowledgeBaseSeederSmokeTest | 3 | Lulus |
| KF-08 | Keamanan chatbot, guardrail, classifier, dan rate limit | ChatbotGuardrailVerifierTest, ChatbotSafetyClassifierTest, RateLimitingTest | 14 | Lulus |
| KF-09 | Export, template surat, dan smoke test aplikasi | ExportCompatibilityTest, TemplateLetterheadTest, ExampleTest | 11 | Lulus |

Total: 304 test.

Catatan: pemetaan ini menghubungkan suite otomatis repository dengan kode fungsi Bab III. Pemetaan tidak berarti setiap skenario Bab III hanya diuji oleh satu kelas test; beberapa kelas, khususnya chatbot dan transaksi, mendukung lebih dari satu fungsi.
