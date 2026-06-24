<?php

return [
    [
        'id' => 'cara-bayar-zakat',
        'title' => 'Cara bayar zakat',
        'keywords' => ['bayar', 'pembayaran', 'transfer', 'cara bayar', 'rekening', 'qris', 'tunai'],
        'answer' => 'Pembayaran zakat di Masjid An-Nur mengikuti arahan panitia pada periode berjalan. Jika metode transfer, QRIS, atau tunai belum tertulis di portal, konfirmasi nomor rekening dan nominal ke panitia sebelum membayar.',
        'source_label' => 'Panduan Zakat Masjid An-Nur',
        'actions' => [
            ['type' => 'suggested_reply', 'label' => 'Total uang', 'message' => 'Berapa total uang yang terkumpul?'],
            ['type' => 'open_tab', 'target' => 'laporan', 'label' => 'Buka Ringkasan'],
        ],
    ],
    [
        'id' => 'zakat-fitrah',
        'title' => 'Zakat fitrah',
        'keywords' => ['zakat fitrah', 'fitrah', 'beras fitrah', 'uang fitrah', 'jiwa', 'fitrah berapa', 'hitung fitrah'],
        'answer' => 'Zakat fitrah adalah zakat wajib menjelang Hari Raya Idul Fitri, dibayar per jiwa.

📊 TAKARAN DI MASJID AN-NUR (Tahun 2026):
• Uang: Rp 50.000 per jiwa
• Beras: 2,5 kg per jiwa (atau setara)

💡 CONTOH PERHITUNGAN:
Jika keluarga Anda 4 orang:
• Uang: 4 × Rp 50.000 = Rp 200.000
• Beras: 4 × 2,5 kg = 10 kg

⚠️ Takaran mengikuti periode aktif di Masjid An-Nur. Jika ada perubahan, silakan konfirmasi ke panitia.',
        'source_label' => 'Panduan Zakat Masjid An-Nur',
        'actions' => [
            ['type' => 'suggested_reply', 'label' => 'Total jiwa', 'message' => 'Berapa total jiwa zakat fitrah?'],
            ['type' => 'suggested_reply', 'label' => 'Cara bayar', 'message' => 'Bagaimana cara membayar zakat fitrah?'],
        ],
    ],
    [
        'id' => 'zakat-mal-definisi',
        'title' => 'Apa itu zakat mal',
        'keywords' => ['zakat mal', 'zakat harta', 'apa itu zakat mal', 'mal', 'harta', 'penghasilan', 'nishab', 'nisab'],
        'answer' => 'Zakat mal adalah zakat atas harta/aset yang mencapai nishab (nilai minimum wajib zakat).

📌 DEFINISI:
Harta yang telah disimpan selama 1 tahun hijriyah (haul) dan mencapai nilai minimum tertentu.

💡 MANFAAT:
• Membersihkan harta dari hak mustahik (fakir miskin)
• Membantu kategori asnaf yang ditentukan (QS 9:60)
• Ibadah mendekatkan diri kepada Allah
• Membangun kesadaran sosial ekonomi

📊 STANDAR UMUM SAAT INI (Global):
• Nishab emas: 85 gram emas murni (~Rp 50-80 juta, tergantung harga emas)
• Nishab perak: 595 gram (~Rp 10-15 juta)
• Tarif zakat: 2.5% dari total aset yang memenuhi syarat
• Syarat waktu: Harta sudah dimiliki 1 tahun (haul)

Harta yang dihitung: Uang, emas, perak, barang dagangan, investasi, dll
Dikurangi: Kebutuhan hidup dan hutang yang mengikat

⚠️ PENTING:
• Perhitungan kompleks tergantung jenis aset
• Fatwa berbeda antar mazhab
• ZAKKY TIDAK BISA MENETAPKAN KEWAJIBAN PRIBADI
• Untuk kasus pribadi, diskusi dengan Panitia Zakat An-Nur',
        'source_label' => 'Fatwa Ulama (BAZNAS, Syafi\'i), Qur\'an 9:60',
        'actions' => [
            ['type' => 'suggested_reply', 'label' => 'Contoh hitung', 'message' => 'Contoh perhitungan zakat mal?'],
            ['type' => 'suggested_reply', 'label' => 'Nishab berapa', 'message' => 'Nishab zakat mal berapa?'],
        ],
    ],
    [
        'id' => 'zakat-mal-contoh',
        'title' => 'Contoh perhitungan zakat mal',
        'keywords' => ['contoh zakat mal', 'hitung zakat mal', 'skenario zakat', 'case zakat mal', 'berapa zakat'],
        'answer' => 'Contoh BUKAN keputusan final Anda — setiap kasus berbeda per mazhab dan kondisi.

📌 SKENARIO 1: PNS dengan gaji + tabungan
Gaji bulanan: Rp 10 juta × 12 bulan = Rp 120 juta/tahun
Tabungan: Rp 50 juta
Total aset bruto: Rp 170 juta

Dikurangi kebutuhan hidup (1 tahun): ~Rp 30 juta
Aset neto: Rp 140 juta (di atas nishab ~Rp 50-80 juta ✓)

Zakat 2.5%: Rp 140 juta × 2.5% = Rp 3.5 juta per tahun

───────────────────────────────────────────

📌 SKENARIO 2: Pedagang dengan usaha
Pendapatan kotor per tahun: Rp 300 juta
Biaya operasional: Rp 150 juta
Laba bersih: Rp 150 juta
Dikurangi kebutuhan hidup: Rp 30 juta
Aset neto untuk zakat: Rp 120 juta

Zakat 2.5%: Rp 120 juta × 2.5% = Rp 3 juta per tahun

───────────────────────────────────────────

⚠️ DISCLAIMER PENTING:
• Contoh ini BUKAN fatwa pribadi Anda
• Nishab actual berbeda: harga emas fluktuatif, jenis harta beragam
• Hutang dan kebutuhan hidup dihitung berbeda per mazhab
• ZAKKY BISA SALAH dalam kasus pribadi
• KONFIRMASI KE PANITIA ZAKAT AN-NUR SEBELUM MEMBAYAR

Untuk hitung yang akurat, silakan konsultasi dengan Panitia atau ustadz terpercaya.',
        'source_label' => 'Fatwa BAZNAS, Ulama Syafi\'i/Hanafi',
        'actions' => [
            ['type' => 'suggested_reply', 'label' => 'Apa itu zakat mal', 'message' => 'Apa itu zakat mal?'],
            ['type' => 'suggested_reply', 'label' => 'Hubungi panitia', 'message' => 'Bagaimana cara konsultasi dengan panitia?'],
        ],
    ],
    [
        'id' => 'fidyah',
        'title' => 'Fidyah',
        'keywords' => ['fidyah', 'bayar fidyah', 'puasa', 'hari', 'fidyah berapa', 'hitung fidyah'],
        'answer' => 'Fidyah adalah pembayaran untuk kondisi tidak menjalankan puasa Ramadan. Dibayar per hari puasa yang tertinggal.

📊 TAKARAN DI MASJID AN-NUR (Tahun 2026):
• Uang: Rp 30.000 per hari
• Beras: 0,75 kg per hari (atau setara)

💡 CONTOH PERHITUNGAN:
Jika tidak bisa puasa 5 hari:
• Uang: 5 × Rp 30.000 = Rp 150.000
• Beras: 5 × 0,75 kg = 3,75 kg

⚠️ Takaran berlaku periode aktif Ramadan. Silakan konfirmasi ke panitia untuk periode berbeda.',
        'source_label' => 'Panduan Zakat Masjid An-Nur',
        'actions' => [
            ['type' => 'suggested_reply', 'label' => 'Apa itu fidyah?', 'message' => 'Apa perbedaan fidyah dan zakat fitrah?'],
            ['type' => 'suggested_reply', 'label' => 'Cara bayar', 'message' => 'Bagaimana cara membayar fidyah?'],
        ],
    ],
    [
        'id' => 'infaq-shodaqoh',
        'title' => 'Infaq shodaqoh',
        'keywords' => ['infaq', 'infak', 'shodaqoh', 'sedekah', 'sodaqoh'],
        'answer' => 'Infaq dan shodaqoh adalah penerimaan sosial yang dicatat terpisah dari kategori zakat. Di portal publik, nominalnya masuk dalam rekap kategori jika sudah ada transaksi valid.',
        'source_label' => 'Panduan Zakat Masjid An-Nur',
        'actions' => [
            ['type' => 'suggested_reply', 'label' => 'Kategori terbesar', 'message' => 'Kategori terbesar apa?'],
            ['type' => 'open_tab', 'target' => 'laporan', 'label' => 'Buka Ringkasan'],
        ],
    ],
    [
        'id' => 'batas-waktu-zakat',
        'title' => 'Batas waktu zakat',
        'keywords' => ['batas waktu', 'deadline', 'kapan bayar', 'waktu zakat'],
        'answer' => 'Batas waktu dan jadwal penerimaan zakat mengikuti ketentuan panitia pada periode berjalan. Jika jadwal belum tampil di portal, konfirmasi langsung ke panitia agar tidak salah periode.',
        'source_label' => 'Panduan Zakat Masjid An-Nur',
        'actions' => [
            ['type' => 'suggested_reply', 'label' => 'Update terakhir', 'message' => 'Kapan data terakhir diperbarui?'],
        ],
    ],
    [
        'id' => 'cara-baca-ringkasan',
        'title' => 'Cara membaca ringkasan',
        'keywords' => ['cara baca ringkasan', 'ringkasan penerimaan', 'laporan penerimaan'],
        'answer' => 'Tab Ringkasan Penerimaan menampilkan total jiwa, uang, beras, dan rincian per kategori. Gunakan tab ini untuk membaca kondisi penerimaan terbaru tanpa menunggu rekap manual.',
        'source_label' => 'Panduan Portal Zakat Masjid An-Nur',
        'actions' => [
            ['type' => 'open_tab', 'target' => 'laporan', 'label' => 'Buka Ringkasan'],
            ['type' => 'suggested_reply', 'label' => 'Update terakhir', 'message' => 'Kapan data terakhir diperbarui?'],
        ],
    ],
    [
        'id' => 'cara-baca-grafik',
        'title' => 'Cara membaca grafik',
        'keywords' => ['cara baca grafik', 'grafik harian', 'grafik'],
        'answer' => 'Tab Grafik Harian membantu melihat pola penerimaan zakat per hari dalam periode aktif. Grafik berguna untuk melihat hari ramai, tren penerimaan, dan konteks data ringkasan.',
        'source_label' => 'Panduan Portal Zakat Masjid An-Nur',
        'actions' => [
            ['type' => 'open_tab', 'target' => 'grafik', 'label' => 'Lihat Grafik'],
            ['type' => 'suggested_reply', 'label' => 'Total uang', 'message' => 'Berapa total uang yang terkumpul?'],
        ],
    ],
    [
        'id' => 'batas-kemampuan-zakky',
        'title' => 'Batas kemampuan Zakky',
        'keywords' => ['zakky bisa apa', 'batas kemampuan', 'ai salah', 'akurasi', 'validasi', 'bisa bantu apa'],
        'answer' => 'Zakky membantu membaca data publik dan menjawab panduan umum. Zakky tidak menetapkan kewajiban pribadi, nomor rekening, keputusan fikih khusus, atau validasi pembayaran final; hal penting tetap dikonfirmasi ke panitia zakat.',
        'source_label' => 'Panduan Zakky',
        'actions' => [
            ['type' => 'suggested_reply', 'label' => 'Total uang', 'message' => 'Berapa total uang yang terkumpul?'],
            ['type' => 'suggested_reply', 'label' => 'Cara bayar', 'message' => 'Bagaimana cara membayar zakat?'],
        ],
    ],
];
