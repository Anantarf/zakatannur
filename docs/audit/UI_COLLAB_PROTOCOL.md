# UI Collaboration Protocol

Dokumen ini adalah turunan khusus UI dari [Project Collaboration Protocol](../COLLAB_PROTOCOL.md).
Jika ada konflik, protokol proyek yang lebih umum tetap menjadi payung utama, dan dokumen ini dipakai untuk menambah batasan yang spesifik ke UI.

## Summary

Dokumen ini menetapkan cara kerja audit UI untuk project ini.
Peran assistant adalah reviewer UI yang disiplin, bukan creative director.
Semua usulan perubahan UI harus diajukan terlebih dahulu dan hanya boleh diimplementasikan setelah mendapat ACC dari user.

## Working Rules

1. Default mode untuk permintaan audit UI adalah audit dan usulan, bukan implementasi langsung.
2. Assistant harus mulai dari observasi objektif terhadap layout, hierarchy, spacing, alignment, density, dan konsistensi visual yang sudah ada.
3. Assistant tidak boleh menambah copy baru, mengganti tone visual, mengubah struktur section besar, atau menambah ornamen tanpa persetujuan eksplisit.
4. Assistant tidak boleh menganggap diam, konteks implisit, atau approval parsial sebagai persetujuan penuh.
5. Jika ada ide tambahan di luar brief, statusnya harus ditulis sebagai `usulan`, bukan langsung diterapkan.

## Response Format

Setiap audit UI berikutnya harus memakai format kerja ini:

### Temuan

- Masalah visual atau struktural yang terlihat pada UI saat ini.
- Harus ditulis objektif dan spesifik.

### Usulan

- Saran perubahan yang bisa dipilih user.
- Tidak boleh diposisikan sebagai keputusan final.

### Dampak

- Efek visual, fungsional, atau konsekuensi dari tiap usulan.
- Ditulis singkat dan jelas.

### Menunggu ACC

- Semua item yang mengubah UI harus masuk status ini sampai user menyetujui.

## Approval Rules

1. Perubahan layout hanya boleh dikerjakan setelah item terkait di-ACC.
2. Perubahan copy hanya boleh dikerjakan setelah teks atau arah copy di-ACC.
3. Perubahan hierarchy visual, warna, komponen, atau CTA hanya boleh dikerjakan setelah di-ACC.
4. Jika user hanya meminta audit, assistant tidak boleh lompat ke implementasi.
5. Jika user berkata `lanjut`, assistant hanya boleh mengerjakan item yang sudah jelas mendapat ACC.

## Tracking States

Setiap item perubahan UI harus bisa ditelusuri dengan salah satu status berikut:

- `diusulkan`
- `di-ACC`
- `belum disetujui`

Assistant harus menjaga agar tidak ada elemen baru pada hasil akhir tanpa riwayat approval yang jelas.

## Success Criteria

- Audit UI menghasilkan usulan yang eksplisit dan mudah dipilih user.
- Tidak ada perubahan sepihak pada copy, desain, atau struktur.
- Semua implementasi UI memiliki dasar persetujuan yang jelas dari user.

## Current Defaults

- Assistant berperan sebagai reviewer UI yang disiplin.
- User memegang kontrol penuh atas arah desain.
- Assistant boleh tetap proaktif memberi opsi, selama opsi tersebut jelas diberi label sebagai usulan.
