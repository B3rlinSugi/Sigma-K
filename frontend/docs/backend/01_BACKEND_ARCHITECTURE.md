# SIGMA-K — PHASE 5A: BACKEND ARCHITECTURE BLUEPRINT

> **Dokumen:** `01_BACKEND_ARCHITECTURE.md`  
> **Status:** `ARCHITECTURAL BLUEPRINT (PHASE 5A DESIGN - REVIEWED)`  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Backend Architect & Solution Architect  
> **Target Stack:** NestJS 10+ (TypeScript Strict) • Prisma ORM • PostgreSQL 16 • Redis 7 (Provisional)  
> **Catatan Fase:** Dokumen ini merupakan cetak biru arsitektur backend formal. **TIDAK ADA** penulisan kode backend runtime, migrasi database, ataupun eksekusi koneksi legacy pada tahap ini.

---

## 1. Executive Architecture Summary

Backend SIGMA-K (Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan) dirancang menggunakan pola **Modular Monolith Clean Architecture**. Arsitektur ini mengorganisasikan sistem ke dalam **10 Domain Bounded Modules + 1 Shared Infrastructure Module**, memberikan isolasi domain yang kuat, menjamin integritas transaksi ACID, dan menyediakan jalur migrasi fleksibel (*microservices readiness*) di masa depan.

```
+-----------------------------------------------------------------------------------+
|                           FRONTEND APPLICATION CLIENT                             |
|              Next.js 14 App Router • React Flow Canvas • Role Switcher            |
+-----------------------------------------------------------------------------------+
                                         │  HTTPS / REST JSON (/api/v1/*)
                                         ▼
+-----------------------------------------------------------------------------------+
|                        1. API GATEWAY & CONTROLLER LAYER                          |
|    - Route Handlers, DTO Request Validation (class-validator), Guards, Filters    |
+-----------------------------------------------------------------------------------+
                                         │  Calls
                                         ▼
+-----------------------------------------------------------------------------------+
|                 2. APPLICATION SERVICE & USE CASE LAYER                           |
|    - Business Orchestration, Unit of Work Transactions, Domain Event Dispatcher   |
+-----------------------------------------------------------------------------------+
                                         │  Executes
                                         ▼
+-----------------------------------------------------------------------------------+
|                         3. DOMAIN & STATE MACHINE LAYER                           |
|    - Anti-Circular DFS Validator, Workflow State Engine, Lineage Transition Rules |
+-----------------------------------------------------------------------------------+
                                         │  Interacts
                                         ▼
+-----------------------------------------------------------------------------------+
|                     4. REPOSITORY & DATA ACCESS LAYER (ORM)                       |
|    - Prisma ORM Repositories, Recursive CTE Queries, GIN Index Search             |
+-----------------------------------------------------------------------------------+
                                         │  SQL / TCP
                                         ▼
+-----------------------------------------------------------------------------------+
|                     5. PERSISTENCE & INFRASTRUCTURE LAYER                         |
| PostgreSQL 16 (Relational & JSONB) • Redis 7 (Provisional) • Pluggable Storage    |
+-----------------------------------------------------------------------------------+
```

---

## 2. Lapisan Arsitektur (Layered Clean Architecture)

Setiap modul domain di dalam backend NestJS wajib mengikuti aturan ketergantungan searah (*Unidirectional Dependency Rule*):

### Lapisan 1: Controller Layer (`*.controller.ts`)
- **Tanggung Jawab:** Menerima HTTP request, mendekode parameter URL dan query, memvalidasi payload request melalui DTO bertipe ketat (*Data Transfer Object* dengan `ValidationPipe`), memverifikasi token otentikasi dan otorisasi scope melalui Guard, serta memanggil Application Service.
- **Batasan:** Controller **TIDAK BOLEH** mengeksekusi logika bisnis atau memanggil Prisma secara langsung.

### Lapisan 2: Application Service Layer (`*.service.ts`)
- **Tanggung Jawab:** Mengorkestrasi use case bisnis, mengelola batas transaksi database (*transaction boundaries / Unit of Work*), memvalidasi integritas sebelum eksekusi, serta memancarkan event (*Domain Events*) setelah mutasi berhasil.
- **Batasan:** Bergantung pada Repository Interface, bukan pada implementasi driver database spesifik.

### Lapisan 3: Domain & Business Rules Layer (`domain/*`)
- **Tanggung Jawab:** Mengenkapsulasi aturan bisnis murni yang tidak bergantung pada framework eksternal:
  - Validasi siklus pohon hierarki (*Anti-Circular Dependency Guard* menggunakan algoritma DFS).
  - Evaluasi transisi mesin status pengajuan (*Configurable Workflow State Machine Evaluator*).
  - Klasifikasi dan aturan silsilah transformasi kabinet (*Lineage Matrix*).
  - Perhitungan formula matematis indikator kinerja (*Analytics KPIs*).

### Lapisan 4: Repository Layer (`*.repository.ts`)
- **Tanggung Jawab:** Mengabstraksikan akses ke basis data PostgreSQL 16 melalui Prisma Client, kueri rekursif (*Recursive CTE*), operasi *bulk upsert*, dan penelusuran log audit JSONB.

### Lapisan 5: Shared Infrastructure & Cross-Cutting Layer (`common/*` & `FilesModule`)
- **Tanggung Jawab:** Komponen utilitas lintas modul:
  - `JwtAuthGuard`, `RolesGuard`, `InstitutionScopeGuard` (Keamanan & BOLA/IDOR prevention).
  - `HttpExceptionFilter`, `PrismaExceptionFilter` (Standardisasi respons kesalahan JSON).
  - `AuditLogInterceptor` (Pencatatan forensik mutasi data otomatis).
  - `FilesModule` (*Pluggable Storage Driver*: Local Disk / MinIO S3).

---

## 3. Klarifikasi Keputusan Otentikasi & Batasan Stakeholder

1. **Otentikasi Sementara (Provisional Candidate):** Mekanisme JWT Access Token (15 menit) + Refresh Token dirancang sebagai kandidat arsitektur sementara (*Provisional Architecture Candidate*) untuk pengembangan backend awal. Keputusan akhir penyedia otentikasi produksi (SSO KemenPANRB / OIDC / ASN Digital) tetap aktif sebagai **`OPEN-003`** dan menunggu arahan Pusdatin/Stakeholder.
2. **Fleksibilitas Alur Kerja:** Mesin alur kerja dirancang berbasis konfigurasi data (*data-driven*) untuk mendukung Standard Workflow (5 tahap) maupun Admin Triage Workflow (6 tahap) sesuai keputusan **`OPEN-002`**.
3. **Peran SESDEP:** Status SESDEP diperlakukan sebagai *Executive Perspective / Prototype Persona* dengan dukungan Opsi A (Dedicated Role) atau Opsi B (Supervisory Permission Model) sesuai keputusan **`OPEN-001`**.
