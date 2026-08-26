# 03. DATA DICTIONARY & ATTRIBUTE SPECIFICATIONS: SIGMA-K

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Data Architect & Data Governance Specialist  
> **Kolaborator:** Ikhsan (Data Analyst)  

Dokumen ini mendefinisikan Kamus Data (*Data Dictionary*) resmi untuk seluruh atribut entitas sistem SIGMA-K, mencakup metadata teknis, metadata bisnis, dan tata kelola data.

---

## 1. Kamus Data: Domain Master Instansi & Profil (`institutions`, `institution_profiles`)

| Field Name | Target Data Type | Nullable | Business Meaning & Definition | Example Value | Source Table / Field | Target Entity | Metadata Category & Notes |
|---|---|:---:|---|---|---|---|---|
| `id` | `UUID` | **NO** | Identifier unik global entitas instansi. | `e3b0c442-98fc-1c14-9af3-4be27063f912` | `tb_instansi.id` (INT) | `Institution` | Technical. UUIDv4 untuk keamanan API & distributed-ready. |
| `code` | `VARCHAR(50)` | **NO** | Kode unik registrasi nasional instansi pemerintah. | `KL-042` / `PEMDA-3100` | `tb_instansi.kode_instansi` | `Institution` | Business (Unique Constraint). Standar kodefikasi nasional. |
| `name` | `VARCHAR(255)` | **NO** | Nama resmi instansi sesuai regulasi pembentukan. | `Kementerian Koordinator Bidang Pangan` | `tb_instansi.nama_instansi` | `Institution` | Business (Mandatory). Indeks B-Tree & Trigram. |
| `short_name` | `VARCHAR(50)` | YES | Akronim / singkatan resmi instansi. | `Kemenko Pangan` | `tb_instansi.singkatan` | `Institution` | Business. Digunakan pada label kartu ringkas. |
| `institution_type_id` | `INT` | **NO** | Foreign key klasifikasi jenis instansi. | `1` (Kementerian Teknis) | `tb_instansi.id_jenis` | `Institution` | Technical / Relational. Terikat ke `institution_types`. |
| `region_id` | `INT` | YES | Foreign key wilayah administratif (khusus Pemda/Vertikal). | `3100` (DKI Jakarta) | `tb_instansi.id_wilayah` | `Institution` | Technical / Relational. Terikat ke `regions`. |
| `status` | `ENUM` | **NO** | Status operasional instansi (`ACTIVE`, `INACTIVE`). | `ACTIVE` | `tb_instansi.status` | `Institution` | Operational. Mengontrol visibilitas di katalog aktif. |
| `created_at` | `TIMESTAMPTZ`| **NO** | Waktu pencatatan pertama kali di sistem. | `2026-08-25 20:00:00+07` | Generated | `Institution` | Governance (System Audit). |
| `updated_at` | `TIMESTAMPTZ`| **NO** | Waktu pembaruan data terakhir disahkan. | `2026-08-25 20:15:00+07` | Generated | `Institution` | Governance (System Audit). |
| `deleted_at` | `TIMESTAMPTZ`| YES | Penanda waktu soft-delete (penonaktifan instansi). | `NULL` | None (New Field) | `Institution` | **PROPOSED FIELD** (REQ-023). Menjaga referensi historis. |
| `address` | `TEXT` | YES | Alamat kantor pusat instansi. | `Jl. Medan Merdeka Barat No. 17, Jakarta` | `tb_instansi.alamat` | `InstitutionProfile`| Business Metadata. |
| `phone` | `VARCHAR(50)` | YES | Nomor telepon call center / sekretariat resmi. | `(021) 3840123` | `tb_instansi.telepon` | `InstitutionProfile`| Business Metadata. |
| `email` | `VARCHAR(100)`| YES | Alamat surat elektronik resmi instansi (.go.id). | `persuratan@kemenkopangan.go.id` | `tb_instansi.email` | `InstitutionProfile`| Business Metadata. Validasi format email. |
| `website_url` | `VARCHAR(255)`| YES | Tautan portal resmi instansi pemerintah. | `https://kemenkopangan.go.id` | `tb_instansi.website` | `InstitutionProfile`| Business Metadata. Validasi HTTPS URL. |
| `logo_path` | `VARCHAR(255)`| YES | Path lokasi penyimpanan berkas logo instansi. | `/uploads/logos/inst-pangan-logo.png` | None (New Field) | `InstitutionProfile`| **PROPOSED FIELD** (REQ-006). Aset visual profil. |
| `vision_statement` | `TEXT` | YES | Pernyataan visi instansi sesuai Renstra. | `Terwujudnya kedaulatan pangan nasional...` | None (New Field) | `InstitutionProfile`| **PROPOSED FIELD** (REQ-006). Informasi profil. |
| `mission_statement`| `TEXT` | YES | Butir-butir misi instansi pemerintah. | `1. Mengkoordinasikan kebijakan pangan...` | None (New Field) | `InstitutionProfile`| **PROPOSED FIELD** (REQ-006). Informasi profil. |
| `legal_basis_summary`| `TEXT` | YES | Ringkasan dasar hukum regulasi pembentukan instansi. | `Perpres Nomor 139 Tahun 2024` | None (New Field) | `InstitutionProfile`| **PROPOSED FIELD** (REQ-006). Legalitas formal. |

---

## 2. Kamus Data: Domain Kabinet & Histori (`cabinets`, `cabinet_periods`, `cabinet_memberships`, `institution_lineages`)

| Field Name | Target Data Type | Nullable | Business Meaning & Definition | Example Value | Source Table / Field | Target Entity | Metadata Category & Notes |
|---|---|:---:|---|---|---|---|---|
| `cabinet_id` | `UUID` | **NO** | Identifier unik master kabinet pemerintahan. | `cab-merah-putih-01` | `data_kl.id` | `Cabinet` | Technical. UUIDv4. |
| `cabinet_name` | `VARCHAR(100)`| **NO** | Nama resmi era kabinet kepresidenan. | `Kabinet Merah Putih` | `data_kl.nama_kabinet` | `Cabinet` | Business (Unique). Nama kabinet Indonesia. |
| `president_name` | `VARCHAR(100)`| **NO** | Nama Presiden Republik Indonesia yang memimpin. | `Prabowo Subianto` | `data_kl.presiden` | `Cabinet` | Business. Metadata kepemimpinan nasional. |
| `vice_president_name`| `VARCHAR(100)`| YES | Nama Wakil Presiden Republik Indonesia. | `Gibran Rakabuming Raka` | None (New Field) | `Cabinet` | **PROPOSED FIELD** (REQ-001). Informasi pimpinan. |
| `is_active_cabinet`| `BOOLEAN` | **NO** | Penanda kabinet aktif utama default sistem. | `TRUE` | `data_kl.is_active` | `Cabinet` | Operational (BRULE-003: Tepat 1 aktif). |
| `period_id` | `UUID` | **NO** | Identifier rentang masa jabatan kabinet. | `per-2024-2029` | None (New Field) | `CabinetPeriod` | **PROPOSED FIELD** (REQ-002). Normalisasi periode. |
| `start_date` | `DATE` | **NO** | Tanggal awal pelantikan / berlakunya kabinet. | `2024-10-21` | `data_kl.tahun` (Parsed) | `CabinetPeriod` | Business / Temporal Integrity. |
| `end_date` | `DATE` | YES | Tanggal berakhirnya masa jabatan kabinet. | `NULL` (Berjalan) / `2029-10-20` | None (New Field) | `CabinetPeriod` | Business / Temporal Integrity. |
| `legal_decree_number`| `VARCHAR(100)`| YES | Nomor Keppres/Perpres pembentukan kabinet. | `Keppres No. 133/P Tahun 2024` | None (New Field) | `CabinetPeriod` | **PROPOSED FIELD** (REQ-002). Dasar hukum formal. |
| `membership_id` | `UUID` | **NO** | Identifier relasi keanggotaan K/L pada kabinet. | `mem-pangan-01` | None (Deconstructed) | `CabinetMembership`| **PROPOSED FIELD** (REQ-003). Menggantikan `list_id_kl`. |
| `category` | `ENUM` | **NO** | Klasifikasi kementerian pada kabinet (`KEMENKO`, `TEKNIS`, `LPNK`, `LNS`). | `KEMENKO` | None (New Field) | `CabinetMembership`| **PROPOSED FIELD** (REQ-003). Filter dashboard SESDEP. |
| `joined_date` | `DATE` | **NO** | Tanggal kementerian resmi bergabung dalam kabinet. | `2024-10-21` | None (New Field) | `CabinetMembership`| Business Metadata. Mendukung reshuffle kabinet. |
| `is_active_in_cabinet`| `BOOLEAN` | **NO** | Status keaktifan instansi pada kabinet tersebut. | `TRUE` | None (New Field) | `CabinetMembership`| Operational. Mengakomodasi kementerian dibubarkan. |
| `lineage_id` | `UUID` | **NO** | Identifier relasi silsilah perubahan kelembagaan. | `lin-001` | None (New Field) | `InstitutionLineage`| **PROPOSED FIELD** (REQ-004). Graf transisi instansi. |
| `predecessor_id`| `UUID` | YES | ID instansi pendahulu (sebelum dipecah/merger). | `inst-dikbudristek-01` | None (New Field) | `InstitutionLineage`| Relational FK ke `Institution`. |
| `successor_id` | `UUID` | **NO** | ID instansi penerus hasil transformasi. | `inst-dikdasmen-01` | None (New Field) | `InstitutionLineage`| Relational FK ke `Institution`. |
| `transition_type`| `ENUM` | **NO** | Tipe transisi (`SPLIT`, `MERGE`, `RENAME`, `NEW`, `DISSOLVED`). | `SPLIT` | None (New Field) | `InstitutionLineage`| Business Metadata (BR-004). |

---

## 3. Kamus Data: Domain Struktur Organisasi & Tugas-Fungsi (`organization_units`, `tugas_fungsi`)

| Field Name | Target Data Type | Nullable | Business Meaning & Definition | Example Value | Source Table / Field | Target Entity | Metadata Category & Notes |
|---|---|:---:|---|---|---|---|---|
| `unit_id` | `UUID` | **NO** | Identifier unik unit kerja struktural. | `unit-setjen-01` | `tbl_ref_instansi_org.id` | `OrganizationUnit` | Technical. UUIDv4. |
| `institution_id`| `UUID` | **NO** | Foreign key instansi pemilik unit kerja. | `inst-pangan-01` | `tbl_ref_instansi_org.id_instansi` | `OrganizationUnit` | Relational FK. Scoping kepemilikan. |
| `parent_id` | `UUID` | YES | Relasi hierarkis ke unit atasan (`parent_id = NULL` jika pimpinan tertinggi). | `unit-menteri-01` | `tbl_ref_instansi_org.parent_id` | `OrganizationUnit` | Relational FK Self-referencing (Cycle Guard). |
| `unit_code` | `VARCHAR(50)` | YES | Kodefikasi internal unit organisasi instansi. | `SETJEN-01` | None (New Field) | `OrganizationUnit` | **PROPOSED FIELD** (REQ-008). Penomoran unit. |
| `unit_name` | `VARCHAR(255)`| **NO** | Nama resmi unit kerja struktural. | `Biro Perencanaan dan Kerja Sama` | `tbl_ref_instansi_org.nama_unit` | `OrganizationUnit` | Business Metadata (Mandatory). |
| `echelon_level_id`| `INT` | YES | Foreign key tingkatan jabatan struktural / eselon. | `2` (Eselon II.a) | `tbl_ref_instansi_org.id_eselon` | `OrganizationUnit` | Relational FK ke `position_levels`. |
| `hierarchy_level`| `INT` | **NO** | Tingkat kedalaman pohon (1=Menteri, 2=Eselon I, 3=Eselon II, dst). | `3` | None (New Field) | `OrganizationUnit` | **PROPOSED FIELD** (REQ-008). Kalkulasi depth pohon. |
| `sort_order` | `INT` | **NO** | Urutan tampilan bagan secara horizontal. | `1` | None (New Field) | `OrganizationUnit` | **PROPOSED FIELD** (REQ-008). Visual ordering. |
| `tupoksi_id` | `UUID` | **NO** | Identifier butir tugas atau rincian fungsi. | `tup-001` | None (New Field) | `TugasFungsi` | **PROPOSED FIELD** (REQ-007). Entitas tugas fungsi. |
| `type` | `ENUM` | **NO** | Jenis pernyataan (`DUTY` = Tugas Pokok, `FUNCTION` = Rincian Fungsi). | `FUNCTION` | None (New Field) | `TugasFungsi` | Business Metadata (Mandatory). |
| `content_text` | `TEXT` | **NO** | Redaksi kalimat resmi tugas pokok atau butir fungsi. | `Pelaksanaan koordinasi perumusan kebijakan pangan...` | None (New Field) | `TugasFungsi` | Business Metadata (Full-Text Search Index). |
| `legal_article_reference`| `VARCHAR(100)`| YES | Rujukan pasal/ayat regulasi dasar hukum. | `Pasal 5 ayat (2) huruf a` | None (New Field) | `TugasFungsi` | **PROPOSED FIELD** (REQ-007). Akuntabilitas hukum. |
| `sequence_number`| `INT` | **NO** | Urutan butir fungsi (huruf a, b, c / poin 1, 2, 3). | `1` | None (New Field) | `TugasFungsi` | Business Metadata. |

---

## 4. Kamus Data: Domain Workflow, Audit & Notifikasi

| Field Name | Target Data Type | Nullable | Business Meaning & Definition | Example Value | Source Table / Field | Target Entity | Metadata Category & Notes |
|---|---|:---:|---|---|---|---|---|
| `ticket_id` | `UUID` | **NO** | Identifier unik berkas pengajuan perubahan data. | `tkt-8812` | None (New Field) | `SubmissionTicket` | **PROPOSED FIELD** (REQ-010). Tiket workflow. |
| `ticket_number`| `VARCHAR(50)` | **NO** | Nomor registrasi pengajuan resmi unik. | `TKT-20260825-0042` | None (New Field) | `SubmissionTicket` | Business (Unique). Format human-readable. |
| `status` | `ENUM` | **NO** | Status state machine tiket (`DRAFT`, `SUBMITTED`, `IN_REVIEW`, `VERIFIED`, `REVISION_REQUIRED`, `APPROVED`, `REJECTED`). | `SUBMITTED` | None (New Field) | `SubmissionTicket` | Operational / State Machine Engine. |
| `submission_notes`| `TEXT` | YES | Catatan penjelasan operator mengenai alasan perubahan data. | `Penataan biro baru sesuai Perpres 139/2024` | None (New Field) | `SubmissionTicket` | Business Metadata. |
| `legal_doc_path`| `VARCHAR(255)`| YES | Lokasi penyimpanan berkas PDF dasar hukum regulasi. | `/uploads/legal_docs/perpres_139_2024.pdf` | None (New Field) | `SubmissionTicket` | **PROPOSED FIELD** (REQ-021). Lampiran hukum. |
| `audit_log_id` | `BIGSERIAL` | **NO** | Identifier sekuensial tak terhapuskan pada tabel audit. | `10042` | None (New Field) | `AuditLog` | **PROPOSED FIELD** (REQ-014). Immutable Log. |
| `action_type` | `VARCHAR(50)` | **NO** | Jenis aksi mutasi (`CREATE`, `UPDATE`, `DELETE`, `SUBMIT`, `VERIFY`, `APPROVE`). | `APPROVE` | None (New Field) | `AuditLog` | Governance / Audit Trail. |
| `old_values` | `JSONB` | YES | Snapshot data sebelum terjadi perubahan. | `{"unitName": "Biro Lama"}` | None (New Field) | `AuditLog` | Governance (Snapshot Forensik). GIN Index. |
| `new_values` | `JSONB` | YES | Snapshot data baru setelah perubahan diterapkan. | `{"unitName": "Biro Perencanaan"}` | None (New Field) | `AuditLog` | Governance (Snapshot Forensik). GIN Index. |
| `ip_address` | `INET` | YES | Alamat IP pengguna saat melakukan aksi mutasi. | `10.20.30.40` | None (New Field) | `AuditLog` | Security / Network Audit. |
