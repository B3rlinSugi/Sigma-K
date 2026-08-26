# E-SKLD BACKEND DOCUMENTATION — STEP 4
## MASTER ORGANIZATIONAL STRUCTURE & POSITIONS

---

## 1. Objective

Step 4 mengimplementasikan domain struktur organisasi master (*Organizational Units*) dan master jabatan (*Positions*) sebagai fondasi data kelembagaan (*baseline structure*) yang akan dikonsumsi oleh modul penataan kelembagaan (*Submissions*) di tahap berikutnya.

---

## 2. API Endpoints Created

| Method | Endpoint | Filter | Deskripsi |
|---|---|:---:|---|
| `GET` | `/api/v1/institutions/{id}/units` | `auth` | Mengambil seluruh struktur pohon unit organisasi untuk instansi target terotentikasi. |
| `GET` | `/api/v1/units/{id}` | `auth` | Mengambil detail unit organisasi, informasi induk (*parent*), anak (*children*), dan jabatan (*positions*). |
| `GET` | `/api/v1/units/{id}/positions` | `auth` | Mengambil daftar jabatan (*positions*) dalam unit organisasi tertentu. |
| `GET` | `/api/v1/positions/{id}` | `auth` | Mengambil detail jabatan lengkap beserta asosiasi unit dan instansinya. |

---

## 3. Authorization & Scope Resolution

Seluruh endpoint Step 4 terintegrasi secara *Zero-Trust* dengan `AuthorizationService` dan `ScopeResolver`:
1. **Pemeriksaan Izin Dasar**: Pengguna harus memiliki izin `VIEW` pada instansi pemilik unit/jabatan.
2. **Hierarki 4-Tier Scope**:
   - `SUPER_ADMIN`: Wewenang global lintas seluruh instansi.
   - `USER`: Membaca unit/jabatan pada instansi asal (`home_institution_id`).
   - `ADMIN` & `VERIFIER`: Membaca unit/jabatan pada instansi yang terdaftar di `user_scopes` aktif.
   - `Access Grant`: Membaca unit/jabatan jika memiliki *access grant* aktif dengan izin `VIEW` pada instansi target.
3. **Pencegahan BOLA / IDOR**: Permintaan terhadap unit ID atau position ID milik instansi lain tanpa izin wewenang otomatis ditolak dengan `403 Forbidden`.

---

## 4. Organizational Hierarchy & Anti-Cycle Algorithm

Pohon hierarki unit dibangun secara rekursif melalui [`OrgHierarchyService::buildHierarchyTree()`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/app/Services/OrgStructure/OrgHierarchyService.php) dengan mekanisme proteksi keamanan:
- **Anti-Self-Loop**: Jika data memiliki `parent_unit_id === id`, simpul tersebut otomatis diperlakukan sebagai simpul akar (*root*) untuk mencegah perulangan tak berujung (*infinite loop*).
- **Anti-Cycle DFS Traversal**: Traversal melacak himpunan leluhur (*ancestor path set*). Jika terdeteksi siklus tertutup ($A \rightarrow B \rightarrow C \rightarrow A$), tautan siklus diputus secara aman dan dicatat pada sistem log tanpa mengakibatkan *stack overflow* atau *crash*.
- **Pengurutan Deterministik**: Simpul diurutkan secara konsisten berdasarkan `order_index ASC, id ASC`.

---

## 5. Position (Jabatan) Domain Behavior

Tata kelola jabatan dikelola melalui [`PositionService`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/app/Services/OrgStructure/PositionService.php):
- Mengambil jabatan aktif berdasarkan `unit_id`.
- Memvalidasi kepemilikan instansi melalui unit induk (`positions -> organizational_units -> institutions`).
- Mengembalikan metadata eselon, jenis jabatan (`STRUKTURAL`, `JFT`, `JFU`), dan jumlah formasi.

---

## 6. Database Tables Consumed

1. `organizational_units` (Tabel fisik master unit organisasi)
2. `positions` (Tabel fisik master jabatan)
3. `institutions` (Tabel fisik master instansi)
4. `users` (Tabel fisik pengguna)
5. `user_scopes` (Tabel fisik wewenang regional)
6. `access_grants` & `access_grant_permissions` (Tabel fisik delegasi wewenang)
7. `permissions` & `role_permissions` (Tabel fisik RBAC)

---

## 7. Database Immutability Verification

- **Schema Changes**: **0 (None)**.
- **Table Alterations**: **0 (None)**.
- **RBAC Alterations**: **0 (None)**.
- Seluruh struktur 21 tabel MySQL 8.x InnoDB tetap utuh dan tidak tersentuh.

---

## 8. Test Coverage

- **Suite Uji Baru**: [`tests/unit/OrganizationalStructureTest.php`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/tests/unit/OrganizationalStructureTest.php) (13 tests: `UNIT-01` .. `UNIT-08`, `POS-01` .. `POS-05`).
- **Total Test Terakumulasi**: **55 tests, 180 assertions, 0 errors, 0 failures (100% PASS)**.
