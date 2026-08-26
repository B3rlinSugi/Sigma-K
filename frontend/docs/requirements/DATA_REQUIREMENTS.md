# DATA REQUIREMENTS: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Senior Data Architect & Requirements Engineer  
> **Peringatan Keras:** Dokumen ini mendefinisikan kebutuhan data pada **level konseptual dan logika bisnis**. **TIDAK** ada pembuatan skema DDL fisik, script migrasi, atau pembuatan database pada tahap ini.

---

## 1. Klasifikasi Kategori Data

```
+-----------------------------------------------------------------------------------+
|                            SIGMA-K DATA TAXONOMY                                  |
+-----------------------------------------------------------------------------------+
| 1. MASTER DATA: Institution, OrganizationUnit, TugasFungsi, Cabinet               |
| 2. REFERENCE DATA: InstitutionType, Region, Position/Echelon                      |
| 3. TRANSACTIONAL DATA: SubmissionTicket, SubmissionItem, Verification, Approval    |
| 4. HISTORICAL DATA: CabinetPeriod, CabinetMembership, InstitutionLineage          |
| 5. AUDIT DATA: AuditLog (Immutable mutation events)                               |
| 6. ANALYTICAL DATA: PosturASNView, AggregatedEchelonStats, ComparativeMetrics     |
| 7. IAM & COMMUNICATION: User, Role, Permission, UserScope, Notification           |
+-----------------------------------------------------------------------------------+
```

---

## 2. Rincian Entitas Data Konseptual

### A. Entitas Master & Profil Kelembagaan

#### 1. Entity: `Institution` (Master Instansi)
- **Purpose:** Entitas induk penyimpan identitas resmi seluruh instansi pemerintah pusat (Kementerian, LPNK, LNS) dan pemerintah daerah (Provinsi, Kab/Kota).
- **Important Attributes:** `id`, `code` (kode unik nasional), `name` (nama resmi), `short_name` (akronim/singkatan), `institution_type_id`, `region_id`, `status` (`ACTIVE`/`INACTIVE`), `created_at`, `updated_at`, `deleted_at`.
- **Ownership:** Dimiliki dan dikelola secara terpusat oleh Administrator Sistem KemenPANRB.
- **Lifecycle:** Created $\rightarrow$ Active $\rightarrow$ Modified via Approval $\rightarrow$ Soft Deleted.
- **Historical Requirement:** Wajib mendukung soft delete dan pelacakan silsilah perubahan nomenklatur antarperiode.
- **Relationship:** 
  - Belongs-To `InstitutionType`, Belongs-To `Region`
  - Has-One `InstitutionProfile`
  - Has-Many `OrganizationUnit`, Has-Many `TugasFungsi`
  - Has-Many `CabinetMembership`, Has-Many `SubmissionTicket`
- **Data Quality Concern:** Pencegahan duplikasi nama instansi, konsistensi kodefikasi standar nasional Kemendagri/BPS/KemenPANRB, dan cleansing data legacy dari tabel `data_map_*`.

---

#### 2. Entity: `InstitutionProfile` (Profil Detail Instansi)
- **Purpose:** Menyimpan informasi komprehensif profil operasional instansi pemerintah.
- **Important Attributes:** `institution_id`, `address`, `phone`, `email`, `website_url`, `logo_path`, `vision_statement`, `mission_statement`, `legal_basis_summary`.
- **Ownership:** Dikelola drafnya oleh Operator Instansi (USER), disahkan oleh Admin.
- **Lifecycle:** Mengikuti siklus draf submission instansi.
- **Historical Requirement:** Menyimpan versi profil terakhir yang disetujui.
- **Relationship:** Belongs-To `Institution` (1-to-1).
- **Data Quality Concern:** Validitas format email dan URL website resmi instansi pemerintah (.go.id).

---

#### 3. Entity: `OrganizationUnit` (Unit Organisasi Hierarkis)
- **Purpose:** Merepresentasikan unit kerja struktural berjenjang di dalam instansi (Sekretariat Jenderal, Kedeputian, Direktorat, Biro, Bagian, dsb.).
- **Important Attributes:** `id`, `institution_id`, `parent_id` (relasi atasan), `unit_code`, `unit_name`, `echelon_level_id`, `hierarchy_level`, `is_active`, `sort_order`.
- **Ownership:** Dikelola oleh Operator Instansi dalam mode draf, disahkan oleh Admin.
- **Lifecycle:** Drafted $\rightarrow$ Submitted $\rightarrow$ Verified $\rightarrow$ Approved $\rightarrow$ Active / Soft Deleted.
- **Historical Requirement:** Perubahan susunan unit terekam dalam tiket pengajuan historis.
- **Relationship:** 
  - Belongs-To `Institution`, Belongs-To `OrganizationUnit` (Self-referencing via `parent_id`)
  - Belongs-To `Position / EchelonLevel`
  - Has-Many `TugasFungsi`
- **Data Quality Concern:** Proteksi circular dependency pada `parent_id`, integritas pohon saat unit atasan dinonaktifkan, dan pembersihan hierarki legacy dari `tbl_ref_instansi_org`.

---

#### 4. Entity: `TugasFungsi` (Tugas Pokok & Rincian Fungsi)
- **Purpose:** Menyimpan butir-butir Tugas dan Rincian Fungsi resmi per instansi dan per unit organisasi.
- **Important Attributes:** `id`, `institution_id`, `organization_unit_id` (opsional jika level instansi), `type` (`DUTY`/`FUNCTION`), `content_text`, `legal_article_reference` (rujukan pasal/ayat), `sequence_number`.
- **Ownership:** Dikelola oleh Operator Instansi (USER), disahkan oleh Admin.
- **Lifecycle:** Mengikuti siklus submission instansi.
- **Historical Requirement:** Terekam versi tupoksi sesuai dasar hukum regulasi yang berlaku.
- **Relationship:** Belongs-To `Institution`, Belongs-To `OrganizationUnit`.
- **Data Quality Concern:** Teks tidak boleh kosong, rujukan dasar hukum harus terisi, konsistensi urutan nomor butir fungsi.

---

### B. Entitas Kabinet & Histori Kelembagaan

#### 5. Entity: `Cabinet` (Master Kabinet)
- **Purpose:** Entitas penyimpan identitas era kabinet pemerintahan Indonesia.
- **Important Attributes:** `id`, `name` (misal: "Kabinet Merah Putih"), `president_name`, `vice_president_name`, `description`, `is_active`, `created_at`.
- **Ownership:** Dikelola penuh oleh Admin KemenPANRB.
- **Lifecycle:** Created $\rightarrow$ Set Active $\rightarrow$ Archived.
- **Historical Requirement:** Seluruh data kabinet masa lampau tersimpan permanen sebagai referensi komparasi.
- **Relationship:** Has-Many `CabinetPeriod`, Has-Many `CabinetMembership`.
- **Data Quality Concern:** Keunikan nama kabinet, hanya tepat satu kabinet yang berstatus `is_active = TRUE`.

---

#### 6. Entity: `CabinetPeriod` (Periode Kabinet)
- **Purpose:** Menentukan rentang masa jabatan waktu berlakunya suatu kabinet.
- **Important Attributes:** `id`, `cabinet_id`, `start_date`, `end_date`, `legal_decree_number` (nomor Keppres/Perpres pembentukan), `status` (`DRAFT`/`ACTIVE`/`COMPLETED`).
- **Ownership:** Admin.
- **Lifecycle:** Created $\rightarrow$ Active $\rightarrow$ Completed.
- **Historical Requirement:** Wajib menyimpan rentang waktu riil masa jabatan.
- **Relationship:** Belongs-To `Cabinet`, Has-Many `CabinetMembership`.
- **Data Quality Concern:** Validasi temporal `end_date >= start_date`.

---

#### 7. Entity: `CabinetMembership` (Keanggotaan Instansi dalam Kabinet)
- **Purpose:** Memetakan instansi kementerian/lembaga yang menjadi anggota resmi suatu kabinet secara relasional ternormalisasi.
- **Important Attributes:** `id`, `cabinet_period_id`, `institution_id`, `membership_category` (`KEMENKO`/`KEMENTERIAN_TEKNIS`/`LPNK`/`LNS`), `joined_date`, `ended_date`, `is_active_in_cabinet`.
- **Ownership:** Admin.
- **Lifecycle:** Joined $\rightarrow$ Active In Cabinet $\rightarrow$ Ended/Transitioned.
- **Historical Requirement:** Mencatat riwayat masuk dan keluarnya instansi pada kabinet tertentu (misal akibat *reshuffle*).
- **Relationship:** Belongs-To `CabinetPeriod`, Belongs-To `Institution`.
- **Data Quality Concern:** Mencegah duplikasi entitas instansi pada periode kabinet yang sama (`UNIQUE(cabinet_period_id, institution_id)`), menggantikan kolom teks delimit `list_id_kl` legacy.

---

### C. Entitas Tata Kelola Workflow (Submission, Verification, Approval, Revision)

#### 8. Entity: `SubmissionTicket` (Tiket Pengajuan Perubahan Data)
- **Purpose:** Menyimpan berkas pengajuan perubahan data kelembagaan dari Operator Instansi ke tim Verifikator.
- **Important Attributes:** `id`, `ticket_number` (kode unik `TKT-YYYYMMDD-XXXX`), `institution_id`, `submitter_user_id`, `status` (`DRAFT`, `SUBMITTED`, `IN_REVIEW`, `VERIFIED`, `REVISION_REQUIRED`, `APPROVED`, `REJECTED`), `submission_notes`, `legal_doc_path`, `submitted_at`, `approved_at`.
- **Ownership:** Dimiliki bersama antara User pengaju, Verifikator peninjau, dan Admin.
- **Lifecycle:** Sesuai State Machine Workflow (Draft $\rightarrow$ Submitted $\rightarrow$ In Review $\rightarrow$ Verified/Revision $\rightarrow$ Approved).
- **Historical Requirement:** Menjadi bukti legal alur pengesahan perubahan struktur instansi.
- **Relationship:** 
  - Belongs-To `Institution`, Belongs-To `User` (`submitter_user_id`)
  - Has-Many `SubmissionItem`, Has-Many `VerificationLog`.
- **Data Quality Concern:** Keterikatan dokumen dasar hukum wajib ada saat submit, nomor tiket tidak boleh bentrok.

---

#### 9. Entity: `SubmissionItem` (Item Perubahan Draf)
- **Purpose:** Menyimpan rincian delta perubahan data (sebelum vs sesudah) per unit kerja atau profil.
- **Important Attributes:** `id`, `submission_ticket_id`, `target_entity_type` (`PROFILE`/`ORG_UNIT`/`TUPOKSI`), `target_entity_id`, `action_type` (`CREATE`/`UPDATE`/`DELETE`), `payload_before_json`, `payload_after_json`.
- **Ownership:** System (dihasilkan otomatis dari draf kerja).
- **Lifecycle:** Mengikuti siklus hidup tiket pengajuan.
- **Historical Requirement:** Memungkinkan tampilan Diff Viewer komparasi sebelum vs sesudah.
- **Relationship:** Belongs-To `SubmissionTicket`.
- **Data Quality Concern:** Format payload JSON terstruktur dan konsisten.

---

#### 10. Entity: `VerificationLog` (Log Peninjauan & Revisi)
- **Purpose:** Mencatat hasil telaah, rekomendasi, dan catatan perbaikan dari Verifikator.
- **Important Attributes:** `id`, `submission_ticket_id`, `verifier_user_id`, `decision` (`PASS`/`REVISION`/`REJECT`), `notes`, `verified_at`.
- **Ownership:** Verifikator.
- **Lifecycle:** Append-only per iterasi peninjauan.
- **Historical Requirement:** Merekam seluruh jejak dialog perbaikan antara verifikator dan operator.
- **Relationship:** Belongs-To `SubmissionTicket`, Belongs-To `User` (`verifier_user_id`).
- **Data Quality Concern:** Catatan wajib diisi saat keputusan adalah revisi atau tolak.

---

### D. Entitas Manajemen Pengguna, Hak Akses & Notifikasi

#### 11. Entity: `User` (Pengguna Sistem)
- **Purpose:** Menyimpan akun pengguna yang memiliki akses ke sistem SIGMA-K.
- **Important Attributes:** `id`, `username`, `email`, `password_hash`, `full_name`, `nip`, `phone_number`, `institution_id` (scope instansi asal), `is_active`, `last_login_at`.
- **Ownership:** Dikelola oleh Admin KemenPANRB.
- **Lifecycle:** Created $\rightarrow$ Active $\rightarrow$ Deactivated.
- **Historical Requirement:** Riwayat login dan aktivitas tercatat di audit log.
- **Relationship:** Belongs-To `Institution` (Scope), Belongs-To `Role`, Has-Many `Notification`, Has-Many `AuditLog`.
- **Data Quality Concern:** Keunikan email/username, keamanan hashing password.

---

#### 12. Entity: `Role` & `Permission` (Peran & Izin RBAC)
- **Purpose:** Mengatur hak akses pengguna berdasarkan peran (`USER`, `ADMIN`, `VERIFIKATOR`, `PIMPINAN`).
- **Important Attributes:** `id`, `name`, `code`, `description`, `permissions_matrix`.
- **Ownership:** Admin (Master Security).
- **Lifecycle:** Master Reference.
- **Relationship:** Many-to-Many antara `Role` dan `Permission`, One-to-Many antara `Role` dan `User`.
- **Data Quality Concern:** Integritas pemisahan kewenangan (*Separation of Duties*).

---

#### 13. Entity: `Notification` (Antrean Notifikasi Realtime & Riwayat)
- **Purpose:** Menyimpan pesan notifikasi aktivitas sistem untuk setiap pengguna.
- **Important Attributes:** `id`, `user_id`, `title`, `message`, `category` (`MUTATION`/`WORKFLOW`/`SYSTEM`), `related_entity_type`, `related_entity_id`, `action_url`, `is_read`, `created_at`.
- **Ownership:** System (Penerbit) $\rightarrow$ User (Penerima).
- **Lifecycle:** Created $\rightarrow$ Broadcasted Realtime $\rightarrow$ Read by User $\rightarrow$ Archived.
- **Historical Requirement:** Riwayat notifikasi tersimpan untuk ditinjau kembali di Notification Center.
- **Relationship:** Belongs-To `User`.
- **Data Quality Concern:** Penyampaian seketika (*realtime delivery*) dan penandaan status baca (`is_read`).

---

### E. Entitas Audit & Analitik

#### 14. Entity: `AuditLog` (Jejak Audit Permanen)
- **Purpose:** Merekam seluruh aktivitas mutasi data dan aksi krusial pengguna secara immutable.
- **Important Attributes:** `id`, `user_id`, `ip_address`, `user_agent`, `action_type` (`CREATE`/`UPDATE`/`DELETE`/`APPROVE`/`REJECT`), `entity_name`, `entity_id`, `old_values_json`, `new_values_json`, `created_at`.
- **Ownership:** System (Immutable Append-Only).
- **Lifecycle:** Append-only (tidak dapat diubah atau dihapus).
- **Historical Requirement:** Retensi permanen untuk kebutuhan audit kepatuhan dan forensik data.
- **Relationship:** Belongs-To `User` (Nullable jika event sistem).
- **Data Quality Concern:** Kelengkapan metadata snapshot JSON.

---

#### 15. Entity: `PosturASNAnalytics` (Data Analitik Postur Kelembagaan)
- **Purpose:** Agregasi data kondisi dan postur aparatur per instansi untuk kebutuhan visualisasi pimpinan dan kajian Data Analyst.
- **Important Attributes:** `id`, `institution_id`, `echelon_level_id`, `total_positions`, `filled_positions`, `vacant_positions`, `reporting_period`, `source_reference`.
- **Ownership:** Data Analyst (Ikhsan) bersama Tim KemenPANRB.
- **Lifecycle:** Periodic Snapshot / Aggregation Refresh.
- **Historical Requirement:** Menyimpan tren postur kelembagaan dari tahun ke tahun.
- **Relationship:** Belongs-To `Institution`, Belongs-To `Position / EchelonLevel`.
- **Data Quality Concern:** Akurasi angka agregat yang merujuk pada view legacy `v_postur_asn` dan kebutuhan sinkronisasi berkala.
