# 06. DATABASE ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Database Architect & Principal Engineer  
> **Target Database Engine:** PostgreSQL 16+  
> **Peringatan Keras:** Dokumen ini mendefinisikan **arsitektur data konseptual dan logis**. **TIDAK** ada pembuatan DDL fisik, eksekusi migration, atau pembuatan database pada tahap ini.

---

## 1. Taksonomi & Segmentasi Skema Data

Arsitektur database SIGMA-K membagi data menjadi 5 domain relasional yang terisolasi secara logis:

```
+-----------------------------------------------------------------------------------+
|                        DATABASE DOMAIN SEGMENTATION (PostgreSQL)                  |
+-----------------------------------------------------------------------------------+
| 1. MASTER SCHEMA: institutions, organization_units, tugas_fungsi, positions       |
| 2. CABINET & HISTORY SCHEMA: cabinets, cabinet_periods, cabinet_memberships,      |
|                              institution_lineages                                 |
| 3. WORKFLOW & STAGING SCHEMA: submission_tickets, submission_items,               |
|                               verification_logs, draft_workspaces                 |
| 4. SECURITY & IAM SCHEMA: users, roles, permissions, role_permissions,            |
|                           user_institution_scopes, notifications                  |
| 5. AUDIT & ANALYTICS SCHEMA: audit_logs (partitioned), asn_posture_snapshots,     |
|                              analytics_aggregates                                 |
+-----------------------------------------------------------------------------------+
```

---

## 2. Isolasi Master Data Aktif vs Data Draf Kerja

Untuk menjamin master data aktif tidak terkontaminasi oleh perubahan yang belum disetujui, arsitektur database menerapkan **Staging & Delta Payload Pattern**:

```
+--------------------------+          Submit Tiket          +---------------------------+
|   DRAFT STAGING TABLES   | ---------------------------->  |   SUBMISSION BUNDLE       |
| (Perubahan belum sah)    |                                | - submission_tickets      |
+--------------------------+                                | - submission_items (JSONB)|
                                                                        |
                                                                        | Approve by Admin
                                                                        v (Atomic Transaction)
+--------------------------+                                +---------------------------+
|   AUDIT LOGS (JSONB)     | <----------------------------- |   LIVE MASTER DATA        |
| (Snapshot histori mutasi)|                                | (Tabel Master Resmi Aktif)|
+--------------------------+                                +---------------------------+
```

1. **Live Master Tables (`institutions`, `organization_units`, `tugas_fungsi`):** Hanya menyimpan data yang berstatus resmi, terverifikasi, dan aktif.
2. **Draft / Staging (`submission_items`):** Menyimpan delta usulan perubahan dalam bentuk terstruktur (`payload_before_json` dan `payload_after_json`) berformat PostgreSQL `JSONB`.
3. **Atomic Commit on Approval:** Saat tiket disetujui Admin, Application Layer membaca delta `payload_after_json` dan menerapkan pembaruan ke Live Master Tables dalam satu transaksi ACID tunggal.

---

## 3. Desain Model Entitas Logis (Logical Entity Relationships)

### A. Domain Master Instansi & Profil
- **`institutions`:** `id (UUID PK)`, `code (VARCHAR UK)`, `name (VARCHAR)`, `short_name (VARCHAR)`, `institution_type_id (FK)`, `region_id (FK)`, `status (ENUM)`, `created_at`, `updated_at`, `deleted_at (Soft Delete)`.
- **`institution_profiles`:** `id (UUID PK)`, `institution_id (FK UK 1:1)`, `address (TEXT)`, `phone (VARCHAR)`, `email (VARCHAR)`, `website_url (VARCHAR)`, `logo_path (VARCHAR)`, `vision_statement (TEXT)`, `mission_statement (TEXT)`, `legal_basis_summary (TEXT)`.
- **`institution_types`:** `id (INT PK)`, `code (VARCHAR UK)`, `name (VARCHAR)`.
- **`regions`:** `id (INT PK)`, `code (VARCHAR UK)`, `name (VARCHAR)`, `level (ENUM: PROVINSI/KABUPATEN/KOTA)`.

### B. Domain Kabinet & Histori Kelembagaan
- **`cabinets`:** `id (UUID PK)`, `name (VARCHAR UK, misal 'Kabinet Merah Putih')`, `president_name (VARCHAR)`, `vice_president_name (VARCHAR)`, `description (TEXT)`, `is_active (BOOLEAN default FALSE)`, `created_at`.
- **`cabinet_periods`:** `id (UUID PK)`, `cabinet_id (FK)`, `start_date (DATE)`, `end_date (DATE Nullable)`, `legal_decree_number (VARCHAR)`, `status (ENUM: DRAFT/ACTIVE/COMPLETED/ARCHIVED)`.
- **`cabinet_memberships`:** `id (UUID PK)`, `cabinet_period_id (FK)`, `institution_id (FK)`, `category (ENUM: KEMENKO/KEMENTERIAN_TEKNIS/LPNK/LNS)`, `joined_date (DATE)`, `ended_date (DATE Nullable)`, `is_active (BOOLEAN)`.  
  *Constraint:* `UNIQUE(cabinet_period_id, institution_id)`.
- **`institution_lineages`:** `id (UUID PK)`, `predecessor_institution_id (FK)`, `successor_institution_id (FK)`, `transition_type (ENUM: SPLIT/MERGE/RENAME/NEW/DISSOLVED)`, `cabinet_period_id (FK)`, `notes (TEXT)`, `effective_date (DATE)`.

### C. Domain Struktur Organisasi & Tugas-Fungsi
- **`organization_units`:** `id (UUID PK)`, `institution_id (FK)`, `parent_id (FK Self-referencing Nullable)`, `unit_code (VARCHAR)`, `unit_name (VARCHAR)`, `echelon_level_id (FK)`, `hierarchy_level (INT)`, `sort_order (INT)`, `is_active (BOOLEAN)`, `created_at`, `deleted_at`.
- **`position_levels` (Eselon):** `id (INT PK)`, `code (VARCHAR UK)`, `name (VARCHAR, misal 'Eselon I.a', 'Jabatan Fungsional')`, `rank_order (INT)`.
- **`tugas_fungsi`:** `id (UUID PK)`, `institution_id (FK)`, `organization_unit_id (FK Nullable)`, `type (ENUM: DUTY/FUNCTION)`, `content_text (TEXT)`, `legal_article_reference (VARCHAR)`, `sequence_number (INT)`, `created_at`.

### D. Domain Tata Kelola Workflow
- **`submission_tickets`:** `id (UUID PK)`, `ticket_number (VARCHAR UK)`, `institution_id (FK)`, `submitter_user_id (FK)`, `status (ENUM: DRAFT/SUBMITTED/IN_REVIEW/VERIFIED/REVISION_REQUIRED/APPROVED/REJECTED)`, `submission_notes (TEXT)`, `legal_doc_path (VARCHAR)`, `submitted_at`, `approved_at`, `approved_by_user_id (FK Nullable)`.
- **`submission_items`:** `id (UUID PK)`, `submission_ticket_id (FK)`, `target_entity_type (ENUM)`, `target_entity_id (UUID Nullable)`, `action_type (ENUM: CREATE/UPDATE/DELETE)`, `payload_before (JSONB)`, `payload_after (JSONB)`.
- **`verification_logs`:** `id (UUID PK)`, `submission_ticket_id (FK)`, `verifier_user_id (FK)`, `decision (ENUM: PASS/REVISION/REJECT)`, `notes (TEXT)`, `verified_at`.

### E. Domain Keamanan, Audit & Analitik
- **`users`:** `id (UUID PK)`, `username (VARCHAR UK)`, `email (VARCHAR UK)`, `password_hash (VARCHAR)`, `full_name (VARCHAR)`, `nip (VARCHAR)`, `institution_id (FK Scoped Nullable)`, `is_active (BOOLEAN)`, `last_login_at`.
- **`roles` & `permissions`:** Standar RBAC many-to-many.
- **`notifications`:** `id (UUID PK)`, `user_id (FK)`, `title (VARCHAR)`, `message (TEXT)`, `category (ENUM)`, `action_url (VARCHAR)`, `is_read (BOOLEAN default FALSE)`, `created_at`.
- **`audit_logs`:** `id (BIGSERIAL PK)`, `user_id (FK Nullable)`, `action_type (VARCHAR)`, `entity_name (VARCHAR)`, `entity_id (VARCHAR)`, `old_values (JSONB)`, `new_values (JSONB)`, `ip_address (INET)`, `user_agent (VARCHAR)`, `created_at (TIMESTAMP WITH TIME ZONE default NOW())`.

---

## 4. Strategi Indeks & Optimasi PostgreSQL

1. **B-Tree Composite Index:**
   - `idx_cabinet_membership (cabinet_period_id, institution_id)`
   - `idx_org_unit_parent (institution_id, parent_id, sort_order)`
   - `idx_submission_status (institution_id, status, submitted_at)`
2. **GIN Indexing pada JSONB:**
   - `idx_audit_new_values_gin ON audit_logs USING GIN (new_values)` (Memungkinkan query pencarian audit instan berdasarkan kunci atribut yang berubah).
   - `idx_submission_items_payload ON submission_items USING GIN (payload_after)`.
3. **Partitioning Strategy pada Audit Log:**
   - Tabel `audit_logs` dikonfigurasi menggunakan *Range Partitioning* per tahun/kuartal (`PARTITION BY RANGE (created_at)`), menjaga performa tulis tetap konstan seiring bertambahnya jutaan baris log.
