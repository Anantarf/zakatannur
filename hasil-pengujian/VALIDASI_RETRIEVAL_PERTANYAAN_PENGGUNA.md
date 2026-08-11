# Validasi Retrieval Pertanyaan Pengguna

## 1. Informasi Pengujian

| Item | Nilai |
|---|---|
| Repository commit | `53f03f9b6c7eadfe778d8e382aad20b35c9262da` |
| Tanggal pengujian | 11 Agustus 2026 |
| Environment | Local, Laravel 9.52.21, PHP 8.2.12, MySQL via XAMPP |
| Model embedding | `text-embedding-3-small` |
| Threshold retrieval | `0.45` |
| Top-k | `3` |
| Jumlah entry Knowledge Base aktif | `54` |
| Mekanisme uji | Bootstrap Laravel read-only melalui `php -r`, memanggil `KnowledgeRetriever::search($query, 3)` dan `ChatbotActionDetector::intent()` |

Catatan: pengujian ini tidak mengubah konfigurasi retrieval, threshold, Knowledge Base, keyword fallback, atau routing.

## 2. Ground Truth

| ID | Persona | Query | Konteks Relevan Ada? | Entry/Topik Target | Alasan |
|---|---|---|---|---|---|
| U01-Q01 | Pak Amri | assalamualaikum, mau tanya. gaji saya sebulan 8jt, itu udah kena wajib zakat blm ya? | Ya | `zakat-penghasilan` | Query menanyakan kewajiban zakat atas gaji bulanan. |
| U01-Q02 | Pak Amri | saya sama istri punya tabungan bareng skitar 100jt udah setaun lebih, itu di gabung apa dihitung sendiri2 zakatnya? | Ya | `zakat-tabungan`, `zakat-harta-campuran` | Query menanyakan tabungan dan pemisahan kepemilikan harta campuran. |
| U01-Q03 | Pak Amri | kalo hartanya belum saya itung semua, apa bisa langsung dikira-kira aja zakatnya pak/bu? | Ya | `zakat-harta-campuran`, `catatan-metodologi-zakat`, `cara-zakky-menganalisis-kasus` | KB memuat prinsip pemisahan harta, kehati-hatian perhitungan, dan analisis bertahap. |
| U01-Q04 | Pak Amri | orang tua saya udah gak puasa krn sakit, itu bayar fidyah pake uang boleh apa harus beras? | Ya | `fidyah`, `siapa-boleh-fidyah`, `case-sakit-menahun` | Query membahas fidyah untuk orang tua/sakit dan bentuk pembayaran. |
| U01-Q05 | Pak Amri | mau nyalurin zakat lewat masjid an-nur, prosesnya gmn ya, transfer atau dateng langsung? | Ya | `cara-bayar-zakat`, `jadwal-penerimaan`, `konfirmasi-pembayaran` | Query membahas alur pembayaran/penyaluran zakat melalui Masjid An-Nur. |
| U01-Q06 | Pak Amri | masjid an-nur ada pengajian rutin bwt bapak2 gak ya, pengen ikutan | Tidak | - | KB aktif tidak memuat informasi pengajian rutin bapak-bapak. |
| U02-Q01 | Rafi | kak mau nanya, aku ada emas peninggalan nenek kira2 90gr udah lama disimpen, itu wajib dizakatin juga ga sih? | Ya | `zakat-emas-perak` | Query menanyakan zakat emas yang disimpan lama. |
| U02-Q02 | Rafi | kalo penghasilan freelance tiap bulan naik turun gitu, ngitung zakatnya gimana ya soalnya ga tetap | Ya | `zakat-penghasilan` | Query menanyakan zakat penghasilan tidak tetap/freelance. |
| U02-Q03 | Rafi | misal duit gaji ku masuk ke tabungan terus disimpen, ntar pas ditabung dihitung zakat lagi ga? apa udah kena pas gajian doang | Ya | `zakat-penghasilan`, `zakat-tabungan`, `zakat-harta-campuran` | Query menggabungkan penghasilan, tabungan, dan risiko perhitungan ganda. |
| U02-Q04 | Rafi | duh kemaren lupa bayar zakat fitrah pas lebaran udah lewat gini gimana ya masih bisa bayar ga | Ya | `kapan-bayar-zakat-fitrah`, `zakat-fitrah` | Query membahas waktu pembayaran zakat fitrah yang terlewat. |
| U02-Q05 | Rafi | nisab zakat penghasilan taun ini berapa sih kak, aku baru mau mulai belajar | Ya | `zakat-penghasilan`, `nisab-dan-haul` | Query menanyakan nisab zakat penghasilan. |
| U02-Q06 | Rafi | eh btw masjidnya bisa disewa buat resepsi nikahan ga ya, temenku nanya | Tidak | - | KB aktif tidak memuat informasi sewa masjid untuk resepsi. |

## 3. Hasil Retrieval

| ID | Routing | Kandidat Retrieval Top-3 | Score | Diterima/Ditolak | Kategori | Catatan |
|---|---|---|---|---|---|---|
| U01-Q01 | AI RAG | `zakat-penghasilan`; `zakat-penghasilan-potongan-pajak-bpjs`; `zakat-mal-terlewat-tahun-lalu` | 0.609; 0.556; 0.533 | Diterima | TP | Target utama ditemukan pada peringkat 1. |
| U01-Q02 | AI RAG | `zakat-tabungan`; `zakat-harta-campuran`; `zakat-saham-investasi-reksadana` | 0.607; 0.570; 0.550 | Diterima | TP | Dua target relevan ditemukan pada peringkat 1 dan 2. |
| U01-Q03 | AI RAG | `zakat-harta-campuran`; `zakat-warisan`; `zakat-piutang` | 0.686; 0.650; 0.641 | Diterima | TP | Target harta campuran ditemukan pada peringkat 1; kandidat lain masih dalam domain zakat mal. |
| U01-Q04 | AI RAG | `siapa-boleh-fidyah`; `case-sakit-menahun`; `fidyah` | 0.631; 0.629; 0.602 | Diterima | TP | Seluruh kandidat top-3 relevan untuk fidyah/sakit. |
| U01-Q05 | AI RAG | `cara-bayar-zakat`; `salur-zakat-sendiri-vs-panitia`; `jadwal-penerimaan` | 0.716; 0.639; 0.606 | Diterima | TP | Target alur pembayaran ditemukan pada peringkat 1. |
| U01-Q06 | AI RAG | `cara-bayar-zakat`; `fidyah`; `jadwal-penerimaan` | 0.480; 0.470; 0.453 | Diterima | FP | Query pengajian tidak ada di KB, tetapi sistem menerima konteks lain di atas threshold. |
| U02-Q01 | AI RAG | `zakat-emas-perak`; `zakat-mal-terlewat-tahun-lalu`; `zakat-uang-pesangon` | 0.619; 0.480; 0.478 | Diterima | TP | Target emas ditemukan pada peringkat 1. |
| U02-Q02 | AI RAG | `zakat-penghasilan`; `zakat-penghasilan-potongan-pajak-bpjs`; `zakat-tabungan` | 0.687; 0.592; 0.559 | Diterima | TP | Target penghasilan ditemukan pada peringkat 1. |
| U02-Q03 | AI RAG | `zakat-tabungan`; `zakat-penghasilan`; `zakat-uang-pesangon` | 0.726; 0.664; 0.645 | Diterima | TP | Dua target utama ditemukan pada peringkat 1 dan 2. |
| U02-Q04 | Fast-path intent terdeteksi: `ask_payment_info`; retrieval tetap diuji terpisah | `zakat-mal-terlewat-tahun-lalu`; `kapan-bayar-zakat-fitrah`; `bingung-pilih-pembayaran` | 0.661; 0.637; 0.605 | Diterima | TP | Target waktu zakat fitrah ditemukan pada top-3, meskipun bukan peringkat 1. |
| U02-Q05 | AI RAG | `zakat-penghasilan`; `zakat-penghasilan-potongan-pajak-bpjs`; `catatan-metodologi-zakat` | 0.634; 0.585; 0.578 | Diterima | TP | Target penghasilan ditemukan pada peringkat 1. |
| U02-Q06 | AI RAG | - | - | Ditolak | TN | Query sewa resepsi tidak memiliki kandidat di atas threshold. |

## 4. Confusion Matrix

| Kategori | Jumlah |
|---|---:|
| True Positive | 10 |
| False Negative | 0 |
| True Negative | 1 |
| False Positive | 1 |
| Total query | 12 |

## 5. Metrik

| Metrik | Rumus | Nilai |
|---|---|---:|
| Precision | TP / (TP + FP) | 0.91 |
| Recall | TP / (TP + FN) | 1.00 |
| Specificity | TN / (TN + FP) | 0.50 |
| F1-score | 2PR / (P + R) | 0.95 |

## 6. Ringkasan per Persona

| Persona | Jumlah Query | TP | FN | TN | FP | Temuan Penting |
|---|---:|---:|---:|---:|---:|---|
| U01 - Pak Amri | 6 | 5 | 0 | 0 | 1 | Pertanyaan zakat praktis berhasil ditangani, tetapi query pengajian rutin diterima sebagai konteks Masjid/Fidyah/Jadwal. |
| U02 - Rafi | 6 | 5 | 0 | 1 | 0 | Gaya santai, singkatan, dan query multi-topik tetap menemukan konteks relevan; query sewa resepsi berhasil ditolak. |

## 7. Temuan

- Variasi bahasa natural seperti "gaji 8jt", "skitar 100jt", "freelance naik turun", dan "duit gaji masuk ke tabungan" tetap menemukan entry relevan dalam top-3.
- Query multi-topik berhasil ditangani pada U01-Q02 dan U02-Q03 karena dua konteks utama muncul bersama dalam top-3.
- Query U02-Q04 menemukan target `kapan-bayar-zakat-fitrah` pada top-3, tetapi peringkat 1 adalah `zakat-mal-terlewat-tahun-lalu`; ini menunjukkan kedekatan semantik kata "terlewat" dapat mengangkat topik zakat mal yang terlewat.
- Query U01-Q06 menjadi FP: tidak ada konteks pengajian rutin bapak-bapak di KB, tetapi kata "Masjid An-Nur" dan gaya pertanyaan layanan lokal membuat kandidat `cara-bayar-zakat`, `fidyah`, dan `jadwal-penerimaan` melewati threshold.
- Query U02-Q06 menjadi TN karena tidak ada kandidat retrieval di atas threshold untuk topik sewa resepsi.

## 8. Perbandingan dengan Dataset Terstruktur

| Dataset | Total | Positif | Negatif | TP | FN | TN | FP | Precision | Recall | Specificity | F1-score |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| Dataset terstruktur | 61 | 41 | 20 | 41 | 0 | 20 | 0 | 1.00 | 1.00 | 1.00 | 1.00 |
| Validasi pertanyaan pengguna | 12 | 10 | 2 | 10 | 0 | 1 | 1 | 0.91 | 1.00 | 0.50 | 0.95 |

Validasi pertanyaan pengguna menunjukkan recall tetap 1.00 pada query positif yang diuji, tetapi precision dan specificity turun karena satu query negatif tentang pengajian rutin diterima sebagai konteks lain. Hasil ini berlaku untuk 12 query, 54 entry Knowledge Base, konfigurasi retrieval, dan commit repository yang diuji; hasil tidak digeneralisasikan ke seluruh variasi pertanyaan pengguna.
