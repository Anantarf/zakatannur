# User Flow Risk Review

Dokumen ini merangkum alur operator saat memakai fitur review risiko transaksi di area internal.

## Tujuan

- membantu operator menemukan transaksi yang perlu dicek ulang
- menjaga supaya form input tetap fokus pada pencatatan transaksi
- memindahkan proses verifikasi manual ke area history dan detail transaksi

## Aktor

- `staff`
- `admin`
- `super_admin`

Semua aktor internal dapat melihat hasil review risiko. Status review operator juga dapat diperbarui dari halaman detail transaksi selama user sudah login ke area internal.

## Alur Utama

### 1. Transaksi dibuat atau diubah

1. operator menyimpan transaksi dari form input
2. sistem menjalankan validasi bisnis utama
3. sistem menjalankan analisis risiko
4. sistem menyimpan hasil review risiko per baris transaksi
5. grup transaksi akan punya ringkasan risiko yang bisa dibaca dari history dan detail

## 2. Operator memindai halaman history

1. operator membuka `Riwayat Transaksi`
2. operator melihat ringkasan kecil di bagian atas:
   - total grup transaksi
   - jumlah grup yang perlu dicek
   - jumlah kandidat kuat
   - jumlah grup belum ditinjau
   - jumlah grup yang sudah ditandai aman
3. operator memakai badge `risk_level` dan `review_status` pada tabel untuk memindai prioritas
4. operator dapat mempersempit daftar dengan filter:
   - tahun
   - kategori
   - level risiko
   - status review

## 3. Operator memeriksa detail transaksi

1. operator membuka detail dari baris transaksi yang ingin diperiksa
2. sistem menampilkan panel `Review Risiko`
3. panel menampilkan:
   - level risiko
   - status review operator
   - ringkasan hasil analisis
   - jumlah alasan deteksi
   - jumlah kandidat transaksi mirip
   - daftar alasan deteksi
   - daftar kandidat transaksi mirip

## 4. Operator mengambil keputusan

Dari halaman detail, operator dapat mengganti `Status Review Operator` menjadi:

- `belum_ditinjau`
- `aman`
- `perlu_tindak_lanjut`

Saat status disimpan:

1. sistem memperbarui seluruh review record dalam grup `no_transaksi` yang sama
2. sistem mencatat `reviewed_by`
3. sistem mencatat `reviewed_at`
4. badge pada history akan ikut berubah mengikuti hasil agregasi terbaru

## Arti Status Operasional

### Risk level

- `normal`: tidak ada sinyal utama yang perlu perhatian
- `warning`: ada sinyal yang perlu dicek ulang
- `suspicious`: ada kandidat kuat duplikasi atau anomali

### Review status

- `belum_ditinjau`: operator belum menutup review
- `aman`: operator sudah menilai transaksi aman
- `perlu_tindak_lanjut`: operator menilai transaksi butuh pengecekan lanjutan

## Prinsip UX

- form input tidak dibebani panel review besar
- history menjadi tempat pemindaian cepat
- detail menjadi tempat verifikasi manual
- keputusan tetap di tangan operator, bukan dipaksa otomatis oleh sistem
