# Panduan Baca — Hasil Pengujian Bab IV (AI Assistant Zakky)

Folder ini isinya bukti mentah pengujian. Kalau cuma mau baca ringkasannya,
baca **`RINGKASAN_HASIL_VALID_BAB_IV.md`** di folder ini — itu narasi lengkap
Bab IV, sudah menjelaskan semua angka dan menaut ke file bukti di bawah.

Tabel di bawah ini panduan cepat: per folder, file mana yang penting dibaca
dan file mana yang boleh dilewati (bukti mentah/pendukung/superseded).

## 00-konfigurasi

| File | Kenapa penting |
|---|---|
| `konfigurasi-tanpa-rahasia.txt` | Snapshot `.env` (tanpa secret) saat pengujian dijalankan. |
| `commit.txt` | Commit hash yang jadi acuan tiap tahap pembekuan evidence. |
| `daftar-command-chatbot.txt` | Daftar command Artisan khusus chatbot yang dipakai di seluruh pengujian ini. |

`daftar-command.txt` — daftar command Artisan lengkap aplikasi, cuma referensi, tidak perlu dibaca baris per baris.

## 01-fungsional

| File | Kenapa penting |
|---|---|
| `pemetaan-304-test-ke-kf.md` | **Baca ini.** Memetakan test otomatis ke KF-01–KF-09, ini bukti fungsional utama. |
| `php-artisan-test-final.txt` | Output lengkap `php artisan test` (309 passed). |
| `npm-run-test-e2e-final-pass-11.txt` | Output E2E publik (Playwright). |

Boleh dilewati: `*-exit-code.txt` (isinya cuma angka `0`), `php-artisan-test-list.txt` (daftar mentah semua nama test), `npm-run-build-final.txt` (log build, bukan hasil uji).

## 02-retrieval

| File | Kenapa penting |
|---|---|
| `chatbot-eval-rag-mysql.txt` | **Baca ini.** Hasil `chatbot:eval-rag` — precision/recall/F1 retrieval. |
| `konversi-evaluasi-retrieval-bab-iii.md` | Konversi 61 skenario retrieval Bab III ke hasil aktual. |

Boleh dilewati: `hasil-eval-rag-terbaru.txt` — identik byte-for-byte dengan `chatbot-eval-rag-mysql.txt`, cuma nama beda.

## 03-kalkulasi

| File | Kenapa penting |
|---|---|
| `tabel-kalkulasi-deterministik.md` | **Baca ini.** Tabel CALC-01–CALC-10 dengan hasil yang diharapkan vs aktual. |
| `output-kalkulasi-extended-terbaru.txt` | Transkrip respons Zakky mentah untuk tiap kasus CALC (bukti pendukung tabel di atas). |

Boleh dilewati: `output-kalkulasi-aktual.txt` dan `output-kalkulasi-extended.txt` — versi lama sebelum revisi nisab BAZNAS, sudah digantikan oleh `-extended-terbaru.txt`. `calculation-extended-runner.php` — script generator, bukan hasil.

## 04-kualitas-jawaban

| File | Kenapa penting |
|---|---|
| `chatbot-eval-behavior-mysql.txt` | Hasil `chatbot:eval-behavior` — 19/19 skenario multi-turn lolos. |
| `chatbot-eval-behavior-rubric-mysql.md` | Rubrik penilaian 12 skenario kualitas jawaban. |
| `rubrik-kualitas-jawaban-final.md` | **Baca ini.** Skor final rubrik (233/240) dengan justifikasi tiap butir. |

Boleh dilewati: `hasil-rubrik-terbaru.md` (draft antara sebelum skor final), `*-exit-code.txt`.

## 05-keamanan

| File | Kenapa penting |
|---|---|
| `chatbot-eval-safety.txt` | **Baca ini.** Hasil classifier keamanan (167 kasus, akurasi 0.862). |
| `tabel-skenario-keamanan-bab-iii.md` | Tabel SEC-01–SEC-06 dengan skenario dan hasil yang diharapkan. |
| `respons-aktual-keamanan.txt` | Transkrip respons Zakky aktual untuk tiap skenario SEC. |

Boleh dilewati: `respons-aktual-keamanan-terbaru.txt` dan `.json` — versi antara/format alternatif dari file di atas. `security-response-runner.php` — script generator.

## 06-performa

| File | Kenapa penting |
|---|---|
| `pengukuran-performa-berulang.md` | **Baca ini.** Ringkasan rata-rata waktu respons & token per jalur (4 skenario x 5 pengulangan). |
| `pengukuran-performa-berulang.json` | Data mentah tiap pengulangan (buat siapa yang mau cek angka individual). |

Boleh dilewati: `pengukuran-performa-terbaru.txt` — draft sebelum data final `-berulang.*`. `performance-runner.php` — script generator.

## Catatan perbaikan (2026-08-05)

Saat merapikan dokumentasi ini, ditemukan 3 file yang masih tersimpan UTF-16LE
padahal `RINGKASAN_HASIL_VALID_BAB_IV.md` bagian "Format Arsip Evidence"
mengklaim semua sudah dikonversi ke UTF-8 tanpa BOM:
`01-fungsional/php-artisan-test-final.txt`, `01-fungsional/php-artisan-test-list.txt`,
dan `03-kalkulasi/output-kalkulasi-extended-terbaru.txt` (dua yang terakhir
berstatus bukti utama). Sudah dikonversi ke UTF-8 tanpa BOM, isi tidak diubah.

Kerjaan QA responsivitas/aksesibilitas UI (audit halaman internal, perbaikan
touch-target tombol) tidak masuk ke dokumentasi ini karena di luar cakupan
Bab IV (khusus AI Assistant Zakky KF-01–KF-09).
