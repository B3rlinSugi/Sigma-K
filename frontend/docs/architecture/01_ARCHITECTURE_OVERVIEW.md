# 01. ARCHITECTURE OVERVIEW: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K (Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan)  
> **Stakeholder Utama:** Pimpinan / Sekretaris Deputi (SESDEP) Kelembagaan dan Tata Laksana, Kementerian PANRB  
> **Tim Implementasi:** Berlin (Lead Full-Stack/Software Architect), Ikhsan (Data Analyst)  
> **Mentor:** Kak Nabila (Mentor), Pak Sigit (Mentor / Data Analyst Lead)  

---

## 1. Visi & Prinsip Arsitektur

SIGMA-K dirancang untuk menjadi **Single Source of Truth** pengelolaan master data kelembagaan nasional (Pusat & Daerah), struktur organisasi hierarkis, tugas dan fungsi, periodesasi kabinet kepresidenan (misal Kabinet Merah Putih), serta pelacakan silsilah perubahan kelembagaan secara historis dan terukur.

Prinsip fundamental arsitektur sistem SIGMA-K:
1. **"Advanced because the problem requires it, not because it looks impressive":** Setiap keputusan teknologi dan pola desain dipilih murni atas dasar kebutuhan bisnis riil, kendala infrastruktur kementerian, dan kemampuan tim pengembang selama periode magang.
2. **Clean Architecture & Separation of Concerns:** Memisahkan secara tegas lapisan Domain Logic, Application Services, Persistence Layer, dan Presentation Layer.
3. **Legacy as Reference, Clean Target State:** Database legacy `eskld` dan repositori lama adalah rujukan logika dan sumber migrasi data melalui pipeline ETL bersih, bukan arsitektur yang disalin mentah.
4. **Data Integrity & Immutability:** Menjamin ACID compliance untuk transaksi mutasi data, pencegahan siklus hierarki pohon (*anti-circular dependency*), dan pencatatan jejak audit tak terhapuskan (*immutable audit trail*).
5. **Realtime Awareness & Low Latency:** Memberikan umpan balik instan melalui pipeline event seketika (*realtime notifications*) pada setiap mutasi data penting.
6. **Executive Readiness:** Memaksimalkan kenyamanan navigasi, visualisasi bagan interaktif, dan komparasi kabinet untuk presentasi di hadapan pimpinan/SESDEP.

---

## 2. Diagram Arsitektur Target Konseptual (End-to-End)

```
+-----------------------------------------------------------------------------------+
|                           CLIENT PRESENTATION TIER                                |
|  [ Modern Web Application (Desktop / Tablet Responsive) ]                         |
|  - Executive Dashboard & Cabinet Visualizer (Kabinet Merah Putih)                 |
|  - Interactive Org Chart Canvas (SVG/Canvas Tree Viewer)                          |
|  - Verification Diff Viewer (Side-by-Side Delta Review)                           |
|  - Realtime Toast & In-App Notification Center                                    |
+-----------------------------------------------------------------------------------+
                                         │  HTTPS / WSS (TLS 1.3)
                                         ▼
+-----------------------------------------------------------------------------------+
|                        API GATEWAY & INGRESS LAYER                                |
|  - Reverse Proxy (Nginx / Cloud Ingress)                                          |
|  - SSL Termination, Security Headers, CORS Policy & Rate Limiting                |
|  - Static Asset Serving & Route Forwarding                                        |
+-----------------------------------------------------------------------------------+
                                         │
                                         ▼
+-----------------------------------------------------------------------------------+
|                       APPLICATION TIER (MODULAR MONOLITH)                         |
|                                                                                   |
|  +-----------------------------------------------------------------------------+  |
|  | Core Application Modules (Bounded Contexts):                                |  |
|  |  1. Auth & RBAC Module (Pluggable Local / SSO OIDC Adapter)                 |  |
|  |  2. Master Institution & Profile Module                                     |  |
|  |  3. Cabinet & Historical Lineage Module                                     |  |
|  |  4. Organization Hierarchy Engine (Adjacency List + Cycle Guard)            |  |
|  |  5. Tugas & Fungsi (Tupoksi) Governance Module                              |  |
|  |  6. Configurable Workflow Engine (Draft -> Verification -> Approval)        |  |
|  |  7. Realtime Notification & Event Dispatcher                                |  |
|  |  8. Audit Trail & Compliance Interceptor                                    |  |
|  |  9. Executive Analytics & Reporting Engine                                  |  |
|  +-----------------------------------------------------------------------------+  |
+-----------------------------------------------------------------------------------+
        │                             │                             │
        ▼                             ▼                             ▼
+-----------------------+   +-----------------------+   +-----------------------+
|   PRIMARY DATABASE    |   |     CACHE & QUEUE     |   |    DOCUMENT STORAGE   |
|     (PostgreSQL)      |   |        (Redis)        |   |   (Local/MinIO/S3)    |
| - Master Live Data    |   | - Session Store       |   | - Legal Basis PDFs    |
| - Draft Staging Data  |   | - Pub/Sub Event Bus   |   | - Institution Logos   |
| - Recursive CTE Trees |   | - Analytics Cache     |   | - Encrypted Storage   |
| - Immutable Audit Log |   | - Queue Workers       |   | - Signed URL Access   |
+-----------------------+   +-----------------------+   +-----------------------+
```

---

## 3. Pemetaan Domain Bisnis ke Modul Arsitektur

| Domain Bisnis (Phase 1) | Modul Arsitektur | Tanggung Jawab Utama |
|---|---|---|
| Institution & Profile | `InstitutionModule` | Master K/L & Pemda, profiling, kodefikasi unik, soft delete. |
| Cabinet & Period | `CabinetModule` | Master kabinet, temporal period, membership K/L, historical transition matrix. |
| Organization Tree & Position | `OrganizationModule` | Pohon hierarki unit kerja, validasi anti-circular, penugasan eselon. |
| Tugas & Fungsi | `TupoksiModule` | Butir tugas dan fungsi terstruktur, rujukan pasal regulasi. |
| Submission, Verify, Approve | `WorkflowModule` | State machine pengajuan, diff payload viewer, verifikasi, atomic approval. |
| Realtime Notification | `NotificationModule` | Event dispatcher, WebSocket/SSE broadcasting, riwayat notifikasi. |
| Audit Trail & Security | `SecurityAuditModule` | RBAC guard, scoping instansi, immutable audit logger. |
| Executive Dashboard & Analytics | `AnalyticsModule` | Agregasi postur ASN, metrik komparasi kabinet, export PDF/Excel. |

---

## 4. Analisis Batasan & Kendala Infrastruktur
1. **Kendala Deployment Kementerian:** Target infrastruktur kementerian (Pusat Data Nasional / On-Premise VM KemenPANRB) mengharuskan arsitektur bersifat *container-ready* (Docker Compose) tanpa ketergantungan pada layanan cloud proprietary (AWS/GCP locked services).
2. **Kapasitas Tim Magang:** Tim teknis terdiri dari 1 Lead Full-Stack Engineer (Berlin) dan 1 Data Analyst (Ikhsan). Arsitektur harus memaksimalkan kecepatan rilis prototype fungsional tanpa menciptakan *operational overhead* yang berlebihan.
3. **Data Legacy Source:** Database legacy `eskld` berjalan pada MySQL legacy dengan tabel ad-hoc. Skema target modern wajib mengisolasi proses transformasi data secara decoupled tanpa mengganggu keutuhan data legacy.
