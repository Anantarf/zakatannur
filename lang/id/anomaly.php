<?php

return [

    'flags' => [

        'exact_duplicate' => [
            'label' => 'Potensi transaksi ganda',
            'summary' => 'Sistem menemukan transaksi lain yang sangat mirip dalam waktu berdekatan.',
            'next_step' => 'Cek apakah ini transaksi dobel atau memang pembayaran terpisah.',
        ],

        'updated_after_receipt_printed' => [
            'label' => 'Diubah setelah kwitansi tercetak',
            'summary' => 'Data transaksi berubah setelah bukti cetak pernah keluar.',
            'next_step' => 'Pastikan perubahan sah dan tidak menimbulkan selisih dengan bukti yang sudah beredar.',
        ],

        'significant_nominal_change' => [
            'label' => 'Perubahan nominal signifikan',
            'summary' => 'Total uang atau beras pada grup transaksi berubah cukup besar.',
            'next_step' => 'Bandingkan nilai lama dan baru, lalu pastikan perubahan sesuai kebutuhan lapangan.',
        ],

        'statistical_outlier' => [
            'label' => 'Outlier statistik',
            'summary' => 'Nominal transaksi jauh di atas rata-rata kebiasaan penerimaan.',
            'next_step' => 'Verifikasi apakah ada kesalahan ketik (typo) angka nol atau jamaah memang membayar dalam jumlah besar.',
        ],

    ],

];