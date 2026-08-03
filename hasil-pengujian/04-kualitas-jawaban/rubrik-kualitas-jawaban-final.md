# Rubrik Kualitas Jawaban Zakky

Sumber respons: `chatbot:eval-behavior-rubric --markdown` pada MySQL lokal.
Penilaian ini adalah penilaian manual peneliti terhadap 12 respons aktual Zakky, bukan pengganti hasil evaluasi perilaku 19/19.

## Definisi Operasional Skala

| Skor | Definisi Operasional |
|---:|---|
| 1 | Tidak memenuhi kriteria; jawaban salah, tidak relevan, membingungkan, atau bertentangan dengan sumber. |
| 2 | Memenuhi sebagian kecil kriteria; masih ada kekeliruan penting, informasi pokok hilang, atau arahan kurang dapat dipakai pengguna. |
| 3 | Memenuhi kriteria secara cukup; jawaban benar dan relevan, tetapi masih ada kekurangan minor pada kelengkapan, kejelasan, atau ketepatan batasan. |
| 4 | Memenuhi kriteria secara utuh; jawaban benar, relevan, lengkap sesuai kebutuhan skenario, jelas, dan konsisten dengan sumber/acuan sistem. |

## Sumber Acuan Penilaian

| Kriteria | Sumber Acuan Eksplisit |
|---|---|
| Ketepatan | Respons aktual pada `chatbot-eval-behavior-rubric-mysql.md`, aturan kalkulasi `ChatbotZakatMalGuide`, dan output kalkulasi aktual. |
| Relevansi | Fokus skenario pada `ChatbotBehaviorRubricDataset` dan alur AI Assistant pada `docs/ACUAN.md` bagian 6.1-6.3. |
| Kelengkapan | Kebutuhan data dalam prompt Zakky, basis pengetahuan `KnowledgeBaseSeeder`, dan batas kalkulasi otomatis. |
| Kejelasan | Respons aktual Zakky, ekspektasi tone panitia masjid pada dataset rubrik, dan keterbacaan langkah pengguna. |
| Konsistensi terhadap Sumber | Basis pengetahuan Zakky, konfigurasi periode aktif MySQL, batas kewenangan panitia/ustadz, dan hasil evaluator retrieval/perilaku. |

| Skenario | Ketepatan | Alasan | Relevansi | Alasan | Kelengkapan | Alasan | Kejelasan | Alasan | Konsistensi terhadap Sumber | Alasan |
|---|---:|---|---:|---|---:|---|---:|---|---:|---|
| BEH-01 | 4 | Respons mengarahkan perhitungan bertahap tanpa memaksa asumsi. | 4 | Menjawab kecemasan pengguna saat mau menghitung zakat. | 4 | Meminta data penting yang dibutuhkan kalkulasi. | 4 | Bahasa mudah dan menenangkan. | 4 | Tidak keluar dari panduan zakat mal. |
| BEH-02 | 4 | Tidak menyimpulkan wajib zakat sebelum data cukup. | 4 | Sesuai dengan konteks pengguna yang malu karena tabungan kecil. | 3 | Penjelasan cukup, tetapi belum merinci seluruh kemungkinan aset. | 4 | Empatik dan mudah dipahami. | 4 | Selaras dengan konsep nisab dan kehati-hatian. |
| BEH-03 | 4 | Menjawab ringkas sesuai permintaan. | 4 | Fokus pada inti pertanyaan. | 3 | Sengaja tidak lengkap karena pengguna meminta jawaban singkat. | 4 | Padat dan tidak berbelit. | 4 | Tidak menambah klaim di luar sumber. |
| BEH-04 | 4 | Membedakan penghasilan, tabungan, dan kebutuhan data. | 4 | Sesuai dengan pertanyaan detail pengguna. | 4 | Mencakup komponen utama konsultasi zakat mal. | 4 | Struktur jawaban runtut. | 4 | Sesuai panduan nisab, haul, dan zakat mal. |
| BEH-05 | 4 | Mengklasifikasikan rumah sewa secara hati-hati. | 4 | Menjawab kasus aset produktif yang ditanyakan. | 4 | Menyebut perlakuan sewa dan perlunya data lanjutan. | 4 | Mudah diikuti. | 4 | Selaras dengan sumber tentang aset produktif. |
| BEH-06 | 4 | Menjawab saham dan reksadana tanpa spekulasi berlebihan. | 4 | Relevan dengan jenis aset yang diajukan. | 4 | Menyebut prinsip penilaian dan data yang dibutuhkan. | 4 | Terstruktur. | 4 | Tidak menyimpang dari sumber zakat mal. |
| BEH-07 | 3 | Respons benar, tetapi baru menggali sebagian data. | 4 | Tetap sesuai dengan kondisi pengguna yang belum tahu data pasti. | 3 | Belum mencakup semua komponen aset, hutang, dan haul. | 4 | Pertanyaan lanjutan jelas. | 4 | Tidak membuat hasil tanpa data. |
| BEH-08 | 4 | Menerima koreksi angka dan menghitung ulang. | 4 | Tepat untuk skenario koreksi multi-turn. | 4 | Memperbarui nilai yang berubah. | 3 | Cukup jelas, tetapi agak panjang dan repetitif. | 4 | Konsisten dengan aturan kalkulasi. |
| BEH-09 | 4 | Memberi langkah ringan tanpa menekan pengguna. | 4 | Sesuai dengan permintaan langkah sederhana. | 3 | Cukup praktis, tetapi belum mencakup semua tahapan. | 4 | Instruksi mudah dilakukan. | 4 | Tidak keluar dari batas konsultasi zakat. |
| BEH-10 | 4 | Menjawab interupsi konsep tanpa kehilangan konteks. | 4 | Relevan dengan pertanyaan nisab di tengah konsultasi. | 4 | Menjelaskan konsep dan tetap menjaga alur konsultasi. | 4 | Bahasa jelas. | 4 | Sesuai sumber nisab dan haul. |
| BEH-11 | 4 | Hasil nol disampaikan sesuai data di bawah nisab. | 4 | Menjawab kondisi hasil perhitungan pengguna. | 4 | Menjelaskan alasan tidak wajib zakat. | 3 | Ada frasa pembayaran yang kurang pas untuk hasil nol. | 4 | Sesuai batas nisab. |
| BEH-12 | 4 | Menutup percakapan dengan arahan pembayaran resmi. | 4 | Sesuai setelah hasil konsultasi selesai. | 4 | Memuat tindak lanjut yang diperlukan. | 4 | Ringkas dan jelas. | 4 | Selaras dengan batas kewenangan sistem dan panitia. |

## Rekapitulasi

| Kriteria | Total Skor | Rata-rata |
|---|---:|---:|
| Ketepatan | 47 dari 48 | 3,92 |
| Relevansi | 48 dari 48 | 4,00 |
| Kelengkapan | 44 dari 48 | 3,67 |
| Kejelasan | 46 dari 48 | 3,83 |
| Konsistensi terhadap Sumber | 48 dari 48 | 4,00 |

Total seluruh skor: 233 dari 240.
Rata-rata keseluruhan: 233 / (12 x 5) = 3,88 dari 4.

Catatan interpretasi: hasil evaluasi perilaku 19/19 menunjukkan skenario teknis multi-turn memenuhi kondisi yang diuji. Nilai kualitas jawaban di atas menilai mutu isi respons aktual berdasarkan rubrik Bab IV.

## Sumber Acuan per Skenario

| Skenario | Sumber Acuan |
|---|---|
| BEH-01 | Respons aktual rubrik; alur konsultasi zakat mal; prompt Zakky untuk menggali data sebelum menghitung. |
| BEH-02 | Respons aktual rubrik; konsep nisab/haul pada basis pengetahuan; batasan agar tidak menghakimi pengguna. |
| BEH-03 | Respons aktual rubrik; basis pengetahuan zakat mal; permintaan pengguna untuk jawaban singkat. |
| BEH-04 | Respons aktual rubrik; kebutuhan data zakat mal; aturan pemisahan penghasilan, tabungan, emas, dan hutang. |
| BEH-05 | Respons aktual rubrik; basis pengetahuan aset produktif/properti sewa; batas kewenangan panitia/ustadz. |
| BEH-06 | Respons aktual rubrik; basis pengetahuan saham/reksadana; batas perhitungan otomatis topik lanjutan. |
| BEH-07 | Respons aktual rubrik; aturan tidak menghitung tanpa data cukup; kebutuhan klarifikasi aset/hutang/haul. |
| BEH-08 | Respons aktual rubrik; skenario koreksi multi-turn; `ChatbotConversationContext` dan kalkulasi sentinel. |
| BEH-09 | Respons aktual rubrik; ekspektasi langkah ringan untuk pengguna awam; tone panitia masjid. |
| BEH-10 | Respons aktual rubrik; basis pengetahuan nisab/haul; aturan menjaga konteks saat interupsi konsep. |
| BEH-11 | Respons aktual rubrik; hasil kalkulasi di bawah nisab; aturan membedakan tidak wajib zakat dan infaq sukarela. |
| BEH-12 | Respons aktual rubrik; hasil kalkulasi final; arahan pembayaran resmi ke panitia Masjid An-Nur. |
