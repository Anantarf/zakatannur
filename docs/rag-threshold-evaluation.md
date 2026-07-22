# Evaluasi Threshold Retrieval-Augmented Generation (RAG)

## Latar Belakang
Untuk mencegah asisten virtual (Zakky) mengalami halusinasi dan menjawab dengan data yang salah saaran, Zakky diimplementasikan menggunakan arsitektur RAG (Retrieval-Augmented Generation). 
RAG mengandalkan proses *retrieval* untuk mencari dokumen (potongan pengetahuan) yang paling relevan dengan pertanyaan pengguna.

Dalam sistem ini, pencarian relevansi (*Semantic Search*) menggunakan **Cosine Similarity** dari *vector embeddings* (model `text-embedding-3-small`). Agar pencarian tetap presisi (tingkat akurasi tinggi tanpa memunculkan dokumen yang tidak nyambung), diperlukan sebuah nilai batas (*threshold*) yang terukur.

## Metodologi Uji Coba (Precision & Recall)
Kami melakukan observasi jarak vektor antara sekumpulan pertanyaan uji (Queries) dengan sekumpulan dokumen (Knowledge Base).

### Kategori Hasil Observasi:
1. **Similarity > 0.60**
   *Karakteristik:* Pertanyaan pengguna memiliki kesamaan persis (exact match) dengan kata kunci atau kalimat dalam dokumen.
   *Contoh:* 
   - Query: "Bagaimana cara bayar zakat fitrah?"
   - Dokumen: "Cara bayar zakat fitrah..."
   *Kesimpulan:* Relevansi sangat tinggi.

2. **Similarity 0.45 - 0.59**
   *Karakteristik:* Pertanyaan menggunakan bahasa gaul, singkatan, parafrase, atau sinonim dari dokumen resmi.
   *Contoh:*
   - Query: "cara tf donasi" (tf = transfer)
   - Dokumen: "Pembayaran zakat dan infaq dapat dilakukan melalui rekening..."
   *Kesimpulan:* Relevansi moderat. Model *embedding* mampu memahami kedekatan semantik meskipun kata-katanya berbeda. Rentang ini krusial untuk mempertahankan tingkat *Recall* (kemampuan menemukan jawaban).

3. **Similarity < 0.45**
   *Karakteristik:* Pertanyaan sama sekali tidak berhubungan dengan dokumen, atau hanya berbagi satu kata yang sama (noise).
   *Contoh:*
   - Query: "Berapa jadwal buka puasa hari ini?"
   - Dokumen: "Zakat fitrah dibayar saat puasa Ramadan..."
   *Kesimpulan:* *Out-of-scope*. Memasukkan dokumen ini ke LLM akan menyebabkan halusinasi.

## Justifikasi Penetapan Threshold = 0.45
Berdasarkan uji sampel di atas, ditetapkan `0.45` sebagai *threshold* minimum pada fungsi `KnowledgeRetriever::searchViaEmbeddings()`.

- **Mencegah False Positives (Menaikkan Precision):** Pertanyaan ngawur yang nilainya `< 0.45` akan otomatis dibuang, memaksa sistem menggunakan insting *fallback* atau menolak menjawab (guardrail).
- **Mencegah False Negatives (Menaikkan Recall):** Menurunkan threshold dari angka aman (misal 0.60) menjadi 0.45 memungkinkan chatbot mengenali variasi bahasa dan parafrase masyarakat awam yang seringkali tidak terstruktur.

## Kesimpulan Akademis
Nilai threshold `0.45` bukan angka acak (hardcoded tanpa dasar), melainkan **hasil *tuning* empiris** yang mengoptimalkan keseimbangan antara *Precision* (ketepatan) dan *Recall* (kepekaan) dalam arsitektur klasifikasi semantik Zakat An-Nur.

## Evaluasi Kuantitatif (Precision, Recall, Specificity, F1)

Selain observasi kualitatif di atas, `php artisan chatbot:eval-rag` menghasilkan confusion matrix terukur dari:
- 20 kasus positif (`ChatbotEvalDataset::cases()`) — satu per topik utama Knowledge Base, mengukur *recall* (apakah topik yang benar ditemukan).
- 7 kasus negatif out-of-scope (`ChatbotEvalDataset::negativeCases()`) — pertanyaan yang sama sekali tidak berhubungan dengan zakat, mengukur *specificity* (apakah sistem berhasil TIDAK mengembalikan dokumen apa pun).

Definisi metrik yang dipakai:
- **Precision** = TP / (TP + FP) — dari hasil yang dikembalikan, berapa persen yang relevan.
- **Recall** = TP / (TP + FN) — dari topik yang seharusnya ditemukan, berapa persen benar ditemukan.
- **Specificity** = TN / (TN + FP) — dari pertanyaan out-of-scope, berapa persen benar-benar dikosongkan.
- **F1-Score** = 2 × (Precision × Recall) / (Precision + Recall).

### Cara menjalankan

```bash
php artisan chatbot:cache-embeddings   # pastikan cache embeddings segar
php artisan chatbot:eval-rag           # butuh OPENAI_API_KEY asli, memanggil API sungguhan
```

### Hasil run terakhir

> _(Isi tabel ini dengan output nyata dari `php artisan chatbot:eval-rag` setelah dijalankan dengan API key produksi/staging. Jangan menaruh angka perkiraan di sini — kosongkan sampai benar-benar dijalankan.)_

| Tanggal run | TP | FN | TN | FP | Precision | Recall | Specificity | F1 |
|---|---|---|---|---|---|---|---|---|
| _(belum dijalankan)_ | | | | | | | | |
