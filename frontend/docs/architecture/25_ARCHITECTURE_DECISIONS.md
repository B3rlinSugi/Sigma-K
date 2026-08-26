# 25. ARCHITECTURE DECISIONS & WORKFLOW CONFLICT REGISTER: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  

Dokumen ini mencatat keputusan arsitektur resmi (*Architecture Decisions*) serta **Pendaftaran Konflik Alur Kerja Kritis (*Workflow Conflict Register*)** untuk memastikan sistem dirancang adaptif terhadap dinamika kebijakan kementerian.

---

## 1. REGISTRASI KONFLIK ALUR KERJA KRITIS (CRITICAL WORKFLOW CONFLICT REGISTER)

> [!WARNING]
> **TEMUAN KONFLIK ARSITEKTUR ALUR KERJA:**  
> Terdapat perbedaan alur tahapan peninjauan data kelembagaan antara spesifikasi Phase 1 dengan konsep fondasi backend legacy terdahulu.

| Parameter Evaluasi | Versi A: Dokumen Phase 1 (Standard Verification) | Versi B: Konsep Backend Terdahulu (Admin Triage First) |
|---|---|---|
| **Urutan Tahapan** | `USER` $\rightarrow$ `VERIFIKATOR` $\rightarrow$ `ADMIN (Approval)` | `USER` $\rightarrow$ `ADMIN (Triage/Review)` $\rightarrow$ `VERIFIER` $\rightarrow$ `FINAL APPROVAL` |
| **Peran Peninjau Pertama** | Verifikator (Analis Kelembagaan KemenPANRB). | Admin (Disposisi berkas ke tim verifikator spesifik). |
| **Karakteristik Alur** | Alur linear langsung ke antrean tim pemeriksa. | Alur penugasan terpusat (*Centralized Assignment Triage*). |
| **Dampak Arsitektur** | Membutuhkan antrean bersama (*Shared Pool Queue*). | Membutuhkan modul penugasan tiket (*Ticket Assignment Module*). |

### Solusi Arsitektural: *Configurable Extensible State Machine Engine*
Untuk menyelesaikan konflik ini tanpa membuat asumsi sepihak, arsitektur backend **TIDAK MENGUNCI URUTAN SECARA HARDCODED**. 

Workflow dirancang menggunakan **State Machine Pattern** yang didefinisikan melalui *State Transition Table*:

```typescript
// Konfigurasi State Transition yang Dapat Diubah Tanpa Refactor Kode
export const WORKFLOW_CONFIG = {
  default_mode: 'STANDARD_VERIFICATION', // Versi A (Default Phase 1)
  transitions: {
    DRAFT: ['SUBMITTED'],
    SUBMITTED: ['IN_REVIEW', 'ADMIN_TRIAGED', 'REJECTED'],
    ADMIN_TRIAGED: ['IN_REVIEW', 'REJECTED'], // Mendukung Versi B jika diaktifkan
    IN_REVIEW: ['VERIFIED', 'REVISION_REQUIRED', 'REJECTED'],
    REVISION_REQUIRED: ['RESUBMITTED'],
    RESUBMITTED: ['IN_REVIEW'],
    VERIFIED: ['APPROVED', 'REJECTED'],
    APPROVED: [] // Final Published State
  }
};
```
*Keputusan Stakeholder yang Dibutuhkan:* Konfirmasi final dari SESDEP / Pak Sigit / Kak Nabila mengenai apakah Admin perlu melakukan penugasan berkas terlebih dahulu sebelum verifikator meneliti.

---

## 2. Ringkasan Keputusan Arsitektur Resmi (Decisions Log)

- **DEC-ARCH-001 (Architecture Style):** Mengadopsi *Modular Monolith with Event-Driven Realtime Components*.
- **DEC-ARCH-002 (Frontend Stack):** Menetapkan *Next.js 14+ (App Router) + React + TypeScript + Tailwind CSS + React Flow*.
- **DEC-ARCH-003 (Backend Stack):** Menetapkan *NestJS 10+ (TypeScript Strict)* sebagai rekomendasi utama.
- **DEC-ARCH-004 (Primary Database):** Menetapkan *PostgreSQL 16* (mendukung Recursive CTE, JSONB indexing, dan ACID compliance).
- **DEC-ARCH-005 (Authentication):** Mengadopsi *Pluggable Dual-Token Strategy (Bcrypt + JWT/Redis Session + Ready OIDC Adapter)*.
- **DEC-ARCH-006 (Authorization):** Mengadopsi *Fine-Grained RBAC + Scoped Institution Access Guard (BOLA Defense)*.
- **DEC-ARCH-007 (Realtime Engine):** Mengadopsi *Socket.io / WebSocket Gateway terintegrasi Event Dispatcher & Redis PubSub*.
- **DEC-ARCH-008 (Organization Hierarchy):** Mengadopsi *Adjacency List (`parent_id`) dengan Recursive CTE & DFS Cycle Guard*.
- **DEC-ARCH-009 (Cabinet & Lineage):** Mengadopsi *Normalized Relational Cabinet Model (`Cabinet`, `CabinetPeriod`, `CabinetMembership`, `InstitutionLineage`)*.
- **DEC-ARCH-010 (API Architecture):** Mengadopsi *RESTful API v1 (`/api/v1/`) dengan Uniform Response Wrapper & OpenAPI Docs*.
- **DEC-ARCH-011 (File Storage):** Mengadopsi *Pluggable Storage Driver (Local Disk for Dev, MinIO/S3 for Staging/Production)*.
- **DEC-ARCH-012 (Search Engine):** Mengadopsi *PostgreSQL Native Full-Text Search + `pg_trgm` GIN Indexing*.
- **DEC-ARCH-013 (Analytics Strategy):** Mengadopsi *Materialized Views + Analytics Redis Cache + Read-Only Analyst Access*.
- **DEC-ARCH-014 (DevOps & Deployment):** Mengadopsi *Multi-Stage Docker Containerization + Docker Compose + Nginx Reverse Proxy*.
- **DEC-ARCH-015 (Repository Strategy):** Mengadopsi *Monorepo Workspace (Turborepo) untuk penyelarasan tipe data dan kecepatan magang*.
