---
name: zakky-scope-expansion
description: Expand Zakky chatbot scope untuk detailed zakat mal guidance dengan disclaimers
type: design-doc
date: 2026-06-25
---

# Zakky Chatbot Scope Expansion — Hybrid Zakat Education

## Overview

Expand Zakky dari hanya **public data Q&A** menjadi **guided zakat education assistant** yang membantu jamaah memahami zakat fitrah, fidyah, dan zakat mal dengan structured guidance dan strong disclaimers.

**Core Principle:** AI educate based on valid sources (ulama/MUI/BAZNAS), guide through case scenarios, always remind panitia confirmation needed.

---

## Current State

### What Zakky Can Answer Now
- ✅ Public data queries (total uang/beras/jiwa)
- ✅ Basic zakat info (fitrah, fidyah, infaq definition)
- ✅ Guidance UI tips (cara baca grafik/ringkasan)
- ✅ Payment info (generic)

### What's Missing
- ❌ Detailed zakat mal education
- ❌ Nishab explanation
- ❌ Case scenario calculations
- ❌ Structured guidance flow
- ❌ Strong disclaimers on accuracy limits

---

## Design: Hybrid Zakat Education

### Scope Tiers

| Tier | Coverage | Approach | Risk |
|------|----------|----------|------|
| **Tier 1: Fitrah & Fidyah** | Detail, per-person, from config | Calculator + examples | Low |
| **Tier 2: Zakat Mal** | General education, references | Guided questions → estimate → disclaimer | Medium |
| **Tier 3: Edge Cases** | Complex assets, multiple sources | "Refer to panitia" only | Low (explicit boundary) |

---

## 1. Tier 1: Fitrah & Fidyah (Detailed)

### Knowledge Base Expansion

**Fitrah Details:**
```
Title: Zakat fitrah detail
Keywords: [zakat fitrah, fitrah, hitung fitrah, berapa fitrah, per jiwa]
Answer: 
  "Zakat fitrah adalah zakat wajib menjelang Hari Raya Idul Fitri. 
  Takaran di Masjid An-Nur (tahun 2026):
  - Uang: Rp 50.000 per jiwa
  - Beras: 2.5 kg per jiwa (atau setara)
  
  Contoh: Keluarga 4 orang = Rp 200.000 atau 10 kg beras.
  
  ⚠️ Zakky baca dari aplikasi, tapi konfirmasi ke panitia jika ada perubahan periode."
Sources: [config/zakat.php - fitrah defaults]
```

**Fidyah Details:**
```
Title: Zakat fidyah detail
Keywords: [fidyah, bayar fidyah, puasa, tidak bisa puasa]
Answer:
  "Fidyah adalah pembayaran untuk kondisi tidak menjalankan puasa. Takaran di An-Nur:
  - Uang: Rp 30.000 per hari
  - Beras: 0.75 kg per hari
  
  Contoh: Tidak puasa 5 hari = Rp 150.000 atau 3.75 kg beras.
  
  ⚠️ Tergantung periode aktif. Konfirmasi ke panitia untuk periode berbeda."
Sources: [config/zakat.php - fidyah defaults]
```

### Calculator Intent

**Intent Detection:**
```
If user says: "Saya keluarga 5 orang, fitrah brp?"
  → Intent: calculate_fitrah_case
  
If user says: "Saya tidak bisa puasa 7 hari, brp fidyah?"
  → Intent: calculate_fidyah_case
```

**Calculation Response:**
```
User: "Keluarga 4 orang, semua bayar fitrah, brp nominal?"

Zakky:
  "Baik, saya bantu hitung fitrah keluarga Anda.
  
  📊 Perhitungan:
  Jumlah jiwa: 4 orang
  Takaran An-Nur (2026): Rp 50.000/jiwa
  Total: 4 × Rp 50.000 = Rp 200.000
  
  Alternatif beras: 4 × 2.5 kg = 10 kg
  
  ⚠️ Disclaimer: 
  - Data dari aplikasi An-Nur periode aktif (Ramadan 2026)
  - Zakky bisa salah jika ada update panitia
  - KONFIRMASI KE PANITIA SEBELUM BAYAR
  
  Tanya: Apa ada yang ingin ditanyakan?"
```

---

## 2. Tier 2: Zakat Mal (General Education + Guided)

### Valid Sources Definition

**Allowed Sources:**
- ✅ Qur'an: QS 9:60 (asnaf zakat), QS 2:267 (zakat aset terbaik)
- ✅ Hadis: Terkait nishab emas, perak, zakat harta dagang
- ✅ Referensi: Fatwa MUI, BAZNAS guidelines, Ulama Syafi'i/Hanafi
- ✅ Local: Panduan panitia An-Nur jika ada

**Invalid Sources:**
- ❌ Opinion tanpa basis syar'i
- ❌ Fatwa dari non-lembaga agama resmi
- ❌ Komputasi pribadi tanpa dalil

### Knowledge Base: Zakat Mal General

**Entry 1: What is Zakat Mal**
```
Title: Apa itu zakat mal
Keywords: [zakat mal, zakat harta, aset, penghasilan, investasi, nishab]
Answer:
  "Zakat mal adalah zakat atas harta/aset yang mencapai nishab (nilai minimum).
  
  Secara umum dari fatwa ulama:
  - Nishab standar: 85 gram emas murni (~Rp 50-80 juta tergantung harga emas)
  - Tarif zakat: 2.5% dari total harta yang wajib zakat
  - Syarat: Sudah disimpan 1 tahun hijriyah (haul)
  
  Harta yang dihitung: Uang, emas, perak, barang dagangan, investasi, dll
  Dikurangi: Hutang yang mengikat
  
  📌 PENTING:
  - Perhitungan nishab kompleks tergantung jenis aset
  - Fatwa berbeda antar mazhab
  - ZAKKY TIDAK BISA MENETAPKAN KEWAJIBAN PRIBADI
  - Untuk kasus pribadi, diskusi dengan panitia/ustadz"
Sources: [Qur'an 9:60, Hadis nishab, Fatwa MUI/BAZNAS]
```

**Entry 2: Calculation Example**
```
Title: Contoh hitung zakat mal
Keywords: [hitung zakat mal, contoh zakat mal, skenario zakat, case zakat]
Answer:
  "Contoh BUKAN keputusan final Anda:
  
  📌 SKENARIO 1: PNS dengan gaji + tabungan
  Gaji bulanan: Rp 10 juta × 12 bulan = Rp 120 juta/tahun
  Tabungan: Rp 50 juta
  Total aset: Rp 170 juta
  Dikurangi kebutuhan hidup (paket BAZNAS): ~Rp 30 juta
  Aset neto: Rp 140 juta (di atas nishab ~Rp 50-80 juta)
  Zakat 2.5%: Rp 140 juta × 2.5% = Rp 3.5 juta
  
  📌 SKENARIO 2: Wiraswasta
  Pendapatan kotor: Rp 300 juta/tahun
  Biaya operasional: Rp 150 juta
  Laba bersih: Rp 150 juta
  [Perhitungan lanjut per waktu haul...]
  
  ⚠️ DISCLAIMER PENTING:
  - Contoh ini BUKAN fatwa pribadi Anda
  - Actual nishab berbeda tergantung: harga emas, jenis harta, hutang, kebutuhan
  - Mazhab berbeda hasil berbeda
  - ZAKKY BISA SALAH
  - KONFIRMASI PANITIA ZAKAT AN-NUR UNTUK KEPUTUSAN FINAL"
Sources: [Fatwa MUI, BAZNAS guidelines, Mazhab Syafi'i]
```

### Zakat Mal Calculation Flow (Updated)

**User triggers case scenario:**
```
User: "Saya PNS gaji Rp 15 juta sebulan, tabungan Rp 80 juta, emas 200 gram. 
        Berapa saya harus zakat?"

Zakky detects: Intent = 'guide_zakat_mal_calculation'
```

**Step 1: Pendahuluan + Definisi + Manfaat**
```
Zakky:
  "Baik, saya bantu Anda memahami zakat harta (zakat mal).
  
  📌 DEFINISI:
  Zakat mal adalah zakat atas harta/aset yang telah mencapai nishab 
  (nilai minimum wajib zakat). Berdasarkan Qur'an 9:60.
  
  💡 MANFAAT:
  - Membersihkan harta (suci dari hak mustahik)
  - Membantu fakir miskin dan kategori asnaf lainnya
  - Ibadah untuk mendekatkan diri kepada Allah
  - Membangun kesadaran sosial
  
  📊 STANDAR UMUM SAAT INI (Global):
  - Nishab emas: 85 gram emas murni (~Rp 50-80 juta tergantung harga emas)
  - Nishab perak: 595 gram (~Rp 10-15 juta)
  - Tarif zakat mal: 2.5% dari total aset yang memenuhi syarat
  - Syarat waktu: Harta sudah dimiliki 1 tahun (haul)
  
  Untuk hitung akurat, saya perlu tahu profil aset Anda.
  Saya akan ajukan beberapa pertanyaan. Siap?"
```

**Step 2: Structured Questions**
```
Zakky:
  "Baik, untuk hitung yang akurat:
  
  1️⃣ Pendapatan Anda per bulan? (gaji atau usaha)
  2️⃣ Pengeluaran rutin per bulan untuk kebutuhan sehari-hari?
  3️⃣ Tabungan/uang cash yang Anda miliki sekarang?
  4️⃣ Apakah Anda punya emas/perak? Berapa gramnya?
  5️⃣ Apakah ada hutang yang mengikat? Berapa?
  6️⃣ Aset lain? (properti, mobil, barang dagangan, saham, dll)
  
  Jawab saja yang relevan dengan situasi Anda, Pak/Bu."
```

**Step 3: Gather Information + Acknowledge**
```
User: "Gaji 15jt/bulan, pengeluaran 8jt/bulan, tabungan 80jt, emas 200gram, 
       hutang 0, properti tidak dihitung."

Zakky: "✅ Data Anda tercatat. Sekarang saya hitung..."
```

**Step 4: Present Calculation Step-by-Step**
```
Zakky:
  "📊 PERHITUNGAN ZAKAT MAL BERDASARKAN DATA ANDA:
  
  A. ASET YANG DIHITUNG:
     • Gaji setahun: Rp 15 juta × 12 bulan = Rp 180 juta
     • Tabungan cash: Rp 80 juta
     • Emas 200 gram (harga emas hari ini ~Rp 900rb/gram): Rp 180 juta
     ───────────────────────────────────────────────
     Total Aset Bruto: Rp 440 juta
  
  B. DIKURANGI KEBUTUHAN HIDUP (1 TAHUN):
     • Pengeluaran rutin: Rp 8 juta × 12 bulan = Rp 96 juta
     • Hutang: Rp 0
     ───────────────────────────────────────────────
     Aset Neto: Rp 440 juta - Rp 96 juta = Rp 344 juta
  
  C. CEK NISHAB:
     Nishab emas: 85 gram = ~Rp 50-80 juta (harga fluktuatif)
     Aset Anda: Rp 344 juta
     ✓ MELEBIHI NISHAB → WAJIB ZAKAT
  
  D. HITUNG ZAKAT (2.5%):
     Rp 344 juta × 2.5% = Rp 8.6 juta per tahun
     (atau ~Rp 717rb per bulan jika ingin dicicil)"
```

**Step 5: Informasi Tarif An-Nur + Konfirmasi ke Panitia**
```
Zakky:
  "📌 PERHITUNGAN ESTIMASI ANDA: Rp 8.6 juta/tahun
  
  ⚠️ PENTING:
  Perhitungan di atas menggunakan standar umum ulama (BAZNAS, Syafi'i).
  
  NAMUN, Panitia Zakat Masjid An-Nur mungkin punya aturan atau metode khusus 
  yang bisa berbeda dari standar umum. Tarif, cara hitung, atau kategori yang 
  dikecualikan bisa disesuaikan dengan ketentuan masjid.
  
  💰 STANDAR SAAT INI DI AN-NUR:
  (Admin: isi dari seeder/config + panitia guidance)
  - Fitrah: Rp 50.000/jiwa atau 2.5 kg beras
  - Fidyah: Rp 30.000/hari atau 0.75 kg beras
  - Zakat Mal: [TANYA KE PANITIA - belum ada aturan di aplikasi]
  - Infaq/Shodaqoh: Fleksibel
  
  ✅ LANGKAH SELANJUTNYA:
  Silakan konfirmasi ke Panitia Zakat Masjid An-Nur dengan membawa:
  - Data gaji/penghasilan Anda
  - Jumlah tabungan
  - Detail aset (emas, properti, dll)
  - Hutang jika ada
  
  Panitia akan:
  1. Validasi perhitungan Anda
  2. Sesuaikan dengan aturan An-Nur (jika ada)
  3. Terima zakat dari Anda
  4. Serahkan ke penerima amanah (fakir, miskin, dll)
  
  Hubungi: [Nama Panitia / Nomor Kontak]
  
  Ada pertanyaan lain?"
```

---

## 3. Tier 3: Edge Cases (Explicit Boundaries)

### When Zakky Refers to Panitia Only

**Cases:**
- Aset kompleks (properti, kendaraan, investasi saham, crypto)
- Hutang besar atau kontroversial
- Business partnership atau kepemilikan bersama
- Pertanyaan fatwa mendalam (e.g., "Apa hukum zakat cryptocurrency?")
- Situasi khusus (anak yatim, orang miskin, dll)

**Response Template:**
```
Zakky:
  "Pertanyaan Anda tentang [topic] itu kompleks dan berbeda tiap mazhab.
  
  Zakky tidak bisa memutuskan, karena:
  - Butuh verifikasi aset langsung
  - Butuh konsultasi mendalam dengan ustadz/panitia
  - Regulasi An-Nur mungkin khusus untuk kasus Anda
  
  Silakan hubungi Panitia Zakat An-Nur langsung:
  📞 [contact panitia]
  
  Mereka siap konsultasi gratis untuk kasus pribadi Anda.
  
  Ada pertanyaan lain yang Zakky bisa bantu?"
```

---

## 4. Implementation Architecture

### New Components

**A. ChatbotZakatMalGuide Service**
```php
class ChatbotZakatMalGuide {
    public function detectZakatMalIntent(string $message): ?string
    
    public function askStructuredQuestions(): array
        // Returns structured prompts for: income, expenses, savings, debts, assets
    
    public function calculateEstimate(array $userData): array
        // Input: income, expenses, assets, debts
        // Output: nishab check, zakat amount, references, disclaimers
    
    public function buildDisclaimerResponse(array $calculation): string
        // Strong disclaimers + panitia CTA
}
```

**B. Expanded zakky_knowledge.php**
```php
[
    'zakat-mal-what-is',
    'zakat-mal-nishab-explanation',
    'zakat-mal-calculation-example',
    'zakat-mal-complex-assets',
    'zakat-fitrah-detail',
    'zakat-fidyah-detail',
    // ... existing entries
]
```

**C. Enhanced ChatbotActionDetector**
```php
public function intent(string $message): ?string {
    // Existing: data queries, payment info
    
    // New:
    if (containsAny($message, ['hitung zakat', 'zakat berapa', 'skenario zakat'])) {
        return 'guide_zakat_mal_calculation';
    }
    if (containsAny($message, ['zakat mal', 'nishab', 'aset', 'harta'])) {
        return 'ask_zakat_mal_general';
    }
}
```

### Data Flow

```
User Question
    ↓
ChatbotActionDetector
    ├→ public_data? → ChatbotPublicDataResponder
    ├→ zakat_fitrah? → Fitrah Calculator (tier 1)
    ├→ zakat_fidyah? → Fidyah Calculator (tier 1)
    ├→ zakat_mal_calc? → ChatbotZakatMalGuide (tier 2) → Structured questions
    ├→ zakat_mal_general? → Knowledge retrieval (tier 2)
    └→ complex_case? → "Refer to panitia" (tier 3)
    ↓
ChatbotOrchestrator
    (route to appropriate provider/handler)
    ↓
Response with Disclaimers
```

---

## 5. Key Features

### A. Multi-Step Guidance (Zakat Mal Cases)

**Flow:** Detect → Ask → Calculate → Explain → Disclaimer → CTA

**Advantage:** User understand step-by-step, not just "answer"

### B. Confirmation Flow (Not Disclaimers)

**Every zakat mal response includes:**
- ✅ Clear explanation of calculation
- ✅ Reference to source (BAZNAS/Ulama)
- ✅ Acknowledgment that An-Nur may have different rules
- ✅ "Silakan konfirmasi ke Panitia An-Nur untuk kepastian"
- 📞 "Hubungi Panitia untuk validasi akhir"

**Tone:** Positive & collaborative, not "AI might be wrong" but "Panitia will verify for certainty"

### C. Tier Boundaries

**Tier 1 (Fitrah/Fidyah):** AI calculable → show formula → light disclaimer
**Tier 2 (Zakat Mal):** AI guidance → structured Q → estimate + heavy disclaimer
**Tier 3 (Complex):** Explicit boundary → refer only

### D. Sources Tracking

Every zakat mal answer includes:
```
Sources: [Qur'an 9:60, Hadis nishab, Fatwa MUI, BAZNAS guidelines]
```

---

## 6. Implementation Phases (One by One)

### FASE 1: Expand Fitrah/Fidyah Knowledge + Simple Calculator
- [ ] Add detailed entries ke zakky_knowledge.php (fitrah, fidyah)
- [ ] Update ChatbotActionDetector untuk detect fitrah/fidyah cases
- [ ] Create simple calculator service
- [ ] Test: "Berapa fitrah 3 orang?" → Rp 150.000 + example
- [ ] Test: "Fidyah 7 hari berapa?" → Rp 210.000 + example
- [ ] Testing checklist passed before moving to Fase 2

### FASE 2: Add Zakat Mal Education (General)
- [ ] Add zakat mal entries ke zakky_knowledge.php (definisi, manfaat, standar global)
- [ ] Update ChatbotActionDetector untuk detect zakat mal queries
- [ ] Test: "Apa itu zakat mal?" → Definition + benefits + standar global rate
- [ ] Test: "Nishab berapa?" → Explanation dengan sumber
- [ ] Testing checklist passed before moving to Fase 3

### FASE 3: Add Zakat Mal Guided Calculation (Structured Q)
- [ ] Create ChatbotZakatMalGuide service (5-step flow)
- [ ] Update ChatbotOrchestrator untuk route ke guide
- [ ] Implement multi-turn conversation state (if needed)
- [ ] Test: "Saya PNS gaji 15jt, tabungan 80jt, emas 200gram, zakat berapa?"
  - Step 1: Definition + benefits + standar global ✓
  - Step 2: Structured questions (5 questions) ✓
  - Step 3: Data gathering ✓
  - Step 4: Calculation step-by-step ✓
  - Step 5: Info An-Nur rates + panitia CTA ✓
- [ ] Testing checklist passed

### FASE 4: Add Tier 3 Edge Cases Boundary
- [ ] Add explicit "refer to panitia" responses
- [ ] Test edge cases (property, crypto, partnership, etc.)
- [ ] All tiers integrated and working

---

## 7. Success Criteria

✅ **Chatbot scope expanded** tanpa menghilangkan safety boundaries
✅ **Jamaah educated** tentang zakat fitrah, fidyah, dan zakat mal
✅ **AI transparansi** — disclaimer prominent, sources clear
✅ **Panitia trust maintained** — referral flow jelas, bukan override
✅ **Usage monitoring** — track mana tier yang paling sering ditanya

---

## 8. Future Iterations

- [ ] Admin panel: Monitor zakat mal questions (audit + improve knowledge base)
- [ ] Panitia integration: Auto-forward complex cases to panitia email
- [ ] Multi-language: English zakat education (if needed)
- [ ] FAQ update: Based on most-asked questions

---

## Files to Create/Modify (Per Fase)

### FASE 1: Fitrah/Fidyah
| File | Action | Complexity | Phase |
|------|--------|------------|-------|
| `config/zakky_knowledge.php` | Expand (fitrah/fidyah detail) | Low | 1 |
| `app/Services/Chatbot/ChatbotActionDetector.php` | Modify (detect fitrah/fidyah cases) | Low | 1 |
| Tests | Create (fitrah/fidyah calculator) | Low | 1 |

### FASE 2: Zakat Mal General
| File | Action | Complexity | Phase |
|------|--------|------------|-------|
| `config/zakky_knowledge.php` | Expand (zakat mal entries) | Low | 2 |
| `app/Services/Chatbot/ChatbotActionDetector.php` | Modify (detect zakat mal queries) | Low | 2 |

### FASE 3: Zakat Mal Guided Calculation
| File | Action | Complexity | Phase |
|------|--------|------------|-------|
| `app/Services/Chatbot/ChatbotZakatMalGuide.php` | Create | Medium | 3 |
| `app/Services/Chatbot/ChatbotOrchestrator.php` | Modify (route to guide) | Low | 3 |
| `resources/js/chatbot-widget.js` | Modify (multi-turn if needed) | Medium | 3 |
| Tests | Create (full flow test) | Medium | 3 |

### FASE 4: Edge Cases
| File | Action | Complexity | Phase |
|------|--------|------------|-------|
| `app/Services/Chatbot/ChatbotZakatMalGuide.php` | Modify (add tier 3 detection) | Low | 4 |
| Tests | Create (edge case handling) | Low | 4 |

---

**Status:** ✅ Design Ready for Approval

**Next Step:** Approve design → Start FASE 1 (Fitrah/Fidyah expansion)

