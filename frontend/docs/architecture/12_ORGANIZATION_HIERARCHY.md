# 12. ORGANIZATION HIERARCHY ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Database Architect & Software Engineer  
> **Kebutuhan Terkait:** [REQ-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-020](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-ORG-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [BRULE-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)  

Dokumen ini mengevaluasi dan merancang model data hierarkis (*Hierarchical Tree Data Modeling*) untuk merepresentasikan bagan struktur organisasi kementerian/lembaga yang berjenjang.

---

## 1. Evaluasi Komparatif Model Struktur Pohon (Tree Models Comparison)

| Kriteria Evaluasi | 1. Adjacency List (`parent_id`) | 2. Materialized Path (`/1/4/12/`) | 3. Nested Set (`lft`, `rgt`) | 4. Closure Table (Bridge Table) |
|---|---|---|---|---|
| **Kemudahan Insert Node Baru** | **Sangat Cepat ($O(1)$)** | Cepat ($O(1)$) | Sangat Lambat ($O(N)$ re-index) | Sedang ($O(D)$ insert rows) |
| **Kemudahan Pemindahan Unit (Re-parenting)** | **Sangat Cepat ($O(1)$ update `parent_id`)** | Lambat ($O(N)$ rewrite prefix) | Sangat Buruk (Kunci seluruh tabel) | Sedang ($O(D)$ delete & insert) |
| **Kemudahan Soft Delete Unit** | **Sederhana ($O(1)$)** | Sederhana | Sangat Kompleks | Sedang |
| **Query Mengambil Seluruh Pohon Sub-unit** | Cepat via Recursive CTE ($O(\log N)$) | Cepat via `LIKE 'path%'` | Sangat Cepat ($O(1)$ range query) | Sangat Cepat (Join bridge) |
| **Penelusuran Leluhur (Ancestor Query)** | Cepat via Recursive CTE | Sangat Cepat (Parse string) | Cepat ($O(1)$ range query) | Sangat Cepat |
| **Pencegahan Data Corruption / Integrity** | **Tinggi (Foreign Key Constraint)** | Rentang string rawan typo | Rentan jika tree tidak seimbang | Tinggi |
| **Kesesuaian dengan Dinamika Restrukturisasi** | **SANGAT TINGGI** | Sedang | SANGAT RENDAH | Tinggi |

---

## 2. Pemilihan Strategi: Adjacency List + PostgreSQL Recursive CTE

### Mengapa Adjacency List Terpilih?
1. **Dinamika Restrukturisasi Birokrasi Indonesia:** Pada masa transisi kabinet, pemindahan unit kerja (*re-parenting* Biro/Direktorat dari satu kementerian ke kementerian lain) sangat sering terjadi. Adjacency List memungkinkan pemindahan unit hanya dengan 1 baris operasi `UPDATE organization_units SET parent_id = :newParentId WHERE id = :targetUnitId` ($O(1)$ complexity).
2. **Kekuatan Recursive CTE di PostgreSQL:** PostgreSQL 16 memiliki query planner yang sangat optimal untuk mengeksekusi *Common Table Expressions (WITH RECURSIVE)*, mampu mengambil 1.000+ node unit kerja kementerian dalam waktu $< 5$ milidetik.
3. **Kompatibilitas Penuh dengan Data Legacy:** Sistem legacy `tbl_ref_instansi_org` sudah menggunakan konsep `parent_id`, sehingga proses migrasi data bersih berjalan mulus tanpa kalkulasi ulang batas nested set yang berisiko rusak.

---

## 3. Algoritma Deteksi Siklus (Anti-Circular Dependency Guard)

Sesuai [REQ-020](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md) dan [BRULE-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md), backend menerapkan algoritma validasi sebelum pemindahan unit kerja:

```
[ AKSI: USER INGIN PINDAHKAN UNIT X KE BAWAH UNIT Y ]
                          │
                          ▼
+-----------------------------------------------------------------------------------+
| 1. Periksa Trivial Self-Parenting: Apakah X == Y?                                 |
|    - Jika Ya: TOLAK DENGAN HTTP 409 CONFLICT ("Unit tidak bisa menjadi atasan diri sendiri"). |
+-----------------------------------------------------------------------------------+
                          │ (Jika Tidak)
                          ▼
+-----------------------------------------------------------------------------------+
| 2. Telusuri Leluhur (Ancestor Traversal):                                         |
|    - Ambil seluruh ancestor_id dari Unit Y ke atas hingga Root Unit.              |
|    - Apakah ID Unit X terdapat dalam daftar ancestor Unit Y?                      |
|    - Jika Ya: TOLAK DENGAN HTTP 409 CONFLICT ("Circular Dependency Terdeteksi:    |
|               Unit X adalah atasan dari Unit Y, Y tidak boleh menjadi atasan X"). |
+-----------------------------------------------------------------------------------+
                          │ (Jika Lolos Validasi)
                          ▼
[ EXECUTE UPDATE parent_id PADA DATABASE ]
```

---

## 4. Pola Query Recursive CTE di PostgreSQL (Blueprint)

```sql
-- Mengambil seluruh bagan hierarki unit kerja dari kementerian tertentu beserta level kedalaman
WITH RECURSIVE org_tree AS (
    -- Anchor member: Unit Pimpinan Tertinggi (parent_id IS NULL)
    SELECT 
        id, institution_id, parent_id, unit_name, echelon_level_id, 1 AS depth,
        ARRAY[id] AS path
    FROM organization_units
    WHERE institution_id = :institutionId AND parent_id IS NULL AND deleted_at IS NULL

    UNION ALL

    -- Recursive member: Mengambil anak unit di bawahnya
    SELECT 
        child.id, child.institution_id, child.parent_id, child.unit_name, child.echelon_level_id, parent.depth + 1,
        parent.path || child.id
    FROM organization_units child
    JOIN org_tree parent ON child.parent_id = parent.id
    WHERE child.deleted_at IS NULL AND NOT child.id = ANY(parent.path) -- Siklus proteksi tambahan di level SQL
)
SELECT * FROM org_tree ORDER BY depth, unit_name;
```
