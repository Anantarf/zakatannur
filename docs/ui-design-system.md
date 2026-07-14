# Sistem Desain Premium (UI/UX Guidelines)

Dokumen ini adalah sumber kebenaran tunggal (*Single Source of Truth*) untuk standar desain, token CSS, dan konvensi komponen yang digunakan di seluruh aplikasi Zakat (baik halaman **Publik** maupun **Admin**).

Gaya utama aplikasi ini adalah perpaduan **Bento Box Layout**, **Soft Neumorphism**, dan **High-Contrast Dark Theme** (untuk titik fokus).

---

## 1. Tipografi (Typography)

Sistem tipografi dirancang untuk keterbacaan tinggi dan kesan *SaaS/Enterprise* yang modern.

*   **Font Family Utama:** `"Plus Jakarta Sans"` (Digunakan untuk semua elemen teks).
*   **Heading (H1 - H3):** Harus menggunakan `font-black` (weight 900) dengan `leading-tight` atau `tracking-tight`. Contoh: `text-2xl font-black tracking-tight text-slate-900`.
*   **Kicker / Eyebrow Text:** Label kecil di atas judul harus menggunakan `uppercase`, `text-[10px]` atau `text-[11px]`, `font-black`, dan jarak huruf yang sangat lebar (`tracking-[0.24em]`). Contoh: `text-[11px] font-black uppercase tracking-[0.24em] text-emerald-600`.
*   **Body Text:** Gunakan `text-sm` atau `text-base` dengan `leading-relaxed` atau `leading-6` dan warna `text-slate-500` atau `text-slate-600` agar nyaman dibaca.

---

## 2. Palet Warna (Color Palette)

Aplikasi ini tidak menggunakan warna dasar bawaan secara acak. Berikut adalah konvensi warna yang ketat:

*   **Primary Brand (Emerald):** Digunakan untuk tombol utama, ikon aktif, grafik utama, dan aksen visual.
    *   `emerald-600` untuk tombol aktif.
    *   `emerald-50` / `emerald-100` untuk *background* aksen lembut.
*   **Neutral / Surface (Slate):** Digunakan untuk teks, garis batas, dan *background*.
    *   `slate-900` / `slate-950`: Teks judul utama, Topbar Gelap, dan elemen Dark Card.
    *   `slate-500` / `slate-600`: Teks paragraf/body pendukung.
    *   `slate-50` / `slate-100`: *Background* aplikasi utama secara global (`.ui-shell`).
*   **Semantic / State Colors:**
    *   **Info:** Biru (`blue-600`).
    *   **Peringatan/Danger:** Merah (`red-600`).
    *   **Pending/Draft:** Kuning/Amber (`amber-500`).

---

## 3. Tata Letak (Layout & Spacing)

Struktur tata letak menggunakan pendekatan **Bento Box** yang sangat bergantung pada *Grid* dan *Gap* yang presisi.

*   **Container Maksimal:** Selalu gunakan pembungkus yang seragam: `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8` untuk seluruh halaman (Publik maupun Admin).
*   **Grid Gaps (Bento Style):** Gunakan `gap-6` atau `gap-8` antar kartu di dalam *grid*. Hindari `gap-4` yang membuat elemen terasa terlalu sesak.
*   **Vertical Spacing:** Gunakan `space-y-6` atau `space-y-8` untuk memisahkan blok (*section*) secara vertikal.
*   **Card Padding:** Kartu harus terasa lega (*spacious*). Gunakan `p-5 sm:p-6` (standar) atau `p-6 lg:p-8` (besar) untuk area dalam kartu utama.

---

## 4. Animasi & Transisi (Micro-Interactions)

Setiap elemen interaktif harus terasa responsif, organik, dan "hidup".

*   **Global Transition:** Semua perubahan warna, ukuran, bayangan, atau status saat di-hover/difokus harus menggunakan class `transition-all duration-300`.
*   **Hover Lift (Efek Melayang):** Elemen yang bisa diklik (tombol, kartu aksi) harus memberikan efek terangkat ke atas:
    *   Gunakan `hover:-translate-y-[2px]` untuk tombol standar.
    *   Gunakan `hover:-translate-y-1` untuk elemen kartu yang lebih besar.
*   **Hover Glow:** Saat tombol utama (*primary*) di-*hover*, munculkan *glow effect* dengan memanipulasi warna bayangan (`hover:shadow-glow-emerald`).

---

## 5. Token Desain & Utility CSS (`app.css`)

Standar di atas telah diprogram secara global ke dalam `resources/css/app.css` dan `tailwind.config.js`. Anda tidak perlu menghafal utility panjang, cukup gunakan *class* berikut di file Blade Anda:

### A. Border Radius & Shadows
*   **Border Radius:** Sistem mengunci sudut di `--radius-card` (28px) untuk `.ui-card`, dan `--radius-button` (16px) untuk `.ui-btn`.
*   **Shadow Premium:** `.shadow-premium` secara otomatis memberikan bayangan besar namun sangat pudar elegan. Class `hover:shadow-premium-hover` membuatnya membesar saat interaksi.

### B. Komponen Kartu (Cards)
Selalu gunakan class ini untuk kontainer putih:
*   `.ui-card`: Kartu putih standar (sudah otomatis punya transisi dan *shadow premium*).
*   **Dark Card (Manual):** Kombinasi class: `bg-slate-900 border border-slate-800 text-white shadow-premium`. Elemen kotak kecil di dalamnya wajib menggunakan `bg-slate-800/50`.

### C. Komponen Tombol (Buttons)
*   `.ui-btn-primary`: Tombol hijau standar dengan efek terangkat dan *glow emerald*.
*   `.ui-btn-secondary`: Tombol putih dengan batas garis elegan.
*   `.ui-action-tile`: Komponen khusus untuk kartu navigasi responsif yang memiliki efek melayang.

---

## 6. Panduan Integrasi Halaman Publik

Target utama desain ini adalah menyatukan bahasa visual dari **Landing Page (Publik)** hingga **Dashboard (Admin)**. Untuk halaman publik, ikuti aturan ketat ini:

1.  **Gunakan Background Shell yang Sama:** Background halaman pendaftaran atau Landing Page harus menggunakan gradien lembut yang sama dengan dashboard (`.ui-shell` atau `bg-slate-50`).
2.  **Gunakan Kotak Bento untuk Menampilkan Fitur:** Saat menampilkan informasi langkah pembayaran zakat atau fitur utama, bungkus dengan `.ui-card` di dalam `grid grid-cols-1 md:grid-cols-3 gap-6`. Jangan membuat blok HTML telanjang tanpa kartu.
3.  **Hero Section High-Contrast:** Bagian atas (*Hero Image / Header*) di halaman publik sangat disarankan menggunakan gaya **Dark Card** raksasa (`bg-slate-950`) untuk memberikan kesan megah, modern, dan premium sejak detik pertama pengunjung tiba di situs Anda.

*Dengan mematuhi seluruh pedoman ini, Anda akan menjamin transisi visual antara pengguna yang sedang melihat landing page hingga akhirnya masuk ke dalam dashboard terasa mulus (seamless) dan 100% konsisten.*

---

## 7. UI Judgement & Quality Report (2026)

Bagian ini merangkum skor UI dan Technical Debt untuk perbaikan selanjutnya.

**Skor Kualitas (Overall: 8.5/10)**
- **Visual hierarchy (8.3/10):** Struktur ada, tetapi beberapa layar masih terlalu padat dan belum cukup tegas membedakan prioritas utama.
- **Information density (8.2/10):** Lengkap, tetapi di beberapa area mobile dan tabel operasional terasa berat saat dipindai cepat.
- **Consistency (8.5/10):** Komponen internal sudah cukup konsisten.
- **Usability (8.8/10):** Alur utama cukup jelas dan actionable.
- **Code Quality (7.8/10):** Ada beberapa class Tailwind inline yang terlalu panjang di Blade.

**Area Perbaikan Utama (Untuk mencapai 9+/10):**
1. **Meredam Keramaian Visual:** Dashboard dan card besar terlalu banyak yang ingin menonjol. Perlu penyederhanaan hierarki.
2. **Density Mobile:** Kurangi teks pada card mobile di "Riwayat Transaksi" agar lebih ringan dipindai.
3. **Skeleton Loading:** Belum ada komponen `.skeleton` khusus untuk *loading state*.
4. **Refactor Inline Tailwind:** Pindahkan class panjang ke `app.css` dengan `@apply` atau buat komponen Blade khusus agar kode lebih bersih.
5. **Konsistensi Istilah:** Seragamkan istilah seperti "Warning", "Perlu Dicek", "Belum Review".
