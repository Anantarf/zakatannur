# Tabel Pengujian Kalkulasi Deterministik

Sumber output aktual: `hasil-pengujian/03-kalkulasi/output-kalkulasi-aktual.txt`.

| Kode | Pertanyaan | Nilai yang Diharapkan | Nilai yang Dikenali Sistem | Hasil Hitung Acuan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| CALC-01 | Hitungkan zakat fitrah untuk 4 orang. | 4 jiwa | 4 jiwa | Uang: 4 x Rp50.000 = Rp200.000; beras: 4 x 2,5 kg = 10 kg | Uang Rp200.000; beras 10,0 kg | Sesuai |
| CALC-02 | Hitungkan fidyah untuk 3 hari. | 3 hari | 3 hari | Uang: 3 x Rp30.000 = Rp90.000; beras: 3 x 0,75 kg = 2,25 kg | Uang Rp90.000; beras 2,25 kg | Sesuai |
| CALC-03 | Penghasilan Rp10.000.000/bulan, tabungan Rp100.000.000, emas 0 gram, hutang 0. | Penghasilan 10 juta/bulan, tabungan 100 juta, emas 0, hutang 0 | Nilai dikenali sesuai input | Penghasilan: Rp120.000.000 x 2,5% = Rp3.000.000; tabungan: Rp100.000.000 x 2,5% = Rp2.500.000; total Rp5.500.000 | Total estimasi zakat Rp5.500.000/tahun | Sesuai |
| CALC-04 | Penghasilan Rp4.000.000/bulan dan tabungan Rp2.000.000. | Data belum lengkap karena emas dan hutang tidak disebutkan | Penghasilan dan tabungan dikenali; emas/hutang diminta ulang | Sistem seharusnya tidak memaksa hitung final sebelum data lengkap | Sistem meminta nilai emas simpanan dan/atau hutang jatuh tempo | Sesuai |

Catatan: nishab pada CALC-03 yang digunakan respons aktual adalah Rp76.500.000. Untuk CALC-04, status sesuai karena sistem memilih klarifikasi data, bukan membuat hasil final dengan asumsi tersembunyi.
