# 08. TARGET POSTGRESQL DATA MODEL BLUEPRINT: SIGMA-K

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Engine Target:** PostgreSQL 16+  
> **Author:** Senior Database Architect & Lead Full-Stack Engineer  
> **Catatan Penting:** Dokumen ini merupakan **Cetak Biru Desain Skema Basis Data Relasional Logis & Fisik**. **TIDAK** ada pembuatan file migration fisik atau eksekusi DDL database pada tahap ini.

---

## 1. Strategi Identifier & Tipe Data PostgreSQL

1. **UUIDv4 Strategy (Master & Transactional Entities):**
   - Digunakan pada `institutions`, `cabinets`, `organization_units`, `submission_tickets`, dan `users`.
   - *Rasional:* Mencegah serangan *ID Enumeration / BOLA*, memudahkan replikasi data multi-node, dan mendukung pembentukan ID deterministik pada client sebelum insert.
2. **BIGSERIAL Strategy (Audit Log Entity):**
   - Digunakan pada `audit_logs`.
   - *Rasional:* Memaksimalkan throughput tulis (*write throughput*) dan menjaga urutan kronologis kejadian secara sekuensial alami tanpa overhead pengurutan UUID.
3. **TIMESTAMPTZ (Timestamp with Time Zone):**
   - Seluruh kolom waktu menggunakan `TIMESTAMP WITH TIME ZONE` berstandar UTC untuk mengakomodasi 3 zona waktu Indonesia (WIB, WITA, WIT) secara akurat.
4. **JSONB with GIN Indexing:**
   - Digunakan pada `old_values`, `new_values`, dan `submission_items.payload_after` untuk komparasi delta data dan pencarian forensik instan.

---

## 2. Spesifikasi Skema Tabel Target PostgreSQL

### A. Tabel: `institutions` (Master Instansi)
| Column Name | PostgreSQL Type | PK | FK | UK | Nullable | Default | Constraints & Index | Rationale |
|---|---|:---:|:---:|:---:|:---:|---|---|---|
| `id` | `UUID` | **PK** | - | - | **NO** | `gen_random_uuid()` | B-Tree PK Index | Identifier unik global instansi. |
| `code` | `VARCHAR(50)` | - | - | **UK** | **NO** | - | `UNIQUE`, Check alphanumeric | Kode registrasi nasional resmi. |
| `name` | `VARCHAR(255)` | - | - | - | **NO** | - | B-Tree & GIN Trigram Index | Nama resmi kementerian/pemda. |
| `short_name` | `VARCHAR(50)` | - | - | - | YES | `NULL` | - | Singkatan / akronim resmi. |
| `institution_type_id` | `INT` | - | `FK` | - | **NO** | - | `REFERENCES institution_types(id)` | Relasi ke tipe instansi. |
| `region_id` | `INT` | - | `FK` | - | YES | `NULL` | `REFERENCES regions(id)` | Relasi wilayah (Pemda/Vertikal). |
| `status` | `VARCHAR(20)` | - | - | - | **NO** | `'ACTIVE'` | Check (`ACTIVE`, `INACTIVE`) | Status operasional instansi. |
| `created_at` | `TIMESTAMPTZ` | - | - | - | **NO** | `NOW()` | - | Audit waktu pembuatan. |
| `updated_at` | `TIMESTAMPTZ` | - | - | - | **NO** | `NOW()` | - | Audit waktu pengubahan. |
| `deleted_at` | `TIMESTAMPTZ` | - | - | - | YES | `NULL` | Partial Index `WHERE deleted_at IS NULL` | Dukungan soft delete (REQ-023). |

---

### B. Tabel: `cabinets`, `cabinet_periods`, `cabinet_memberships` (Kabinet & Keanggotaan)
| Table & Column | PostgreSQL Type | PK | FK | UK | Nullable | Constraints & Index | Rationale |
|---|---|:---:|:---:|:---:|:---:|---|---|
| **`cabinets.id`** | `UUID` | **PK** | - | - | **NO** | `gen_random_uuid()` | ID unik master kabinet. |
| **`cabinets.name`** | `VARCHAR(100)` | - | - | **UK** | **NO** | `UNIQUE` (misal 'Kabinet Merah Putih') | Nama era kabinet kepresidenan. |
| **`cabinets.president_name`** | `VARCHAR(100)` | - | - | - | **NO** | - | Nama Presiden Republik Indonesia. |
| **`cabinets.is_active`** | `BOOLEAN` | - | - | - | **NO** | Unique Partial Index `WHERE is_active = TRUE` | Penanda 1 kabinet aktif (BRULE-003). |
| **`cabinet_periods.id`** | `UUID` | **PK** | - | - | **NO** | `gen_random_uuid()` | ID unik periode kabinet. |
| **`cabinet_periods.cabinet_id`** | `UUID` | - | `FK` | - | **NO** | `REFERENCES cabinets(id) ON DELETE CASCADE` | Relasi ke master kabinet. |
| **`cabinet_periods.start_date`** | `DATE` | - | - | - | **NO** | Check `end_date IS NULL OR end_date >= start_date` | Validasi rentang temporal (DQ-006). |
| **`cabinet_periods.end_date`** | `DATE` | - | - | - | YES | - | Tanggal akhir masa jabatan. |
| **`cabinet_memberships.id`** | `UUID` | **PK** | - | - | **NO** | `gen_random_uuid()` | ID unik keanggotaan K/L. |
| **`cabinet_memberships.cabinet_period_id`** | `UUID` | - | `FK` | - | **NO** | `REFERENCES cabinet_periods(id) ON DELETE CASCADE` | Relasi ke periode kabinet. |
| **`cabinet_memberships.institution_id`** | `UUID` | - | `FK` | - | **NO** | `REFERENCES institutions(id) ON DELETE RESTRICT` | Relasi ke instansi kementerian. |
| **`cabinet_memberships.category`** | `VARCHAR(30)` | - | - | - | **NO** | Check (`KEMENKO`, `TEKNIS`, `LPNK`, `LNS`) | Kategori kementerian kabinet. |
| **`cabinet_memberships` (Composite UK)**| - | - | - | **UK** | - | `UNIQUE(cabinet_period_id, institution_id)` | Mencegah duplikasi K/L (DQ-005). |

---

### C. Tabel: `organization_units` (Bagan Struktur Organisasi Hierarkis)
| Column Name | PostgreSQL Type | PK | FK | UK | Nullable | Default | Constraints & Index | Rationale |
|---|---|:---:|:---:|:---:|:---:|---|---|---|
| `id` | `UUID` | **PK** | - | - | **NO** | `gen_random_uuid()` | B-Tree PK Index | ID unik unit kerja. |
| `institution_id` | `UUID` | - | `FK` | - | **NO** | - | `REFERENCES institutions(id) ON DELETE CASCADE` | Instansi pemilik unit kerja. |
| `parent_id` | `UUID` | - | `FK` | - | YES | `NULL` | `REFERENCES organization_units(id) ON DELETE RESTRICT` | Relasi hierarkis Adjacency List. |
| `unit_code` | `VARCHAR(50)` | - | - | - | YES | `NULL` | - | Kode unit organisasi. |
| `unit_name` | `VARCHAR(255)` | - | - | - | **NO** | - | B-Tree Index | Nama unit eselon/biro/direktorat. |
| `echelon_level_id` | `INT` | - | `FK` | - | YES | `NULL` | `REFERENCES position_levels(id)` | Tingkat eselon jabatan unit. |
| `hierarchy_level` | `INT` | - | - | - | **NO** | `1` | Check `hierarchy_level >= 1` | Tingkat kedalaman pohon. |
| `sort_order` | `INT` | - | - | - | **NO** | `0` | - | Urutan horizontal bagan. |
| `is_active` | `BOOLEAN` | - | - | - | **NO** | `TRUE` | - | Status keaktifan unit kerja. |
| `deleted_at` | `TIMESTAMPTZ` | - | - | - | YES | `NULL` | Partial Index `WHERE deleted_at IS NULL` | Soft delete unit kerja. |

---

### D. Tabel: `audit_logs` (Log Kepatuhan Tak Terhapuskan)
| Column Name | PostgreSQL Type | PK | FK | UK | Nullable | Default | Constraints & Index | Rationale |
|---|---|:---:|:---:|:---:|:---:|---|---|---|
| `id` | `BIGSERIAL` | **PK** | - | - | **NO** | Auto Increment | Sequential PK Index | Urutan log permanen. |
| `user_id` | `UUID` | - | `FK` | - | YES | `NULL` | `REFERENCES users(id) ON DELETE SET NULL` | Aktor penanggung jawab mutasi. |
| `action_type` | `VARCHAR(50)` | - | - | - | **NO** | - | B-Tree Index | Jenis aksi (`CREATE`, `UPDATE`, `APPROVE`). |
| `entity_name` | `VARCHAR(100)` | - | - | - | **NO** | - | B-Tree Index | Nama entitas yang dimutasi. |
| `entity_id` | `VARCHAR(100)` | - | - | - | **NO** | - | B-Tree Index | ID entitas target mutasi. |
| `old_values` | `JSONB` | - | - | - | YES | `NULL` | GIN Index `USING GIN (old_values)` | Snapshot nilai sebelum diubah. |
| `new_values` | `JSONB` | - | - | - | YES | `NULL` | GIN Index `USING GIN (new_values)` | Snapshot nilai baru hasil mutasi. |
| `ip_address` | `INET` | - | - | - | YES | `NULL` | - | Alamat IP jaringan pengguna. |
| `user_agent` | `VARCHAR(255)` | - | - | - | YES | `NULL` | - | Peramban client pengguna. |
| `created_at` | `TIMESTAMPTZ` | - | - | - | **NO** | `NOW()` | Range Partitioning by `created_at` | Waktu kejadian presisi ms. |
