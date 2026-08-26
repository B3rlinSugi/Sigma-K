# 06. METADATA ARCHITECTURE & DATA GOVERNANCE STANDARDS: SIGMA-K

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Data Governance Architect & Data Architect  
> **Target Pengguna Dokumen:** Ikhsan (Data Analyst) & Data Engineering Team  

Dokumen ini mendefinisikan Standar Metadata (*Metadata Standard Architecture*) resmi untuk seluruh aset data SIGMA-K, membagi metadata ke dalam 4 pilar klasifikasi.

---

## 1. Empat Pilar Metadata SIGMA-K

```
+-----------------------------------------------------------------------------------+
|                            SIGMA-K METADATA FRAMEWORK                             |
+-----------------------------------------------------------------------------------+
| 1. BUSINESS METADATA: Definisi istilah, pemilik data (Owner), aturan bisnis.      |
| 2. TECHNICAL METADATA: Tipe data, panjang kolom, nullability, indeks, konstrain.  |
| 3. OPERATIONAL METADATA: Frekuensi update, status kualitas data, provenance/asal. |
| 4. GOVERNANCE METADATA: Klasifikasi keamanan, hak akses, retensi, audit trail.    |
+-----------------------------------------------------------------------------------+
```

---

## 2. Struktur Standar Registrasi Metadata (Metadata Schema Standard)

Setiap entitas dan atribut data di SIGMA-K wajib didaftarkan dengan atribut metadata berikut:

| Kategori Metadata | Atribut Metadata | Deskripsi & Definisi | Format / Nilai yang Diizinkan |
|---|---|---|---|
| **Governance** | **Data Owner** | Pejabat/unit penanggung jawab legal atas keabsahan data. | `SESDEP Kelembagaan dan Tata Laksana, KemenPANRB` / Instansi Terkait |
| **Governance** | **Data Steward** | Tim operasional yang memelihara kualitas dan kurasi data harian. | `Data Analyst (Ikhsan)` / `Lead Engineer (Berlin)` |
| **Governance** | **Sensitivity Level** | Klasifikasi kerahasiaan data sesuai standar SPBE. | `PUBLIC`, `INTERNAL`, `CONFIDENTIAL`, `RESTRICTED` |
| **Business** | **Business Definition** | Penjelasan makna istilah bisnis tanpa bahasa teknis. | Teks deskriptif formal Bahasa Indonesia baku. |
| **Business** | **Allowed Values** | Domain nilai yang sah atau referensi lookup yang berlaku. | Daftar nilai valid / ENUM set / Foreign Table reference. |
| **Business** | **Validation Rule** | Aturan logika bisnis yang wajib dipenuhi data. | Ekspresi validasi (misal: "Must be unique", "start_date <= end_date"). |
| **Technical** | **Physical Data Type** | Tipe data pada mesin PostgreSQL target. | `UUID`, `VARCHAR(n)`, `INT`, `TIMESTAMPTZ`, `JSONB`, `BOOLEAN`. |
| **Technical** | **Nullability & Default** | Apakah kolom boleh kosong dan apa nilai bawaannya. | `NOT NULL`, `NULLABLE`, `DEFAULT NOW()`, `DEFAULT FALSE`. |
| **Operational**| **Source Provenance** | Asal sumber data pertama kali diperoleh. | `eskld.tb_instansi` (Legacy) / `MANUAL_INPUT` / `SIASN_API`. |
| **Operational**| **Update Frequency** | Seberapa sering data mengalami siklus pembaruan. | `EVENT_DRIVEN` (Saat terjadi reshuffle/usulan) / `DAILY_REFRESH`. |
| **Operational**| **Quality Status** | Status kebersihan dan validitas data saat ini. | `VERIFIED_CLEAN`, `NEEDS_CLEANSING`, `QUARANTINED`, `DEPRECATED`. |

---

## 3. Sampel Registrasi Metadata Entitas Kunci

### A. Entitas: `institutions.code` (Kode Instansi)
- **Business Definition:** Kode unik registrasi nasional instansi pemerintah Indonesia.
- **Data Owner:** Kementerian PANRB & BKN / Kemendagri.
- **Data Steward:** Data Analyst (Ikhsan).
- **Physical Type:** `VARCHAR(50) NOT NULL UNIQUE`.
- **Validation Rule:** Format wajib berupa huruf besar, angka, dan strip (Regex: `^[A-Z0-9\-]+$`), tidak boleh mengandung spasi.
- **Sensitivity:** `PUBLIC` (Dapat diakses terbuka).
- **Provenance:** `eskld.tb_instansi.kode_instansi` (Cleaned).
- **Quality Status:** `VERIFIED_CLEAN`.

### B. Entitas: `cabinets.is_active` (Kabinet Aktif)
- **Business Definition:** Penanda apakah kabinet tersebut merupakan kabinet pemerintahan yang sedang berjalan saat ini.
- **Data Owner:** SESDEP Kelembagaan KemenPANRB.
- **Validation Rule:** Tepat 1 (satu) record di seluruh database yang bernilai `TRUE` pada satu waktu ([BRULE-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)).
- **Sensitivity:** `PUBLIC`.
- **Quality Status:** `VERIFIED_CLEAN`.
