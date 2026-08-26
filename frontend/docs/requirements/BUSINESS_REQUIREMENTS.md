# BUSINESS REQUIREMENTS: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K (Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan)  
> **Stakeholder Utama:** Pimpinan / Sekretaris Deputi (SESDEP) Kelembagaan dan Tata Laksana, Kementerian PANRB  
> **Author:** Senior Business Analyst & Requirements Engineer  

---

## 1. Business Objective
Mewujudkan sistem pengelolaan master data kelembagaan nasional (Kementerian, Lembaga, dan Pemerintah Daerah) yang terintegrasi, akurat, realtime, dan akuntabel guna mendukung pengambilan kebijakan penataan kelembagaan, perampingan birokrasi, serta penataan kabinet pemerintahan di Indonesia secara adaptif dan berbasis data (*data-driven governance*).

---

## 2. Business Problem
Berdasarkan hasil analisis terhadap sistem legacy (E-SKLD) dan tata kelola kelembagaan saat ini, teridentifikasi sejumlah permasalahan bisnis mendasar:
1. **Ketidakmampuan Pelacakan Perubahan Kelembagaan Antar-Kabinet:** Terjadi pemisahan, penggabungan, atau pembentukan nomenklatur kementerian baru pada setiap pergantian kabinet (misal: transisi menuju Kabinet Merah Putih dengan 48+ instansi), namun sistem legacy tidak memiliki model data historis yang mampu membandingkan komposisi instansi antar-periode secara otomatis.
2. **Fragmentasi dan Duplikasi Data Instansi:** Data Kementerian/Lembaga Pusat dan Pemerintah Daerah terpisah di berbagai tabel ad-hoc dan lembar kerja manual, menyebabkan ketiadaan *Single Source of Truth* master data kelembagaan.
3. **Ketiadaan Tata Kelola Validasi & Akuntabilitas Perubahan Data:** Pengubahan data kelembagaan di masa lalu tidak melalui alur verifikasi resmi (tanpa draft, review, atau approval), dan tidak memiliki catatan jejak audit (*audit trail*) serta notifikasi aktivitas seketika.
4. **Data Tugas dan Fungsi (Tupoksi) Belum Terstruktur:** Informasi tugas dan fungsi belum terhubung langsung dengan pohon struktur organisasi dan regulasi hukum pembentukan unit kerja, menyulitkan analisis tumpang tindih fungsi (*overlapping functions*).
5. **Keterbatasan Presentasi Informasi bagi Eksekutif:** Pimpinan kementerian (SESDEP/Deputi) belum memiliki dashboard visual modern yang mampu menyajikan rekapitulasi postur kelembagaan dan status validasi data secara instan dan interaktif.

---

## 3. Business Goals
- **BG-01:** Menyediakan *Single Source of Truth* master data K/L dan Pemda di seluruh Indonesia yang terstandardisasi.
- **BG-02:** Mengakomodasi periodesasi kabinet yang fleksibel dan pelacakan evolusi kelembagaan historis dari masa ke masa.
- **BG-03:** Menerapkan tata kelola data berintegritas tinggi melalui alur pengajuan, verifikasi, persetujuan berjenjang, dan pencatatan audit log mutlak.
- **BG-04:** Menyajikan visualisasi struktur organisasi pohon hierarkis dan keterkaitan tugas fungsi secara interaktif.
- **BG-05:** Meningkatkan efisiensi komunikasi antar-aktor melalui notifikasi realtime atas setiap aktivitas mutasi data.
- **BG-06:** Menyediakan platform analitik dan dashboard eksekutif untuk mendukung pengambilan keputusan strategis oleh SESDEP/Pimpinan.

---

## 4. Stakeholder Goals
- **SESDEP / Pimpinan KemenPANRB:** Memperoleh visibilitas penuh atas postur kelembagaan nasional, rekapitulasi keanggotaan kabinet aktif, serta alat bantu perumusan kebijakan penataan organisasi kementerian/lembaga yang siap dipresentasikan.
- **Tim Analis Kelembagaan & Verifikator KemenPANRB:** Memiliki instrumen kerja yang efisien untuk memvalidasi dasar hukum, meninjau usulan perubahan struktur organisasi dan tupoksi instansi, serta memberikan umpan balik revisi secara transparan.
- **Operator Instansi (User K/L/Pemda):** Memiliki antarmuka mandiri yang mudah digunakan untuk memperbarui profil instansi, menyusun bagan organisasi, mendokumentasikan tupoksi, dan memantau status pengajuan.
- **Data Analyst (Ikhsan):** Memiliki struktur data yang bersih, ternormalisasi, dan kaya metadata untuk menghasilkan kajian kuantitatif, analisis perbandingan kabinet, dan postur ASN.
- **Tim Pengembang & IT KemenPANRB:** Memiliki repositori dengan arsitektur modern, bersih, modular, terdokumentasi rapi, dan mudah dikembangkan secara berkelanjutan.

---

## 5. Current State vs Target State

| Dimensi | Current State (Legacy E-SKLD) | Target State (Modern SIGMA-K) |
|---|---|---|
| **Manajemen Kabinet** | Relasi kabinet denormalized (string delimit `list_id_kl` di `data_kl`). | Relasi relasional formal (`Cabinet`, `CabinetPeriod`, `CabinetMembership`) dengan histori lengkap. |
| **Histori Perubahan** | Sulit melacak perubahan instansi antar periode. | Modul Historical Institutional Tracking (pemekaran, merger, nomenklatur baru). |
| **Master Data Instansi** | Terfragmentasi dengan banyak tabel mapping ad-hoc (`data_map_*`). | Master data terpadu dengan klasifikasi tipe instansi dan kodefikasi nasional baku. |
| **Tugas & Fungsi** | Belum terstruktur dan belum terhubung ke struktur unit kerja. | Modul terdedikasi terhubung dengan regulasi pasal/ayat dan unit kerja. |
| **Struktur Organisasi** | Relasi `parent_id` tanpa proteksi integritas dan visualisasi statis. | Visual Org Chart interaktif dengan validasi *anti-circular dependency*. |
| **Tata Kelola Mutasi Data** | Direct edit tanpa alur verifikasi dan tanpa persetujuan berjenjang. | Siklus terkontrol: Draft $\rightarrow$ Submission $\rightarrow$ Verification $\rightarrow$ Approval/Publishing. |
| **Notifikasi** | Tidak ada notifikasi. | Notifikasi realtime (In-App Toast & Notification Center) saat mutasi data terjadi. |
| **Akuntabilitas & Audit** | Tidak ada audit log. | Immutable Audit Log merekam *who, what, when, old values, new values*. |
| **Dashboard & Analitik** | View agregat sederhana tanpa analitik komparatif. | Modern Executive Dashboard & Analytics Workspace untuk SESDEP & Data Analyst. |

---

## 6. Business Capabilities

```
+-----------------------------------------------------------------------------------+
|                            SIGMA-K BUSINESS CAPABILITIES                          |
+-----------------------------------------------------------------------------------+
| 1. Institutional Master Governance (Pusat & Daerah)                               |
| 2. Dynamic Cabinet & Multi-Period Composition Management                          |
| 3. Historical Institutional Transition & Lineage Tracking                         |
| 4. Interactive Organization Structure & Position Mapping                          |
| 5. Structured Institutional Mandate & Duty-Function Management (Tupoksi)          |
| 6. Governed Data Mutation Workflow (Draft, Verification, Revision, Approval)      |
| 7. Instant Event Notification & Realtime Activity Broadcasting                   |
| 8. Enterprise Audit Logging & Data Traceability                                   |
| 9. Executive Dashboard Visualization & Data Analytics Intelligence                |
+-----------------------------------------------------------------------------------+
```

---

## 7. Business Requirements Register

| ID | Kategori | Deskripsi Business Requirement | Prioritas | Status | Sumber |
|---|---|---|---|---|---|
| **BR-001** | Master Governance | Sistem harus menjadi repositori master data kelembagaan resmi (Kementerian, LPNK, LNS, dan Pemda) yang terstandardisasi nasional. | CRITICAL | **CONFIRMED** | [REQ-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-016](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-002** | Cabinet Management | Sistem harus mampu mengelola multi-kabinet dan multi-periode pemerintahan secara dinamis dan independen. | CRITICAL | **CONFIRMED** | [REQ-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-003** | Cabinet Composition | Sistem harus mampu memetakan komposisi keanggotaan kementerian/lembaga pada kabinet tertentu (misal: Kabinet Merah Putih vs periode sebelumnya). | CRITICAL | **CONFIRMED** | [REQ-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-004** | Historical Lineage | Sistem harus mampu merekam dan menampilkan jejak evolusi kelembagaan (pembentukan baru, pemisahan kementerian, penggabungan, atau pembubaran). | HIGH | **CONFIRMED** | [REQ-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-005** | Tupoksi Governance | Sistem harus menyediakan pengelolaan butir-butir Tugas dan Fungsi yang terikat dengan dasar hukum regulasi dan struktur organisasi. | HIGH | **CONFIRMED** | [REQ-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-006** | Org Chart Modeling | Sistem harus mampu memodelkan dan memvisualisasikan hierarki bagan struktur organisasi instansi secara interaktif. | CRITICAL | **CONFIRMED** | [REQ-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-007** | Governed Workflow | Sistem harus menerapkan alur kerja pengajuan perubahan data berjenjang (User $\rightarrow$ Verifikator $\rightarrow$ Admin) untuk menjaga integritas data. | CRITICAL | **CONFIRMED** | [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-008** | Realtime Awareness | Sistem harus memberikan pemberitahuan realtime seketika kepada pemangku kepentingan atas setiap mutasi data penting. | HIGH | **CONFIRMED** | [REQ-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-009** | Full Auditability | Seluruh aksi mutasi data wajib memiliki jejak audit permanen yang tidak dapat dimanipulasi (*immutable audit trail*). | HIGH | **CONFIRMED** | [REQ-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-010** | Executive Intelligence | Sistem harus menyediakan dashboard eksekutif dan modul analitik untuk mendukung pimpinan (SESDEP) dalam perumusan kebijakan kelembagaan. | CRITICAL | **CONFIRMED** | [REQ-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-011** | Executive Prototype | Sistem harus memiliki prototype interaktif berkualitas tinggi yang siap dipresentasikan di hadapan SESDEP / pimpinan kementerian. | CRITICAL | **CONFIRMED** | [REQ-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-012** | National Scalability | Arsitektur bisnis sistem harus siap diperluas untuk mencakup seluruh 38 Provinsi dan 514 Kabupaten/Kota di Indonesia. | HIGH | **CONFIRMED** | [REQ-017](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-013** | National Identity SSO | Sistem mendukung integrasi otentikasi terpusat dengan Single Sign-On (SSO) KemenPANRB / ASN Digital Nasional. | MEDIUM | **PROPOSED [TBD]** | [REQ-026](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-014** | Multi-Tier Verification | Sistem mendukung alur verifikasi multi-tingkat sesuai struktur eselon di Kedeputian Kelembagaan KemenPANRB. | MEDIUM | **PROPOSED [TBD]** | [REQ-028](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |
| **BR-015** | External Notification | Sistem mendukung notifikasi melalui kanal eksternal (Email / WhatsApp Gateway resmi kementerian). | LOW | **PROPOSED [TBD]** | [REQ-027](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) |

---

## 8. Business Outcomes & Success Indicators

### Business Outcomes
1. Terwujudnya katalog master data kelembagaan nasional yang mutakhir dan akurat 100%.
2. Hilangnya ketergantungan pada tabel manual / ad-hoc dalam merekapitulasi komposisi kabinet.
3. Penurunan waktu verifikasi usulan perubahan struktur kelembagaan dari hitungan minggu menjadi hitungan hari melalui alur digital.
4. Tersedianya rekam jejak historis formal pembentukan dan transformasi kementerian dari setiap era kepresidenan.

### Success Indicators (KPIs)
- **KPI-1:** 100% data instansi kementerian pada Kabinet Merah Putih terpetakan dengan akurat beserta rincian tupoksi dan unit kerja.
- **KPI-2:** Waktu respons penyajian data postur kelembagaan pada dashboard eksekutif $< 2$ detik.
- **KPI-3:** 0% kehilangan data (*zero data loss*) pada proses preservasi data valid dari database legacy `eskld`.
- **KPI-4:** 100% mutasi data master tercatat dalam log audit dengan informasi perubahan sebelum dan sesudah.
- **KPI-5:** Kesiapan Prototype fungsional dan presentasi di hadapan SESDEP tercapai tepat waktu sesuai jadwal magang.

---

## 9. Business Constraints
1. **Regulasi & Kepatuhan Pemerintah:** Sistem harus tunduk pada peraturan perundang-undangan terkait Tata Kelola SPBE (Perpres No. 95 Tahun 2018) dan Satu Data Indonesia (Perpres No. 39 Tahun 2019).
2. **Keterbatasan Masa Magang:** Pengembangan modul inti dan prototype interaktif harus dapat diselesaikan dan tervalidasi dalam rentang waktu pelaksanaan magang mahasiswa.
3. **Pemisahan Peran Tim:** Pembagian tugas antara Full-Stack Engineer (Engineering & Architecture) dan Data Analyst (Data Modeling, Cleansing, & Insights) harus terjaga agar pengiriman milestone tepat sasaran.
4. **Isolasi Folder Legacy:** Tidak boleh memodifikasi atau mengakses folder `KemenPANRB_LEGACY` secara langsung di luar workspace resmi.
