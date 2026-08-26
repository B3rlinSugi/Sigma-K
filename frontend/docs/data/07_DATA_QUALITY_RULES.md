# 07. DATA QUALITY RULES & INTEGRITY SPECIFICATIONS: SIGMA-K

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Data Governance Architect & Lead Database Engineer  
> **Kolaborator:** Ikhsan (Data Analyst)  

Dokumen ini mendefinisikan aturan kualitas data formal (*Data Quality Rules*) yang wajib divalidasi oleh sistem pada tingkat basis data (*database constraints*), *application validation pipes*, dan pengujian migrasi data.

---

## 1. Matriks Aturan Kualitas Data (Data Quality Rules Register)

| Rule ID | Kategori Kualitas Data | Pernyataan Aturan Kualitas (*Rule Statement*) | Entitas Target | Tingkat Keparahan (*Severity*) | Mekanisme Penegakan (*Enforcement Mechanism*) |
|---|---|---|---|:---:|---|
| **DQ-001** | Uniqueness | Kode instansi pemerintah wajib unik secara nasional dan tidak boleh duplikat. | `Institution.code` | **CRITICAL** | `UNIQUE` Constraint di PostgreSQL & DTO Validator. |
| **DQ-002** | Completeness | Nama resmi instansi tidak boleh kosong (`NOT NULL`) dan panjang minimal 3 karakter. | `Institution.name` | **CRITICAL** | `NOT NULL` & Check Constraint `char_length(name) >= 3`. |
| **DQ-003** | Referential Integrity | Jenis instansi wajib mengacu pada ID master tipe instansi yang valid dan aktif. | `Institution.institution_type_id` | **CRITICAL** | Foreign Key Constraint `REFERENCES institution_types(id)`. |
| **DQ-004** | Hierarchy Integrity | Bagan struktur organisasi tidak boleh memiliki relasi melingkar (*Anti-Circular Guard*). | `OrganizationUnit.parent_id` | **CRITICAL** | DFS Cycle Detection di Service Layer + SQL CTE Check. |
| **DQ-005** | Deduplication | Instansi kementerian/lembaga hanya boleh terdaftar 1 kali pada periode kabinet yang sama. | `CabinetMembership` | **CRITICAL** | Composite `UNIQUE(cabinet_period_id, institution_id)`. |
| **DQ-006** | Temporal Validity | Tanggal berakhir periode kabinet tidak boleh lebih kecil dari tanggal mulai (`end_date >= start_date`). | `CabinetPeriod` | **CRITICAL** | Check Constraint `CHECK (end_date IS NULL OR end_date >= start_date)`. |
| **DQ-007** | Business Invariant | Tepat 1 (satu) kabinet yang boleh berstatus aktif utama (`is_active = TRUE`) pada satu waktu. | `Cabinet.is_active` | **HIGH** | Unique Partial Index `WHERE is_active = TRUE` di PostgreSQL. |
| **DQ-008** | Orphan Prevention | Unit kerja atasan (`parent_id`) wajib mengacu pada unit kerja yang masih aktif dan valid. | `OrganizationUnit.parent_id` | **HIGH** | Foreign Key Constraint dengan `ON DELETE RESTRICT`. |
| **DQ-009** | Compliance | Pengajuan tiket perubahan data wajib menyertakan minimal 1 berkas regulasi dasar hukum (PDF). | `SubmissionTicket.legal_doc_path` | **HIGH** | Business Rule Validation pada State Machine Submission. |
| **DQ-010** | Accuracy | Butir tugas dan fungsi wajib mencantumkan pasal/ayat rujukan regulasi hukum. | `TugasFungsi.legal_article_reference` | **HIGH** | DTO Validation & Mandatory Form Check. |
| **DQ-011** | Security / Scoping | Operator instansi hanya boleh membuat draf perubahan untuk instansi yang diikatkan ke akunnya. | `UserScope.institution_id` | **CRITICAL** | Institution Scope Middleware Guard (BOLA Defense). |
| **DQ-012** | Immutability | Log audit tidak boleh diubah (`UPDATE`) atau dihapus (`DELETE`) oleh pengguna mana pun. | `AuditLog` | **CRITICAL** | PostgreSQL Table Rule / Revoke UPDATE, DELETE on `audit_logs`. |
| **DQ-013** | Format Consistency | Format email resmi instansi wajib menggunakan domain pemerintah (.go.id). | `InstitutionProfile.email` | **MEDIUM** | Regex Check Constraint `CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.go\.id$')`. |
| **DQ-014** | Referential Retention | Penghapusan master instansi dan unit organisasi wajib menggunakan soft delete. | `Institution.deleted_at` | **HIGH** | Application ORM Soft-Delete Filter. |
| **DQ-015** | Lineage Validity | Silsilah pemecahan kementerian (*lineage*) wajib memiliki predecessor dan successor yang valid. | `InstitutionLineage` | **HIGH** | Foreign Key Constraints ke tabel `institutions`. |

---

## 2. Klasifikasi Keparahan Kualitas Data (*Severity Breakdown*)
- **CRITICAL (7 Rules):** Pelanggaran aturan ini menyebabkan sistem menolak transaksi secara otomatis (*Fail-Closed*) dan mengembalikan error HTTP 400/409/422.
- **HIGH (6 Rules):** Pelanggaran aturan ini memblokir pengajuan tiket ke tahap verifikasi hingga data diperbaiki oleh user.
- **MEDIUM (2 Rules):** Menghasilkan peringatan validasi (*warning prompt*) pada form draf operator instansi.
