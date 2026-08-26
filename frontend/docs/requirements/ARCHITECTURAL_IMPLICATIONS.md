# ARCHITECTURAL IMPLICATIONS: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  
> **Peringatan:** Dokumen ini memetakan **implikasi arsitektural dari kebutuhan bisnis dan fungsional**. Dokumen ini **BUKAN** desain arsitektur final dan **TIDAK** memilih merek framework/teknologi secara prematur.

---

## 1. Matriks Implikasi Arsitektural Utama

```
+-----------------------------------------------------------------------------------+
|                        REQUIREMENT TO ARCHITECTURE MAPPING                        |
+-----------------------------------------------------------------------------------+
| 1. Realtime Notification         ---> Event-Driven Pipeline / Persistent Transport|
| 2. Historical Cabinet & Lineage  ---> Temporal / Bitemporal Relational Modeling   |
| 3. Large Institutional Dataset   ---> Indexing Strategy, Pagination, Partitioning |
| 4. Scoped RBAC & Draft Isolation ---> Multi-tenant Style Scoping & Staging Tables  |
| 5. Immutable Audit Trail         ---> Append-Only Event Sourcing / Change Logs    |
| 6. Executive Org Chart Rendering ---> Tree Graph Traversal & Client Canvas Memory |
| 7. Legacy Data Migration (eskld) ---> Dedicated ETL Pipeline & Cleansing Step     |
| 8. Scalable Containerization     ---> Decoupled Modular Micro-modular Services    |
+-----------------------------------------------------------------------------------+
```

---

## 2. Analisis Implikasi Detail per Kebutuhan

### 1. Realtime Notification Capability
- **Requirement:** [REQ-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-NOT-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [BRULE-018](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md). Sistem wajib menyiarkan notifikasi seketika saat terjadi mutasi data.
- **Architectural Impact:**
  - Membutuhkan layer komunikasi persisten dua arah atau *unidirectional event stream* antara server dan peramban pengguna.
  - Memerlukan mekanisme Event Bus / Message Broker internal di backend untuk menangkap domain event mutasi dan menyiarkannya ke subscriber.
- **Potential Concern:**
  - Protokol WebSocket memerlukan port terbuka dan stateful connection management; beberapa lingkungan server kementerian memiliki pembatasan firewall proxy atau load balancer kaku.
  - Server-Sent Events (SSE) lebih bersahabat dengan proxy HTTP standar namun bersifat satu arah (*unidirectional*).
- **Decision Needed (Phase 2):**
  - Menentukan transport realtime utama (WebSocket vs SSE) dan mengevaluasi kebutuhan Redis Pub/Sub untuk penskalaan multi-instance.

---

### 2. Historical Cabinet & Institutional Lineage
- **Requirement:** [REQ-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [DEC-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md). Pengelolaan komposisi kabinet multi-periode dan pencatatan pemecahan/penggabungan kementerian antar-kabinet.
- **Architectural Impact:**
  - Merombak total skema legacy `data_kl.list_id_kl` menjadi entitas relasional ternormalisasi (`Cabinet`, `CabinetPeriod`, `CabinetMembership`).
  - Membutuhkan tabel relasi silsilah (*lineage graph / self-referencing many-to-many*) `InstitutionLineage` (`predecessor_id`, `successor_id`, `transition_type`).
- **Potential Concern:**
  - Query penelusuran sejarah kelembagaan yang melibatkan recursive CTE dapat melambat jika skema indeks tidak dioptimasi.
- **Decision Needed (Phase 2 & 3):**
  - Desain skema relational temporal dengan composite indexing pada `(cabinet_period_id, institution_id)` dan strategi query komparasi delta.

---

### 3. Tree Hierarchy & Large Organization Structure
- **Requirement:** [REQ-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-020](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-ORG-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md). Pengelolaan struktur organisasi berjenjang (parent-child) dan visualisasi interaktif anti-circular.
- **Architectural Impact:**
  - Model data pohon hierarkis berbasis Adjacency List (`parent_id`) dengan dukungan kalkulasi level hierarki dan *path enumeration* / recursive query.
  - Implementasi algoritma cycle detection pada backend layer sebelum menyimpan pemindahan atasan unit (`re-parenting`).
- **Potential Concern:**
  - Rendering puluhan ribu node pohon organisasi secara bersamaan pada client browser dapat menyebabkan memori leak atau UI freeze jika tidak menggunakan virtualisasi / on-demand lazy loading.
- **Decision Needed (Phase 2 & 4):**
  - Pemilihan library/metode rendering pohon org-chart di frontend (SVG/Canvas based dengan virtual DOM dan collapsible sub-tree).

---

### 4. Governed Workflow & Draft Staging Isolation
- **Requirement:** [REQ-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [BRULE-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md), [BRULE-017](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md). Pemisahan data draf operasional vs data master publik aktif, dengan alur Verifikasi dan Persetujuan Atomik.
- **Architectural Impact:**
  - Arsitektur data harus mendukung pola *Staging / Shadow Table* atau *JSON Versioned Payload* untuk menyimpan perubahan draf sebelum dipublikasikan.
  - Eksekusi persetujuan (*approval*) wajib dibungkus dalam Database ACID Transaction tunggal untuk mencegah inkonsistensi sebagian (*partial update*).
- **Potential Concern:**
  - Kompleksitas sinkronisasi jika master data mengalami pembaruan darurat sementara terdapat draf yang sedang mengambang (*stale draft conflict*).
- **Decision Needed (Phase 3):**
  - Memilih pola arsitektur draf: *Table Duplication (Staging Tables)* vs *Event/Payload Diff JSON (Submission Items)*.

---

### 5. Scoped RBAC & Multi-Tenant Style Data Scoping
- **Requirement:** [REQ-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [BRULE-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md), [BRULE-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md). Pembatasan akses berbasis peran (User/Admin/Verifikator) dan pembatasan scope instansi bagi Operator.
- **Architectural Impact:**
  - Middleware otorisasi di API gateway / backend controller yang menginjeksi filter `institution_id` secara otomatis pada setiap query mutasi data user.
  - Implementasi RBAC dengan izin granular (*fine-grained permissions*).
- **Potential Concern:**
  - Risiko kebocoran data jika ada endpoint pengubahan yang lupa menyertakan middleware verifikasi scope (*BOLA / IDOR vulnerability*).
- **Decision Needed (Phase 2):**
  - Menerapkan arsitektur Policy-Based Authorization terpusat di tingkat service/repository layer.

---

### 6. Immutable Audit Trail & Forensik Data
- **Requirement:** [REQ-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [BRULE-019](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md), [NFR-AUD-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/NON_FUNCTIONAL_REQUIREMENTS.md). Seluruh mutasi wajib mencatat nilai lama dan nilai baru dalam tabel immutable append-only.
- **Architectural Impact:**
  - Interceptor / Database Trigger / ORM Hook otomatis yang merekam snapshot perubahan entitas ke tabel `audit_logs`.
  - Tabel audit log dirancang terpisah dengan strategi partisi waktu (*time-based partitioning*) untuk mencegah pembengkakan ukuran tabel utama.
- **Potential Concern:**
  - Overhead penulisan (*write performance penalty*) pada setiap operasi database jika logging dilakukan secara sinkronus tanpa optimasi.
- **Decision Needed (Phase 2 & 3):**
  - Evaluasi apakah penulisan log audit dieksekusi secara in-transaction (menjamin konsistensi mutlak) atau asynchronous via event dispatcher.

---

### 7. Legacy Data Preservation & ETL Migration Strategy
- **Requirement:** [REQ-016](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [DEC-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md), [DEC-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md). Data dari database legacy `eskld` wajib dipreservasi dan dimigrasikan ke skema baru secara bersih.
- **Architectural Impact:**
  - Pembuatan script ETL (Extract, Transform, Load) terisolasi untuk membersihkan data string `list_id_kl`, memvalidasi foreign key terputus, dan memetakan tabel ad-hoc `data_map_*`.
  - Database baru dibangun dari skema bersih (*clean architecture*), bukan menimpa database legacy secara *in-place*.
- **Potential Concern:**
  - Inkonsistensi data historis lama jika ada referensi ID instansi yang tidak valid pada `tbl_ref_instansi_org`.
- **Decision Needed (Phase 3):**
  - Prosedur cleansing data legacy bersama Data Analyst Ikhsan sebelum script migrasi final dijalankan.

---

### 8. National Scalability & Containerized Deployment
- **Requirement:** [REQ-017](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [NFR-SCL-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/NON_FUNCTIONAL_REQUIREMENTS.md), [NFR-INF-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/NON_FUNCTIONAL_REQUIREMENTS.md). Skalabilitas untuk seluruh K/L dan Pemda di Indonesia (600+ instansi, puluhan ribu unit kerja) serta portabilitas container Docker.
- **Architectural Impact:**
  - Arsitektur backend modular (*Modular Monolith / Decoupled Clean Architecture*) yang siap dipecah atau di-scale secara horizontal jika beban meningkat.
  - Konfigurasi kontainerisasi Docker lengkap untuk backend, frontend, dan basis data.
- **Potential Concern:**
  - Lingkungan server kementerian (PDN / On-Premise) mungkin memiliki spesifikasi alokasi resource RAM/CPU tertentu.
- **Decision Needed (Phase 2 & 11):**
  - Menyelaraskan spesifikasi resource Docker Compose dengan standar infrastruktur hosting resmi KemenPANRB.
