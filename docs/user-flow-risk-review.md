# User Flow Risk Review

Dokumen ini merangkum alur operator saat memakai fitur review risiko transaksi di area internal.

## Istilah Domain

- `row transaksi`: satu baris data pada tabel `zakat_transactions`
- `grup transaksi`: kumpulan row yang berbagi `no_transaksi` yang sama
- `review risiko`: evidence analisis yang disimpan per row aktif
- `ringkasan review grup`: hasil agregasi review risiko untuk satu grup transaksi

## Tujuan

- membantu operator menemukan transaksi yang perlu dicek ulang
- menjaga supaya form input tetap fokus pada pencatatan transaksi
- memindahkan proses verifikasi manual ke area history dan detail transaksi

## Aktor

- `staff`
- `admin`
- `super_admin`

Semua aktor internal tetap terlibat dalam alur transaksi, tetapi akses review risiko tidak sama untuk semua role.

- `admin` dan `super_admin` dapat melihat hasil review risiko pada halaman `Riwayat Transaksi` dan `Review Anomali`
- `admin` dan `super_admin` dapat memperbarui status review operator dari halaman detail anomali
- `staff` tetap fokus pada pencatatan dan detail transaksi, tanpa akses ke panel review risiko

## Alur Utama

### 1. Transaksi dibuat atau diubah

1. operator menyimpan transaksi dari form input
2. sistem menjalankan validasi bisnis utama
3. sistem menjalankan analisis risiko
4. sistem menyimpan hasil review risiko per row transaksi aktif
5. sistem mempertahankan keputusan review operator yang sudah pernah disimpan
6. grup transaksi akan punya ringkasan review yang bisa dibaca dari history dan detail

## 2. Operator memindai halaman history

1. operator membuka `Riwayat Transaksi`
2. operator melihat ringkasan kecil di bagian atas:
   - total grup transaksi
   - jumlah grup yang perlu dicek
   - jumlah grup belum ditinjau
   - jumlah grup yang sudah ditandai aman
3. operator memakai badge `risk_level` dan `review_status` pada tabel untuk memindai prioritas
4. jika ada banyak warning, operator memprioritaskan berdasarkan alasan, flag, kandidat pembanding, dan skor risiko internal
5. operator dapat mempersempit daftar dengan filter:
   - tahun
   - kategori
   - level risiko
   - status review

## 3. Operator memeriksa detail transaksi

1. operator dengan role `admin` atau `super_admin` membuka detail review dari grup transaksi yang ingin diperiksa
2. sistem menampilkan panel `Review Risiko` pada halaman `Review Anomali`
3. panel menampilkan:
   - level risiko
   - status review operator
   - catatan review operator terakhir
   - ringkasan hasil analisis
   - jumlah alasan deteksi
   - jumlah kandidat transaksi mirip
   - daftar alasan deteksi
   - daftar kandidat transaksi mirip

## 4. Operator mengambil keputusan

Dari halaman detail anomali, operator dapat mengganti `Status Review Operator` menjadi:

- `belum_ditinjau`
- `aman`
- `perlu_tindak_lanjut`

Saat status disimpan:

1. sistem memperbarui seluruh review record dalam grup `no_transaksi` yang sama
2. sistem mencatat `reviewed_by`
3. sistem mencatat `reviewed_at`
4. sistem menyimpan `review_note` bila operator menambahkan konteks keputusan
5. status `perlu_tindak_lanjut` wajib disertai catatan operator
6. badge pada history akan ikut berubah mengikuti hasil agregasi terbaru

## Arti Status Operasional

### Risk level

- `normal`: tidak ada sinyal utama yang perlu perhatian
- `warning`: ada sinyal yang perlu dicek ulang

Pada fase saat ini, sistem hanya memakai dua level risiko: `normal` dan `warning`.
Prioritas antar warning dibantu oleh `risk_score`, `risk_flags`, alasan deteksi, dan kandidat transaksi pembanding.

### Review status

- `belum_ditinjau`: operator belum menutup review
- `aman`: operator sudah menilai transaksi aman
- `perlu_tindak_lanjut`: operator menilai transaksi butuh pengecekan lanjutan

`review_status` adalah keputusan operator. Re-analisis otomatis boleh memperbarui hasil deteksi mesin, tetapi tidak boleh menghapus keputusan review operator yang sudah tersimpan.
`review_note` menyimpan alasan manusia di balik keputusan itu agar warning tidak berhenti di badge saja.

## Prinsip UX

- form input tidak dibebani panel review besar
- history menjadi tempat pemindaian cepat
- detail menjadi tempat verifikasi manual
- keputusan tetap di tangan operator, bukan dipaksa otomatis oleh sistem
