<?php

return [

    'flags' => [

        'exact_duplicate' => [
            'label' => 'Potensi transaksi ganda',
            'summary' => 'Sistem menemukan transaksi lain yang sangat mirip dalam waktu berdekatan.',
            'next_step' => 'Cek apakah ini transaksi dobel atau memang pembayaran terpisah.',
        ],

        'transfer_duplicate_candidate' => [
            'label' => 'Kandidat duplikasi transfer',
            'summary' => 'Terdapat transfer lain dengan nominal dan pembayar yang sama dalam rentang waktu dekat.',
            'next_step' => 'Pastikan transfer bukan dobel catatan untuk pembayaran yang sama.',
        ],

        'payer_match_same_beneficiary' => [
            'label' => 'Pembayar & muzakki sama',
            'summary' => 'Transaksi lain dengan pembayar dan muzakki yang sama muncul dalam waktu berdekatan.',
            'next_step' => 'Verifikasi apakah ini pembayaran terpisah atau pengulatan input.',
        ],

        'payer_match_different_beneficiary' => [
            'label' => 'Pembayar sama, muzakki beda',
            'summary' => 'Nominal dan pembayar sama dengan transaksi lain, namun untuk muzakki yang berbeda.',
            'next_step' => 'Pastikan pembayaran ini memang ditujukan untuk muzakki yang tercatat.',
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

        'restored_after_delete' => [
            'label' => 'Dipulihkan setelah dihapus',
            'summary' => 'Transaksi ini sebelumnya dihapus dan sekarang dipulihkan.',
            'next_step' => 'Periksa kembali kelengkapan data, pembayar, dan nominal sebelum menutup kasus.',
        ],

    ],

];