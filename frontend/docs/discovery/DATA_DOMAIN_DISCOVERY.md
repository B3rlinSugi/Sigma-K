# DATA DOMAIN DISCOVERY: SIGMA-K

> **Status:** DISCOVERY BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  
> **Catatan Penting:** Dokumen ini memetakan domain data konseptual awal. **TIDAK** ada pembuatan database fisik, migration, atau skema DDL final pada tahap ini.

---

## 1. Matriks Pemetaan: Legacy Data ke Domain Baru

| Legacy Data / Table / View | Business Meaning (Makna Bisnis) | Candidate New Domain Entity | Status Evaluasi | Rasional & Catatan Arsitektural |
|---|---|---|---|---|
| `tb_instansi` | Master data instansi pemerintah pusat & umum. | `Institution` (Master Instansi) | **CONFIRMED CANDIDATE** | Menjadi entitas inti master instansi. Perlu standardisasi kolom tipe, kodefikasi unik, dan atribut metadata. |
| `tb_jenis_instansi` | Kategori klasifikasi instansi (Kementerian, LPNK, LNS, Pemda, dll.). | `InstitutionType` (Tipe Instansi) | **CONFIRMED CANDIDATE** | Entitas referensi baku untuk pengelompokan instansi. |
| `tb_wilayah` | Referensi wilayah administratif Indonesia (Provinsi, Kab/Kota). | `Region` (Wilayah Geografis) | **CONFIRMED CANDIDATE** | Referensi geografis untuk relasi instansi pemerintah daerah dan instansi vertikal di daerah. |
| `ref_eselon` | Referensi tingkatan eselon jabatan struktural. | `EchelonLevel` / `PositionLevel` | **CONFIRMED CANDIDATE** | Referensi tingkatan struktur jabatan. Perlu disesuaikan dengan nomenklatur jabatan fungsional. |
| `data_kl` | Data kabinet, tahun aktif, dan daftar kementerian anggota. | `Cabinet` + `CabinetPeriod` + `CabinetMembership` | **NEEDS REDESIGN** | Model lama menggunakan kolom `list_id_kl` (string delimit). Harus dinormalisasi menjadi relasi relasional formal many-to-many. |
| `data_pemda` | Data instansi pemerintah daerah. | `Institution` (dengan tipe `PEMDA_*`) + `RegionalProfile` | **NEEDS NORMALIZATION** | Menyatukan konsep Pemda ke dalam master `Institution` agar tidak terjadi duplikasi entitas instansi. |
| `tbl_ref_instansi_org` | Bagan struktur unit organisasi instansi. | `OrganizationUnit` (Unit Organisasi) | **CONFIRMED CANDIDATE (NEEDS CLEANUP)** | Tetap menggunakan model hierarki `parent_id` (Adjacency List) dengan tambahan validasi integritas struktur. |
| `users` | Akun pengguna dan hak akses legacy. | `User` + `Role` + `UserInstitutionScope` | **NEEDS MODERN REDESIGN** | Diperluas dengan RBAC modern, enkripsi standar industri (Bcrypt/Argon2), dan relasi scope instansi. |
| `data_map` | Pemetaan manual kustom instansi. | *None (Historical Mapping Tracker)* | **TO BE DEPRECATED & RESTRUCTURED** | Tabel ad-hoc legacy harus digantikan dengan relasi formal `CabinetMembership` dan `InstitutionHistory`. |
| `data_map_pemda` | Pemetaan manual instansi pemda. | *None (Consolidated to Master)* | **TO BE DEPRECATED & CONSOLIDATED** | Dihilangkan dan disatukan ke dalam relasi instansi-wilayah standar. |
| `data_map_pemda_baru` | Pemetaan ad-hoc pemda baru. | *None (Consolidated to Master)* | **TO BE DEPRECATED & CONSOLIDATED** | Dihilangkan dan disatukan ke dalam relasi instansi-wilayah standar. |
| `data_map_yudhi_latest` | Tabel temporer pribadi untuk analisis manual. | *None (Analyst Workspace View)* | **TO BE DEPRECATED & CLEANSED** | Anti-pattern database production. Diakomodasi melalui query analitik resmi oleh Data Analyst. |
| `v_postur_asn` | View agregasi data pegawai/postur ASN instansi. | `AsnPostureAnalyticsView` | **CANDIDATE (ANALYSIS NEEDED)** | Rujukan analisis postur kelembagaan. Perlu sinkronisasi model bersama Data Analyst Ikhsan. |
| `VIEW rekap/dashboard` | Kumpulan view agregat dashboard legacy. | `AnalyticsSummaryService` / Cached Aggregates | **NEEDS MODERN RE-ENGINEERING** | Dioptimasi pada level data pipeline / view analitik modern. |

---

## 2. Analisis Redesign Domain Kunci

### A. Redesign Domain Kabinet & Keanggotaan (Cabinet & Membership)
- **Kondisi Legacy:** 
  Tabel `data_kl` menggabungkan nama kabinet, tahun, status aktif, dan string berisi ID instansi:
  ```
  data_kl: [id, nama_kabinet, tahun, is_active, list_id_kl = "1,4,12,45,78"]
  ```
- **Masalah:** 
  Tidak dapat melakukan join foreign key, tidak bisa melacak kapan suatu kementerian mulai bergabung atau keluar dari kabinet, query pencarian sangat lambat dan rawan error.
- **Rancangan Domain Baru (Konseptual):**
  1. `Cabinet`: Menyimpan identitas kabinet (Nama, Presiden, Deskripsi).
  2. `CabinetPeriod`: Menyimpan masa berlaku kabinet (Tahun Mulai, Tahun Selesai, Status Periode Aktif).
  3. `CabinetMembership`: Menyimpan relasi antara `CabinetPeriod` dan `Institution`, dilengkapi dengan metadata tanggal bergabung, tanggal selesai, dan status nomenklatur pada periode tersebut.

---

### B. Redesign Domain Pengelolaan Tugas & Fungsi (Tupoksi)
- **Kondisi Legacy:** 
  Tupoksi belum memiliki entitas tabel khusus yang terstruktur.
- **Rancangan Domain Baru (Konseptual):**
  1. `InstitutionDutyFunction`: Menyimpan butir Tugas Pokok dan butir-butir Rincian Fungsi.
  2. Terikat secara relasional ke `Institution` dan `OrganizationUnit` terkait.
  3. Menyimpan rujukan dasar hukum (Pasal/Ayat regulasi) dan versi regulasi.

---

### C. Redesign Domain Workflow Verifikasi & Submission
- **Kondisi Legacy:** 
  Tidak ada sistem tiket pengajuan; manipulasi data dilakukan secara direct edit.
- **Rancangan Domain Baru (Konseptual):**
  1. `SubmissionTicket`: Menyimpan entitas tiket pengajuan (`ticket_number`, `institution_id`, `submitter_id`, `status`, `submitted_at`).
  2. `SubmissionItem`: Menyimpan payload perubahan data (sebelum vs sesudah) dalam bentuk terstruktur.
  3. `VerificationLog`: Menyimpan jejak review verifikator (`verifier_id`, `status_decision`, `notes`, `verified_at`).

---

### D. Redesign Domain Audit & Realtime Notification
- **Kondisi Legacy:** 
  Tidak tersedia audit log dan notifikasi.
- **Rancangan Domain Baru (Konseptual):**
  1. `AuditLog`: Immutable table (`user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `timestamp`).
  2. `Notification`: Menyimpan antrean notifikasi per user (`user_id`, `title`, `message`, `category`, `is_read`, `action_url`, `created_at`).

---

## 3. Konsep Entitas Relasional Baru (Level Konseptual)

```
[Cabinet] 1 --- * [CabinetPeriod] 1 --- * [CabinetMembership] * --- 1 [Institution]
                                                                          |
                                                                          +--- 1 [InstitutionProfile]
                                                                          +--- * [InstitutionDutyFunction]
                                                                          +--- * [OrganizationUnit] (hierarchical parent_id)
                                                                          |              |
                                                                          |              +--- 1 [PositionLevel/Eselon]
                                                                          |
                                                                          +--- * [SubmissionTicket]
                                                                                         |
                                                                                         +--- * [SubmissionItem]
                                                                                         +--- * [VerificationLog]

[User] 1 --- * [Role]
[User] * --- * [Institution] (Scope)
[User] 1 --- * [Notification]
[User] 1 --- * [AuditLog]
```

---

## 4. Status Discovery Data
- Seluruh tabel legacy telah dipetakan maknanya.
- Kebutuhan normalisasi tabel `data_kl` dan pembersihan tabel ad-hoc `data_map_*` telah terdokumentasi.
- Desain skema DDL fisik dan eksekusi migrasi database **DITANGGUHKAN** hingga fase arsitektur dan persetujuan user.
