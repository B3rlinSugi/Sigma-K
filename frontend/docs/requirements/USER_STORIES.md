# USER STORIES: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Senior Product Analyst & Requirements Engineer  

Dokumen ini mendefinisikan kebutuhan pengguna dalam format standar *Agile User Story* untuk seluruh persona aktor utama sistem SIGMA-K.

---

## 1. Persona Aktor
1. **Operator Instansi (USER):** Staf perwakilan K/L atau Pemda yang bertugas memperbarui profil, menyusun struktur organisasi, dan menginput tugas-fungsi instansinya.
2. **Petugas Verifikasi (VERIFIKATOR):** Staf/analis kelembagaan KemenPANRB yang meninjau, mengoreksi, dan memvalidasi keabsahan data usulan instansi.
3. **Administrator Sistem (ADMIN):** Pengelola teknis dan tata kelola sistem di KemenPANRB yang mengelola master kabinet, master referensi, user, dan persetujuan akhir.
4. **Pimpinan / SESDEP (PIMPINAN):** Sekretaris Deputi / Pimpinan KemenPANRB yang memantau postur kelembagaan dan menggunakan dashboard untuk pengambilan keputusan strategis.
5. **Data Analyst (DATA ANALYST - Ikhsan):** Rekan tim analitik yang mengolah data postur ASN, komparasi antar-kabinet, dan pemodelan data kelembagaan.

---

## 2. Daftar User Stories

### A. Persona: Operator Instansi (USER)

#### US-001: Melihat Profil & Struktur Instansi Sendiri
- **As an** Operator Instansi (USER),
- **I want to** melihat profil lengkap dan bagan struktur organisasi instansi saya secara visual dan terstruktur,
- **So that** saya dapat memahami kondisi data kelembagaan instansi saya yang sedang aktif di sistem.
- **Priority:** HIGH
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-006](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-INST-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-ORG-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

#### US-002: Membuat Draf Pembaruan Struktur Organisasi
- **As an** Operator Instansi (USER),
- **I want to** menyusun dan mengubah draf unit kerja pada bagan struktur organisasi instansi saya (menambah unit, mengubah atasan/parent, menentukan eselon),
- **So that** susunan organisasi di SIGMA-K selalu sesuai dengan peraturan menteri/kepala lembaga terbaru tanpa langsung mengubah master data aktif.
- **Priority:** CRITICAL
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-ORG-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-SUB-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

#### US-003: Mengelola Butir Tugas dan Fungsi (Tupoksi)
- **As an** Operator Instansi (USER),
- **I want to** menginput dan memperbarui butir-butir Tugas Pokok dan Rincian Fungsi serta mengaitkannya ke unit organisasi terkait beserta rujukan pasal hukumnya,
- **So that** tugas dan fungsi kelembagaan instansi saya terdokumentasi secara legal dan terstruktur di tingkat pusat.
- **Priority:** HIGH
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-TUP-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-TUP-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

#### US-004: Mengirimkan Pengajuan Perubahan Data (Submission)
- **As an** Operator Instansi (USER),
- **I want to** mengirimkan berkas draf perubahan data yang telah saya susun lengkap dengan dokumen dasar hukum pendukung ke tim verifikator KemenPANRB,
- **So that** usulan perubahan data instansi saya dapat diproses dan disahkan secara resmi.
- **Priority:** CRITICAL
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-SUB-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-SUB-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

#### US-005: Menerima Catatan Revisi & Melakukan Resubmission
- **As an** Operator Instansi (USER),
- **I want to** menerima pemberitahuan dan membaca catatan koreksi dari verifikator serta dapat memperbaiki butir data yang salah untuk dikirim ulang,
- **So that** pengajuan perubahan data instansi saya dapat segera memenuhi standar verifikasi.
- **Priority:** HIGH
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-REV-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-REV-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

### B. Persona: Petugas Verifikasi (VERIFIKATOR)

#### US-006: Meninjau Antrean Tiket Pengajuan (Verification Queue)
- **As a** Petugas Verifikasi (VERIFIKATOR),
- **I want to** melihat daftar seluruh tiket pengajuan data kelembagaan yang masuk dengan filter jenis instansi dan status pengajuan,
- **So that** saya dapat memprioritaskan dan memproses peninjauan data secara sistematis.
- **Priority:** CRITICAL
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-VER-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-006](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

#### US-007: Membandingkan Perubahan Data (Diff View)
- **As a** Petugas Verifikasi (VERIFIKATOR),
- **I want to** melihat tampilan perbandingan berdampingan (*side-by-side diff*) antara data eksisting dengan data usulan baru yang diajukan user,
- **So that** saya dapat mengidentifikasi setiap penambahan, penghapusan, atau pergeseran unit kerja dengan sangat cepat dan akurat.
- **Priority:** HIGH
- **Status:** **PROPOSED**
- **Related Requirement:** [REQ-019](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-VER-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

#### US-008: Memberikan Keputusan Verifikasi & Catatan Koreksi
- **As a** Petugas Verifikasi (VERIFIKATOR),
- **I want to** menyetujui tiket untuk diteruskan ke Admin atau mengembalikannya ke User dengan catatan koreksi yang terperinci,
- **So that** kualitas dan validitas legalitas data kelembagaan tetap terjamin sebelum resmi dipublikasikan.
- **Priority:** CRITICAL
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-VER-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

### C. Persona: Administrator Sistem (ADMIN)

#### US-009: Mengelola Master Kabinet & Periode Pemerintahan
- **As an** Administrator Sistem (ADMIN),
- **I want to** membuat, mengedit data kabinet (misal: Kabinet Merah Putih) dan menentukan rentang waktu periode aktifnya,
- **So that** sistem dapat mengakomodasi pergantian era pemerintahan secara fleksibel.
- **Priority:** CRITICAL
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-CAB-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-PER-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

#### US-010: Mengelola Keanggotaan K/L dalam Kabinet Tertentu
- **As an** Administrator Sistem (ADMIN),
- **I want to** memasukkan atau mengeluarkan kementerian/lembaga ke dalam daftar anggota kabinet aktif secara relasional,
- **So that** komposisi resmi kementerian pada setiap kabinet terpetakan dengan tepat tanpa menggunakan format teks delimit legacy.
- **Priority:** CRITICAL
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-MEM-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

#### US-011: Memberikan Persetujuan Akhir Data (Final Approval)
- **As an** Administrator Sistem (ADMIN),
- **I want to** menyetujui pengajuan yang telah lolos verifikasi sehingga data usulan resmi diterapkan secara atomik ke Master Data aktif,
- **So that** master data kelembagaan nasional terbarui dengan akuntabilitas penuh.
- **Priority:** CRITICAL
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-APP-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-APP-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

#### US-012: Memantau Log Audit (Audit Trail Viewer)
- **As an** Administrator Sistem (ADMIN),
- **I want to** menelusuri catatan jejak audit seluruh aktivitas pengguna dan mutasi data master,
- **So that** saya dapat melakukan audit investigasi, menjaga kepatuhan, dan mencegah penyalahgunaan data.
- **Priority:** HIGH
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-AUD-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-AUD-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

### D. Persona: Pimpinan / SESDEP (PIMPINAN)

#### US-013: Memantau Dashboard Eksekutif & Komposisi Kabinet
- **As a** Pimpinan / SESDEP (PIMPINAN),
- **I want to** melihat ringkasan metrik kelembagaan nasional, daftar 48 kementerian pada Kabinet Merah Putih, dan status keaktifan instansi secara visual dan modern,
- **So that** saya dapat memperoleh wawasan menyeluruh secara cepat untuk perumusan kebijakan tata kelola birokrasi.
- **Priority:** CRITICAL
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-DSH-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-DSH-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

#### US-014: Membandingkan Evolusi Struktur Kelembagaan Antar-Kabinet
- **As a** Pimpinan / SESDEP (PIMPINAN),
- **I want to** membandingkan komposisi kementerian dan riwayat pemecahan/penggabungan instansi antara kabinet saat ini dengan kabinet terdahulu,
- **So that** saya dapat mengevaluasi dampak restrukturisasi organisasi kementerian terhadap efektivitas pemerintahan.
- **Priority:** HIGH
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-MEM-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-MEM-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

### E. Persona: Data Analyst (DATA ANALYST - Ikhsan)

#### US-015: Menganalisis Postur ASN & Rekapitulasi Eselon
- **As a** Data Analyst (DATA ANALYST),
- **I want to** mengakses data agregat postur kelembagaan (`v_postur_asn`), sebaran eselon, dan korelasi unit kerja,
- **So that** saya dapat menyusun laporan analitik kuantitatif yang solid untuk mendukung rekomendasi penataan kelembagaan ke pimpinan.
- **Priority:** HIGH
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-ANA-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-ANA-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)

---

### F. Kebutuhan Notifikasi Lintas Aktor (All Personas)

#### US-016: Menerima Notifikasi Realtime atas Peristiwa Mutasi Data
- **As a** Pengguna Sistem SIGMA-K (All Actors),
- **I want to** menerima pop-up toast dan badge notifikasi seketika di peramban saat ada data baru diajukan, diverifikasi, atau disetujui,
- **So that** saya selalu terinformasi tanpa harus melakukan penyegaran (*refresh*) halaman manual.
- **Priority:** HIGH
- **Status:** **CONFIRMED**
- **Related Requirement:** [REQ-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-NOT-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [FR-NOT-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md)
- **Acceptance Criteria Ref:** [AC-016](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/ACCEPTANCE_CRITERIA.md)
