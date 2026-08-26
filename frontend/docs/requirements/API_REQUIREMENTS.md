# API REQUIREMENTS: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  
> **Catatan:** Dokumen ini mendefinisikan **kebutuhan kemampuan komunikasi data antarmuka sistem (API Capabilities)** pada level kontrak logika. **TIDAK** ada penentuan URL endpoint final, framework backend, atau implementasi kode pada tahap ini.

---

## 1. Standar Umum Kemampuan API
1. **Format Pertukaran Data:** Seluruh operasi API wajib menggunakan format data terstruktur standar (JSON over HTTPS).
2. **Otentikasi & Otorisasi:** Setiap operasi yang dilindungi wajib memverifikasi token otentikasi serta mengevaluasi izin peran (RBAC) dan batasan scope instansi sebelum memproses permintaan.
3. **Respon Terstandardisasi:** Seluruh respon API wajib memuat metadata status keberhasilan, pesan deskriptif, payload data utama, serta kode status kesalahan standar jika terjadi kegagalan.
4. **Paginasi & Penyaringan:** Seluruh operasi yang mengembalikan kumpulan data banyak (katalog/daftar) wajib menyediakan parameter paginasi, pencarian teks, dan filter kategori.

---

## 2. Rincian Kemampuan API per Domain

### A. Authentication & User Management Capability
- **API-CAP-001 (User Login):** System shall provide an operation to authenticate user credentials (username/email and encrypted password) and issue a secure cryptographically signed session token along with user role and scoped institution metadata.
- **API-CAP-002 (User Logout):** System shall provide an operation to invalidate the current user session token and terminate the active session.
- **API-CAP-003 (Get Current User Profile):** System shall provide an operation to retrieve the authenticated user's profile details, assigned role, permissions, and institution scope.
- **API-CAP-004 (Manage Users):** System shall provide administrative operations to create, list, retrieve, update status, and reset passwords for user accounts.

---

### B. Institution Master & Profile Capability
- **API-CAP-005 (List Institutions):** System shall provide an operation to query and retrieve a paginated list of institutions with multi-criteria filtering (institution type, region, active status, cabinet membership).
- **API-CAP-006 (Get Institution Details):** System shall provide an operation to retrieve the comprehensive profile of an institution, including its legal basis, contact info, active organization units, and duty-functions.
- **API-CAP-007 (Create Institution):** System shall provide an operation for administrators to register a new master institution with national unique code validation.
- **API-CAP-008 (Update Institution Base Data):** System shall provide an operation to update master institution attributes with audit logging.
- **API-CAP-009 (Save Institution Profile Draft):** System shall provide an operation for scoped operators to save draft profile changes without altering the active live master data.

---

### C. Cabinet, Cabinet Period & Membership Capability
- **API-CAP-010 (List Cabinets):** System shall provide an operation to retrieve all presidential cabinets along with their period metadata and active status flag.
- **API-CAP-011 (Create/Update Cabinet):** System shall provide an administrative operation to create or update cabinet master records and set the single active cabinet context.
- **API-CAP-012 (Manage Cabinet Periods):** System shall provide an operation to define and validate start/end date ranges and legal decree numbers for a cabinet period.
- **API-CAP-013 (List Cabinet Members):** System shall provide an operation to retrieve all ministry/agency members associated with a specific cabinet period.
- **API-CAP-014 (Add Member to Cabinet):** System shall provide an operation to enroll an institution into a cabinet period with category classification and join date.
- **API-CAP-015 (Remove Member from Cabinet):** System shall provide an operation to record the transition or departure of an institution from a cabinet period with transition lineage metadata.
- **API-CAP-016 (Get Historical Cabinet Comparison):** System shall provide an operation to compare institutional compositions between two selected cabinets and return the delta matrix (new, split, merged, renamed).

---

### D. Organization Structure & Tugas-Fungsi Capability
- **API-CAP-017 (Get Organization Tree):** System shall provide an operation to retrieve the complete hierarchical parent-child unit tree of an institution for interactive org-chart rendering.
- **API-CAP-018 (Save Draft Organization Unit):** System shall provide an operation to create, update, or reposition (re-parent) an organization unit in draft mode with automatic circular dependency validation.
- **API-CAP-019 (Delete Draft Organization Unit):** System shall provide an operation to mark a unit for removal in draft mode with cascade handling for descendant units.
- **API-CAP-020 (Get Tugas & Fungsi):** System shall provide an operation to retrieve structured duty and function records for an institution or specific organization unit.
- **API-CAP-021 (Save Draft Tugas & Fungsi):** System shall provide an operation to create or update structured duty and function points with legal article citations in draft mode.

---

### E. Submission & Verification Workflow Capability
- **API-CAP-022 (Submit Change Ticket):** System shall provide an operation for operators to submit a draft change bundle along with legal document attachments, transitioning the draft to `SUBMITTED` and locking the workspace.
- **API-CAP-023 (List Verification Queue):** System shall provide an operation for verifiers to query submitted tickets with filtering by institution category and submission date.
- **API-CAP-024 (Get Submission Diff Details):** System shall provide an operation to retrieve side-by-side payload diffs (before vs after) and attached legal documents for a specific ticket.
- **API-CAP-025 (Submit Verification Decision):** System shall provide an operation for verifiers to record a verification decision (`PASS`, `REVISION_REQUIRED`, `REJECTED`) along with mandatory feedback notes.
- **API-CAP-026 (Resubmit Revision):** System shall provide an operation for operators to submit corrected draft items in response to revision feedback.

---

### F. Approval & Publishing Capability
- **API-CAP-027 (List Pending Approvals):** System shall provide an operation for administrators to list all verified tickets ready for final authorization.
- **API-CAP-028 (Execute Final Approval):** System shall provide an administrative operation to atomically apply the verified draft changes into the live Master Data tables and close the ticket as `APPROVED`.

---

### G. Realtime Notification & Event Stream Capability
- **API-CAP-029 (Realtime Event Subscription):** System shall provide a persistent realtime connection capability (e.g., event stream / channel) to broadcast instantaneous mutation events to connected clients.
- **API-CAP-030 (List User Notifications):** System shall provide an operation to retrieve the user's notification history with read/unread status.
- **API-CAP-031 (Mark Notification Read):** System shall provide an operation to mark one or all notifications as read for the authenticated user.

---

### H. Audit Trail & Analytics Capability
- **API-CAP-032 (Query Audit Logs):** System shall provide an operation for administrators to search and filter immutable audit records by actor, date range, entity type, and action.
- **API-CAP-033 (Get Executive Dashboard Summary):** System shall provide an operation to aggregate and deliver high-level executive metrics (total institutions, active cabinet stats, pending queues, recent activity feed).
- **API-CAP-034 (Get ASN Posture & Echelon Analytics):** System shall provide an operation to retrieve aggregated ASN posture metrics and echelon distribution datasets for analytical charts.
- **API-CAP-035 (Export Report Dataset):** System shall provide an operation to generate and stream structured export data (PDF / Excel / JSON) for institutions, cabinet compositions, and org structures.
