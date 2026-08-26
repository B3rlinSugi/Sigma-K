# 03. TECHNOLOGY STACK SELECTION: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Principal Full-Stack Engineer  
> **Prinsip:** "Advanced because the problem requires it, not because it looks impressive."

Dokumen ini mendokumentasikan hasil evaluasi teknis, justifikasi pemilihan, dan rekomendasi tumpukan teknologi (*Technology Stack*) komprehensif untuk seluruh lapisan arsitektur SIGMA-K.

---

## 1. Ringkasan Tumpukan Teknologi Terpilih (Target Stack Matrix)

| Lapisan Arsitektur | Teknologi Terpilih | Versi Target / Standar | Peran Utama dalam Sistem |
|---|---|---|---|
| **Frontend Framework** | **React + Next.js (App Router)** | Next.js 14+ / React 18+ (TypeScript) | Web client eksekutif, Server-Side Rendering (SSR) untuk dashboard awal, Client Components untuk canvas org-chart interaktif & realtime feed. |
| **Frontend Styling & UI** | **Tailwind CSS + Radix UI Primitives** | Tailwind v3.4+ / Lucide Icons | Design system eksekutif modern, glassmorphism, token warna kementerian, dark/light theme, komponen aksesibel. |
| **State & Data Fetching** | **TanStack Query (React Query) + Zustand** | TanStack Query v5 / Zustand v4 | Manajemen cache data client, optimistic updates, background refetching, dan global client state. |
| **Tree & Chart Visualizer** | **D3.js / React Flow + Recharts** | React Flow v11+ / Recharts v2+ | Rendering bagan struktur organisasi pohon (zoom/pan, collapsible, anti-circular guard) dan grafik analitik kabinet. |
| **Backend Framework** | **NestJS (TypeScript)** *OR* **Laravel (PHP 8.3+)** | *Evaluated in Detail below* | Core API Modular Monolith, routing, dependency injection, validation pipe, RBAC guard, transaction manager. |
| **Primary Database** | **PostgreSQL** | PostgreSQL 16+ | Relational ACID storage, recursive CTE queries (pohon hierarki), JSONB snapshot audit, GIN index full-text search. |
| **Cache & Event Broker** | **Redis** | Redis 7+ | Session store, rate limiting store, query caching, Pub/Sub event bus untuk notifikasi realtime. |
| **Realtime Engine** | **Socket.io / WebSocket Server (Node) / SSE** | WSS over TLS 1.3 | Saluran komunikasi persisten dua arah untuk toast notifikasi mutasi data seketika. |
| **Document Storage** | **MinIO (S3-Compatible) / Local Storage Driver** | S3 API Standard | Penyimpanan dokumen dasar hukum PDF regulasi kelembagaan dan aset logo instansi dengan proteksi signed URL. |
| **Container & Reverse Proxy** | **Docker + Docker Compose + Nginx** | Docker Engine 24+ / Nginx Alpine | Portabilitas deployment di PDN / VM KemenPANRB, reverse proxy, rate limiting, SSL termination. |
| **Quality & DevSecOps** | **TypeScript Strict, ESLint, Vitest/Jest, GitHub Actions** | Node 20 LTS / CI/CD Standard | Static analysis, automated unit/integration testing, conventional commits, automated Docker build pipeline. |

---

## 2. Rincian Evaluasi Pemilihan Komponen Kunci

### A. Evaluasi Frontend: Next.js vs Vite React vs Nuxt/Vue
- **Pemenang Terpilih:** **Next.js (React + TypeScript)**
- **Justifikasi Teknis:**
  1. *SSR & Hybrid Rendering:* Dashboard pimpinan (SESDEP) dan katalog instansi publik dapat di-render secara cepat di server (*Server Components*), memangkas First Contentful Paint (FCP) menjadi $< 800$ ms.
  2. *Ekosistem Komponen Enterprise:* Ekosistem React memiliki pustaka visualisasi pohon (*React Flow / D3*) dan chart data (*Recharts*) paling matang di industri, krusial untuk rendering bagan struktur dan komparasi kabinet.
  3. *Type Safety End-to-End:* Penggunaan TypeScript penuh menjamin kontrak tipe data sinkron antara DTO API backend dan tampilan UI.

---

### B. Evaluasi Backend: NestJS vs Laravel vs Spring Boot
- **Pemenang Rekomendasi Utama:** **NestJS (TypeScript)** *(Alternatif Kuat: Laravel 11 PHP)*
- **Justifikasi Teknis NestJS:**
  1. *Unified TypeScript Ecosystem:* Berbagi interface data (DTO & Type Definitions) secara monorepo antara frontend Next.js dan backend NestJS. Lead engineer (Berlin) dapat bergerak cepat tanpa hambatan *context switching* bahasa.
  2. *Arsitektur Modular Enterprise Bawaan:* NestJS memaksa pemisahan kode ke dalam `Module`, `Controller`, `Service`, `Repository`, dan `Guard` secara ketat, sempurna untuk gaya *Modular Monolith*.
  3. *Dukungan WebSocket & Event-Driven Native:* NestJS memiliki integrasi native `@nestjs/websockets` (Socket.io) dan `@nestjs/event-emitter`, memudahkan penyiaran notifikasi realtime mutasi data.
  4. *ORM Modern & Type-Safe:* Menggunakan Prisma ORM atau TypeORM dengan PostgreSQL, mendukung transaksi ACID dan penanganan query JSONB yang sangat elegan.
- **Catatan Evaluasi Laravel (PHP):**
  - Laravel 11 adalah alternatif yang sangat kuat jika infrastruktur server eksisting KemenPANRB berbasis PHP/FPM. Namun untuk kebutuhan realtime WebSocket intensif dan sharing types dengan Next.js, NestJS memberikan kohesi arsitektural yang lebih unggul bagi tim magang.

---

### C. Evaluasi Database: PostgreSQL vs MySQL vs MariaDB
- **Pemenang Terpilih:** **PostgreSQL 16**
- **Justifikasi Teknis:**
  1. *Keunggulan Recursive CTE (Common Table Expressions):* Pohon hierarki struktur organisasi (`parent_id`) dan penelusuran silsilah kementerian kabinet (*lineage*) memerlukan query rekursif hierarkis yang dieksekusi dengan kecepatan dan optimasi indeks terbaik pada PostgreSQL.
  2. *Kekuatan JSONB & Indexing GIN:* Snapshot nilai sebelum vs sesudah pada tabel `audit_logs` dan item draf submission disimpan dalam format JSONB yang dapat diindeks menggunakan GIN (*Generalized Inverted Index*), memungkinkan pencarian histori mutasi data secara instan.
  3. *Pencegahan Concurrency Issue:* PostgreSQL memiliki MVCC (*Multi-Version Concurrency Control*) superior dan isolasi transaksi serializable untuk mencegah *race condition* saat verifikator dan user memproses tiket secara bersamaan.
  4. *Kesiapan Masa Depan (Future GIS & Full-Text):* Ekstensi PostGIS siap diaktifkan jika kebutuhan pemetaan koordinat Pemda (REQ-029) diaktifkan, serta mesin Full-Text Search bawaan tanpa perlu memasang Elasticsearch.

---

### D. Evaluasi Realtime: WebSocket (Socket.io) vs Server-Sent Events (SSE)
- **Pemenang Terpilih:** **WebSocket (via Socket.io dengan Fallback Polling / Redis Adapter)**
- **Justifikasi Teknis:**
  1. Komunikasi persisten dua arah memungkinkan client mengirim acknowledge saat notifikasi dibaca (*instant read-receipt*) tanpa memicu HTTP request tambahan.
  2. Socket.io menyediakan mekanisme otomatis *heartbeat*, *reconnection backoff*, *room partitioning* (misal room per instansi atau room per user), dan graceful fallback jika proxy jaringan client memblokir koneksi WSS murni.

---

## 3. Matriks Komparasi Ringkas Stack Utama

```
+-----------------------------------------------------------------------------------+
|                        SIGMA-K RECOMMENDED CORE STACK                             |
+-----------------------------------------------------------------------------------+
|  Frontend : Next.js 14+ (App Router) + React 18 + TypeScript + Tailwind CSS       |
|  Backend  : NestJS 10+ (Modular Monolith) + TypeScript Strict                     |
|  Database : PostgreSQL 16 (Relational, Recursive CTE, JSONB Audit)                |
|  Cache    : Redis 7 (Session, Pub/Sub, Queue, Cache)                              |
|  Realtime : Socket.io / WebSocket (Event Dispatcher Pipeline)                     |
|  Storage  : MinIO S3-Compatible Driver (PDFs & Media)                             |
|  DevOps   : Docker Compose + Nginx Reverse Proxy + GitHub Actions                 |
+-----------------------------------------------------------------------------------+
```
