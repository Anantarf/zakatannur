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
        'keywords' => ['zakat fitrah', 'fitrah', 'beras fitrah', 'uang fitrah', 'jiwa'],
        'answer' => 'Zakat fitrah dicatat per jiwa dan dapat berbentuk uang atau beras sesuai ketentuan panitia pada periode berjalan. Zakky bisa membaca total jiwa dan total penerimaan fitrah dari ringkasan publik.',
        'source_label' => 'Panduan Zakat Masjid An-Nur',
        'actions' => [
            ['type' => 'suggested_reply', 'label' => 'Total jiwa', 'message' => 'Berapa total jiwa zakat fitrah?'],
            ['type' => 'suggested_reply', 'label' => 'Total beras', 'message' => 'Berapa total beras yang terkumpul?'],
        ],
    ],
    [
        'id' => 'zakat-mal',
        'title' => 'Zakat mal',
        'keywords' => ['zakat mal', 'mal', 'nishab', 'nisab', 'harta', 'penghasilan'],
        'answer' => 'Zakat mal berkaitan dengan harta yang telah memenuhi syarat. Untuk hitungan pribadi, nishab, atau kasus khusus, gunakan panduan panitia atau ustadz yang dipercaya; Zakky tidak menetapkan kewajiban personal.',
        'source_label' => 'Panduan Zakat Masjid An-Nur',
        'actions' => [
            ['type' => 'suggested_reply', 'label' => 'Kategori tercatat', 'message' => 'Kategori apa saja yang tercatat?'],
        ],
    ],
    [
        'id' => 'fidyah',
        'title' => 'Fidyah',
        'keywords' => ['fidyah', 'bayar fidyah', 'puasa', 'hari'],
        'answer' => 'Fidyah dicatat untuk kondisi tertentu yang tidak dapat menjalankan puasa sesuai ketentuan. Nominal dan tata cara lokal mengikuti arahan panitia Masjid An-Nur pada periode berjalan.',
        'source_label' => 'Panduan Zakat Masjid An-Nur',
        'actions' => [
            ['type' => 'suggested_reply', 'label' => 'Kategori tercatat', 'message' => 'Kategori apa saja yang tercatat?'],
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
