# SIGMA-K PHASE 2: ARCHITECTURE & TECHNOLOGY STACK REPORT

> **Status:** `ARCHITECTURE READY WITH OPEN DECISIONS`  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K (Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan)  
> **Stakeholder Utama:** Pimpinan / Sekretaris Deputi (SESDEP) Kelembagaan dan Tata Laksana, Kementerian PANRB  
> **Tim Pengembang:** Berlin (Lead Software Architect & Full-Stack), Ikhsan (Data Analyst)  
> **Mentor:** Kak Nabila (Mentor), Pak Sigit (Mentor / Data Analyst Lead)  

---

## 1. Executive Summary

Laporan ini menyajikan hasil pelaksanaan **Phase 2: Architecture & Technology Stack Selection** untuk project SIGMA-K. Seluruh kebutuhan bisnis, fungsional, non-fungsional, user story, dan use case dari Phase 0 (Discovery) dan Phase 1 (Requirements) telah dievaluasi secara objektif untuk menghasilkan **Cetak Biru Teknis (Technical Blueprint)** yang solid, aman, terukur, dan siap diimplementasikan.

Prinsip utama yang memandu seluruh keputusan:  
> *"Advanced because the problem requires it, not because it looks impressive."*

---

## 2. Ringkasan Rekomendasi Arsitektur Sistem

- **Gaya Arsitektur:** **Modular Monolith with Event-Driven Realtime Components**.  
  Menyediakan isolasi domain modul yang ketat, kekuatan transaksi atomik ACID lokal untuk alur verifikasi/approval, dan pipeline penyiaran event realtime berbasis Redis Pub/Sub tanpa kerumitan operasional microservices.
- **Workflow State Engine:** **Configurable Extensible State Machine Engine**.  
  Menyelesaikan potensi konflik alur kerja (*Phase 1 Standard Verification vs Legacy Admin Triage*) dengan memisahkan konfigurasi transisi status dari kode logika bisnis.

---

## 3. Rekomendasi Tumpukan Teknologi (Technology Stack)

```
+-----------------------------------------------------------------------------------+
|                        SIGMA-K TARGET TECHNOLOGY STACK                            |
+-----------------------------------------------------------------------------------+
| Lapisan Frontend      : Next.js 14+ (App Router) + React 18 + TypeScript          |
| Desain & Komponen     : Tailwind CSS + Radix UI Primitives + Lucide Icons         |
| Canvas Visualizer     : React Flow (v11+) + D3 Tree Engine (Org Chart Canvas)     |
| State & Cache Client  : TanStack Query v5 + Zustand v4                            |
| Lapisan Backend       : NestJS 10+ (TypeScript Strict) + Prisma ORM               |
| Alternatif Backend    : Laravel 11 (PHP 8.3+)                                     |
| Basis Data Utama      : PostgreSQL 16+ (Recursive CTE, JSONB Indexing, ACID)      |
| Cache & Message Queue : Redis 7 (Session, Rate Limiting, Event Bus)               |
| Realtime Engine       : Socket.io / WebSocket Gateway over TLS 1.3                |
| Document Storage      : MinIO S3-Compatible Driver / Local Disk Driver            |
| Format Repositori     : Monorepo Workspace (Turborepo) with Package `@sigma/types`|
| Container & Ingress   : Docker Multi-Stage + Docker Compose + Nginx Reverse Proxy |
| CI/CD Automation      : GitHub Actions Pipeline (Lint, Test, Security, Build)     |
+-----------------------------------------------------------------------------------+
```

---

## 4. Rincian Rekomendasi Tiap Lapisan

### A. Frontend Tier (Next.js + React Flow)
- Memungkinkan Server-Side Rendering (SSR) untuk memangkas waktu muat awal dashboard pimpinan menjadi $< 800$ ms.
- Menyediakan kanvas bagan organisasi interaktif berbasis React Flow yang dilengkapi *Virtual Viewport Rendering*, *Collapsible Sub-trees*, dan *Client-side Cycle Guard*.
- Dilengkapi komponen *Side-by-Side Diff Viewer* untuk mempermudah Verifikator memeriksa usulan perubahan struktur.

### B. Backend Tier (NestJS Modular Monolith)
- Menyatukan ekosistem TypeScript dari database hingga UI, memungkinkan berbagi kontrak tipe data `@sigma/types` secara instan.
- Menerapkan *Layered Clean Architecture* (Controller $\rightarrow$ Use Case Service $\rightarrow$ Domain Entity $\rightarrow$ Repository) dengan transaksi database terisolasi.

### C. Database Tier (PostgreSQL 16)
- **Recursive CTE:** Mengeksekusi kueri bagan organisasi hierarkis Adjacency List (`parent_id`) dalam waktu $< 5$ milidetik.
- **JSONB GIN Indexing:** Menyimpan snapshot nilai sebelum vs sesudah pada tabel `audit_logs` dan item draf submission yang dapat dicari secara instan.
- **Normalized Cabinet Model:** Menghapus format denormalisasi string `list_id_kl` legacy dan menggantikannya dengan relasi relasional formal `Cabinet`, `CabinetPeriod`, `CabinetMembership`, dan `InstitutionLineage`.

### D. Realtime & Notification Tier (Socket.io + Redis)
- Menyiarkan notifikasi mutasi data (Create, Update, Delete, Submit, Verify, Approve) ke peramban pengguna secara seketika ($< 1$ detik) tanpa me-refresh halaman.
- Dilengkapi mekanisme pencegahan duplikasi event (*Event ID Deduplication*) dan *Exponential Backoff Reconnection*.

### E. Security & Governance Tier
- Menerapkan *Zero Trust*, *Bcrypt Password Hashing*, *Dual-Token Session (JWT Access + Redis Refresh Token)*, dan *Scoped Institution Guard* untuk mencegah kerentanan BOLA/IDOR antar-kementerian.
- Log audit bersifat *immutable append-only* dan tabel dipartisi per periode waktu.

### F. DevOps & Deployment Tier
- Bersifat *Deployment-Agnostic*: Siap dijalankan di Pusat Data Nasional (PDN), Cloud KemenPANRB, atau Virtual Machine On-Premise menggunakan Docker Compose dan Nginx.

---

## 5. Ringkasan Architecture Decision Records (15 ADRs)

| Nomor ADR | Judul Keputusan | Status | Keputusan Terpilih |
|---|---|:---:|---|
| [ADR-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-001.md) | Architecture Style Selection | **APPROVED** | Modular Monolith + Event-Driven Realtime Components |
| [ADR-002](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-002.md) | Frontend Stack Selection | **APPROVED** | Next.js 14+ App Router + React + TypeScript + React Flow |
| [ADR-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-003.md) | Backend Stack Selection | **APPROVED** | NestJS 10+ (TypeScript Strict) dengan Prisma ORM |
| [ADR-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-004.md) | Primary Database Engine | **APPROVED** | PostgreSQL 16+ (Recursive CTE & JSONB) |
| [ADR-005](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-005.md) | Authentication Strategy | **APPROVED** | Pluggable Dual-Token (Bcrypt + JWT/Redis + OIDC Ready) |
| [ADR-006](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-006.md) | Authorization & Scoping | **APPROVED** | RBAC + Scoped Institution Access Guard (BOLA Defense) |
| [ADR-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-007.md) | Realtime Transport | **APPROVED** | Socket.io / WebSocket Gateway over TLS 1.3 |
| [ADR-008](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-008.md) | Organization Hierarchy | **APPROVED** | Adjacency List (`parent_id`) + Recursive CTE + DFS Guard |
| [ADR-009](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-009.md) | Cabinet Historical Model | **APPROVED** | Normalized Relational Model + `InstitutionLineage` Graph |
| [ADR-010](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-010.md) | API Architecture | **APPROVED** | RESTful API v1 (`/api/v1/`) with Uniform Response Wrapper |
| [ADR-011](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-011.md) | Document Storage | **APPROVED** | Pluggable Driver (Local Disk for Dev, MinIO/S3 for Staging) |
| [ADR-012](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-012.md) | Search Engine Strategy | **APPROVED** | PostgreSQL Native Full-Text Search + `pg_trgm` GIN Index |
| [ADR-013](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-013.md) | Analytics Workload | **APPROVED** | Materialized Views + Redis Cache + Safe Read-Only Access |
| [ADR-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-014.md) | Containerization & DevOps | **APPROVED** | Multi-Stage Docker + Docker Compose + GitHub Actions |
| [ADR-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/architecture/adr/ADR-015.md) | Repository Strategy | **APPROVED** | Monorepo Workspace (Turborepo) with Package `@sigma/types` |

---

## 6. Daftar Keputusan Terbuka yang Membutuhkan Validasi Stakeholder (Open Decisions)

Terdapat 3 poin keputusan kebijakan yang dicatat sebagai *Open Decisions* dan telah diakomodasi secara adaptif dalam arsitektur:
1. **Keputusan Urutan Alur Verifikasi (OD-WF-01):** Konfirmasi apakah alur pengajuan menggunakan verifikasi langsung (`User` $\rightarrow$ `Verifikator` $\rightarrow$ `Admin`) atau melalui disposisi admin terlebih dahulu (`User` $\rightarrow$ `Admin Review` $\rightarrow$ `Verifikator` $\rightarrow$ `Final Approval`). *Arsitektur telah siap mendukung kedua varian via Configurable State Machine.*
2. **Keputusan Kebijakan SSO Kementerian (OD-IAM-01):** Konfirmasi ketersediaan server Identity Provider (OIDC / OAuth2) KemenPANRB / ASN Digital Nasional. *Arsitektur telah mengadopsi Pluggable Auth Strategy.*
3. **Keputusan Spesifikasi Server Hosting (OD-INF-01):** Konfirmasi target alokasi resource RAM/CPU dan lingkungan jaringan server target (PDN vs On-Premise VM). *Arsitektur telah berstandar kontainer Docker netral.*

---

## 7. Rekomendasi Tahapan Berikutnya (Next Phase Recommendation)

Berdasarkan kesiapan cetak biru arsitektur, tim direkomendasikan untuk melanjutkan ke:

1. **Phase 3: Data Architecture, Modeling & Migration Strategy:**  
   - Data Analyst (Ikhsan) menyusun Kamus Data resmi dan profiling database legacy `eskld`.
   - Lead Engineer merumuskan skema relasional detail Prisma dan script ETL migrasi staging.
2. **Phase 4: UX/UI Interactive Prototype for SESDEP Presentation:**  
   - Mengembangkan prototype antarmuka interaktif (Dashboard Eksekutif, Komposisi Kabinet Merah Putih 48 K/L, Org Chart Canvas interaktif, dan Diff Viewer) untuk divalidasi langsung oleh pimpinan kementerian.

---

> [!IMPORTANT]
> **FINAL STATUS:** `ARCHITECTURE READY WITH OPEN DECISIONS`  
> Seluruh 26 dokumen cetak biru teknis dan 15 ADR telah selesai disusun secara lengkap di `docs/architecture/`. Tidak ada baris kode implementasi, migration DDL fisik, atau database nyata yang dibuat, mematuhi 100% aturan ketat Phase 2. Arsitektur siap ditinjau bersama mentor dan stakeholder.
