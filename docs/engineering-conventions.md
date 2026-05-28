# Engineering Conventions

Dokumen ini melengkapi `docs/COLLAB_PROTOCOL.md` dengan aturan teknis harian supaya repo tetap rapi saat fitur baru masuk.

## 1. Source of Truth

- Label domain seperti `risk_level`, `review_status`, dan istilah operasional lain sebaiknya dipusatkan di model atau service domain, bukan dirangkai ulang di Blade.
- Jika satu aturan bisnis dipakai di query, service, dan UI, usahakan ada satu sumber utama yang jelas lalu layer lain hanya mengonsumsi hasilnya.

## 2. Blade vs Service

- Blade dipakai untuk rendering dan kondisi presentasi ringan.
- Formatting, label mapping, agregasi status, pemilihan copy berdasarkan rule bisnis, dan metadata list sebaiknya disiapkan dari controller/service.
- Jika sebuah Blade mulai memanggil helper domain atau merangkai banyak `match`/`str_replace`, itu sinyal logika perlu dipindahkan ke backend.

## 3. Service Boundaries

- Satu service sebaiknya punya satu fokus utama.
- Jika satu service mulai mengurus query, agregasi, label UI, dan fallback sekaligus, pecah menjadi helper kecil sebelum fitur baru menumpuk.
- Untuk refactor kecil, prioritaskan ekstraksi method privat yang punya nama domain jelas sebelum memecah file besar-besaran.

## 4. Build Artifacts

- `public/build/` dianggap artefak hasil build Vite.
- Perlakukan perubahan di folder itu sebagai output release, bukan tempat edit manual.
- Jika asset berubah karena perubahan source frontend, review source utamanya dulu di `resources/`, baru cek artefaknya sebagai hasil turunan.
- Jangan campurkan cleanup artefak build dengan perubahan backend/domain jika tidak perlu.

## 5. Scratch Files

- File eksperimen, dump, atau pengecekan sementara simpan di `tmp/` atau area scratch lain yang memang di-ignore.
- Jangan menaruh file `test_*`, dump HTML, atau helper sekali pakai di root repo.

## 6. Verification Minimum

Sebelum merge perubahan yang menyentuh behavior:

- jalankan `composer test` untuk cek regresi dasar
- jalankan `composer verify` jika perubahan menyentuh Blade, routing, atau area yang sensitif terhadap render
- jika ada migration baru, pastikan dampak operasionalnya jelas dan sudah di-ACC

## 7. Review Mindset

- Prioritaskan konsistensi perilaku lebih dulu, baru kerapian struktur.
- Jika harus memilih, bug yang berpotensi membingungkan operator lebih penting daripada cleanup kosmetik.
- Setelah perilaku aman, baru rapikan duplikasi, naming, dan kebisingan repo.
