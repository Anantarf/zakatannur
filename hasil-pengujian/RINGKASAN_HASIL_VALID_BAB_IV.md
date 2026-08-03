# Ringkasan Hasil Valid Untuk Bab IV

Tanggal pengujian: 2026-08-03

## Snapshot Repository

- Branch: `master`
- Commit awal pengujian: `044afd16195b1e6aac5316f834e06a5dfcb38cd2`
- Pesan commit awal: `docs(db-schema): document chatbot and zakat_periods tables`
- Commit pembekuan Bab IV: diisi setelah `git commit -m "docs(thesis): finalize Bab IV testing evidence"`.

## Konfigurasi Terverifikasi

- `APP_ENV`: `local`
- `APP_URL`: `http://localhost`
- `DB_CONNECTION`: `mysql`
- `DB_DATABASE`: `zakatannur`
- `OPENAI_CHAT_MODEL`: `gpt-5.6-terra`
- `OPENAI_FAST_MODEL`: `gpt-5.6-luna`
- `OPENAI_PREMIUM_MODEL`: `gpt-5.6-terra`
- Embedding model: `text-embedding-3-small`
- Retrieval threshold: `0.45`
- Jumlah kandidat retrieval: `3`
- Keyword fallback: aktif jika hasil semantic kosong atau embedding gagal.
- Riwayat percakapan: 8 log terakhir.
- Safety classifier threshold: confident `0.66`, ambiguous `0.45`.
- Jumlah entri basis pengetahuan: `54`.

## Hasil Pengujian Otomatis

- `php artisan test`: valid, exit code `0`, hasil `304 passed`.
- `npm run build`: valid, exit code `0`, build berhasil. Ada warning Browserslist/caniuse-lite usang, tidak menggagalkan build.

## Hasil Evaluasi Retrieval

Command: `php artisan chatbot:eval-rag`

Status: valid setelah MySQL aktif.

- True Positive: `41`
- False Negative: `0`
- True Negative: `20`
- False Positive: `0`
- Precision: `1`
- Recall: `1`
- Specificity: `1`
- F1-score: `1`
- Fact-check gagal: `0`

File bukti utama: `hasil-pengujian/02-retrieval/chatbot-eval-rag-mysql.txt`

## Hasil Evaluasi Perilaku Multi-Turn

Command: `php artisan chatbot:eval-behavior`

Status: valid setelah MySQL aktif dan API/model merespons.

- Total skenario: `19`
- Lolos: `19`
- Gagal: `0`

File bukti utama: `hasil-pengujian/04-kualitas-jawaban/chatbot-eval-behavior-mysql.txt`

## Hasil Evaluasi Rubrik Kualitas Jawaban

Command: `php artisan chatbot:eval-behavior-rubric --markdown`

Status: respons Zakky valid sudah tersimpan dan sudah dinilai manual mengikuti rubrik Bab III.

- 12 skenario kualitas jawaban.
- Total skor: `233` dari `240`.
- Rata-rata keseluruhan: `3,88` dari `4`.
- Hasil evaluasi perilaku `19/19` tidak digunakan sebagai pengganti skor kualitas jawaban.

File bukti utama: `hasil-pengujian/04-kualitas-jawaban/chatbot-eval-behavior-rubric-mysql.md`
File skor final: `hasil-pengujian/04-kualitas-jawaban/rubrik-kualitas-jawaban-final.md`

## Hasil Evaluasi Keamanan

Command: `php artisan chatbot:eval-safety`

Status: valid.

- Total kasus: `161`
- Top-1 akurasi semua tingkat keyakinan: `0.845`
- Kasus confident skor >= `0.66`: `12`
- Akurasi kasus confident: `1`
- Cakupan confident: `0.075`
- Kasus ambiguous `0.45-0.66`: `119`
- Kasus no_match `< 0.45`: `30`

Catatan interpretasi: angka ini adalah hasil classifier, bukan jaminan keamanan sistem secara menyeluruh. Bagian penting untuk Bab IV adalah cakupan confident yang rendah, yaitu `7,5%`. Classifier tepat pada kasus yang diyakininya, tetapi mayoritas kasus berada pada kategori ambiguous sehingga harus dibahas sebagai keterbatasan.

File bukti utama: `hasil-pengujian/05-keamanan/chatbot-eval-safety.txt`
Tabel skenario Bab III: `hasil-pengujian/05-keamanan/tabel-skenario-keamanan-bab-iii.md`

## Hasil E2E Publik

Command: `npm run test:e2e`

Status: valid setelah perbaikan kontras, stabilisasi rate-limit E2E, dan pembaruan baseline visual.

Ringkasan:

- 7 test passed.
- Exit code `0`.
- UI audit desktop, tablet, dan mobile lulus.
- Visual regression desktop, tablet, dan mobile lulus.
- Snapshot visual dibatasi ke area `main` dan memask elemen dinamis seperti angka, chart, gambar, dan widget chat.

File bukti utama: `hasil-pengujian/01-fungsional/npm-run-test-e2e-final-pass-11.txt`

## Konversi Retrieval Bab III

- Konteks sesuai: `41`
- Konteks tidak sesuai: `0`
- Pertanyaan tanpa konteks relevan yang berhasil ditolak: `20`
- Pertanyaan negatif yang salah menerima konteks: `0`
- Total kasus: `61`
- Sesuai hasil yang diharapkan: `61`
- Tidak sesuai: `0`

File konversi: `hasil-pengujian/02-retrieval/konversi-evaluasi-retrieval-bab-iii.md`

## Pengukuran Performa Berulang

Status: valid, 4 skenario x 5 pengulangan.

- Berbasis aturan: rata-rata `26,00 ms`, token `0`.
- Data publik: rata-rata `19,80 ms`, token `0`.
- RAG pengetahuan cepat: rata-rata `11,80 ms`, token `0`.
- RAG dengan kalkulasi deterministik: rata-rata `5719,80 ms`, rata-rata token `2749,80`, model `gpt-5.6-terra`.

File data mentah: `hasil-pengujian/06-performa/pengukuran-performa-berulang.json`
File ringkasan: `hasil-pengujian/06-performa/pengukuran-performa-berulang.md`

## Pengujian Kalkulasi

Status: valid.

- Fitrah 4 jiwa: Rp200.000 dan 10,0 kg beras.
- Fidyah 3 hari: Rp90.000 dan 2,25 kg beras.
- Zakat mal lengkap: estimasi Rp5.500.000 per tahun.
- Data zakat mal tidak lengkap: sistem meminta klarifikasi, tidak memaksa hasil.

File bukti: `hasil-pengujian/03-kalkulasi/tabel-kalkulasi-deterministik.md`

## Catatan Pembekuan

Setelah seluruh evidence ditambahkan, repository perlu dibekukan dengan commit baru agar versi penelitian dapat ditelusuri.

