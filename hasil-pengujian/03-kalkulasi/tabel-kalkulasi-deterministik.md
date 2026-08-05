# Tabel Pengujian Kalkulasi Deterministik

Sumber output aktual:

- `hasil-pengujian/03-kalkulasi/output-kalkulasi-aktual.txt`
- `hasil-pengujian/03-kalkulasi/output-kalkulasi-extended-terbaru.txt`

Sumber nilai nisab pada pengujian MySQL lokal: periode aktif di tabel `zakat_periods` memakai override langsung `nishab_annual_rupiah = Rp91.681.728`, mengikuti Keputusan Ketua BAZNAS RI Nomor 15 Tahun 2026 (bukan lagi hasil kali `nishab_gold_gram x gold_price_per_gram` - lihat `AnnualZakatDefaults::nishabAnnual()` di `app/Services/Transactions/AnnualZakatDefaults.php`).

| Kode | Pertanyaan | Nilai yang Diharapkan | Nilai yang Dikenali Sistem | Hasil Hitung Acuan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| CALC-01 | Hitungkan zakat fitrah untuk 4 orang. | 4 jiwa | 4 jiwa | Uang: 4 x Rp50.000 = Rp200.000; beras: 4 x 2,5 kg = 10 kg | Uang Rp200.000; beras 10,0 kg | Sesuai |
| CALC-02 | Hitungkan fidyah untuk 3 hari. | 3 hari | 3 hari | Uang: 3 x Rp30.000 = Rp90.000; beras: 3 x 0,75 kg = 2,25 kg | Uang Rp90.000; beras 2,25 kg | Sesuai |
| CALC-03 | Penghasilan Rp10.000.000/bulan, tabungan Rp100.000.000, emas 0 gram, hutang 0. | Penghasilan 10 juta/bulan, tabungan 100 juta, emas 0, hutang 0 | Nilai dikenali sesuai input | Penghasilan: Rp120.000.000 x 2,5% = Rp3.000.000; tabungan: Rp100.000.000 x 2,5% = Rp2.500.000; total Rp5.500.000 | Total estimasi zakat Rp5.500.000/tahun | Sesuai |
| CALC-04 | Penghasilan Rp4.000.000/bulan dan tabungan Rp2.000.000. | Data belum lengkap karena emas dan hutang tidak disebutkan | Penghasilan dan tabungan dikenali; emas/hutang diminta ulang | Sistem seharusnya tidak memaksa hitung final sebelum data lengkap | Sistem meminta nilai emas simpanan dan/atau hutang jatuh tempo | Sesuai |
| CALC-05 | Penghasilan Rp7.640.144/bulan, tabungan Rp0, emas 0 gram, hutang 0. | Kasus batas tepat nisab penghasilan: 12 x Rp7.640.144 = Rp91.681.728 | Nilai dikenali sesuai input | Rp91.681.728 x 2,5% = Rp2.292.043,2, dibulatkan ke bawah oleh sistem | Zakat penghasilan Rp2.292.043/tahun; komponen tabungan/emas tidak ditampilkan karena nilainya nol | Sesuai |
| CALC-06 | Fitrah tahun 2026 itu berapa ya per orang? | Format tidak valid untuk kalkulasi jumlah jiwa; angka tahun tidak boleh dianggap jumlah orang | Sistem tidak mengambil angka 2026 sebagai jumlah jiwa | Sistem seharusnya meminta jumlah orang, bukan menghitung 2026 jiwa | Sistem bertanya: "Berapa orang yang mau dihitung fitrahnya?" | Sesuai |
| CALC-07 | Saya panen padi 2000 kg. Hitungkan zakat pertanian saya sekarang. | Perhitungan di luar cakupan otomatis zakat mal lanjutan | Panen 2000 kg dikenali sebagai kasus pertanian | Sistem boleh memberi arahan umum, tetapi tidak menetapkan angka zakat personal | Sistem menjelaskan nisab/kadar umum dan menyatakan belum bisa menetapkan angka pribadi; diarahkan ke panitia/ustadz | Sesuai |
| CALC-08 | Sentinel kalkulasi hanya berisi `income_monthly = Rp10.000.000`. | Pengujian cakupan zakat penghasilan saja | Sistem mengenali hanya data penghasilan | Penghasilan: Rp120.000.000 x 2,5% = Rp3.000.000; tidak ada komponen tabungan/emas | Sistem hanya menampilkan `Estimasi Zakat Penghasilan`; tidak menampilkan `Estimasi Zakat Tabungan & Emas` atau total gabungan | Sesuai |
| CALC-09 | Sentinel kalkulasi hanya berisi `savings = Rp100.000.000`. | Pengujian cakupan tabungan saja | Sistem mengenali hanya data tabungan | Tabungan: Rp100.000.000 x 2,5% = Rp2.500.000 | Sistem hanya menampilkan `Estimasi Zakat Tabungan & Emas`; tidak menampilkan `Estimasi Zakat Penghasilan` atau total gabungan | Sesuai |
| CALC-10 | Sentinel kalkulasi berisi `income_monthly = Rp10.000.000`, `savings = 0`, `gold_gram = 0`, `debt = 0`. | Guard agar field wealth nol dari LLM tidak memunculkan blok kosong | Sistem mengenali penghasilan dan mengabaikan wealth nol untuk penentuan section | Penghasilan: Rp120.000.000 x 2,5% = Rp3.000.000; tabungan/emas nol tidak perlu ditampilkan | Sistem hanya menampilkan `Estimasi Zakat Penghasilan`; tidak menampilkan `Estimasi Zakat Tabungan & Emas` atau total gabungan | Sesuai |

## Asumsi Perhitungan Zakat Mal

Nilai nisab Rp91.681.728 berasal dari override `nishab_annual_rupiah` pada periode aktif MySQL lokal, diisi mengikuti SK Ketua BAZNAS RI Nomor 15 Tahun 2026 - bukan angka yang dibuat manual di dokumen, dan bukan lagi hasil kali 85 gram x harga emas (angka SK ini tidak habis dibagi 85 gram secara genap, sehingga skema gram x harga tidak bisa merepresentasikannya persis). Rumus yang dipakai sistem ada di `app/Services/Chatbot/ChatbotZakatMalGuide.php`, nilai nisabnya dari `AnnualZakatDefaults::nishabAnnual()`.

Zakat penghasilan dan zakat tabungan/emas dinilai terhadap nisab secara terpisah:

- Zakat penghasilan memakai penghasilan bruto tahunan.
- Zakat tabungan/emas memakai harta simpanan saat ini ditambah nilai emas dan dikurangi hutang.
- Keduanya tidak digabung sebagai satu basis harta.
- Komponen hasil ditampilkan sesuai cakupan data yang relevan: penghasilan saja menampilkan komponen penghasilan, tabungan/emas saja menampilkan komponen harta simpanan, dan total gabungan hanya ditampilkan bila lebih dari satu komponen relevan.
- Field tabungan/emas bernilai nol tidak memunculkan komponen tabungan/emas kosong, agar output tidak menyiratkan ada harta simpanan yang benar-benar dinilai.
- Total akhir adalah penjumlahan nilai zakat yang sudah dihitung dari dua basis berbeda, hanya ketika kedua basis memang relevan pada input.

Dengan demikian, CALC-03 bukan menjumlahkan penghasilan tahunan dan tabungan sebagai basis yang sama. Sistem menghitung Rp3.000.000 dari penghasilan dan Rp2.500.000 dari tabungan, lalu menjumlahkan nilai zakatnya menjadi Rp5.500.000 sebagai estimasi total - kedua basis (Rp120.000.000 dan Rp100.000.000) masih di atas nisab baru Rp91.681.728, jadi hasil akhirnya kebetulan tidak berubah walau nisabnya naik dari Rp76.500.000.
