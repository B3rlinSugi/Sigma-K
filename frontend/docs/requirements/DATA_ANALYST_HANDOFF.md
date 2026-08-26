# DATA ANALYST HANDOFF & WORK BACKLOG: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Target Persona:** Data Analyst (Ikhsan) & Data Engineering Team  
> **Author:** Senior Data Architect & Lead Full-Stack Engineer  
> **Tujuan Dokumen:** Dokumen ini berfungsi sebagai **katalog backlog kerja analitik dan data pipeline** yang spesifik dan terstruktur untuk dikerjakan oleh Data Analyst selama masa pengembangan SIGMA-K.

---

## 1. Peran & Lingkup Kerja Data Analyst
Dalam arsitektur pengembangan SIGMA-K, peran Data Analyst berfokus pada:
1. **Data Profiling & Quality Assessment:** Mengaudit integritas, duplikasi, dan kelengkapan data pada database legacy `eskld`.
2. **Data Dictionary & Cleansing Rules:** Menyusun kamus data resmi kelembagaan, merumuskan aturan standardisasi kode instansi, dan membersihkan tabel ad-hoc (`data_map_*`).
3. **Historical Cabinet Lineage Modeling:** Memetakan silsilah perubahan kementerian antar-kabinet (misal: Kabinet Indonesia Maju $\rightarrow$ Kabinet Merah Putih).
4. **Metrics & KPI Formulation:** Merumuskan kalkulasi metrik kelembagaan, formulasi view agregasi postur ASN (`v_postur_asn`), serta perancangan dimensi analitik untuk Executive Dashboard.

---

## 2. Backlog Kebutuhan Kerja Data Analyst (Data Requirements Register)

| DATA-REQ-ID | Kategori | Deskripsi Kebutuhan Analitik | Data yang Dibutuhkan | Tujuan Bisnis | Output yang Diharapkan | Prioritas | Status |
|---|---|---|---|---|---|:---:|:---:|
| **DATA-REQ-001** | Data Profiling & Audit | Audit kelengkapan dan anomali data master instansi pada tabel legacy `tb_instansi` dan `data_pemda`. | Dump tabel `tb_instansi`, `data_pemda`, `tb_jenis_instansi`, `tb_wilayah` dari `eskld`. | Mengidentifikasi data instansi yang duplikat, tidak aktif, atau memiliki foreign key terputus (*orphan*). | Dokumen *Data Profiling & Quality Report* berisi daftar anomali dan rasio kelengkapan data. | **CRITICAL** | **CONFIRMED** |
| **DATA-REQ-002** | Legacy Data Mapping | Pemetaan relasi data dari tabel scratch `data_map_*` dan `data_map_yudhi_latest` ke skema master instansi baru. | Tabel `data_map`, `data_map_pemda`, `data_map_pemda_baru`, `data_map_yudhi_latest`. | Memahami logika pemetaan ad-hoc legacy agar tidak ada relasi penting yang hilang saat migrasi. | Matriks *Legacy to Target Mapping Sheet* (Excel / Markdown) dengan rekomendasi data yang dipertahankan vs dibuang. | **CRITICAL** | **CONFIRMED** |
| **DATA-REQ-003** | Cabinet Data Cleansing | Dekonstruksi kolom string delimit `data_kl.list_id_kl` menjadi data relasional baris (*relational rows*). | Tabel `data_kl` (kolom `tahun`, `is_active`, `list_id_kl`). | Mengonversi daftar ID instansi legacy berformat string koma menjadi relasi ternormalisasi `CabinetMembership`. | Tabel hasil ekstraksi relasi kabinet legacy yang bersih dan siap diimpor ke skema baru. | **CRITICAL** | **CONFIRMED** |
| **DATA-REQ-004** | Kabinet Merah Putih Lineage | Penyusunan data silsilah pembentukan kementerian pada Kabinet Merah Putih (48 K/L). | Daftar kementerian Kabinet Merah Putih (2024-2029) dan Kabinet Indonesia Maju (2019-2024). | Memetakan kementerian mana yang baru dibentuk, dipecah (misal: 1 kementerian menjadi 3), atau berganti nama. | Matriks *Institutional Transition Lineage Matrix* (`predecessor_id` $\rightarrow$ `successor_id`). | **CRITICAL** | **CONFIRMED** |
| **DATA-REQ-005** | Master Data Dictionary | Penyusunan Kamus Data (*Data Dictionary*) resmi untuk seluruh atribut entitas master kelembagaan. | Seluruh entitas conceptual data discovery. | Menyediakan standardisasi definisi, format tipe data, aturan validasi, dan sumber rujukan data. | Dokumen *SIGMA-K Official Data Dictionary v1.0*. | **HIGH** | **CONFIRMED** |
| **DATA-REQ-006** | ASN Posture Metrics | Analisis formula query view `v_postur_asn` dan validasi keterkaitan dengan data unit kerja eselon. | Definisi view `v_postur_asn`, data eselon `ref_eselon`, dan data unit kerja `tbl_ref_instansi_org`. | Memastikan angka rekapitulasi aparatur dan jabatan pada dashboard memiliki akurasi dan dasar rujukan yang valid. | Spesifikasi Algoritma Agregasi Postur Kelembagaan & Rekomendasi Indexing. | **HIGH** | **CONFIRMED** |
| **DATA-REQ-007** | Dashboard Executive Metrics | Formulasi metrik dan KPI kelembagaan untuk Executive Dashboard SESDEP. | Data agregat master instansi, keanggotaan kabinet, tiket verifikasi, dan postur ASN. | Menyajikan indikator kinerja utama tata kelola kelembagaan secara visual dan mudah dipahami pimpinan. | Dokumen *Dashboard KPI & Metric Calculation Specs* (rumus agregasi, filter dimensi, frekuensi update). | **HIGH** | **CONFIRMED** |
| **DATA-REQ-008** | Hierarchy Depth Profiling | Analisis kedalaman dan integritas hierarki unit kerja pada `tbl_ref_instansi_org`. | Tabel `tbl_ref_instansi_org` (kolom `id`, `parent_id`, `id_instansi`, nama unit). | Memastikan tidak ada circular dependency legacy dan mengetahui kedalaman pohon maksimal (level eselon). | Laporan analisis distribusi level hierarki per kementerian dan daftar perbaikan *orphan parent_id*. | **HIGH** | **CONFIRMED** |
| **DATA-REQ-009** | Reporting & Export Templates | Perancangan format tata letak laporan ekspor resmi (PDF/Excel) data kelembagaan. | Kebutuhan pelaporan pimpinan / stakeholder KemenPANRB. | Menstandarkan format keluaran laporan kelembagaan nasional. | Mockup / Template Dataset Ekspor (Profil K/L, Rekapitulasi Kabinet, Struktur Bagan). | **MEDIUM** | **PROPOSED** |
| **DATA-REQ-010** | Tupoksi Semantic Clustering | Eksplorasi clustering kata kunci butir tugas dan fungsi untuk mendeteksi kesamaan fungsi antar K/L. | Teks tugas dan fungsi instansi kementerian. | Eksplorasi analitik tingkat lanjut untuk membantu analis kelembagaan mendeteksi tumpang tindih tupoksi. | Laporan eksplorasi kesamaan kata kunci tupoksi (*Text Similarity Prototype*). | **LOW** | **TBD** |

---

## 3. Alur Kolaborasi: Data Analyst & Lead Full-Stack Engineer

```
[DATA ANALYST (Ikhsan)]                          [LEAD FULL-STACK ENGINEER]
          |                                                   |
          | 1. Profiling Legacy Data (eskld)                  |
          | 2. Cleansing & Mapping Matrix                     |
          v                                                   |
[CLEAN DATASET & SPECS] ------------------------------------> | 3. Implementasi Migration Scripts (Phase 3)
          |                                                   | 4. Integrasi Core Backend API (Phase 5)
          v                                                   v
[FORMULASI METRIK & KPI] -----------------------------------> | 5. Visualisasi Chart Dashboard & Analytics (Phase 8)
          |                                                   |
          v                                                   v
[VALIDASI DATA POSTUR ASN] ---------------------------------> [END-TO-END VERIFIKASI DATA (Phase 9 & 10)]
```

---

## 4. Prioritas Kerja Segera (Phase 1 s.d Phase 3)
1. **Langkah 1:** Melakukan audit awal terhadap database legacy `eskld` untuk menghasilkan laporan data profiling (`DATA-REQ-001`).
2. **Langkah 2:** Memetakan tabel ad-hoc `data_map_*` dan membersihkan data string delimit `list_id_kl` (`DATA-REQ-002` & `DATA-REQ-003`).
3. **Langkah 3:** Menyusun matriks silsilah perubahan kementerian pada Kabinet Merah Putih (`DATA-REQ-004`).
4. **Langkah 4:** Menyusun Kamus Data resmi bersama Lead Engineer (`DATA-REQ-005`).
