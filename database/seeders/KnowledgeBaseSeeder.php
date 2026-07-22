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
Halo, saya Zakky, asisten informasi publik Masjid An-Nur. Saya di sini untuk bantu jamaah memahami zakat, fidyah, infaq/shodaqoh, cara pembayaran, sampai estimasi perhitungan sederhana.

Beberapa hal yang bisa saya bantu:
- Jelasin zakat fitrah, zakat mal, fidyah, infaq, dan shodaqoh dengan bahasa yang mudah dipahami.
- Bantu menentukan jenis pembayaran yang paling sesuai dengan kondisi Anda.
- Hitungkan estimasi sederhana lengkap dengan rumusnya.
- Kasih arahan awal untuk kasus-kasus khusus.
- Arahkan ke panitia atau ustadz kalau kasusnya butuh keputusan lebih lanjut.

Satu hal yang perlu diingat: saya bukan pengganti panitia atau ustadz. Untuk kasus yang butuh keputusan fikih, hasil akhirnya tetap perlu dikonfirmasi ke pihak yang berwenang, ya.
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
Saya senang bisa bantu, tapi ada batasnya juga. Saya asisten informasi publik - membantu memberi arah awal atas kondisi Anda, lalu mengarahkan ke panitia atau ustadz kalau detailnya perlu dipastikan lebih jauh.

Yang tidak bisa saya lakukan:
- Mengeluarkan fatwa pribadi.
- Memastikan seseorang wajib atau tidak wajib zakat dalam kasus yang kompleks.
- Mengubah atau memverifikasi transaksi.
- Menampilkan data pribadi muzakki/mustahik.
- Menggantikan keputusan panitia dan ustadz.

Kalau pertanyaan Anda menyangkut kondisi pribadi, hutang, emas perhiasan, ibu hamil/menyusui, usaha, atau hal lain yang butuh pertimbangan fikih, saya akan kasih arahan awal dulu, lalu sarankan konsultasi ke panitia atau ustadz.
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
Untuk urusan layanan lokal dan teknis pembayaran, langsung saja hubungi panitia Masjid An-Nur - itu lebih cepat daripada tanya saya.

Contohnya untuk hal-hal seperti: nomor rekening/QRIS resmi, jadwal penerimaan zakat, konfirmasi setelah transfer, bukti pembayaran atau kuitansi, pembayaran yang belum tercatat, salah pilih jenis pembayaran, atau info lokasi penerimaan zakat.

Saya bisa kasih panduan umum, tapi konfirmasi akhir untuk layanan Masjid An-Nur tetap lewat panitia.
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
Kalau pertanyaan Anda butuh keputusan fikih yang sifatnya pribadi atau rumit, sebaiknya konsultasi langsung ke ustadz.

Contoh kasusnya: punya hutang besar atau cicilan jangka panjang, penghasilan tidak tetap, emas perhiasan yang dipakai sehari-hari, ibu hamil/menyusui yang tidak berpuasa, utang puasa lama, zakat usaha dengan aset-piutang-hutang, atau masih ragu apakah sudah wajib zakat.

Saya bisa kasih gambaran umum dulu supaya Anda lebih siap saat konsultasi nanti.
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
Zakat adalah harta tertentu yang wajib dikeluarkan seorang muslim ketika sudah memenuhi syarat tertentu, lalu diberikan kepada golongan yang berhak menerimanya.

Zakat punya dua fungsi sekaligus: sebagai ibadah (bentuk ketaatan kepada Allah SWT) dan sebagai fungsi sosial (membantu pemerataan kesejahteraan dan meringankan beban mustahik).

Bedanya dengan infaq dan shodaqoh, zakat punya ketentuan khusus - jenis harta, nisab, kadar, waktu, sampai golongan penerimanya sudah diatur, bukan sekadar sukarela.
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
Zakat punya dasar hukum dari ajaran Islam sekaligus regulasi pengelolaan zakat di Indonesia.

Al-Qur'an menjelaskan golongan penerima zakat, salah satunya di QS. At-Taubah ayat 60. Di Indonesia, pengelolaan zakat diatur dalam Undang-Undang Nomor 23 Tahun 2011, sementara syarat dan tata cara penghitungan zakat mal serta zakat fitrah diatur dalam Peraturan Menteri Agama Nomor 52 Tahun 2014 beserta perubahannya.

Bagi Masjid An-Nur, regulasi ini jadi dasar supaya pengelolaan zakat berjalan tertib, amanah, transparan, dan bisa dipertanggungjawabkan.
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
Zakat itu ada dua jenis utama: zakat fitrah dan zakat mal.

Zakat fitrah wajib dibayarkan setiap muslim menjelang Idulfitri, berkaitan dengan jiwa, dan dihitung per orang. Zakat mal adalah zakat atas harta - berlaku kalau sudah mencapai nisab dan memenuhi ketentuan sesuai jenis hartanya, misalnya penghasilan, emas, perak, tabungan, atau perdagangan.

Kalau Anda ingin memberi secara sukarela di luar kewajiban ini, itu masuk kategori infaq atau shodaqoh, bukan zakat.
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
Zakat fitrah wajib ditunaikan setiap muslim menjelang Idulfitri, dibayar per jiwa - baik untuk diri sendiri maupun keluarga yang jadi tanggungan.

Untuk layanan Masjid An-Nur saat ini:
- Uang: **Rp {$zakatFitrahUangFmt} per jiwa**.
- Beras: **{$zakatFitrahBerasKg} kg per jiwa**.

Tinggal kalikan dengan jumlah jiwa. Contoh untuk 4 jiwa: uangnya 4 x Rp {$zakatFitrahUangFmt} = **Rp 200.000**, berasnya 4 x {$zakatFitrahBerasKg} kg = **10 kg**.

Sebagai catatan, acuan umum BAZNAS sendiri menyebut beras/makanan pokok 2,5 kg atau 3,5 liter per jiwa - kalau dibayar uang, nominalnya menyesuaikan harga setempat. Nominal di atas berlaku untuk periode zakat berjalan dan bisa diperbarui sesuai informasi resmi panitia.
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
Zakat mal adalah zakat atas harta - wajib dikeluarkan kalau harta itu dimiliki penuh, sudah mencapai nisab, dan memenuhi ketentuan haul sesuai jenisnya.

Contoh harta yang bisa masuk zakat mal: penghasilan, emas dan perak, tabungan/simpanan, aset perdagangan atau usaha, sampai harta lain yang memenuhi ketentuan. Kadarnya umumnya 2,5%, tapi cara menghitungnya bisa berbeda tergantung jenis harta dan kondisi masing-masing.

Di layanan Masjid An-Nur, zakat penghasilan, emas, tabungan, dan usaha semuanya dicatat dalam kategori Zakat Mal.
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
Nisab dan haul adalah dua syarat utama yang menentukan apakah harta sudah wajib dizakati.

Nisab adalah batas minimal nilai harta - kalau belum mencapai itu, belum wajib zakat. Setiap jenis harta punya acuan berbeda, misalnya emas 85 gram, pertanian sekitar 653 kg gabah, atau ternak kambing mulai 40 ekor. Haul adalah syarat kepemilikan genap 1 tahun (kalender Hijriyah) untuk harta seperti tabungan, emas, atau aset usaha - zakat baru dikeluarkan setelah harta dimiliki genap 1 tahun dan tetap di atas nisab.

Ada pengecualian yang perlu diingat:
- Zakat penghasilan dihitung tiap kali menerima penghasilan di atas nisab, tanpa menunggu haul.
- Zakat pertanian juga tanpa haul - dikeluarkan tiap panen.
- Zakat fitrah malah tidak pakai nisab maupun haul sama sekali.

Karena acuan nisab mengikuti harga emas/bahan pokok terkini, sebaiknya nominal pastinya dikonfirmasi ke panitia saat mendekati waktu perhitungan zakat.
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
Kalau Anda penasaran kenapa hasil hitungan saya begitu, ini prinsip yang saya pakai: kehati-hatian di atas segalanya.

Prinsip yang saya pakai:
- Zakat penghasilan dihitung dari arus pendapatan yang diterima, terpisah dari tabungan.
- Zakat tabungan dan emas dihitung dari harta yang tersimpan saat ini.
- Penghasilan tahunan tidak dijumlahkan mentah-mentah dengan saldo tabungan - karena saldo tabungan biasanya sudah mencerminkan hasil penghasilan yang diterima dan dibelanjakan sepanjang tahun, jadi kalau digabung, penghasilan yang sama malah terhitung dua kali.
- Harta yang belum dimiliki penuh, dana titipan, atau harta campur usaha perlu dipisahkan dulu sebelum dihitung.
- Untuk kasus yang punya perbedaan pendapat ulama, saya cuma kasih arah awal - bukan keputusan final.

Perlu diketahui juga, sebagian lembaga menghitung zakat penghasilan dari bruto, sebagian lain dari bersih setelah kebutuhan pokok. Untuk pendekatan yang dipakai Masjid An-Nur, silakan konfirmasi ke panitia atau ustadz.
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
Zakat penghasilan adalah zakat atas pendapatan halal dari pekerjaan - gaji, honor, upah, jasa, bonus, THR, atau pendapatan profesi lainnya. Kadarnya **2,5%** dari penghasilan yang sudah mencapai nisab.

Nisabnya setara 85 gram emas, mengikuti harga emas saat ini: sekitar **Rp {$nishabRupiahFmt} per tahun**, atau sekitar **Rp {$nishabBulananFmt} per bulan**. Contohnya, kalau penghasilan bulanan Rp 10.000.000 (sudah di atas nisab bulanan), zakatnya 2,5% x Rp 10.000.000 = **Rp 250.000**.

Dua kondisi yang sering ditanyakan:
- Penghasilan tidak tetap (freelance): hitung total penghasilan dalam satu periode lalu bandingkan dengan nisab - bisa dihitung saat mencapai nisab atau secara tahunan.
- Bonus/THR: dihitung sebagai bagian penghasilan bulan diterimanya, ikut kena zakat kalau total penghasilan bulan itu sudah di atas nisab.

Ini estimasi sederhana. Kalau ada hutang, cicilan, atau tanggungan keluarga yang memengaruhi perhitungan, siapkan rinciannya supaya panitia atau ustadz bisa bantu memastikan hasil akhirnya.
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
Zakat emas dan perak termasuk bagian dari zakat mal. Nisab emas **85 gram**, nisab perak **595 gram**, kadarnya **2,5%**.

Rumusnya: 2,5% x nilai emas/perak yang memenuhi syarat (nilai rupiahnya mengikuti harga saat zakat dihitung). Contoh: emas simpanan 100 gram yang sudah memenuhi syarat, zakatnya 2,5% dari nilai 100 gram emas tersebut.

Untuk emas perhiasan yang dipakai sehari-hari (bukan simpanan/investasi), ada perbedaan pendapat ulama - sebagian mewajibkan, sebagian tidak selama pemakaiannya wajar. Saya bisa kasih arah awal untuk kasus ini, tapi kalau jumlahnya cukup besar atau Anda masih ragu, sebaiknya konfirmasi ke ustadz atau panitia.
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
Zakat tabungan adalah zakat atas uang simpanan yang sudah memenuhi ketentuan zakat mal - dimiliki penuh, dari sumber halal, mencapai nisab, dan sudah disimpan genap 1 tahun (haul).

Caranya: hitung saldo milik sendiri (bukan dana titipan orang lain), bandingkan dengan nisab zakat mal, lalu kalau memenuhi syarat, zakatnya 2,5% x saldo.

Kalau saldo naik turun sepanjang tahun, yang jadi patokan biasanya saldo saat perhitungan zakat dilakukan. Tapi kalau saldo sempat turun jauh di bawah nisab di tengah tahun, sebagian pendapat menganggap haulnya perlu dihitung ulang dari saat saldo kembali mencapai nisab. Kalau tabungan Anda bercampur dana titipan, dana usaha, atau hutang, sebaiknya konsultasikan ke panitia atau ustadz supaya tidak salah hitung.
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
Zakat perdagangan atau usaha adalah zakat atas aset usaha yang memenuhi ketentuan zakat mal - berlaku untuk usaha besar maupun kecil seperti warung atau toko.

Rumusnya: (kas usaha + nilai barang dagangan + piutang yang kemungkinan tertagih) - hutang usaha jatuh tempo = aset zakat. Kalau aset zakat mencapai nisab dan memenuhi haul, zakatnya 2,5% x aset zakat.

Karena tiap usaha punya komposisi stok, modal, piutang, dan hutang yang berbeda, sebaiknya konsultasi dulu ke panitia atau ustadz sebelum menentukan nominal final, supaya tidak salah memasukkan komponen aset.
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
Fidyah adalah pembayaran pengganti bagi orang yang tidak mampu berpuasa Ramadan karena alasan tertentu dan tidak memungkinkan diganti dengan qadha.

Untuk layanan Masjid An-Nur saat ini:
- Uang: **Rp {$fidyahUangFmt} per hari**.
- Beras: **{$fidyahBerasKg} kg per hari**.

Tinggal kalikan dengan jumlah hari yang ditinggalkan. Contoh untuk 5 hari: uangnya 5 x Rp {$fidyahUangFmt} = **Rp 150.000**, berasnya 5 x {$fidyahBerasKg} kg = **3,75 kg**.

Sebagai catatan, salah satu pendapat fikih menyebut takaran fidyah sekitar 0,75 kg beras/makanan pokok per hari - kalau dibayar uang, nominalnya menyesuaikan lembaga atau wilayah. Nominal dari lembaga zakat nasional bisa berbeda dari layanan lokal Masjid An-Nur; angka di atas khusus berlaku untuk layanan Masjid An-Nur.

Untuk kasus ibu hamil, menyusui, sakit, atau utang puasa lama, ketentuannya bisa beda-beda - siapkan kronologi singkat supaya panitia atau ustadz bisa bantu menentukan arahnya.
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

Bedanya dengan zakat: zakat bersifat wajib kalau memenuhi syarat, dan punya ketentuan nisab, kadar, serta penerima tertentu. Infaq dan shodaqoh sukarela, tidak ada nisab, dan bisa diberikan kapan saja - shodaqoh malah maknanya lebih luas karena bisa berupa materi maupun non-materi.

Kalau Anda ingin memberi bantuan sukarela lewat Masjid An-Nur, pilih saja kategori Infaq/Shodaqoh.
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
Mustahik adalah orang atau kelompok yang berhak menerima zakat. Ada delapan golongannya:
- Fakir
- Miskin
- Amil
- Mualaf
- Riqab
- Gharim
- Fisabilillah
- Ibnu sabil

Saya hanya bisa jelaskan kategori umumnya - penentuan seseorang sebagai mustahik tetap perlu proses verifikasi oleh panitia atau pihak yang berwenang.
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
Muzakki adalah orang atau pihak yang menunaikan zakat karena sudah memenuhi syarat wajib zakat.

Di layanan Masjid An-Nur, muzakki bisa berupa:
- Jamaah yang bayar zakat fitrah untuk diri sendiri.
- Kepala keluarga yang membayarkan zakat fitrah keluarganya.
- Jamaah yang bayar zakat mal.
- Jamaah yang menyalurkan fidyah, infaq, maupun shodaqoh.

Data pribadi muzakki tetap dijaga dan tidak seluruhnya ditampilkan di halaman publik.
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
Amil zakat adalah pihak yang bertugas membantu pengelolaan zakat - mulai dari penerimaan, pencatatan, pendistribusian, sampai pelaporan.

Di Masjid An-Nur, panitia zakat berperan:
- Menerima pembayaran zakat/fidyah/infaq/shodaqoh.
- Memberi informasi teknis pembayaran.
- Memverifikasi data kalau diperlukan.
- Menyalurkan zakat ke pihak yang berhak.
- Menyediakan informasi ringkasan ke jamaah.

Saya cuma bantu informasi awal - keputusan layanan tetap ada di panitia.
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
Masih bingung mau bayar apa? Coba cek ini:
- **Zakat Fitrah** - kalau mau bayar kewajiban menjelang Idulfitri untuk diri sendiri atau keluarga.
- **Fidyah** - kalau mau mengganti puasa Ramadan yang tidak bisa diqadha.
- **Zakat Mal** - kalau mau bayar zakat atas harta/penghasilan/emas/tabungan/usaha yang sudah memenuhi syarat.
- **Infaq/Shodaqoh** - kalau mau memberi bantuan sukarela di luar kewajiban zakat.

Kalau kondisi Anda cukup khusus - punya hutang, penghasilan tidak tetap, emas perhiasan, atau masih ragu apakah sudah wajib zakat - anggap jawaban saya sebagai arah awal, lalu konfirmasi detailnya ke panitia atau ustadz.
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
Zakat fitrah wajib bagi setiap muslim yang memenuhi ketentuan. Biasanya, kepala keluarga yang membayarkannya untuk dirinya dan anggota keluarga yang jadi tanggungannya - yaitu diri sendiri, istri/suami, anak, dan anggota keluarga lain dalam tanggungan.

Kalau ada kondisi khusus seperti bayi baru lahir, anggota keluarga yang sudah mandiri, atau keluarga beda tempat tinggal, siapkan daftar anggota keluarga supaya panitia atau ustadz bisa bantu memastikan siapa saja yang perlu dihitung.
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
Zakat fitrah ditunaikan di bulan Ramadan sampai sebelum salat Idulfitri dilaksanakan - jadi sebaiknya jangan mepet.

Panitia biasanya membuka penerimaan pada periode tertentu menjelang Idulfitri. Kalau khawatir kelewatan, segera konfirmasi jadwal penerimaan ke panitia dan ikuti informasi resmi yang diumumkan Masjid An-Nur.
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
Fidyah berlaku untuk orang yang tidak mampu berpuasa Ramadan dan tidak memungkinkan mengganti dengan qadha.

Kasus yang sering ditanyakan: lansia yang tidak kuat berpuasa, orang sakit menahun yang tidak memungkinkan qadha, atau kondisi seperti ibu hamil/menyusui yang perlu penjelasan lebih hati-hati karena ada perbedaan pendapat ulama.

Untuk kasus sederhana seperti lansia atau sakit menahun, fidyah bisa jadi pilihan langsung. Untuk ibu hamil, menyusui, atau utang puasa lama, bawa ringkasan kondisinya saat bertanya ke ustadz atau panitia.
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
Alurnya simpel:
- Tentukan dulu jenis pembayarannya (Zakat Fitrah, Fidyah, Zakat Mal, atau Infaq/Shodaqoh).
- Siapkan nama pembayar dan jumlah jiwa/nominal.
- Bayar lewat metode yang tersedia - tunai di lokasi, transfer bank, QRIS, atau cek kalau diterima panitia.
- Simpan bukti pembayarannya.

Kalau bayar non-tunai (termasuk cek), lakukan konfirmasi sesuai arahan panitia - khusus cek, statusnya bisa menunggu proses pencairan dulu.

Saya cuma bisa mengarahkan alurnya. Pastikan nomor rekening, QRIS, atau metode pembayaran yang Anda pakai memang berasal dari informasi resmi panitia Masjid An-Nur.
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
Sudah transfer tapi belum yakin statusnya? Siapkan ini untuk konfirmasi ke panitia:
- Nama pembayar.
- Jenis pembayaran.
- Nominal dan waktu pembayaran.
- Bukti transfer/QRIS (atau kuitansi kalau bayar tunai).

Kalau pembayaran belum muncul di ringkasan publik, ada beberapa kemungkinan: datanya belum diverifikasi/dicocokkan panitia, bukti pembayaran belum diterima, dashboard belum memperbarui data terbaru, atau pembayarannya tercatat di kategori lain.

Saya tidak bisa memverifikasi atau menerbitkan bukti pembayaran final - validasi dan bukti resmi tetap mengikuti data dan konfirmasi panitia Masjid An-Nur.
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
Jadwal penerimaan zakat Masjid An-Nur mengikuti informasi resmi yang diumumkan panitia pada periode berjalan.

Secara umum, zakat fitrah diterima di bulan Ramadan sampai sebelum salat Idulfitri, zakat mal/infaq/shodaqoh mengikuti layanan yang tersedia, dan fidyah dibayarkan sesuai jumlah hari puasa yang ditinggalkan.

Kalau jadwalnya belum terlihat di portal, langsung saja konfirmasi ke panitia Masjid An-Nur.
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

Yang ditampilkan:
- Total penerimaan uang dan beras.
- Total jiwa untuk zakat fitrah.
- Rincian per kategori (Zakat Fitrah, Fidyah, Zakat Mal, Infaq/Shodaqoh).
- Grafik penerimaan harian.
- Riwayat transaksi terbaru dalam bentuk terbatas.
- Informasi umum periode zakat.

Dengan begini, jamaah bisa lihat perkembangan penerimaan tanpa perlu menunggu rekap manual, sekaligus jadi bentuk pertanggungjawaban pengelolaan zakat. Tapi transparansi di sini bukan berarti semua data pribadi dibuka - keterbukaan tetap berjalan tanpa mengabaikan privasi muzakki dan mustahik.
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
Grafik penerimaan harian membantu Anda melihat pola penerimaan zakat dari hari ke hari - hari mana yang penerimaannya tinggi, bagaimana perkembangannya selama periode aktif, dan trennya secara visual.

Kalau angkanya terlihat beda dari ekspektasi, kemungkinan datanya masih dalam proses pembaruan atau hanya menampilkan transaksi yang sudah tercatat.
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
Data pribadi muzakki dan mustahik perlu dijaga, jadi halaman publik hanya menampilkan data ringkasan - bukan seluruh identitas pribadi.

Yang tampil di publik:
- Total penerimaan.
- Ringkasan per kategori.
- Grafik penerimaan.
- Info transaksi terbaru secara terbatas, kalau diperlukan.

Yang tidak ditampilkan: data lengkap muzakki/mustahik, kontak pribadi, dan detail sensitif pembayaran.

Tujuannya supaya transparansi tetap jalan tanpa mengorbankan privasi jamaah.
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
Punya hutang atau cicilan memang bisa memengaruhi perhitungan zakat, terutama kalau itu kewajiban yang harus segera dibayar dan menyangkut kebutuhan pokok.

Kalau setelah kebutuhan pokok dan kewajiban utama masih ada harta/penghasilan yang mencapai nisab, zakat tetap perlu diperhatikan. Tapi kalau hutang jatuh tempo membuat harta tidak lagi mencapai nisab, kondisinya perlu dihitung lebih hati-hati - dan untuk zakat penghasilan, caranya bisa beda tergantung panduan ulama atau lembaga yang diikuti.

Karena kondisi tiap orang beda, saya cuma bisa kasih arah awal. Untuk hasil yang lebih tepat, bawa informasi penghasilan, hutang, kebutuhan pokok, dan jumlah tanggungan saat konsultasi ke panitia atau ustadz.
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
Kalau seseorang sudah lanjut usia dan tidak mampu berpuasa serta tidak memungkinkan qadha, fidyah bisa jadi pilihan sesuai ketentuan syariat.

Caranya: hitung jumlah hari puasa yang ditinggalkan, lalu kalikan dengan nominal fidyah per hari. Untuk layanan Masjid An-Nur saat ini, itu **Rp {$fidyahUangFmt} per hari** (uang) atau **{$fidyahBerasKg} kg per hari** (beras) - dan fidyah ini boleh dibantu pembayarannya oleh keluarga.

Kalau kondisi lansianya masih memungkinkan berpuasa atau qadha, sebaiknya keputusan fidyah dipastikan dulu ke ustadz dengan menjelaskan kondisi kesehatannya.
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
Kalau seseorang sakit menahun sehingga tidak mampu berpuasa dan tidak memungkinkan mengganti puasa di kemudian hari, fidyah bisa jadi pilihan sesuai ketentuan syariat.

Caranya sama seperti kasus lain: hitung jumlah hari yang ditinggalkan, lalu bayarkan fidyah per hari sesuai ketentuan berlaku. Tapi kalau kondisi sakitnya masih berpotensi sembuh, qadha mungkin tetap perlu dipertimbangkan.

Karena kondisi kesehatan tiap orang beda-beda, sebaiknya konsultasi ke ustadz atau panitia untuk memastikan apakah cukup fidyah atau tetap perlu qadha.
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
Untuk ibu hamil atau menyusui yang tidak berpuasa, kewajiban penggantinya bisa beda-beda tergantung alasannya - apakah khawatir terhadap diri sendiri, anaknya, atau keduanya.

Dalam beberapa kondisi, kewajibannya bisa berupa qadha, fidyah, atau keduanya, sesuai perbedaan pendapat ulama.

Saya bisa kasih arah awal untuk kasus ini, tapi supaya kewajibannya sesuai kondisi Anda, sebaiknya konfirmasi detailnya ke ustadz atau panitia zakat.
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
Salah pilih jenis pembayaran? Segera konfirmasi ke panitia Masjid An-Nur - misalnya seharusnya Zakat Fitrah tapi terpilih Infaq, seharusnya Zakat Mal tapi terpilih Fidyah, nominal benar tapi kategorinya salah, atau keterangan pembayaran belum sesuai.

Siapkan info berikut saat konfirmasi:
- Nama pembayar.
- Nominal dan waktu pembayaran.
- Jenis pembayaran yang dipilih dan yang seharusnya.
- Bukti pembayaran kalau ada.

Saya tidak bisa mengubah data transaksi - perubahan atau koreksi hanya bisa dilakukan panitia sesuai prosedur.
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
Zakat pertanian atau perkebunan adalah zakat atas hasil panen yang memenuhi nisab - dikeluarkan tiap kali panen, tanpa syarat haul 1 tahun seperti zakat mal lainnya.

Nisabnya sekitar **653 kg gabah** atau hasil panen yang setara. Kadarnya beda tergantung cara pengairan:
- Pengairan alami (hujan, sungai, tanpa biaya besar): **10%**. Contoh untuk 1.000 kg gabah, zakatnya 10% x 1.000 kg = **100 kg**.
- Pengairan berbiaya (irigasi, pompa, perawatan berbayar): **5%**. Contoh untuk 1.000 kg gabah, zakatnya 5% x 1.000 kg = **50 kg**.
- Pengairan campuran: sebaiknya konsultasikan langsung ke panitia/ustadz.

Untuk komoditas selain padi/gabah, nisab perlu disetarakan dengan nilai atau ukuran hasil pertanian yang berlaku - bukan otomatis dipukul rata 653 kg. Saya bisa kasih contoh rumusnya, tapi perhitungan final tetap perlu lihat jenis panen, berat bersih, biaya pengairan, dan waktu panen - siapkan data itu saat konfirmasi ke panitia.
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
Zakat peternakan berlaku untuk hewan ternak tertentu seperti kambing/domba dan sapi/kerbau, kalau memenuhi syarat umum: jenis dan jumlah ternaknya, apakah digembalakan atau bagian dari usaha intensif, sudah mencapai haul (1 tahun), dan sudah mencapai nisab sesuai jenisnya.

Nisab dan kadarnya mengikuti tabel jumlah ternak, bukan persentase uang seperti zakat penghasilan:
- Kambing/domba, mulai wajib pada **40 ekor**: 40-120 ekor zakatnya 1 ekor, 121-200 ekor zakatnya 2 ekor, 201-300 ekor zakatnya 3 ekor.
- Sapi/kerbau, mulai wajib pada **30 ekor**: 30-39 ekor zakatnya 1 ekor anak sapi (sekitar 1 tahun), 40-59 ekor zakatnya 1 ekor sapi (sekitar 2 tahun).

Kalau ternak dipelihara sebagai usaha komersial intensif (jual-beli, penggemukan, produksi bisnis), perhitungannya bisa masuk pembahasan zakat perdagangan - bukan tabel ternak murni, jadi status ternaknya perlu dikonfirmasi dulu. Saya bisa jelaskan tabel dasarnya, tapi belum bisa hitung otomatis untuk semua variasi ternak yang bercampur - detailnya perlu dirapikan dulu oleh panitia atau ustadz.
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
Zakat properti sewa (istilah fikihnya "mustaghallat") adalah zakat atas penghasilan dari aset yang disewakan - rumah kontrakan, kos-kosan, ruko, kios, atau properti lain yang menghasilkan pendapatan sewa.

Properti yang dipakai pribadi tidak otomatis kena zakat. Yang diperhatikan adalah pendapatan bersih sewa (pendapatan dikurangi biaya operasional wajar), bukan nilai propertinya - kalau pendapatan bersihnya mencapai nisab, zakatnya **2,5%**. Contoh: pendapatan sewa bersih Rp 20.000.000 dalam suatu periode, zakatnya 2,5% x Rp 20.000.000 = **Rp 500.000**. Sebagai catatan, properti yang dibeli untuk dijual kembali (bukan disewakan) masuk zakat perdagangan, bukan zakat sewa.

Dalam praktik kontemporer, hasil sewa dianalisis sebagai pendapatan dari aset produktif - pendekatannya bisa mengikuti zakat penghasilan atau zakat mal, tergantung panduan lembaga/ustadz yang diikuti, jadi bukan satu rumus mutlak untuk semua kasus. Kalau propertinya masih cicilan, ada biaya renovasi besar, atau disewakan tidak rutin, siapkan pendapatan sewa, biaya operasional, cicilan jatuh tempo, dan periode penerimaan sebelum konfirmasi ke panitia.
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
Zakat saham, investasi, dan reksadana termasuk pembahasan zakat mal kontemporer. Objek zakatnya bisa berupa nilai kepemilikan, dividen, capital gain, atau hasil investasi - tergantung jenis aset dan pendapat yang diikuti.

Kalau investasi dimiliki sebagai aset dan sudah mencapai nisab serta haul, zakat perlu diperhatikan. Kadar yang umum dipakai **2,5%** dari objek zakat (nilai portofolio, dividen, atau capital gain), tapi perhitungannya bisa beda sesuai jenis investasi (saham konvensional vs syariah, reksadana, obligasi/sukuk). Contoh: objek zakat investasi Rp 50.000.000 yang sudah memenuhi syarat, zakatnya 2,5% x Rp 50.000.000 = **Rp 1.250.000**.

Karena topik ini kontemporer dan pendekatannya beda-beda antar lembaga - ada yang fokus ke nilai kepemilikan, ada yang fokus ke keuntungan seperti dividen/capital gain - saya tidak mengunci satu rumus untuk semua kasus, cuma kasih arah awal. Untuk portofolio yang kompleks, siapkan nilai portofolio, dividen, capital gain, jenis instrumen, dan periode kepemilikan sebelum konfirmasi ke panitia, ustadz, atau lembaga zakat.
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
Harta warisan tidak otomatis kena zakat hanya karena baru diterima. Zakat baru perlu diperhatikan setelah harta itu sah jadi milik ahli waris dan memenuhi syarat zakat sesuai jenis hartanya.

Tergantung bentuk warisannya:
- Uang, tabungan, atau emas yang sudah jadi milik ahli waris: diperhatikan sebagai zakat mal/tabungan kalau mencapai nisab.
- Tanah/rumah yang dipakai pribadi: tidak otomatis kena zakat.
- Properti yang disewakan: masuk pembahasan zakat properti sewa.
- Aset yang dijual jadi uang simpanan: diperhatikan sebagai zakat mal.
- Usaha atau barang dagangan: masuk zakat perdagangan.

Karena warisan sering bercampur dengan pembagian ahli waris, hutang pewaris, atau aset yang belum terjual, saya kasih arahan awal berdasarkan bentuk hartanya dulu. Untuk hasil yang lebih tepat, bawa rincian bentuk warisan yang diterima saat konsultasi ke ustadz atau panitia.
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
Untuk kasus khusus, saya tidak langsung kasih keputusan final - saya bantu menganalisis secara bertahap.

Alurnya:
1. Identifikasi jenis harta atau kewajiban yang ditanyakan.
2. Arahkan ke kategori yang sesuai (zakat fitrah, zakat mal, fidyah, atau infaq/shodaqoh).
3. Cek syarat umum seperti nisab, haul, dan kepemilikan penuh.
4. Beri estimasi awal kalau datanya sederhana.
5. Tandai faktor yang bisa mengubah hasil (hutang, cicilan, status harta, dana titipan, perbedaan pendapat ulama).
6. Arahkan ke panitia atau ustadz kalau butuh keputusan fikih atau verifikasi layanan.

Dengan cara ini, Anda bisa paham dulu arah masalahnya sebelum mengambil langkah berikutnya.
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
Tidak semua aset pribadi otomatis kena zakat. Aset yang dipakai untuk kebutuhan sehari-hari tidak otomatis jadi objek zakat - baru perlu diperhatikan kalau berubah fungsi jadi aset usaha, barang dagangan, investasi, atau sumber penghasilan.

Yang dilihat bukan cuma nilai asetnya, tapi fungsi dan status kepemilikannya. Kalau aset itu menghasilkan pendapatan, pendapatannya bisa masuk pembahasan zakat mal sesuai jenisnya. Misalnya, rumah yang ditempati sendiri beda dengan rumah yang disewakan; kendaraan yang dipakai pribadi beda dengan kendaraan yang jadi aset usaha atau barang dagangan.

Kalau aset Anda bercampur antara kebutuhan pribadi, investasi, dan usaha, sebaiknya konfirmasi detail kasusnya ke panitia atau ustadz.
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
Kendaraan perlu dilihat dari fungsi dan penggunaannya, bukan sekadar nilainya:
- Mobil/motor untuk kebutuhan pribadi: tidak otomatis kena zakat.
- Kendaraan yang jadi barang dagangan: masuk zakat perdagangan.
- Kendaraan sebagai aset usaha: diperhatikan dari penghasilan/keuntungan usahanya.
- Kendaraan yang disewakan: pendapatan sewanya bisa masuk zakat penghasilan atau zakat mal.

Jadi pertanyaan tentang kendaraan biasanya perlu diklasifikasikan dulu: dipakai pribadi, dijual kembali, disewakan, atau jadi aset operasional usaha. Untuk keputusan akhir, terutama kalau terkait usaha, cicilan, atau pendapatan sewa, sebaiknya konfirmasi ke panitia atau ustadz.
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
Sama seperti aset lain, rumah dilihat dari fungsi dan status penggunaannya:
- Rumah yang ditempati sendiri untuk kebutuhan keluarga: tidak otomatis kena zakat.
- Rumah yang disewakan: diperhatikan dari penghasilan sewanya.
- Rumah yang dibeli untuk dijual kembali: bisa masuk aset dagangan atau investasi.
- Rumah kosong: perlu dilihat niat dan fungsinya - untuk tempat tinggal, investasi, disewakan, atau dijual.

Jadi rumah pribadi yang dipakai sebagai tempat tinggal beda dengan properti yang menghasilkan pendapatan. Kalau rumah Anda masih cicilan, disewakan tidak rutin, atau bercampur tujuan investasi, sebaiknya konfirmasi detailnya ke panitia atau ustadz.
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
Hadiah atau hibah tidak otomatis kena zakat begitu diterima. Setelah sah jadi milik penerima, baru harta itu diperhatikan sesuai jenisnya:
- Berupa uang dan disimpan: masuk zakat tabungan kalau mencapai nisab.
- Berupa emas: masuk zakat emas kalau mencapai nisab.
- Berupa barang yang dipakai pribadi: tidak otomatis kena zakat.
- Jadi aset yang disewakan atau diperjualbelikan: bisa berubah jadi zakat properti sewa atau zakat perdagangan.

Saya bisa bantu arahkan kategori awalnya, tapi untuk hasil yang lebih tepat, perlu dilihat bentuk hadiah, nilai, status kepemilikan, dan penggunaan hartanya.
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
Uang pesangon dilihat sebagai harta yang diterima dan jadi milik Anda:
- Diterima sebagai uang dan disimpan: bisa masuk zakat tabungan/mal kalau mencapai nisab.
- Langsung dipakai untuk kebutuhan pokok, pengobatan, hutang, atau kebutuhan mendesak: perhitungannya perlu lebih hati-hati.
- Jadi modal usaha: pembahasannya lanjut ke zakat usaha.
- Bercampur dengan tabungan lama: perlu dipisahkan dulu supaya komponen hartanya jelas.

Untuk keputusan akhir, terutama kalau pesangon terkait kebutuhan hidup, hutang, atau kondisi keluarga, sebaiknya konfirmasi detailnya ke panitia atau ustadz.
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
Dana pensiun dilihat dari cara penerimaannya:
- Diterima rutin tiap bulan: bisa dianalisis seperti penghasilan bulanan.
- Cair sekaligus dan disimpan: bisa masuk zakat tabungan/mal kalau mencapai nisab.
- Langsung dipakai untuk kebutuhan pokok, pengobatan, atau pelunasan hutang: perhitungannya perlu lebih hati-hati.
- Dijadikan modal usaha: pembahasannya lanjut ke zakat usaha.

Karena kondisi pensiun, kebutuhan hidup, dan hutang tiap orang beda, sebaiknya konfirmasi detail kasusnya ke panitia atau ustadz supaya perhitungannya lebih tepat.
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
Piutang adalah harta yang masih ada di pihak lain karena dipinjam atau belum dibayarkan:
- Piutang kuat, besar kemungkinan tertagih: bisa diperhatikan dalam perhitungan zakat.
- Piutang lemah, sulit tertagih, atau tidak jelas kapan dibayar: perhitungannya perlu lebih hati-hati.
- Untuk usaha, piutang yang kemungkinan tertagih: biasanya masuk zakat perdagangan.
- Piutang yang baru diterima kembali: diperhatikan sebagai bagian dari simpanan.

Karena status piutang beda-beda, saya kasih arah awal berdasarkan kemungkinan tertagihnya. Kalau nilainya besar atau statusnya tidak jelas, sebaiknya konfirmasi ke panitia atau ustadz.
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
Kalau harta Anda campur - misalnya rekening pribadi bercampur uang usaha, tabungan bercampur dana titipan, atau uang keluarga bercampur uang pribadi - itu perlu dipisahkan dulu sebelum dihitung zakatnya.

Caranya:
1. Pisahkan dana yang benar-benar milik pribadi.
2. Pisahkan dana titipan/milik orang lain.
3. Pisahkan uang usaha dari kebutuhan pribadi kalau memungkinkan.
4. Tentukan apakah harta pribadi atau aset usahanya mencapai nisab.

Untuk harta campuran, sebaiknya konfirmasi detail kasusnya ke panitia atau ustadz supaya tidak salah memasukkan komponen harta.
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
Bayar zakat pakai cek bisa dilakukan kalau metode ini tersedia dan diterima panitia Masjid An-Nur.

Yang perlu diperhatikan:
- Pastikan cek ditujukan ke pihak yang benar sesuai arahan panitia.
- Pastikan nominal, tanggal, dan keterangannya jelas.
- Simpan salinan/dokumentasi ceknya.
- Setelah menyerahkan cek, konfirmasi ke panitia - statusnya bisa menunggu proses pencairan sesuai prosedur.

Saya tidak bisa memverifikasi keabsahan cek atau status pencairannya; validasi akhir tetap dilakukan panitia.
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
Untuk zakat mal yang datanya sederhana - zakat penghasilan, tabungan, dan emas - saya bisa bantu hitung estimasi otomatis.

Tapi untuk topik lanjutan berikut, saat ini saya cuma bisa kasih panduan konsep, rumus umum, dan arahan konsultasi - belum bisa hitung otomatis:
- Zakat pertanian/perkebunan.
- Zakat peternakan.
- Zakat saham, reksadana, dan investasi.
- Zakat properti sewa.
- Zakat warisan.
- Zakat usaha dengan stok, piutang, dan hutang yang kompleks.

Alasannya sederhana: perhitungan topik-topik ini butuh data yang lebih spesifik daripada yang bisa ditangani kalkulator saya. Saya lebih pilih jujur soal keterbatasan ini daripada kasih angka yang berisiko keliru. Untuk kasus lanjutan, siapkan dulu jenis harta, nilai, status kepemilikan, hutang terkait, biaya operasional, dan periode kepemilikan - itu bikin konsultasi ke panitia atau ustadz jadi lebih cepat dan akurat.
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
