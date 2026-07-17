<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\KnowledgeBase;
use App\Services\Transactions\AnnualZakatDefaultsResolver;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        // Nominal was previously hardcoded here as its own separate source of truth, which drifted
        // from the real configured ZakatPeriod (fidyah showed Rp30.000 in KB text vs Rp50.000
        // actually configured; nisab showed a stale hardcoded Rp91.681.728 vs the Rp76.500.000 the
        // real calculator computes). Reading from AnnualZakatDefaultsResolver - the same source the
        // chatbot's own calculator (ChatbotZakatMalGuide) and the transaction system use - means the
        // KB text can't drift from what the system actually does again.
        $year = (int) AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
        $defaults = app(AnnualZakatDefaultsResolver::class)->resolve($year);

        $zakatFitrahUang = $defaults->fitrahCashPerJiwa;
        $zakatFitrahBerasKg = $defaults->fitrahBerasPerJiwa;
        $fidyahUang = $defaults->fidyahPerHari;
        $fidyahBerasKg = $defaults->fidyahBerasPerHari;
        $nishabRupiah = $defaults->nishabGoldGram * $defaults->goldPricePerGram;

        // Heredoc interpolation can't call number_format() inline, so format once here.
        $zakatFitrahUangFmt = number_format($zakatFitrahUang, 0, ',', '.');
        $fidyahUangFmt = number_format($fidyahUang, 0, ',', '.');
        $nishabRupiahFmt = number_format($nishabRupiah, 0, ',', '.');
        $nishabBulananFmt = number_format((int) ($nishabRupiah / 12), 0, ',', '.');

        $entries = [
            [
                'id' => 'tentang-zakky',
                'title' => 'Tentang Zakky',
                'keywords' => [
                    'zakky',
                    'chatbot',
                    'asisten zakat',
                    'bantuan zakat',
                    'bisa bantu apa',
                    'tentang zakky',
                    'masjid an-nur',
                ],
                'answer' => <<<TEXT
Zakky adalah asisten informasi publik Masjid An-Nur yang membantu jamaah memahami panduan umum zakat, fidyah, infaq/shodaqoh, cara pembayaran, estimasi perhitungan sederhana, dan informasi transparansi publik.

Zakky dapat membantu:
1. Menjelaskan zakat fitrah, zakat mal, fidyah, infaq, dan shodaqoh.
2. Membantu jamaah memilih jenis pembayaran yang sesuai.
3. Memberikan estimasi perhitungan sederhana.
4. Menjawab case umum dan case khusus secara hati-hati.
5. Mengarahkan jamaah ke panitia atau ustadz jika kasusnya membutuhkan keputusan lebih lanjut.

Zakky bukan pengganti panitia atau ustadz. Untuk kasus yang membutuhkan keputusan fikih, hasil akhirnya sebaiknya dikonfirmasi kepada pihak yang berwenang.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'batas-kemampuan-zakky',
                'title' => 'Batas Kemampuan Zakky',
                'keywords' => [
                    'batas zakky',
                    'zakky bisa apa',
                    'fatwa',
                    'konsultasi',
                    'keputusan zakat',
                    'validasi pembayaran',
                    'data pribadi',
                    'akurasi zakky',
                ],
                'answer' => <<<TEXT
Zakky adalah asisten informasi publik. Zakky membantu memberikan arah awal atas kondisi pribadi jamaah, lalu mengarahkan ke panitia atau ustadz jika detail kasus perlu dipastikan.

Zakky tidak dapat:
1. Mengeluarkan fatwa pribadi.
2. Memastikan seseorang wajib atau tidak wajib zakat dalam kasus kompleks.
3. Mengubah, membatalkan, atau memverifikasi transaksi.
4. Menampilkan data pribadi muzakki atau mustahik.
5. Menggantikan keputusan panitia atau ustadz.

Jika pertanyaan berkaitan dengan kondisi pribadi, hutang, emas perhiasan, ibu hamil/menyusui, usaha, atau kasus lain yang memerlukan pertimbangan fikih, Zakky akan memberi arahan awal dan menyarankan konsultasi kepada panitia atau ustadz.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'kapan-konsultasi-panitia',
                'title' => 'Kapan Harus Konsultasi ke Panitia',
                'keywords' => [
                    'konsultasi panitia',
                    'hubungi panitia',
                    'tanya panitia',
                    'konfirmasi pembayaran',
                    'sudah transfer',
                    'bukti pembayaran',
                    'jadwal zakat',
                    'rekening zakat',
                ],
                'answer' => <<<TEXT
Silakan konsultasi atau konfirmasi ke panitia Masjid An-Nur jika pertanyaan berkaitan dengan layanan lokal dan teknis pembayaran.

Contohnya:
1. Nomor rekening, QRIS, atau metode pembayaran resmi.
2. Jadwal penerimaan zakat.
3. Konfirmasi setelah transfer.
4. Bukti pembayaran atau kuitansi.
5. Pembayaran belum tercatat.
6. Salah memilih jenis pembayaran.
7. Informasi lokasi penerimaan zakat.

Zakky dapat memberi panduan umum, tetapi konfirmasi akhir layanan Masjid An-Nur tetap melalui panitia.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'kapan-konsultasi-ustadz',
                'title' => 'Kapan Harus Konsultasi ke Ustadz',
                'keywords' => [
                    'konsultasi ustadz',
                    'fatwa',
                    'hukum zakat',
                    'kasus khusus',
                    'ragu wajib zakat',
                    'qadha atau fidyah',
                    'emas perhiasan',
                    'hutang',
                    'ibu hamil',
                    'menyusui',
                ],
                'answer' => <<<TEXT
Silakan konsultasi kepada ustadz jika pertanyaan membutuhkan keputusan fikih yang bersifat pribadi atau kompleks.

Contohnya:
1. Memiliki hutang besar atau cicilan jangka panjang.
2. Penghasilan tidak tetap.
3. Emas perhiasan yang dipakai sehari-hari.
4. Ibu hamil atau menyusui yang tidak berpuasa.
5. Utang puasa lama.
6. Zakat usaha dengan aset, piutang, dan hutang usaha.
7. Ragu apakah sudah wajib zakat atau belum.

Zakky hanya memberi gambaran umum agar jamaah lebih siap saat berkonsultasi.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'pengertian-zakat',
                'title' => 'Pengertian Zakat',
                'keywords' => [
                    'apa itu zakat',
                    'pengertian zakat',
                    'definisi zakat',
                    'zakat adalah',
                    'makna zakat',
                ],
                'answer' => <<<TEXT
Zakat adalah harta tertentu yang wajib dikeluarkan oleh seorang muslim apabila telah memenuhi syarat tertentu, kemudian diberikan kepada golongan yang berhak menerimanya.

Secara umum, zakat memiliki dua fungsi utama:
1. Fungsi ibadah, yaitu bentuk ketaatan seorang muslim kepada Allah SWT.
2. Fungsi sosial, yaitu membantu pemerataan kesejahteraan dan meringankan beban mustahik.

Zakat berbeda dari infaq dan shodaqoh karena zakat memiliki ketentuan khusus, seperti jenis harta, nisab, kadar, waktu, dan golongan penerima.
TEXT,
                'source_label' => 'BAZNAS; UU No. 23 Tahun 2011',
            ],
            [
                'id' => 'dasar-hukum-zakat',
                'title' => 'Dasar Hukum Zakat',
                'keywords' => [
                    'dasar hukum zakat',
                    'hukum zakat',
                    'regulasi zakat',
                    'undang-undang zakat',
                    'uu zakat',
                    'pma zakat',
                    'aturan zakat',
                ],
                'answer' => <<<TEXT
Zakat memiliki dasar hukum dalam ajaran Islam dan regulasi pengelolaan zakat di Indonesia.

Dasar penting:
1. Al-Qur'an menjelaskan golongan penerima zakat, salah satunya pada QS. At-Taubah ayat 60.
2. Pengelolaan zakat di Indonesia diatur dalam Undang-Undang Nomor 23 Tahun 2011 tentang Pengelolaan Zakat.
3. Syarat dan tata cara penghitungan zakat mal serta zakat fitrah diatur dalam Peraturan Menteri Agama Nomor 52 Tahun 2014 beserta perubahannya.

Dalam konteks Masjid An-Nur, regulasi ini menjadi dasar bahwa pengelolaan zakat perlu dilakukan secara tertib, amanah, transparan, dan dapat dipertanggungjawabkan.
TEXT,
                'source_label' => 'QS At-Taubah: 60; UU No. 23 Tahun 2011; PMA No. 52 Tahun 2014',
            ],
            [
                'id' => 'jenis-zakat',
                'title' => 'Jenis-Jenis Zakat',
                'keywords' => [
                    'jenis zakat',
                    'macam zakat',
                    'zakat fitrah',
                    'zakat mal',
                    'zakat harta',
                    'zakat apa saja',
                ],
                'answer' => <<<TEXT
Secara umum, zakat terbagi menjadi dua jenis utama:

1. Zakat Fitrah
Zakat yang wajib dibayarkan oleh setiap muslim menjelang Idulfitri. Zakat ini berkaitan dengan jiwa dan biasanya dihitung per orang.

2. Zakat Mal
Zakat atas harta yang dimiliki seseorang apabila telah memenuhi syarat tertentu, seperti mencapai nisab dan memenuhi ketentuan sesuai jenis hartanya. Zakat mal dapat mencakup zakat penghasilan, emas, perak, tabungan, perdagangan, dan jenis harta lainnya.

Jika ingin memberi secara sukarela di luar kewajiban zakat, maka masuk ke kategori infaq atau shodaqoh.
TEXT,
                'source_label' => 'BAZNAS',
            ],
            [
                'id' => 'zakat-fitrah',
                'title' => 'Zakat Fitrah',
                'keywords' => [
                    'zakat fitrah',
                    'fitrah',
                    'beras fitrah',
                    'uang fitrah',
                    'fitrah berapa',
                    'zakat per jiwa',
                    'idulfitri',
                    'hitung zakat fitrah',
                    'fitrah 4 orang',
                    'fitrah keluarga',
                    'jumlah jiwa',
                    'kalkulator fitrah',
                ],
                'answer' => <<<TEXT
Zakat fitrah adalah zakat yang wajib ditunaikan oleh setiap muslim menjelang Idulfitri. Zakat ini dibayarkan per jiwa, baik untuk diri sendiri maupun anggota keluarga yang menjadi tanggungan.

Acuan umum BAZNAS menyebutkan zakat fitrah dapat ditunaikan dalam bentuk beras/makanan pokok 2,5 kg atau 3,5 liter per jiwa. Jika dibayarkan dalam bentuk uang, nominalnya dapat menyesuaikan harga beras/makanan pokok dan ketentuan lembaga atau wilayah setempat.

Untuk layanan zakat Masjid An-Nur saat ini:
1. Uang: Rp {$zakatFitrahUangFmt} per jiwa.
2. Beras: {$zakatFitrahBerasKg} kg per jiwa.

Rumus: jumlah jiwa x nominal per jiwa.

Contoh untuk 4 jiwa:
1. Uang: 4 x Rp {$zakatFitrahUangFmt} = Rp 200.000.
2. Beras: 4 x {$zakatFitrahBerasKg} kg = 10 kg.

Nominal dapat diperbarui sesuai periode zakat dan informasi resmi panitia.
TEXT,
                'source_label' => 'BAZNAS; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'zakat-mal',
                'title' => 'Zakat Mal',
                'keywords' => [
                    'zakat mal',
                    'zakat harta',
                    'mal',
                    'zakat penghasilan',
                    'zakat emas',
                    'zakat tabungan',
                    'zakat usaha',
                    'nisab',
                    'haul',
                ],
                'answer' => <<<TEXT
Zakat mal adalah zakat atas harta yang wajib dikeluarkan apabila telah memenuhi syarat tertentu, seperti kepemilikan penuh, mencapai nisab, dan memenuhi ketentuan haul sesuai jenis hartanya.

Contoh harta yang dapat termasuk zakat mal:
1. Penghasilan.
2. Emas dan perak.
3. Tabungan atau simpanan.
4. Aset perdagangan atau usaha.
5. Harta lain yang memenuhi ketentuan zakat.

Kadar zakat mal umumnya 2,5%, tetapi cara menghitungnya dapat berbeda sesuai jenis harta dan kondisi pribadi.

Untuk pembayaran di Masjid An-Nur, zakat penghasilan, zakat emas, zakat tabungan, dan zakat usaha dapat dicatat dalam kategori Zakat Mal.
TEXT,
                'source_label' => 'BAZNAS; PMA No. 52 Tahun 2014',
            ],
            [
                'id' => 'nisab-dan-haul',
                'title' => 'Nisab dan Haul',
                'keywords' => [
                    'nisab',
                    'haul',
                    'apa itu nisab',
                    'apa itu haul',
                    'nisab itu apa',
                    'haul itu apa',
                    'batas nisab',
                    'pengertian nisab',
                    'pengertian haul',
                    'nisab zakat',
                    'haul zakat',
                    'nishab',
                ],
                'answer' => <<<TEXT
Nisab dan haul adalah dua syarat utama yang menentukan apakah suatu harta sudah wajib dizakati.

1. Nisab
Nisab adalah batas minimal nilai harta yang membuatnya wajib dizakati. Kalau harta belum mencapai nisab, belum wajib zakat. Setiap jenis harta punya acuan nisab berbeda - misalnya emas 85 gram, pertanian sekitar 653 kg gabah, atau ternak kambing mulai 40 ekor.

2. Haul
Haul adalah syarat kepemilikan genap 1 tahun (menurut kalender Hijriyah/Qomariyah) untuk harta seperti tabungan, emas, atau aset usaha. Zakat baru dikeluarkan setelah harta itu dimiliki genap 1 tahun dan tetap berada di atas nisab.

Perlu diperhatikan:
1. Zakat penghasilan/profesi umumnya dihitung tiap kali menerima penghasilan yang sudah di atas nisab, tanpa menunggu haul 1 tahun.
2. Zakat pertanian juga tidak memakai syarat haul - dikeluarkan setiap kali panen.
3. Zakat fitrah tidak memakai nisab maupun haul - wajib bagi setiap muslim yang mampu menjelang Idulfitri.

Karena acuan nisab bisa berubah mengikuti harga emas/bahan pokok terkini, sebaiknya nominal pastinya dikonfirmasi ke panitia saat mendekati waktu perhitungan zakat.
TEXT,
                'source_label' => 'BAZNAS; PMA No. 52 Tahun 2014',
            ],
            [
                'id' => 'catatan-metodologi-zakat',
                'title' => 'Catatan Metodologi Perhitungan Zakat',
                'keywords' => [
                    'metodologi zakat',
                    'cara hitung zakat',
                    'bruto atau bersih',
                    'zakat penghasilan bersih',
                    'zakat penghasilan bruto',
                    'double counting',
                    'tabungan dan penghasilan digabung',
                    'kenapa hasilnya segini',
                ],
                'answer' => <<<TEXT
Zakky menggunakan pendekatan kehati-hatian dalam memberi estimasi zakat.

Prinsip yang digunakan:
1. Zakat penghasilan dihitung dari arus pendapatan yang diterima, terpisah dari tabungan.
2. Zakat tabungan dan emas dihitung dari harta yang tersimpan dan dimiliki saat ini.
3. Penghasilan tahunan tidak dijumlahkan mentah dengan saldo tabungan sebagai satu basis - karena saldo tabungan biasanya sudah mencerminkan hasil penghasilan yang diterima dan dibelanjakan sepanjang tahun, menjumlahkan keduanya akan menghitung penghasilan yang sama dua kali.
4. Harta yang belum dimiliki penuh, dana titipan, atau harta yang bercampur dengan usaha perlu dipisahkan sebelum dihitung.
5. Untuk kasus yang memiliki perbedaan pendapat ulama, Zakky hanya memberi arah awal, bukan keputusan final.

Dalam praktiknya, sebagian lembaga menghitung zakat penghasilan dari penghasilan bruto, sementara sebagian lain mempertimbangkan penghasilan bersih setelah kebutuhan pokok. Untuk memastikan pendekatan yang dipakai layanan Masjid An-Nur, jamaah dapat mengonfirmasi kepada panitia atau ustadz.
TEXT,
                'source_label' => 'BAZNAS; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'zakat-penghasilan',
                'title' => 'Zakat Penghasilan atau Profesi',
                'keywords' => [
                    'zakat penghasilan',
                    'zakat profesi',
                    'zakat gaji',
                    'gaji bulanan',
                    'honor',
                    'bonus',
                    'thr',
                    'nisab penghasilan',
                    'hitung zakat penghasilan',
                    'gaji 10 juta zakat',
                    '2.5 persen gaji',
                    'penghasilan tidak tetap',
                    'freelance',
                    'freelancer',
                    'pendapatan naik turun',
                ],
                'answer' => <<<TEXT
Zakat penghasilan adalah zakat atas pendapatan halal dari pekerjaan - gaji, honor, upah, jasa, bonus, THR, atau pendapatan profesi lainnya.

Acuan zakat penghasilan (nisab setara 85 gram emas, mengikuti harga emas yang berlaku saat ini):
1. Nisab tahunan: Rp {$nishabRupiahFmt}.
2. Nisab bulanan: sekitar Rp {$nishabBulananFmt}.
3. Kadar zakat: 2,5%.

Rumus: 2,5% x penghasilan yang telah mencapai nisab.

Contoh: penghasilan bulanan Rp 10.000.000 sudah di atas nisab bulanan:
2,5% x Rp 10.000.000 = Rp 250.000.

Dua kondisi yang sering ditanyakan:
1. Penghasilan tidak tetap (freelance): hitung total penghasilan dalam satu periode, bandingkan dengan nisab - bisa dihitung saat mencapai nisab atau secara tahunan.
2. Bonus/THR: dihitung sebagai bagian dari penghasilan pada bulan diterimanya, ikut kena zakat 2,5% jika total penghasilan bulan itu sudah di atas nisab.

Ini estimasi sederhana. Jika ada hutang, cicilan, atau tanggungan keluarga yang memengaruhi perhitungan, siapkan rincian tersebut agar panitia atau ustadz dapat membantu memastikan hasil akhirnya.
TEXT,
                'source_label' => 'BAZNAS - Zakat Penghasilan',
            ],
            [
                'id' => 'zakat-emas-perak',
                'title' => 'Zakat Emas dan Perak',
                'keywords' => [
                    'zakat emas',
                    'zakat perak',
                    'emas batangan',
                    'logam mulia',
                    'perhiasan',
                    'cincin emas',
                    'kalung emas',
                    'nisab emas',
                    'hitung zakat emas',
                    'emas 100 gram zakat',
                    'emas perhiasan',
                    'emas dipakai',
                    'apakah perhiasan wajib zakat',
                ],
                'answer' => <<<TEXT
Zakat emas dan perak termasuk bagian dari zakat mal.

Acuan umum:
1. Nisab emas: 85 gram.
2. Nisab perak: 595 gram.
3. Kadar zakat: 2,5%.

Rumus: 2,5% x nilai emas/perak yang memenuhi syarat (nilai rupiah mengikuti harga emas/perak saat zakat dihitung).

Contoh: emas simpanan 100 gram yang sudah memenuhi syarat - zakatnya 2,5% dari nilai 100 gram emas tersebut.

Untuk emas perhiasan yang dipakai sehari-hari (bukan simpanan/investasi), terdapat perbedaan pendapat ulama - sebagian mewajibkan, sebagian tidak selama pemakaiannya wajar. Zakky dapat memberi arah awal untuk kasus ini. Jika jumlah emas cukup besar atau ragu apakah wajib dizakati, sebaiknya detailnya dikonfirmasi kepada ustadz atau panitia.
TEXT,
                'source_label' => 'BAZNAS - Zakat Emas dan Perak',
            ],
            [
                'id' => 'zakat-tabungan',
                'title' => 'Zakat Tabungan',
                'keywords' => [
                    'zakat tabungan',
                    'zakat simpanan',
                    'saldo tabungan',
                    'uang di bank',
                    'deposito',
                    'simpanan',
                    'rekening',
                    'hitung zakat tabungan',
                    'saldo berubah',
                    'tabungan naik turun',
                ],
                'answer' => <<<TEXT
Zakat tabungan adalah zakat atas uang simpanan yang telah memenuhi ketentuan zakat mal, terutama jika dimiliki penuh, berasal dari sumber halal, mencapai nisab, dan memenuhi ketentuan haul (disimpan genap 1 tahun).

Panduan umum:
1. Hitung saldo tabungan/simpanan milik sendiri, bukan dana titipan orang lain.
2. Bandingkan dengan nisab zakat mal.
3. Jika memenuhi syarat, zakatnya 2,5% x saldo.

Jika saldo naik turun sepanjang tahun, perhatikan apakah simpanan tersebut tetap memenuhi nisab dan haul. Dalam praktik sederhana, saldo yang dihitung adalah saldo pada waktu perhitungan zakat - tetapi kalau saldo sempat turun jauh di bawah nisab di tengah tahun, sebagian pendapat menganggap haulnya perlu dihitung ulang dari saat saldo kembali mencapai nisab. Jika tabungan bercampur dana titipan, dana usaha, atau hutang, atau saldonya sempat turun jauh di bawah nisab, sebaiknya konsultasikan kepada panitia atau ustadz agar tidak salah hitung.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal; BAZNAS',
            ],
            [
                'id' => 'zakat-perdagangan',
                'title' => 'Zakat Perdagangan atau Usaha',
                'keywords' => [
                    'zakat perdagangan',
                    'zakat usaha',
                    'zakat toko',
                    'zakat warung',
                    'zakat bisnis',
                    'aset usaha',
                    'barang dagangan',
                    'modal usaha',
                    'hitung zakat usaha',
                    'usaha kecil',
                ],
                'answer' => <<<TEXT
Zakat perdagangan atau usaha adalah zakat atas aset usaha yang memenuhi ketentuan zakat mal - berlaku untuk usaha besar maupun kecil seperti warung/toko.

Rumus: (kas usaha + nilai barang dagangan + piutang yang kemungkinan tertagih) − hutang usaha jatuh tempo = aset zakat.

Jika aset zakat mencapai nisab dan memenuhi ketentuan haul, zakatnya 2,5% x aset zakat.

Karena setiap usaha punya komposisi stok, modal, piutang, dan hutang yang berbeda, Zakky menyarankan konsultasi kepada panitia atau ustadz sebelum menentukan nominal final, agar tidak salah memasukkan komponen aset.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal; BAZNAS',
            ],
            [
                'id' => 'fidyah',
                'title' => 'Fidyah',
                'keywords' => [
                    'fidyah',
                    'bayar fidyah',
                    'tidak puasa',
                    'puasa ramadan',
                    'fidyah berapa',
                    'hitung fidyah',
                    'lansia',
                    'sakit menahun',
                    'fidyah per hari',
                    'tidak puasa 5 hari',
                    'kalkulator fidyah',
                ],
                'answer' => <<<TEXT
Fidyah adalah pembayaran pengganti bagi orang yang tidak mampu menjalankan puasa Ramadan karena alasan tertentu dan tidak memungkinkan menggantinya dengan qadha sesuai ketentuan syariat.

Panduan umum:
1. Fidyah dihitung per hari puasa yang ditinggalkan.
2. Dalam salah satu pendapat fikih, takaran fidyah adalah sekitar 0,75 kg beras/makanan pokok per hari.
3. Jika dibayarkan dalam bentuk uang, nominalnya dapat menyesuaikan ketentuan lembaga atau wilayah setempat.

Untuk layanan Masjid An-Nur saat ini:
1. Uang: Rp {$fidyahUangFmt} per hari.
2. Beras: {$fidyahBerasKg} kg per hari.

Rumus: jumlah hari yang ditinggalkan x nominal per hari.

Contoh untuk 5 hari:
1. Uang: 5 x Rp {$fidyahUangFmt} = Rp 150.000.
2. Beras: 5 x {$fidyahBerasKg} kg = 3,75 kg.

Catatan: nominal fidyah dari lembaga zakat nasional dapat berbeda dari layanan lokal Masjid An-Nur karena mengikuti kebijakan lembaga, harga makanan pokok setempat, dan bentuk penyalurannya. Nominal di atas berlaku untuk layanan Masjid An-Nur.

Untuk ibu hamil, menyusui, sakit, atau utang puasa lama, ketentuannya dapat berbeda sesuai kondisi. Jamaah sebaiknya menyiapkan kronologi singkat agar panitia atau ustadz dapat membantu menentukan arahnya.
TEXT,
                'source_label' => 'BAZNAS; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'infaq-shodaqoh',
                'title' => 'Infaq dan Shodaqoh',
                'keywords' => [
                    'infaq',
                    'infak',
                    'shodaqoh',
                    'sedekah',
                    'donasi',
                    'sumbangan',
                    'beda zakat dan infaq',
                    'sukarela',
                    'zakat atau infaq',
                    'beda zakat sedekah',
                    'wajib atau sunnah',
                    'donasi sukarela',
                ],
                'answer' => <<<TEXT
Infaq dan shodaqoh adalah pemberian sukarela di luar kewajiban zakat.

Perbedaan umum:
1. Zakat bersifat wajib jika memenuhi syarat, memiliki ketentuan nisab, kadar, dan penerima tertentu.
2. Infaq dan shodaqoh bersifat sukarela, tidak memiliki nisab, dan dapat diberikan kapan saja.
3. Shodaqoh memiliki makna lebih luas karena dapat berupa materi maupun non-materi.

Jika ingin memberi bantuan sukarela melalui Masjid An-Nur, jamaah dapat memilih kategori Infaq/Shodaqoh.
TEXT,
                'source_label' => 'BAZNAS; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'mustahik-8-asnaf',
                'title' => 'Mustahik dan 8 Golongan Penerima Zakat',
                'keywords' => [
                    'mustahik',
                    'penerima zakat',
                    '8 asnaf',
                    'fakir',
                    'miskin',
                    'amil',
                    'mualaf',
                    'riqab',
                    'gharim',
                    'fisabilillah',
                    'ibnu sabil',
                ],
                'answer' => <<<TEXT
Mustahik adalah orang atau kelompok yang berhak menerima zakat.

Delapan golongan penerima zakat:
1. Fakir.
2. Miskin.
3. Amil.
4. Mualaf.
5. Riqab.
6. Gharim.
7. Fisabilillah.
8. Ibnu sabil.

Zakky hanya menjelaskan kategori umum. Penentuan seseorang sebagai mustahik tetap perlu proses verifikasi oleh panitia atau pihak yang berwenang.
TEXT,
                'source_label' => 'QS At-Taubah: 60; BAZNAS',
            ],
            [
                'id' => 'muzakki',
                'title' => 'Muzakki',
                'keywords' => [
                    'muzakki',
                    'pembayar zakat',
                    'orang yang bayar zakat',
                    'wajib zakat',
                    'siapa muzakki',
                ],
                'answer' => <<<TEXT
Muzakki adalah orang atau pihak yang menunaikan zakat karena telah memenuhi syarat wajib zakat.

Dalam layanan Masjid An-Nur, muzakki dapat berupa:
1. Jamaah yang membayar zakat fitrah untuk diri sendiri.
2. Kepala keluarga yang membayarkan zakat fitrah keluarganya.
3. Jamaah yang membayar zakat mal.
4. Jamaah yang menyalurkan fidyah, infaq, atau shodaqoh.

Data pribadi muzakki perlu dijaga dan tidak seluruhnya ditampilkan pada halaman publik.
TEXT,
                'source_label' => 'BAZNAS; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'amil-zakat',
                'title' => 'Amil Zakat',
                'keywords' => [
                    'amil',
                    'amil zakat',
                    'panitia zakat',
                    'pengelola zakat',
                    'petugas zakat',
                ],
                'answer' => <<<TEXT
Amil zakat adalah pihak yang bertugas membantu pengelolaan zakat, mulai dari penerimaan, pencatatan, pendistribusian, hingga pelaporan.

Dalam konteks layanan Masjid An-Nur, panitia zakat berperan membantu jamaah dalam:
1. Menerima pembayaran zakat, fidyah, infaq, dan shodaqoh.
2. Memberikan informasi teknis pembayaran.
3. Memverifikasi data atau pembayaran jika diperlukan.
4. Menyalurkan zakat kepada pihak yang berhak sesuai ketentuan.
5. Menyediakan informasi ringkasan kepada jamaah.

Zakky hanya membantu informasi awal. Keputusan layanan tetap berada pada panitia.
TEXT,
                'source_label' => 'QS At-Taubah: 60; UU No. 23 Tahun 2011; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'bingung-pilih-pembayaran',
                'title' => 'Bingung Memilih Jenis Pembayaran',
                'keywords' => [
                    'bingung bayar zakat',
                    'pilih zakat apa',
                    'zakat atau infaq',
                    'mau bayar tapi bingung',
                    'jenis pembayaran',
                    'kategori pembayaran',
                    'bayar apa',
                ],
                'answer' => <<<TEXT
Jika masih bingung memilih jenis pembayaran, gunakan panduan berikut:

1. Pilih Zakat Fitrah jika ingin membayar kewajiban zakat menjelang Idulfitri untuk diri sendiri atau anggota keluarga.
2. Pilih Fidyah jika ingin mengganti puasa Ramadan yang tidak dapat diganti dengan qadha karena alasan tertentu.
3. Pilih Zakat Mal jika ingin membayar zakat atas harta, penghasilan, emas, tabungan, usaha, atau aset lain yang sudah memenuhi syarat.
4. Pilih Infaq/Shodaqoh jika ingin memberi bantuan sukarela di luar kewajiban zakat.

Jika kondisi pribadi cukup khusus, seperti memiliki hutang, penghasilan tidak tetap, emas perhiasan, atau ragu sudah wajib zakat atau belum, gunakan jawaban Zakky sebagai arah awal lalu konfirmasi detailnya kepada panitia atau ustadz.
TEXT,
                'source_label' => 'BAZNAS; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'siapa-wajib-zakat-fitrah',
                'title' => 'Siapa yang Wajib Zakat Fitrah',
                'keywords' => [
                    'siapa wajib zakat fitrah',
                    'wajib fitrah',
                    'anak kecil zakat fitrah',
                    'bayar fitrah keluarga',
                    'keluarga zakat fitrah',
                    'tanggungan zakat',
                ],
                'answer' => <<<TEXT
Zakat fitrah wajib ditunaikan oleh setiap muslim yang memenuhi ketentuan. Dalam praktik keluarga, zakat fitrah biasanya dibayarkan oleh kepala keluarga untuk dirinya dan anggota keluarga yang menjadi tanggungannya.

Yang biasanya dibayarkan:
1. Diri sendiri.
2. Istri atau suami yang menjadi tanggungan.
3. Anak.
4. Anggota keluarga lain yang berada dalam tanggungan.

Jika ada kondisi khusus, seperti bayi baru lahir, anggota keluarga yang sudah mandiri, atau keluarga berbeda tempat tinggal, siapkan daftar anggota keluarga agar panitia atau ustadz dapat membantu memastikan siapa saja yang perlu dihitung.
TEXT,
                'source_label' => 'BAZNAS; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'kapan-bayar-zakat-fitrah',
                'title' => 'Waktu Membayar Zakat Fitrah',
                'keywords' => [
                    'kapan bayar zakat fitrah',
                    'waktu zakat fitrah',
                    'batas waktu fitrah',
                    'deadline zakat fitrah',
                    'telat zakat fitrah',
                ],
                'answer' => <<<TEXT
Zakat fitrah ditunaikan pada bulan Ramadan hingga sebelum pelaksanaan salat Idulfitri.

Panduan umum:
1. Sebaiknya dibayarkan sebelum salat Idulfitri.
2. Panitia biasanya membuka penerimaan zakat pada periode tertentu menjelang Idulfitri.
3. Jika khawatir terlambat, segera konfirmasi jadwal penerimaan kepada panitia.

Untuk jadwal layanan Masjid An-Nur, ikuti informasi resmi yang diumumkan oleh panitia.
TEXT,
                'source_label' => 'BAZNAS; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'siapa-boleh-fidyah',
                'title' => 'Siapa yang Membayar Fidyah',
                'keywords' => [
                    'siapa bayar fidyah',
                    'wajib fidyah',
                    'boleh fidyah',
                    'lansia fidyah',
                    'sakit menahun fidyah',
                    'ibu hamil fidyah',
                    'menyusui fidyah',
                ],
                'answer' => <<<TEXT
Fidyah berkaitan dengan kondisi seseorang yang tidak mampu menjalankan puasa Ramadan dan tidak memungkinkan menggantinya dengan qadha sesuai ketentuan syariat.

Contoh kondisi yang sering ditanyakan:
1. Lansia yang tidak kuat berpuasa.
2. Orang sakit menahun yang tidak memungkinkan qadha.
3. Kondisi tertentu seperti ibu hamil atau menyusui, yang memerlukan penjelasan lebih hati-hati karena terdapat perbedaan pendapat ulama.

Untuk kasus sederhana seperti lansia atau sakit menahun, fidyah dapat menjadi pilihan. Untuk ibu hamil, menyusui, atau utang puasa lama, bawalah ringkasan kondisinya saat bertanya kepada ustadz atau panitia.
TEXT,
                'source_label' => 'BAZNAS; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'cara-bayar-zakat',
                'title' => 'Cara Membayar Zakat di Masjid An-Nur',
                'keywords' => [
                    'cara bayar zakat',
                    'bayar zakat an nur',
                    'pembayaran zakat',
                    'transfer zakat',
                    'qris zakat',
                    'bayar tunai',
                    'metode pembayaran',
                    'rekening zakat',
                    'pembayaran online',
                    'cek',
                    'cheque',
                    'giro',
                    'bilyet giro',
                ],
                'answer' => <<<TEXT
Untuk membayar zakat di Masjid An-Nur, jamaah dapat mengikuti informasi resmi yang disediakan oleh panitia.

Panduan umum:
1. Tentukan jenis pembayaran: Zakat Fitrah, Fidyah, Zakat Mal, atau Infaq/Shodaqoh.
2. Siapkan nama pembayar, jumlah jiwa atau nominal, dan keterangan jika diperlukan.
3. Bayar lewat metode yang tersedia: tunai di lokasi, transfer bank, QRIS, atau cek (jika diterima panitia).
4. Simpan bukti pembayaran.
5. Jika membayar non-tunai (termasuk cek), lakukan konfirmasi sesuai arahan panitia - untuk cek, status pembayaran dapat menunggu proses pencairan.

Zakky hanya mengarahkan alur pembayaran. Pastikan nomor rekening, QRIS, atau metode pembayaran yang dipakai berasal dari informasi resmi panitia Masjid An-Nur.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'konfirmasi-pembayaran',
                'title' => 'Konfirmasi, Bukti, dan Status Pembayaran',
                'keywords' => [
                    'konfirmasi pembayaran',
                    'sudah transfer',
                    'bukti transfer',
                    'pembayaran belum masuk',
                    'cek pembayaran',
                    'validasi pembayaran',
                    'bukti pembayaran',
                    'kuitansi',
                    'kwitansi',
                    'struk',
                    'status pembayaran',
                    'sudah masuk belum',
                    'belum tercatat',
                    'data belum muncul',
                    'transfer belum masuk',
                ],
                'answer' => <<<TEXT
Jika sudah membayar dan ingin memastikan statusnya, atau sudah transfer tapi belum tercatat, berikut langkah dan data yang perlu disiapkan.

Siapkan untuk konfirmasi ke panitia:
1. Nama pembayar.
2. Jenis pembayaran.
3. Nominal.
4. Waktu pembayaran.
5. Bukti transfer/QRIS (jika non-tunai) atau kuitansi (jika tunai).

Jika pembayaran belum muncul di ringkasan publik, beberapa kemungkinan:
1. Data belum diverifikasi/dicocokkan panitia.
2. Bukti pembayaran belum diterima.
3. Dashboard publik belum memperbarui data terbaru.
4. Pembayaran tercatat di kategori lain.

Zakky tidak dapat memverifikasi atau menerbitkan bukti pembayaran final. Validasi dan bukti resmi tetap mengikuti data dan konfirmasi panitia Masjid An-Nur.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'jadwal-penerimaan',
                'title' => 'Jadwal Penerimaan Zakat',
                'keywords' => [
                    'jadwal zakat',
                    'jadwal penerimaan',
                    'kapan buka zakat',
                    'jam penerimaan zakat',
                    'periode zakat',
                    'batas waktu zakat',
                ],
                'answer' => <<<TEXT
Jadwal penerimaan zakat Masjid An-Nur mengikuti informasi resmi yang diumumkan oleh panitia pada periode berjalan.

Panduan umum:
1. Zakat fitrah biasanya diterima pada bulan Ramadan hingga sebelum salat Idulfitri.
2. Zakat mal, infaq, dan shodaqoh dapat mengikuti layanan yang tersedia.
3. Fidyah dapat dibayarkan sesuai jumlah hari puasa yang ditinggalkan dan arahan panitia.

Jika jadwal belum terlihat di portal, silakan konfirmasi langsung kepada panitia Masjid An-Nur.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur; BAZNAS',
            ],
            [
                'id' => 'dashboard-publik',
                'title' => 'Dashboard Publik',
                'keywords' => [
                    'dashboard publik',
                    'laporan zakat',
                    'ringkasan penerimaan',
                    'transparansi',
                    'total zakat',
                    'grafik zakat',
                    'data publik',
                    'total penerimaan',
                    'laporan penerimaan',
                    'uang terkumpul',
                    'beras terkumpul',
                    'kategori zakat',
                    'akuntabilitas',
                    'laporan publik',
                    'uang zakat kemana',
                    'kepercayaan jamaah',
                    'laporan masjid',
                ],
                'answer' => <<<TEXT
Dashboard publik menampilkan ringkasan pengelolaan zakat secara transparan, tanpa membuka seluruh data pribadi muzakki atau mustahik.

Informasi yang dapat ditampilkan:
1. Total penerimaan uang dan beras.
2. Total jiwa untuk zakat fitrah jika tersedia.
3. Rincian berdasarkan kategori: Zakat Fitrah, Fidyah, Zakat Mal, dan Infaq/Shodaqoh.
4. Grafik penerimaan harian.
5. Riwayat transaksi terbaru dalam bentuk terbatas.
6. Informasi umum terkait periode zakat.

Dashboard publik membantu jamaah melihat perkembangan penerimaan tanpa menunggu rekap manual, sekaligus jadi bentuk pertanggungjawaban pengelolaan zakat kepada jamaah. Transparansi di sini bukan berarti semua data pribadi dibuka - tujuannya keterbukaan informasi tetap berjalan tanpa mengabaikan privasi muzakki dan mustahik.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur; Prinsip Transparansi dan Privasi Data; UU No. 23 Tahun 2011',
            ],
            [
                'id' => 'grafik-harian',
                'title' => 'Grafik Penerimaan Harian',
                'keywords' => [
                    'grafik harian',
                    'grafik zakat',
                    'tren penerimaan',
                    'data harian',
                    'penerimaan per hari',
                    'laporan grafik',
                ],
                'answer' => <<<TEXT
Grafik penerimaan harian membantu jamaah melihat pola penerimaan zakat dari hari ke hari.

Grafik ini dapat digunakan untuk:
1. Melihat hari dengan penerimaan tinggi.
2. Memahami perkembangan penerimaan selama periode aktif.
3. Membaca tren penerimaan secara visual.
4. Melengkapi informasi pada ringkasan penerimaan.

Jika angka grafik berbeda dari ekspektasi, kemungkinan data masih dalam proses pembaruan atau hanya menampilkan transaksi yang sudah tercatat.
TEXT,
                'source_label' => 'Panduan Portal Zakat Masjid An-Nur',
            ],
            [
                'id' => 'privasi-data-publik',
                'title' => 'Privasi Data Publik',
                'keywords' => [
                    'privasi data',
                    'data pribadi',
                    'nama muzakki',
                    'nama mustahik',
                    'kenapa nama tidak tampil',
                    'data tidak tampil',
                    'anonim',
                ],
                'answer' => <<<TEXT
Data pribadi muzakki dan mustahik perlu dijaga. Karena itu, halaman publik sebaiknya menampilkan data ringkasan, bukan seluruh identitas pribadi.

Yang dapat ditampilkan di publik:
1. Total penerimaan.
2. Ringkasan per kategori.
3. Grafik penerimaan.
4. Informasi transaksi terbaru secara terbatas jika diperlukan.

Yang tidak perlu ditampilkan secara terbuka:
1. Data lengkap muzakki.
2. Data lengkap mustahik.
3. Informasi kontak pribadi.
4. Detail sensitif pembayaran.

Tujuannya agar transparansi tetap berjalan tanpa mengabaikan privasi jamaah.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur; Prinsip Perlindungan Data',
            ],
            [
                'id' => 'case-punya-hutang',
                'title' => 'Case: Punya Hutang atau Cicilan',
                'keywords' => [
                    'punya hutang',
                    'ada cicilan',
                    'zakat tapi punya hutang',
                    'hutang banyak',
                    'cicilan rumah',
                    'cicilan motor',
                    'kpr',
                    'wajib zakat kalau punya hutang',
                ],
                'answer' => <<<TEXT
Hutang atau cicilan dapat memengaruhi perhitungan zakat, terutama jika hutang tersebut merupakan kewajiban yang harus segera dibayar dan berkaitan dengan kebutuhan pokok.

Panduan umum:
1. Jika setelah kebutuhan pokok dan kewajiban utama masih ada harta atau penghasilan yang mencapai nisab, zakat tetap perlu diperhatikan.
2. Jika hutang jatuh tempo membuat harta tidak lagi mencapai nisab, maka kondisi tersebut perlu dihitung lebih hati-hati.
3. Untuk zakat penghasilan, cara menghitung bisa berbeda tergantung panduan ulama atau lembaga zakat yang diikuti.

Karena kondisi hutang setiap orang berbeda, Zakky dapat memberi arah awal dari data yang tersedia. Agar hasilnya lebih tepat, jamaah dapat mengonfirmasi detailnya kepada panitia atau ustadz dengan membawa informasi penghasilan, hutang, kebutuhan pokok, dan jumlah tanggungan.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'case-lansia-tidak-puasa',
                'title' => 'Case: Lansia Tidak Kuat Puasa',
                'keywords' => [
                    'lansia tidak puasa',
                    'orang tua tidak puasa',
                    'sepuh tidak puasa',
                    'fidyah lansia',
                    'orang tua bayar fidyah',
                ],
                'answer' => <<<TEXT
Jika seseorang sudah lanjut usia dan tidak mampu berpuasa serta tidak memungkinkan menggantinya dengan qadha, fidyah dapat menjadi pilihan sesuai ketentuan syariat.

Panduan umum:
1. Hitung jumlah hari puasa yang ditinggalkan.
2. Kalikan dengan nominal fidyah per hari atau takaran makanan pokok per hari.
3. Fidyah dapat dibantu pembayarannya oleh keluarga.

Untuk layanan Masjid An-Nur saat ini:
1. Uang: Rp {$fidyahUangFmt} per hari.
2. Beras: {$fidyahBerasKg} kg per hari.

Jika kondisi lansia masih mungkin berpuasa atau masih mungkin qadha, keputusan fidyah sebaiknya dipastikan kepada ustadz dengan menjelaskan kondisi kesehatannya.
TEXT,
                'source_label' => 'BAZNAS; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'case-sakit-menahun',
                'title' => 'Case: Sakit Menahun',
                'keywords' => [
                    'sakit menahun',
                    'sakit tidak bisa puasa',
                    'penyakit kronis',
                    'fidyah sakit',
                    'tidak mampu qadha',
                    'sakit permanen',
                ],
                'answer' => <<<TEXT
Jika seseorang sakit menahun sehingga tidak mampu berpuasa dan tidak memungkinkan mengganti puasa di kemudian hari, fidyah dapat menjadi pilihan sesuai ketentuan syariat.

Panduan umum:
1. Hitung jumlah hari puasa yang ditinggalkan.
2. Bayarkan fidyah per hari sesuai ketentuan yang berlaku.
3. Jika kondisi sakit masih berpotensi sembuh, maka qadha mungkin tetap perlu diperhatikan.

Karena kondisi kesehatan bisa berbeda-beda, Zakky menyarankan konsultasi kepada ustadz atau panitia untuk memastikan apakah cukup fidyah atau tetap perlu qadha.
TEXT,
                'source_label' => 'BAZNAS; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'case-ibu-hamil-menyusui',
                'title' => 'Case: Ibu Hamil atau Menyusui Tidak Puasa',
                'keywords' => [
                    'ibu hamil tidak puasa',
                    'ibu menyusui tidak puasa',
                    'hamil bayar fidyah',
                    'menyusui bayar fidyah',
                    'qadha atau fidyah',
                    'fidyah ibu hamil',
                    'fidyah menyusui',
                ],
                'answer' => <<<TEXT
Untuk ibu hamil atau menyusui yang tidak berpuasa, kewajiban mengganti puasa dapat berbeda tergantung alasan tidak berpuasa, apakah karena khawatir terhadap diri sendiri, anak, atau keduanya.

Dalam beberapa kondisi, kewajiban dapat berupa qadha, fidyah, atau keduanya menurut perbedaan pendapat ulama.

Zakky dapat memberi arah awal untuk kasus ini. Agar kewajibannya sesuai dengan kondisi pribadi, jamaah dapat mengonfirmasi detailnya kepada ustadz atau panitia zakat.
TEXT,
                'source_label' => 'Panduan Fidyah; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'case-salah-pilih-pembayaran',
                'title' => 'Case: Salah Pilih Jenis Pembayaran',
                'keywords' => [
                    'salah pilih pembayaran',
                    'salah kategori',
                    'salah pilih zakat',
                    'harusnya infaq',
                    'harusnya zakat mal',
                    'salah input jenis',
                ],
                'answer' => <<<TEXT
Jika jamaah merasa salah memilih jenis pembayaran, segera konfirmasi kepada panitia Masjid An-Nur.

Contoh:
1. Seharusnya Zakat Fitrah tetapi memilih Infaq.
2. Seharusnya Zakat Mal tetapi memilih Fidyah.
3. Nominal sudah benar tetapi kategori salah.
4. Pembayaran sudah dilakukan tetapi keterangan belum sesuai.

Siapkan informasi berikut:
1. Nama pembayar.
2. Nominal pembayaran.
3. Waktu pembayaran.
4. Jenis pembayaran yang dipilih.
5. Jenis pembayaran yang seharusnya.
6. Bukti pembayaran jika ada.

Zakky tidak dapat mengubah data transaksi. Perubahan atau koreksi hanya dapat dilakukan oleh panitia sesuai prosedur.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'zakat-pertanian-perkebunan',
                'title' => 'Zakat Pertanian dan Perkebunan',
                'keywords' => [
                    'zakat pertanian',
                    'zakat perkebunan',
                    'zakat panen',
                    'hasil panen',
                    'gabah',
                    'sawah',
                    'kebun',
                    'nisab pertanian',
                    'zakat padi',
                    'zakat jagung',
                    'zakat buah',
                    'panen wajib zakat',
                    'pengairan alami',
                    'irigasi berbayar',
                    'hitung zakat pertanian',
                    'zakat panen berapa',
                    'contoh hitung pertanian',
                ],
                'answer' => <<<TEXT
Zakat pertanian atau perkebunan adalah zakat atas hasil panen yang memenuhi nisab - dikeluarkan setiap kali panen, tanpa syarat haul (1 tahun) seperti zakat mal lainnya.

Panduan umum:
1. Nisab: sekitar 653 kg gabah atau hasil panen yang setara.
2. Pengairan alami (hujan, sungai, tanpa biaya besar): kadar zakat 10%.
3. Pengairan berbiaya (irigasi, pompa, perawatan berbayar): kadar zakat 5%.
4. Pengairan campuran: konsultasikan ke panitia/ustadz untuk perhitungan yang tepat.

Rumus: kadar (10% atau 5%) x hasil panen.

Contoh untuk 1.000 kg gabah:
1. Pengairan alami: 10% x 1.000 kg = 100 kg.
2. Pengairan berbiaya: 5% x 1.000 kg = 50 kg.

Untuk komoditas selain padi/gabah, nisab perlu disetarakan dengan nilai atau ukuran hasil pertanian yang berlaku - bukan otomatis dipukul rata 653 kg. Karena itu, jenis komoditas dan berat bersih hasil panen perlu diketahui sebelum menghitung final.

Zakky dapat membantu memberi contoh rumus, tetapi perhitungan final hasil panen perlu melihat jenis panen, berat bersih, biaya pengairan, dan waktu panen. Siapkan data tersebut saat mengonfirmasi perhitungan kepada panitia.
TEXT,
                'source_label' => 'BAZNAS - Zakat Pertanian; PMA No. 52 Tahun 2014',
            ],
            [
                'id' => 'zakat-peternakan',
                'title' => 'Zakat Peternakan',
                'keywords' => [
                    'zakat peternakan',
                    'zakat ternak',
                    'zakat kambing',
                    'zakat domba',
                    'zakat sapi',
                    'zakat kerbau',
                    'ternak wajib zakat',
                    'haul ternak',
                    'hitung zakat peternakan',
                    'nisab kambing',
                    'nisab sapi',
                    '40 ekor kambing',
                    '30 ekor sapi',
                    'contoh hitung ternak',
                ],
                'answer' => <<<TEXT
Zakat peternakan adalah zakat atas hewan ternak tertentu, seperti kambing/domba dan sapi/kerbau, jika memenuhi syarat umum.

Yang perlu dicek:
1. Jenis ternaknya, misalnya kambing, domba, sapi, atau kerbau.
2. Jumlah ternak yang dimiliki.
3. Apakah ternak digembalakan atau menjadi bagian dari usaha intensif.
4. Apakah kepemilikan sudah mencapai haul (dimiliki 1 tahun).
5. Apakah jumlahnya mencapai nisab sesuai jenis ternak.

Nisab dan contoh kadar zakat peternakan:
1. Kambing/domba: mulai wajib pada 40 ekor - 40-120 ekor zakatnya 1 ekor, 121-200 ekor zakatnya 2 ekor, 201-300 ekor zakatnya 3 ekor.
2. Sapi/kerbau: mulai wajib pada 30 ekor - 30-39 ekor zakatnya 1 ekor anak sapi (sekitar 1 tahun), 40-59 ekor zakatnya 1 ekor sapi (sekitar 2 tahun).

Perhitungannya tidak berbentuk persentase uang seperti zakat penghasilan 2,5%, tetapi mengikuti tabel jumlah dan jenis ternak di atas. Jika ternak dipelihara sebagai usaha komersial intensif (misalnya untuk jual-beli, penggemukan, atau produksi bisnis), perhitungannya dapat berbeda dan bisa masuk pembahasan zakat perdagangan - bukan tabel ternak murni. Karena itu, status ternak sebagai gembalaan atau aset usaha perlu dikonfirmasi lebih dulu.

Zakky dapat menjelaskan tabel dasarnya, tetapi belum menghitung otomatis seluruh variasi ternak. Jika ternak bercampur antara kambing, domba, sapi, kerbau, indukan, anak ternak, dan aset usaha, detailnya perlu dirapikan dulu sebelum dihitung oleh panitia atau ustadz.
TEXT,
                'source_label' => 'BAZNAS - Zakat Ternak Kambing dan Sapi',
            ],
            [
                'id' => 'zakat-properti-sewa',
                'title' => 'Zakat Properti Sewa',
                'keywords' => [
                    'zakat properti',
                    'zakat sewa',
                    'zakat kontrakan',
                    'zakat kos kosan',
                    'zakat kosan',
                    'zakat ruko',
                    'rumah disewakan',
                    'kontrakan disewakan',
                    'mustaghallat',
                    'penghasilan sewa',
                    'sewa rumah',
                    'sewa ruko',
                    'hitung zakat properti',
                    'sewa bersih',
                    'contoh hitung properti',
                ],
                'answer' => <<<TEXT
Zakat properti sewa (mustaghallat) adalah zakat atas penghasilan dari aset yang disewakan - rumah kontrakan, kos-kosan, ruko, kios, atau properti lain yang menghasilkan pendapatan sewa.

Panduan umum:
1. Properti yang dipakai pribadi tidak otomatis menjadi objek zakat.
2. Yang diperhatikan adalah pendapatan bersih sewa (pendapatan sewa dikurangi biaya operasional wajar), bukan nilai propertinya.
3. Jika pendapatan bersih mencapai nisab, kadar zakatnya 2,5%.
4. Properti yang dibeli untuk dijual kembali (bukan disewakan) masuk pembahasan zakat perdagangan, bukan zakat sewa.

Rumus: 2,5% x pendapatan bersih sewa yang memenuhi nisab.

Contoh: pendapatan sewa bersih Rp 20.000.000 dalam periode tertentu, sudah memenuhi nisab:
2,5% x Rp 20.000.000 = Rp 500.000.

Dalam praktik zakat kontemporer, hasil sewa umumnya dianalisis sebagai pendapatan dari aset produktif - pendekatan perhitungannya dapat mengikuti zakat penghasilan atau zakat mal sesuai panduan lembaga/ustadz yang diikuti, bukan satu rumus yang berlaku mutlak untuk semua kasus.

Untuk kasus properti masih cicilan, biaya renovasi besar, atau disewakan tidak rutin, siapkan pendapatan sewa, biaya operasional, cicilan jatuh tempo, dan periode penerimaan. Data ini membantu panitia memastikan apakah yang dihitung pendapatan bersih sewa atau masuk kategori lain.
TEXT,
                'source_label' => 'BAZNAS Daerah - Zakat Properti; Panduan Umum Zakat Mal',
            ],
            [
                'id' => 'zakat-saham-investasi-reksadana',
                'title' => 'Zakat Saham, Investasi, dan Reksadana',
                'keywords' => [
                    'zakat saham',
                    'zakat investasi',
                    'zakat reksadana',
                    'zakat obligasi',
                    'zakat sukuk',
                    'capital gain',
                    'dividen',
                    'portofolio investasi',
                    'investor',
                    'saham syariah',
                    'reksadana syariah',
                    'hitung zakat saham',
                    'zakat portofolio',
                    'contoh hitung saham',
                    'kalkulator saham',
                ],
                'answer' => <<<TEXT
Zakat saham, investasi, dan reksadana termasuk pembahasan zakat mal kontemporer. Objek zakatnya dapat berupa nilai kepemilikan, dividen, capital gain, atau hasil investasi, tergantung jenis aset dan pendapat yang diikuti.

Panduan umum:
1. Jika investasi dimiliki sebagai aset dan telah mencapai nisab serta haul, zakat perlu diperhatikan.
2. Kadar zakat yang umum digunakan: 2,5% dari objek zakat (nilai portofolio, dividen, atau capital gain, tergantung pendekatan).
3. Perhitungan dapat berbeda sesuai jenis investasi (saham konvensional vs syariah, reksadana, obligasi/sukuk).

Rumus estimasi: 2,5% x objek zakat investasi yang memenuhi nisab dan haul.

Contoh: objek zakat investasi Rp 50.000.000 yang sudah memenuhi syarat:
2,5% x Rp 50.000.000 = Rp 1.250.000.

Karena topik ini kontemporer dan pendekatannya bisa berbeda antar lembaga - ada yang menghitung dari nilai kepemilikan saham, ada yang fokus ke keuntungan investasi seperti dividen/capital gain - Zakky tidak mengunci satu rumus untuk semua kasus investasi, hanya memberi arah awal. Untuk portofolio yang kompleks, siapkan nilai portofolio, dividen, capital gain, jenis instrumen, dan periode kepemilikan sebelum mengonfirmasi kepada panitia, ustadz, atau lembaga zakat.
TEXT,
                'source_label' => 'BAZNAS - Zakat Saham dan Investasi',
            ],
            [
                'id' => 'zakat-warisan',
                'title' => 'Zakat Warisan',
                'keywords' => [
                    'zakat warisan',
                    'harta warisan',
                    'warisan kena zakat',
                    'menerima warisan',
                    'uang warisan',
                    'tanah warisan',
                    'rumah warisan',
                    'bagian warisan',
                    'warisan orang tua',
                    'tabungan dari warisan',
                ],
                'answer' => <<<TEXT
Harta warisan tidak otomatis dizakati hanya karena baru diterima. Zakat baru perlu diperhatikan setelah harta itu sah menjadi milik ahli waris dan memenuhi syarat zakat sesuai jenis hartanya.

Panduan umum berdasarkan bentuk warisannya:
1. Uang, tabungan, atau emas: setelah menjadi milik ahli waris, diperhatikan sebagai zakat mal/tabungan jika mencapai nisab.
2. Tanah/rumah yang dipakai pribadi: tidak otomatis menjadi objek zakat.
3. Properti yang disewakan: penghasilan sewanya masuk pembahasan zakat properti sewa.
4. Aset yang dijual menjadi uang simpanan: uangnya diperhatikan sebagai zakat mal jika memenuhi syarat.
5. Usaha atau barang dagangan: masuk pembahasan zakat perdagangan.

Karena warisan sering bercampur dengan pembagian ahli waris, hutang pewaris, atau aset yang belum terjual, Zakky memberi arahan awal berdasarkan bentuk hartanya. Untuk hasil yang lebih tepat, jamaah dapat mengonfirmasi detailnya kepada ustadz atau panitia dengan membawa rincian bentuk warisan yang diterima.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal',
            ],
            [
                'id' => 'cara-zakky-menganalisis-kasus',
                'title' => 'Cara Zakky Membantu Menganalisis Kasus Zakat',
                'keywords' => [
                    'cara zakky menjawab',
                    'analisis kasus zakat',
                    'konsultasi zakat',
                    'bingung kasus zakat',
                    'kasus khusus zakat',
                    'cara menentukan zakat',
                    'apakah saya wajib zakat',
                ],
                'answer' => <<<TEXT
Jika jamaah memiliki kasus khusus, Zakky membantu menganalisis secara bertahap, bukan langsung memberi keputusan final.

Alur yang digunakan Zakky:
1. Mengidentifikasi jenis harta atau kewajiban yang ditanyakan.
2. Mengarahkan kasus ke zakat fitrah, zakat mal, fidyah, atau infaq/shodaqoh.
3. Memeriksa syarat umum seperti nisab, haul, kepemilikan penuh, dan jenis objek zakat.
4. Memberikan estimasi awal jika datanya sederhana dan didukung kalkulator Zakky.
5. Menandai faktor yang bisa mengubah hasil, seperti hutang, cicilan, status harta, penghasilan tidak tetap, dana titipan, atau perbedaan pendapat ulama.
6. Mengarahkan jamaah ke panitia atau ustadz jika kasusnya membutuhkan keputusan fikih atau verifikasi layanan.

Dengan cara ini, Zakky membantu jamaah memahami arah masalahnya terlebih dahulu sebelum mengambil langkah berikutnya.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'zakat-aset-pribadi',
                'title' => 'Zakat Aset Pribadi',
                'keywords' => [
                    'zakat aset pribadi',
                    'aset pribadi kena zakat',
                    'barang pribadi kena zakat',
                    'aset dipakai sendiri',
                    'harta pribadi',
                    'kebutuhan pribadi',
                    'aset usaha atau pribadi',
                ],
                'answer' => <<<TEXT
Tidak semua aset pribadi otomatis menjadi objek zakat.

Panduan umum:
1. Aset yang dipakai untuk kebutuhan pribadi tidak otomatis menjadi objek zakat.
2. Aset pribadi perlu diperhatikan jika berubah fungsi menjadi aset usaha, barang dagangan, investasi, atau sumber penghasilan.
3. Yang dilihat bukan hanya nilai asetnya, tetapi fungsi dan status kepemilikannya.
4. Jika aset menghasilkan pendapatan, pendapatan tersebut dapat masuk pembahasan zakat mal sesuai jenisnya.

Contoh:
Rumah yang ditempati sendiri berbeda dengan rumah yang disewakan. Kendaraan yang dipakai pribadi berbeda dengan kendaraan yang menjadi aset usaha atau barang dagangan.

Zakky dapat membantu memberikan arah awal. Agar hasilnya lebih tepat, jamaah dapat mengonfirmasi detail kasus kepada panitia atau ustadz, terutama jika asetnya bercampur antara kebutuhan pribadi, investasi, dan usaha.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'zakat-kendaraan',
                'title' => 'Zakat Kendaraan',
                'keywords' => [
                    'zakat kendaraan',
                    'mobil kena zakat',
                    'motor kena zakat',
                    'kendaraan pribadi kena zakat',
                    'mobil pribadi',
                    'motor pribadi',
                    'kendaraan usaha',
                    'mobil dagangan',
                ],
                'answer' => <<<TEXT
Kendaraan perlu dilihat dari fungsi dan penggunaannya.

Panduan umum:
1. Mobil atau motor yang dipakai untuk kebutuhan pribadi tidak otomatis menjadi objek zakat.
2. Kendaraan yang menjadi barang dagangan masuk pembahasan zakat perdagangan.
3. Kendaraan yang dipakai sebagai aset usaha dapat diperhatikan dari penghasilan atau keuntungan usahanya.
4. Jika kendaraan disewakan, pendapatan sewanya dapat masuk pembahasan zakat penghasilan atau zakat mal.

Berdasarkan informasi umum, pertanyaan tentang kendaraan biasanya perlu diklasifikasikan dulu: dipakai pribadi, dijual kembali, disewakan, atau menjadi aset operasional usaha.

Untuk keputusan akhir, terutama jika kendaraan terkait usaha, cicilan, atau pendapatan sewa, sebaiknya detailnya dikonfirmasi kepada panitia atau ustadz.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'zakat-rumah-pribadi',
                'title' => 'Zakat Rumah Pribadi',
                'keywords' => [
                    'zakat rumah pribadi',
                    'rumah pribadi kena zakat',
                    'rumah yang ditempati',
                    'rumah sendiri kena zakat',
                    'rumah tinggal',
                    'rumah kosong kena zakat',
                    'rumah investasi',
                ],
                'answer' => <<<TEXT
Rumah perlu dilihat dari fungsi dan status penggunaannya.

Panduan umum:
1. Rumah yang ditempati sendiri untuk kebutuhan keluarga tidak otomatis menjadi objek zakat.
2. Rumah yang disewakan perlu diperhatikan dari penghasilan sewanya.
3. Rumah yang dibeli untuk dijual kembali dapat masuk pembahasan aset dagangan atau investasi.
4. Rumah kosong perlu dilihat niat dan fungsinya: untuk tempat tinggal, investasi, disewakan, atau dijual.

Berdasarkan informasi umum, rumah pribadi yang dipakai sebagai tempat tinggal berbeda dengan properti yang menghasilkan pendapatan.

Agar hasilnya lebih tepat, jamaah dapat mengonfirmasi detail kasus kepada panitia atau ustadz, terutama jika rumah masih cicilan, disewakan tidak rutin, atau bercampur dengan tujuan investasi.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'zakat-hadiah-hibah',
                'title' => 'Zakat Hadiah atau Hibah',
                'keywords' => [
                    'zakat hadiah',
                    'zakat hibah',
                    'dapat hadiah uang',
                    'dapat uang hibah',
                    'uang pemberian kena zakat',
                    'hadiah kena zakat',
                    'hibah kena zakat',
                ],
                'answer' => <<<TEXT
Hadiah atau hibah tidak otomatis langsung menjadi zakat saat diterima. Setelah hadiah atau hibah tersebut sah menjadi milik penerima, harta itu diperhatikan sesuai jenisnya.

Panduan umum:
1. Jika hadiah berupa uang dan disimpan, maka dapat masuk pembahasan zakat tabungan jika mencapai nisab dan memenuhi ketentuan.
2. Jika hadiah berupa emas, maka dapat masuk pembahasan zakat emas jika mencapai nisab.
3. Jika hadiah berupa barang yang dipakai pribadi, tidak otomatis menjadi objek zakat.
4. Jika hadiah berupa aset yang kemudian disewakan atau diperjualbelikan, pembahasannya dapat berubah menjadi zakat properti sewa atau zakat perdagangan.

Zakky dapat membantu mengarahkan kategori awal. Untuk hasil yang lebih tepat, perlu dilihat bentuk hadiah, nilai, status kepemilikan, dan penggunaan harta tersebut.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'zakat-uang-pesangon',
                'title' => 'Zakat Uang Pesangon',
                'keywords' => [
                    'zakat pesangon',
                    'pesangon kena zakat',
                    'uang pesangon',
                    'uang phk kena zakat',
                    'dapat pesangon',
                    'pesangon disimpan',
                    'pesangon untuk hutang',
                ],
                'answer' => <<<TEXT
Uang pesangon perlu dilihat sebagai harta yang diterima dan kemudian menjadi milik penerima.

Panduan umum:
1. Jika pesangon diterima sebagai uang dan disimpan, maka dapat masuk pembahasan zakat tabungan atau zakat mal jika mencapai nisab.
2. Jika dana tersebut langsung digunakan untuk kebutuhan pokok, pengobatan, hutang, atau kebutuhan mendesak, perhitungannya perlu lebih hati-hati.
3. Jika pesangon menjadi modal usaha, pembahasannya dapat berlanjut ke zakat usaha.
4. Jika pesangon bercampur dengan tabungan lama, perlu dipisahkan agar komponen hartanya jelas.

Zakky dapat memberi arah awal. Untuk keputusan akhir, terutama jika pesangon terkait kebutuhan hidup, hutang, atau kondisi keluarga, sebaiknya jamaah mengonfirmasi detailnya kepada panitia atau ustadz.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'zakat-dana-pensiun',
                'title' => 'Zakat Dana Pensiun',
                'keywords' => [
                    'zakat dana pensiun',
                    'uang pensiun kena zakat',
                    'pensiunan bayar zakat',
                    'uang pensiun',
                    'pencairan dana pensiun',
                    'pensiun bulanan',
                    'dana pensiun cair',
                ],
                'answer' => <<<TEXT
Dana pensiun perlu dilihat dari cara penerimaannya: rutin setiap bulan atau cair sekaligus.

Panduan umum:
1. Jika dana pensiun diterima rutin setiap bulan, dapat dianalisis seperti penghasilan bulanan.
2. Jika dana pensiun cair sekaligus dan disimpan, dapat masuk pembahasan zakat tabungan atau zakat mal jika mencapai nisab.
3. Jika langsung digunakan untuk kebutuhan pokok, pengobatan, atau pelunasan hutang, perhitungannya perlu lebih hati-hati.
4. Jika dana pensiun dijadikan modal usaha, pembahasannya dapat berlanjut ke zakat usaha.

Karena kondisi pensiun, kebutuhan hidup, dan hutang setiap orang berbeda, jamaah sebaiknya mengonfirmasi detail kasus kepada panitia atau ustadz agar perhitungannya lebih tepat.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'zakat-piutang',
                'title' => 'Zakat Piutang',
                'keywords' => [
                    'zakat piutang',
                    'uang dipinjam orang',
                    'teman hutang ke saya',
                    'piutang kena zakat',
                    'uang belum dibayar',
                    'utang orang ke saya',
                    'piutang usaha',
                ],
                'answer' => <<<TEXT
Piutang adalah harta yang masih berada pada pihak lain karena dipinjam atau belum dibayarkan.

Panduan umum:
1. Jika piutang kuat dan besar kemungkinan tertagih, piutang dapat diperhatikan dalam perhitungan zakat.
2. Jika piutang lemah, sulit tertagih, atau tidak jelas kapan dibayar, perhitungannya perlu lebih hati-hati.
3. Untuk usaha, piutang yang kemungkinan tertagih biasanya diperhatikan dalam zakat perdagangan.
4. Jika piutang baru diterima kembali, harta tersebut dapat diperhatikan sebagai bagian dari harta simpanan.

Karena status piutang bisa berbeda-beda, Zakky memberi arah awal berdasarkan kemungkinan tertagihnya. Jika nilainya besar atau status penagihannya tidak jelas, sebaiknya jamaah mengonfirmasi detailnya kepada panitia atau ustadz.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'zakat-harta-campuran',
                'title' => 'Zakat Harta Campuran',
                'keywords' => [
                    'harta campuran',
                    'tabungan campur',
                    'uang usaha campur pribadi',
                    'dana titipan',
                    'uang titipan',
                    'rekening campur',
                    'uang pribadi dan usaha',
                    'bingung hitung harta',
                ],
                'answer' => <<<TEXT
Harta campuran perlu dipisahkan terlebih dahulu sebelum dihitung zakatnya.

Contoh harta campuran:
1. Rekening pribadi bercampur dengan uang usaha.
2. Tabungan bercampur dengan dana titipan.
3. Uang keluarga bercampur dengan uang pribadi.
4. Dana operasional usaha bercampur dengan keuntungan pribadi.

Panduan umum:
1. Pisahkan dana yang benar-benar milik pribadi.
2. Pisahkan dana titipan atau dana milik orang lain.
3. Pisahkan uang usaha dari uang kebutuhan pribadi jika memungkinkan.
4. Setelah dipisahkan, tentukan apakah harta pribadi atau aset usaha tersebut mencapai nisab.

Zakky dapat membantu menjelaskan arah perhitungan. Untuk harta campuran, sebaiknya jamaah mengonfirmasi detail kasus kepada panitia atau ustadz agar tidak salah memasukkan komponen harta.
TEXT,
                'source_label' => 'Panduan Umum Zakat Mal; Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'pembayaran-cek',
                'title' => 'Pembayaran Menggunakan Cek',
                'keywords' => [
                    'pembayaran cek',
                    'bayar pakai cek',
                    'cek',
                    'cheque',
                    'bilyet giro',
                    'giro',
                    'metode cek',
                    'zakat pakai cek',
                ],
                'answer' => <<<TEXT
Pembayaran zakat menggunakan cek dapat dilakukan jika metode tersebut tersedia dan diterima oleh panitia Masjid An-Nur.

Panduan umum:
1. Pastikan cek ditujukan kepada pihak yang benar sesuai arahan panitia.
2. Pastikan nominal, tanggal, dan keterangan pembayaran jelas.
3. Simpan salinan atau dokumentasi cek jika diperlukan.
4. Konfirmasi kepada panitia setelah menyerahkan cek - status pembayaran dapat menunggu proses pencairan sesuai prosedur panitia.

Zakky tidak dapat memverifikasi keabsahan cek atau status pencairannya. Validasi akhir tetap dilakukan oleh panitia.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur',
            ],
            [
                'id' => 'batas-hitung-zakat-mal-lanjutan',
                'title' => 'Batas Perhitungan Otomatis Zakat Mal Lanjutan',
                'keywords' => [
                    'kenapa tidak bisa hitung',
                    'tidak bisa menghitung',
                    'hitung pertanian',
                    'hitung peternakan',
                    'hitung saham',
                    'hitung reksadana',
                    'hitung warisan',
                    'kalkulator belum tersedia',
                    'zakat mal lanjutan',
                ],
                'answer' => <<<TEXT
Zakky dapat membantu menghitung estimasi otomatis untuk beberapa jenis zakat mal yang datanya sederhana, seperti zakat penghasilan, tabungan, dan emas.

Untuk topik lanjutan berikut, Zakky saat ini hanya memberi panduan konsep, rumus umum, dan arahan konsultasi - belum menghitung otomatis:
1. Zakat pertanian/perkebunan.
2. Zakat peternakan.
3. Zakat saham, reksadana, dan investasi.
4. Zakat properti sewa.
5. Zakat warisan.
6. Zakat usaha dengan stok, piutang, dan hutang yang kompleks.

Alasannya, perhitungan topik ini butuh data yang lebih spesifik yang belum didukung kalkulator otomatis Zakky. Zakky lebih memilih menjawab jujur dan hati-hati daripada memberi angka yang berisiko keliru.

Untuk kasus lanjutan, siapkan data inti terlebih dahulu: jenis harta, nilai, status kepemilikan, hutang terkait, biaya operasional, dan periode kepemilikan. Data itu membuat konsultasi dengan panitia atau ustadz lebih cepat dan akurat.
TEXT,
                'source_label' => 'Panduan Publik Masjid An-Nur',
            ],
        ];

        // ponytail: firstOrCreate, bukan updateOrCreate - seeder ini untuk instalasi awal saja.
        // Kalau Admin sudah mengedit sebuah entri lewat /internal/knowledge-base, re-run seeder
        // ini tidak akan menimpanya balik. Untuk memperbaiki isi bawaan (bukan hasil edit Admin),
        // hapus dulu barisnya dari tabel knowledge_bases baru jalankan ulang seeder-nya.
        foreach ($entries as $entry) {
            KnowledgeBase::firstOrCreate(
                ['slug' => $entry['id']],
                [
                    'title' => $entry['title'],
                    'keywords' => $entry['keywords'],
                    'answer' => $entry['answer'],
                    'source_label' => $entry['source_label'] ?? null,
                    'actions' => [],
                    'is_active' => true,
                ]
            );
        }
    }
}

