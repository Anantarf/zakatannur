# Pengukuran Performa Berulang

Sumber data mentah: `hasil-pengujian/06-performa/pengukuran-performa-berulang.json`.
Waktu pengukuran: 2026-08-03T19:57:39+07:00.
Pengukuran ini adalah observasi waktu pemrosesan berulang, bukan benchmark formal.

## Data Pengulangan

| Kode Uji | Pengulangan | Pertanyaan | Jalur | Model | Waktu Pemrosesan (ms) | Jumlah Token | Status |
|---|---:|---|---|---|---:|---:|---|
| PERF-01 | 1 | Hitungkan zakat fitrah untuk 4 orang. | Berbasis aturan | - | 103 | 0 | Berhasil |
| PERF-01 | 2 | Hitungkan zakat fitrah untuk 4 orang. | Berbasis aturan | - | 5 | 0 | Berhasil |
| PERF-01 | 3 | Hitungkan zakat fitrah untuk 4 orang. | Berbasis aturan | - | 5 | 0 | Berhasil |
| PERF-01 | 4 | Hitungkan zakat fitrah untuk 4 orang. | Berbasis aturan | - | 5 | 0 | Berhasil |
| PERF-01 | 5 | Hitungkan zakat fitrah untuk 4 orang. | Berbasis aturan | - | 12 | 0 | Berhasil |
| PERF-02 | 1 | Berapa total penerimaan zakat tahun ini? | Data publik | - | 36 | 0 | Berhasil |
| PERF-02 | 2 | Berapa total penerimaan zakat tahun ini? | Data publik | - | 26 | 0 | Berhasil |
| PERF-02 | 3 | Berapa total penerimaan zakat tahun ini? | Data publik | - | 11 | 0 | Berhasil |
| PERF-02 | 4 | Berapa total penerimaan zakat tahun ini? | Data publik | - | 8 | 0 | Berhasil |
| PERF-02 | 5 | Berapa total penerimaan zakat tahun ini? | Data publik | - | 18 | 0 | Berhasil |
| PERF-03 | 1 | Apa yang dimaksud nisab dan haul dalam zakat mal? | Pengetahuan cepat / retrieval langsung | - | 12 | 0 | Berhasil |
| PERF-03 | 2 | Apa yang dimaksud nisab dan haul dalam zakat mal? | Pengetahuan cepat / retrieval langsung | - | 8 | 0 | Berhasil |
| PERF-03 | 3 | Apa yang dimaksud nisab dan haul dalam zakat mal? | Pengetahuan cepat / retrieval langsung | - | 22 | 0 | Berhasil |
| PERF-03 | 4 | Apa yang dimaksud nisab dan haul dalam zakat mal? | Pengetahuan cepat / retrieval langsung | - | 8 | 0 | Berhasil |
| PERF-03 | 5 | Apa yang dimaksud nisab dan haul dalam zakat mal? | Pengetahuan cepat / retrieval langsung | - | 9 | 0 | Berhasil |
| PERF-04 | 1 | Saya punya penghasilan Rp10.000.000 per bulan, tabungan Rp100.000.000, emas 0 gram, dan hutang 0. Hitungkan zakat mal saya. | RAG dengan kalkulasi deterministik | gpt-5.6-terra | 7424 | 2734 | Berhasil |
| PERF-04 | 2 | Saya punya penghasilan Rp10.000.000 per bulan, tabungan Rp100.000.000, emas 0 gram, dan hutang 0. Hitungkan zakat mal saya. | RAG dengan kalkulasi deterministik | gpt-5.6-terra | 4566 | 2758 | Berhasil |
| PERF-04 | 3 | Saya punya penghasilan Rp10.000.000 per bulan, tabungan Rp100.000.000, emas 0 gram, dan hutang 0. Hitungkan zakat mal saya. | RAG dengan kalkulasi deterministik | gpt-5.6-terra | 5304 | 2767 | Berhasil |
| PERF-04 | 4 | Saya punya penghasilan Rp10.000.000 per bulan, tabungan Rp100.000.000, emas 0 gram, dan hutang 0. Hitungkan zakat mal saya. | RAG dengan kalkulasi deterministik | gpt-5.6-terra | 6184 | 2737 | Berhasil |
| PERF-04 | 5 | Saya punya penghasilan Rp10.000.000 per bulan, tabungan Rp100.000.000, emas 0 gram, dan hutang 0. Hitungkan zakat mal saya. | RAG dengan kalkulasi deterministik | gpt-5.6-terra | 5121 | 2753 | Berhasil |

## Rekapitulasi

| Kode Uji | Jalur | Minimum (ms) | Maksimum (ms) | Rata-rata (ms) | Token Minimum | Token Maksimum | Rata-rata Token | Status |
|---|---|---:|---:|---:|---:|---:|---:|---|
| PERF-01 | Berbasis aturan | 5 | 103 | 26,00 | 0 | 0 | 0,00 | 5/5 berhasil |
| PERF-02 | Data publik | 8 | 36 | 19,80 | 0 | 0 | 0,00 | 5/5 berhasil |
| PERF-03 | Pengetahuan cepat / retrieval langsung | 8 | 22 | 11,80 | 0 | 0 | 0,00 | 5/5 berhasil |
| PERF-04 | RAG dengan kalkulasi deterministik | 4566 | 7424 | 5719,80 | 2734 | 2767 | 2749,80 | 5/5 berhasil |

Catatan: PERF-03 bukan jalur RAG generatif penuh. Token 0 dan model `-` menunjukkan respons diselesaikan dari entri pengetahuan cepat/retrieval langsung tanpa pemanggilan model LLM.
