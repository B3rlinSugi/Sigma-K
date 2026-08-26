# 22. PROTOTYPE TO ARCHITECTURE MAPPING: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Product Analyst & Lead Full-Stack Engineer  
> **Tujuan:** Menghubungkan 15 cetak biru layar prototype ([PROTOTYPE_HANDOFF.md](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/PROTOTYPE_HANDOFF.md)) dengan komponen arsitektur backend, database, API, dan realtime.

---

## 1. Matriks Pemetaan Layar Prototype ke Komponen Arsitektur

| Page ID | Nama Layar Prototype | Aktor & Izin Kunci | Endpoint API Backend Terkait | Entitas Data Utama | Komponen Frontend Kunci | Realtime Event Terkait |
|---|---|---|---|---|---|:---:|
| **PAGE-001** | Login & Sesi Otentikasi | Public / Unauth | `POST /api/v1/auth/login` | `users`, `roles` | `LoginForm`, `HeroSplitCard` | - |
| **PAGE-002** | Executive Overview Dashboard | All (`VIEW_DASHBOARD`) | `GET /api/v1/dashboard/summary` | `cabinets`, `institutions` | `MetricGrid`, `CabinetSpotlight`, `ActivityFeed` | `DATA_MUTATED` |
| **PAGE-003** | Katalog Master Instansi | All (`READ_INSTITUTION_LIST`) | `GET /api/v1/institutions` | `institutions`, `types`, `regions` | `DataTable`, `FilterBar`, `SearchInput` | `INSTITUTION_CHANGED` |
| **PAGE-004** | Detail Profil & Legalitas | All / Scoped User | `GET /api/v1/institutions/:id` | `institutions`, `profiles` | `ProfileHeaderCard`, `LegalPdfList` | `DRAFT_UPDATED` |
| **PAGE-005** | Master Kabinet & Periode | Admin (`MANAGE_CABINETS`) | `GET/POST /api/v1/cabinets` | `cabinets`, `cabinet_periods` | `CabinetCardGrid`, `ActiveBadgeModal` | `CABINET_CHANGED` |
| **PAGE-006** | Komposisi Anggota Kabinet | All / Admin | `GET /api/v1/cabinets/active/members` | `cabinet_memberships` | `CategoryTabs`, `MemberEnrollDrawer` | `MEMBERSHIP_UPDATED` |
| **PAGE-007** | Komparasi Antar-Kabinet | All (`VIEW_ANALYTICS`) | `GET /api/v1/cabinets/compare` | `cabinet_memberships`, `lineages` | `DualCabinetBar`, `LineageNodeViewer` | - |
| **PAGE-008** | Visualisasi Bagan Struktur | All / Scoped User | `GET /api/v1/institutions/:id/org-tree` | `organization_units`, `positions` | `ReactFlowCanvas`, `CycleGuardModal` | `TREE_UPDATED` |
| **PAGE-009** | Pengelolaan Tugas & Fungsi | All / Scoped User | `GET/POST /api/v1/tupoksi` | `tugas_fungsi`, `units` | `TupoksiAccordion`, `ArticleCitationInput` | `TUPOKSI_UPDATED` |
| **PAGE-010** | Ruang Kerja Pengajuan | Scoped User (`SUBMIT_CHANGE`) | `POST /api/v1/submissions` | `submission_tickets`, `items` | `ChangeChecklist`, `PdfDropzone` | `TICKET_SUBMITTED` |
| **PAGE-011** | Antrean & Peninjauan Diff | Verifikator (`VERIFY_SUBMISSION`) | `GET/POST /api/v1/verifications` | `submission_tickets`, `items` | `SideBySideDiffViewer`, `DecisionBar` | `VERIFICATION_DONE` |
| **PAGE-012** | Persetujuan Akhir Admin | Admin (`APPROVE_SUBMISSION`) | `POST /api/v1/approvals/:id/publish` | `submission_tickets`, Master Tables | `PendingTable`, `AtomicPublishModal` | `APPROVAL_PUBLISHED` |
| **PAGE-013** | Pusat Notifikasi Realtime | Authenticated Users | `GET /api/v1/notifications` | `notifications` | `RealtimeToast`, `BellHubDrawer` | `NOTIFICATION_PUSH` |
| **PAGE-014** | Penelusur Log Audit | Admin (`VIEW_AUDIT_LOGS`) | `GET /api/v1/audit-logs` | `audit_logs` (Partitioned) | `AuditTable`, `JsonSyntaxModal` | - |
| **PAGE-015** | Analitik Postur & Eselon | Data Analyst, SESDEP | `GET /api/v1/analytics/posture` | `mv_asn_posture_aggregates` | `MultiChartGrid`, `ExportDataBar` | - |

---

## 2. Kesiapan Menuju Presentasi Pimpinan / SESDEP
Dengan pemetaan arsitektural ini, tim pengembang dapat membangun **Interactive Prototype (Fase 4)** yang secara akurat merefleksikan arsitektur backend dan data flow sesungguhnya, siap mendemonstrasikan keunggulan pengelolaan Kabinet Merah Putih dan bagan struktur organisasi di hadapan pimpinan kementerian.
