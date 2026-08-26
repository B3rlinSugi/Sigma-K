# PROJECT PHASE PLAN: SIGMA-K

> **Status:** DISCOVERY BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  
> **Catatan:** Roadmap ini adalah rencana tahapan komprehensif tingkat tinggi untuk memandu seluruh siklus pengembangan SIGMA-K selama periode magang.

---

## Roadmap Fase Pengembangan

```
Phase 0: Discovery (CURRENT)
   |
   v
Phase 1: Requirements Validation & Specification
   |
   v
Phase 2: Architecture & Technology Stack Selection
   |
   v
Phase 3: Data Architecture, Modeling & Migration Strategy
   |
   v
Phase 4: UX/UI Interactive Prototype (For SESDEP Presentation)
   |
   v
Phase 5: Backend API & Business Logic Engine
   |
   v
Phase 6: Frontend Modern Web Application
   |
   v
Phase 7: Realtime Notification & Event Pipeline
   |
   v
Phase 8: Data Analytics & Executive Intelligence Module
   |
   v
Phase 9: End-to-End System Integration & Data Cleansing
   |
   v
Phase 10: Comprehensive Testing & Quality Assurance
   |
   v
Phase 11: Deployment, Containerization & CI/CD
   |
   v
Phase 12: Final Documentation, Knowledge Transfer & Handover
```

---

## Rincian Tiap Fase

### Phase 0 — Discovery (CURRENT PHASE)
- **Fokus:** Analisis inventaris sistem legacy, pemetaan domain bisnis, penyusunan baseline kebutuhan, identifikasi aktor/role, pendataan open questions, dan penyusunan roadmap.
- **Deliverables:** Dokumen Discovery Baseline (`docs/discovery/*.md`).
- **Lead / PIC:** Senior Software Architect & Lead Full-Stack Engineer + Data Analyst.
- **Status:** **IN PROGRESS $\rightarrow$ BASELINE COMPLETE**

---

### Phase 1 — Requirements Validation & Specification
- **Fokus:** Wawancara dan klarifikasi daftar pertanyaan terbuka (*Open Questions*) dengan stakeholder (SESDEP/Pembimbing), finalisasi Software Requirements Specification (SRS), dan penentuan detail acceptance criteria tiap modul.
- **Deliverables:** Dokumen SRS formal, use case specifications, wireframe flow diagram.
- **Lead / PIC:** Lead Engineer bersama Stakeholder.

---

### Phase 2 — Architecture & Technology Stack Selection
- **Fokus:** Evaluasi komparatif dan penetapan tech stack final (Frontend, Backend, Database, Realtime transport, Caching), perancangan arsitektur sistem (Clean Architecture / Modular Monolith / Microservices boundary), API contract standard (OpenAPI/Swagger), dan security framework.
- **Deliverables:** Dokumen System Architecture Document (SAD), API Specification v1, ADR (Architecture Decision Records).
- **Lead / PIC:** Lead Engineer.

---

### Phase 3 — Data Architecture, Modeling & Migration Strategy
- **Fokus:** Desain Entity-Relationship Diagram (ERD) fisik yang ternormalisasi, penyusunan script migration database baru, skema audit trail, serta formulasi pipeline migrasi/cleansing data dari database `eskld`.
- **Deliverables:** Database Schema DDL, Migration Scripts, Data Cleansing & ETL Pipeline Plan.
- **Lead / PIC:** Lead Engineer & Data Analyst (Ikhsan).

---

### Phase 4 — UX/UI Prototype (Executive Presentation Milestone)
- **Fokus:** Perancangan antarmuka visual modern berstandar enterprise (Dashboard, Kabinet, Daftar K/L, Detail Profil, Visual Org Chart, Layar Verifikasi, Realtime Toast) dan pembuatan interactive prototype untuk presentasi di hadapan SESDEP.
- **Deliverables:** Interactive Prototype (High-Fidelity Prototype), Presentasi Deck untuk SESDEP.
- **Lead / PIC:** Lead Full-Stack Engineer.

---

### Phase 5 — Backend Core API & Business Logic Engine
- **Fokus:** Pembangunan RESTful API backend, modul autentikasi & RBAC (User/Admin/Verifikator), CRUD Kabinet & Periodesasi, CRUD Instansi & Profil, Tree Hierarchy Engine (Struktur Organisasi dengan circular dependency guard), dan Workflow Engine (Submission $\rightarrow$ Verification $\rightarrow$ Approval).
- **Deliverables:** Core Backend Application, Automated Unit/Integration Tests Backend, Swagger UI Interactive Docs.
- **Lead / PIC:** Lead Full-Stack Engineer.

---

### Phase 6 — Frontend Modern Web Application
- **Fokus:** Pembangunan aplikasi antarmuka web modern, implementasi komponen UI interaktif, visualisasi org chart pohon, form wizard pengajuan data, diff viewer untuk verifikator, dan dashboard eksekutif.
- **Deliverables:** Frontend Web Application terintegrasi dengan Backend Core API.
- **Lead / PIC:** Lead Full-Stack Engineer.

---

### Phase 7 — Realtime Notification & Event Pipeline
- **Fokus:** Implementasi pipeline event realtime (WebSocket / Server-Sent Events), broadcasting notifikasi saat mutasi data (Create, Update, Delete, Submit, Verify), toast alert seketika, dan notification history center.
- **Deliverables:** Realtime Notification Engine & In-App Notification Hub.
- **Lead / PIC:** Lead Full-Stack Engineer.

---

### Phase 8 — Data Analytics & Executive Intelligence Module
- **Fokus:** Pembangunan modul analitik data kelembagaan, formulasi metrik postur ASN (`v_postur_asn`), matriks perbandingan antar-kabinet, visualisasi tren restrukturisasi birokrasi, dan widget eksekutif.
- **Deliverables:** Modul Data Analytics, Visualisasi Chart/Metrik, Laporan Analitik Kelembagaan.
- **Lead / PIC:** Data Analyst (Ikhsan) bersama Lead Engineer.

---

### Phase 9 — End-to-End System Integration & Data Cleansing
- **Fokus:** Integrasi penuh antara frontend, backend, realtime engine, dan modul analitik. Eksekusi proses migrasi data bersih dari database legacy `eskld` ke skema produksi SIGMA-K.
- **Deliverables:** Sistem Terintegrasi Penuh dengan Data Historis Valid Legacy.
- **Lead / PIC:** Lead Full-Stack Engineer & Data Analyst.

---

### Phase 10 — Comprehensive Testing & Quality Assurance
- **Fokus:** Pengujian menyeluruh mencakup Unit Testing, Integration Testing, End-to-End (E2E) Workflow Testing, Security & Permission Testing, serta Load Testing skalabilitas data instansi nasional.
- **Deliverables:** Test Reports, Test Automation Suite, Bug Fix Register.
- **Lead / PIC:** Lead Full-Stack Engineer.

---

### Phase 11 — Deployment, Containerization & CI/CD
- **Fokus:** Konfigurasi containerization (Dockerfile, Docker Compose), script otomatisasi CI/CD di GitHub Actions, konfigurasi environment produksi/staging, dan deployment ke server kementerian yang ditunjuk.
- **Deliverables:** Docker Environment Setup, CI/CD Pipeline, Staging/Production Deployment Live.
- **Lead / PIC:** Lead Full-Stack Engineer.

---

### Phase 12 — Final Documentation, Knowledge Transfer & Handover
- **Fokus:** Penyusunan dokumentasi teknis lengkap (Developer Guide, Architecture Guide, API Docs), Buku Panduan Pengguna (User Manual per role), dan sesi serah terima (handover) project kepada tim KemenPANRB.
- **Deliverables:** Repository GitHub Terstruktur Lengkap, Comprehensive Documentation, User Manual PDF/Markdown.
- **Lead / PIC:** Lead Full-Stack Engineer & Data Analyst.
