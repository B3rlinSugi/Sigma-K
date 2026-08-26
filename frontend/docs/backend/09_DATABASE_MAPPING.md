# SIGMA-K — DOMAIN MODEL TO POSTGRESQL DATABASE MAPPING

> **Dokumen:** `09_DATABASE_MAPPING.md`  
> **Status:** `DATABASE MAPPING SPECIFICATION (PHASE 5A DESIGN - REVIEWED)`  
> **Referensi Sumber Kebenaran:** Dokumen Data Architecture Phase 3 (`docs/data/08_TARGET_POSTGRESQL_MODEL.md`)  
> **Catatan Fase:** Dokumen ini merupakan pemetaan konseptual. **TIDAK ADA** eksekusi migrasi Prisma/SQL pada fase ini.

---

## 1. Matriks Pemetaan Entitas Domain ke Tabel Relasional PostgreSQL

| Entitas Domain | Target Tabel PostgreSQL | Primary Key (PK) | Foreign Keys (FK) Kunci | Indeks Strategis | Aturan Integritas & Constraints |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`Institution`** | `institutions` | `id` (UUID) | `type_id` $\rightarrow$ `institution_types(id)`<br>`region_id` $\rightarrow$ `regions(id)` | `idx_inst_code` (UNIQUE), `idx_inst_type`, `idx_inst_status` | `code` UNIQUE, `name` NOT NULL |
| **`InstitutionProfile`** | `institution_profiles`| `id` (UUID) | `institution_id` $\rightarrow$ `institutions(id)` (1:1) | `idx_inst_prof_inst_id` (UNIQUE) | `ON DELETE CASCADE` |
| **`InstitutionType`** | `institution_types` | `id` (INT) | - | `idx_inst_type_code` (UNIQUE) | Master referensi jenis instansi |
| **`Region`** | `regions` | `id` (VARCHAR) | `parent_id` $\rightarrow$ `regions(id)` | `idx_reg_code` (UNIQUE) | Master kode wilayah Kemendagri |
| **`Cabinet`** | `cabinets` | `id` (UUID) | - | `idx_cab_name`, `idx_cab_status` | `name` NOT NULL |
| **`CabinetPeriod`** | `cabinet_periods` | `id` (UUID) | `cabinet_id` $\rightarrow$ `cabinets(id)` | `idx_cab_per_cab_id`, `idx_cab_per_dates` | `start_date` NOT NULL |
| **`CabinetMembership`**| `cabinet_memberships`| `id` (UUID) | `cabinet_period_id` $\rightarrow$ `cabinet_periods(id)`<br>`institution_id` $\rightarrow$ `institutions(id)` | `idx_cab_mem_period_inst` (UNIQUE) | Cegah duplikasi instansi dalam satu periode kabinet |
| **`InstitutionLineage`**| `institution_lineage` | `id` (UUID) | `predecessor_id` $\rightarrow$ `institutions(id)`<br>`successor_id` $\rightarrow$ `institutions(id)`<br>`cabinet_period_id` $\rightarrow$ `cabinet_periods(id)` | `idx_lin_pred_succ`, `idx_lin_type` | Rekam silsilah pemecahan/penggabungan kementerian |
| **`CabinetComparisonSummary`** | `[READ MODEL]` | N/A | N/A (Dihitung dari relasi `cabinet_memberships` & `institution_lineage`) | N/A | *Aggregated Read Model* |
| **`OrganizationUnit`** | `organization_units` | `id` (UUID) | `institution_id` $\rightarrow$ `institutions(id)`<br>`parent_id` $\rightarrow$ `organization_units(id)`<br>`echelon_id` $\rightarrow$ `echelon_levels(id)` | `idx_org_inst_id`, `idx_org_parent_id`, `idx_org_code` | Adjacency List, Self-referencing FK, Anti-Cycle Constraint |
| **`EchelonLevel`** | `echelon_levels` | `id` (INT) | - | `idx_ech_code` (UNIQUE), `idx_ech_rank` | Master tingkatan eselon jabatan |
| **`TupoksiItem`** | `tupoksi_items` | `id` (UUID) | `institution_id` $\rightarrow$ `institutions(id)`<br>`unit_id` $\rightarrow$ `organization_units(id)` | `idx_tup_inst_unit`, `idx_tup_type` | `type` IN ('DUTY', 'FUNCTION') |
| **`SubmissionTicket`** | `submission_tickets` | `id` (UUID) | `institution_id` $\rightarrow$ `institutions(id)`<br>`submitter_user_id` $\rightarrow$ `users(id)`<br>`approved_by_user_id` $\rightarrow$ `users(id)` | `idx_sub_ticket_no` (UNIQUE), `idx_sub_status`, `idx_sub_inst` | `ticket_number` UNIQUE (Format `TKT-YYYYMMDD-XXXX`) |
| **`SubmissionItem`** | `submission_items` | `id` (UUID) | `submission_ticket_id` $\rightarrow$ `submission_tickets(id)` | `idx_sub_item_ticket_id` | Kolom `payload_before` & `payload_after` bertipe `JSONB` |
| **`SubmissionRevision`**| `submission_revisions`| `id` (UUID) | `submission_ticket_id` $\rightarrow$ `submission_tickets(id)` | `idx_sub_rev_ticket_id` | Catat nomor iterasi revisi & tanggapan |
| **`VerificationLog`** | `verification_logs` | `id` (UUID) | `submission_ticket_id` $\rightarrow$ `submission_tickets(id)`<br>`verifier_user_id` $\rightarrow$ `users(id)` | `idx_ver_log_ticket`, `idx_ver_log_date` | `decision` IN ('PASS', 'REVISION_REQUIRED', 'REJECT') |
| **`Notification`** | `notifications` | `id` (UUID) | `user_id` $\rightarrow$ `users(id)` | `idx_notif_user_read`, `idx_notif_date` | `is_read` DEFAULT FALSE |
| **`KPICandidate` / `AnalyticsMetric`**| `[READ MODEL]` | N/A | N/A (Dihitung dari kueri agregasi OLAP / Materialized View) | N/A | *Aggregated Read Model* |
| **`AuditLog`** | `audit_logs` | `id` (UUID) | `actor_user_id` $\rightarrow$ `users(id)` | `idx_aud_entity_id`, `idx_aud_date`, `idx_aud_action`, `idx_aud_payload_gin` (GIN Index pada JSONB) | Immutable Append-Only, No UPDATE/DELETE |
| **`User`** | `users` | `id` (UUID) | `institution_id` $\rightarrow$ `institutions(id)` (Opsional) | `idx_usr_username` (UNIQUE), `idx_usr_email` (UNIQUE), `idx_usr_nip` (UNIQUE) | Otentikasi & Scope Instansi |
| **`Role`** | `roles` | `id` (INT) | - | `idx_rol_name` (UNIQUE) | Master peran RBAC |

---

## 2. Strategi Integritas JSONB & GIN Indexing

1. **Snapshot Audit & Submissions:** Kolom `payload_before`, `payload_after`, dan `metadata` di tabel `audit_logs` dan `submission_items` menggunakan tipe data native PostgreSQL `JSONB`.
2. **Kueri Pencarian Cepat GIN:** Indeks `CREATE INDEX idx_aud_payload_gin ON audit_logs USING GIN (new_values jsonb_path_ops);` diterapkan untuk memungkinkan pencarian kata kunci di dalam riwayat forensik dengan latensi sub-10 milidetik.
3. **Pemisahan Read-Model OLAP:** Entitas komparasi kabinet (`CabinetComparisonSummary`) dan indikator intelijensi (`KPICandidate`) tidak memerlukan tabel tersendiri melainkan diagregasikan secara *on-the-fly* atau melalui *Materialized View* untuk menjaga normalisasi data relasional.
