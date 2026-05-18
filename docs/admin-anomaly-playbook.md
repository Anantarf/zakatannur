# Playbook Review Anomali Admin

Dokumen ini dipakai oleh `admin` dan `super_admin` untuk meninjau kasus di menu `Review Anomali`.

## Prinsip Dasar

- Anomali bukan berarti transaksi pasti salah.
- Aturan sistem yang pasti salah sudah ditolak saat simpan.
- Modul `Review Anomali` dipakai untuk kasus yang masih sah disimpan, tetapi patut dicek ulang.

## Arti Flag

- `Potensi transaksi ganda`
  Kasus transaksi sangat mirip dengan transaksi lain. Cek apakah ini memang split kwitansi yang sah atau input dobel.

- `Diubah setelah kwitansi tercetak`
  Ada perubahan setelah dokumen sudah pernah dicetak. Cek apakah revisi ini memang dibenarkan dan sesuai bukti.

- `Direstore setelah dihapus`
  Transaksi sempat masuk sampah lalu dikembalikan. Cek alasan restore dan pastikan tidak ada duplikasi baru.

- `Perubahan nominal signifikan`
  Nilai transaksi berubah cukup jauh dari nilai sebelumnya. Cek apakah ini salah input, revisi data, atau perubahan yang memang sah.

- `Nominal infaq tidak biasa`
  Nilai infaq jauh dari pola histori normal. Ini bisa berarti salah ketik, tetapi bisa juga donasi besar yang valid.

## Kapan Pilih `Aman`

- Bukti transaksi cocok.
- Penjelasan petugas masuk akal.
- Split transaksi atau perubahan nominal memang sah.
- Tidak ada konflik data lain setelah dicek.

## Kapan Pilih `Perlu Tindak Lanjut`

- Ada selisih data yang belum bisa dijelaskan.
- Bukti kwitansi, nominal, atau nama belum cocok.
- Perubahan pasca-kwitansi perlu konfirmasi tambahan.
- Potensi duplikasi belum bisa dipastikan aman.
- Admin memang akan langsung membetulkan atau menyesuaikan transaksi yang sedang direview.

## Urutan Review Yang Disarankan

1. Buka menu `Review Anomali`.
2. Filter ke `Belum Ditinjau`.
3. Baca `Risk Level`, `Flag Aktif`, dan `Alasan deteksi`.
4. Jika ada, cek `Kandidat transaksi mirip`.
5. Cocokkan dengan transaksi asli dan status kwitansi.
6. Putuskan `Aman` atau `Perlu Tindak Lanjut`.

## Catatan Operasional

- Transaksi yang sudah pernah dicetak kwitansinya hanya bisa diubah oleh admin/super_admin.
- Staff tidak melihat data anomaly dan tidak bisa menutup review.
- Jika kasus ternyata valid, status `Aman` tetap penting supaya antrean review bersih.
