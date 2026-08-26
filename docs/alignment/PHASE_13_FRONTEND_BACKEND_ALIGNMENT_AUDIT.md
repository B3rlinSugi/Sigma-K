# PHASE 13 — FRONTEND / BACKEND CONTRACT & BUSINESS ALIGNMENT AUDIT REPORT

**Project**: E-SKLD / SIGMA-K (Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan)  
**Organization**: Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (KemenPANRB)  
**Audit Scope**: Read-Only Architecture, Contract, Domain Model, Workflow, Authorization, and Database Alignment  
**Audit Date**: August 26, 2026  
**Auditor**: Senior Systems Architect & Security Reviewer  
**Audit Status**: `READ-ONLY AUDIT COMPLETE`  

---

## 1. Executive Summary

This document presents a comprehensive, read-only architectural and business alignment audit between the Next.js 14 frontend prototype (`SIGMA-K`) and the verified CodeIgniter 4 backend implementation (`E-SKLD`, `student-management-api`).

### Summary of Audit Scope & Status:
- **Frontend Prototype**: Next.js 14 (App Router) with TypeScript, TailwindCSS, Lucide Icons, and React Flow (`@xyflow/react`). Currently operates entirely on **simulated in-memory mock datasets** (`src/data/mock/`) with zero live HTTP API integration.
- **Backend Implementation**: CodeIgniter 4.4.8 (PHP 8.0.30+) with MySQL 8.x / MariaDB (`eskld_db`). Fully verified with **198 automated PHPUnit tests, 713 assertions, 0 errors, 0 failures (100% PASS)**, implementing 38 REST endpoints under a Zero-Trust 6-factor authorization model.
- **Key Architectural Findings**:
  1. *Stack Divergence*: Prototype documentation (`docs/prototype/08_API_INTEGRATION_MAPPING.md`) documented target backend as NestJS/PostgreSQL, whereas the actual verified technical implementation is CodeIgniter 4/MySQL.
  2. *Final Approval Authority*: The prototype frontend workflow assigned final master data approval to `ADMIN`, whereas the verified business rule and backend implementation strictly designate the **`VERIFIER`** as the sole final approval authority.
  3. *Unbacked Prototype Screens*: Features for Cabinet History / Silsilah Antar-Kabinet (`/cabinets/compare`) and Tugas & Fungsi (`/tupoksi`) are currently powered by mock data and have no backing tables in the immutable 21-table `eskld_db` backend schema.
  4. *Auth & Session Mismatch*: The frontend uses client-side localStorage role switching (`sigma_demo_role`) with persona names (`VERIFIKATOR`, `SESDEP`), whereas the backend authenticates via Bearer JWT with production roles (`USER`, `ADMIN`, `VERIFIER`, `SUPER_ADMIN`).

---

## 2. Current System Baseline

| Dimension | Frontend Prototype (`SIGMA-K`) | Backend Implementation (`E-SKLD`) | Database Baseline (`eskld_db`) |
|---|---|---|---|
| **Technology** | Next.js 14.2.3, React 18, TypeScript 5, TailwindCSS 3.4 | CodeIgniter 4.4.8, PHP 8.0.30+, Composer | MySQL 8.0.30+ / MariaDB 10.6+ |
| **Data Source** | 100% In-Memory Mock Data (`src/data/mock/*.ts`) | Relational MySQL Database with InnoDB | 21 Tables (Strictly Immutable Schema) |
| **API Client** | Mock async delays (`setTimeout`) in `src/services/api/` | 38 JSON REST Endpoints under `/api/v1/` | N/A |
| **Auth & Identity** | `RoleContext` with `localStorage` mockup | JWT (`HS256`), `AuthFilter`, `AuthContext` | `users`, `roles`, `permissions`, `role_permissions` |
| **Roles Recognized** | `USER`, `VERIFIKATOR`, `ADMIN`, `SESDEP` | `USER`, `ADMIN`, `VERIFIER`, `SUPER_ADMIN` | 4 Roles in `roles` table |
| **Workflow Engine** | Prototype 5-Step Stepper in `workflow.config.ts` | Zero-Trust State Machine in Domain Services | 7 Lifecycle / Review Tracking Tables |
| **Test Coverage** | 0 Automated Frontend Tests | 198 Tests, 713 Assertions (100% Pass) | Full relational FK validation scripts |

---

## 3. Frontend Screen Inventory

Audit of all 17 screens across `SIGMA-K/src/app/(dashboard)`:

| Screen ID | Route (`Path`) | Screen Name | Primary Role | Purpose | Current Data Source | Current State | Integration Status |
|:---:|:---|:---|:---|:---|:---|:---|:---|
| `SCR-01` | `/` | Dashboard Eksekutif | All / `SESDEP` | Executive KPIs, active cabinet composition, submission funnel, recent tickets | `MOCK_KPIS`, `MOCK_CABINETS`, `MOCK_SUBMISSIONS` | Prototype Only | **Mock-data only** |
| `SCR-02` | `/cabinets` | Manajemen Kabinet | `ADMIN`, `SESDEP` | Cabinet eras list (Indonesia Maju, Merah Putih), status toggles | `MOCK_CABINETS` | Prototype Only | **Mock-data only** |
| `SCR-03` | `/cabinets/new` | Registrasi Kabinet | `ADMIN` | New cabinet registration form with legal decree metadata | Local React Form State | Prototype Only | **Mock-data only** |
| `SCR-04` | `/cabinets/[id]` | Rincian Kabinet | All | 48 K/L composition, category filters (Kemenko, Teknis) | `MOCK_CABINETS`, `MOCK_CABINET_MEMBERSHIPS` | Prototype Only | **Mock-data only** |
| `SCR-05` | `/cabinets/compare` | Komparasi Kabinet | All / `SESDEP` | Silsilah diff showcase (+7 baru, 3 split, 1 merge, 5 rename, 22 tetap) | `MOCK_CABINET_COMPARISON` | Prototype Only | **Mock-data only** |
| `SCR-06` | `/institutions` | Katalog Instansi | All | Search & filter K/L and 548 Pemda | `MOCK_INSTITUTIONS` | Prototype Only | **Mock-data only** |
| `SCR-07` | `/institutions/[id]` | Profil Instansi | All | Institution details, Perpres 147/2024 basis, units list, tupoksi | `MOCK_INSTITUTIONS`, `MOCK_ORG_UNITS` | Prototype Only | **Mock-data only** |
| `SCR-08` | `/tupoksi` | Master Tupoksi | All | Duties & functions list by legal articles, proposal modal | `MOCK_TUPOKSI` | Prototype Only | **Mock-data only** |
| `SCR-09` | `/structure` | Bagan Organisasi | All | Interactive React Flow canvas with zoom, pan, search, unit drawer | `MOCK_ORG_UNITS` | Prototype Only | **Mock-data only** |
| `SCR-10` | `/submissions` | Pengajuan Usulan | `USER`, `ADMIN` | Ticket list with status tabs, search, new submission modal | `MOCK_SUBMISSIONS` | Prototype Only | **Mock-data only** |
| `SCR-11` | `/submissions/[id]` | Rincian Pengajuan | All | 5-step stepper, DiffViewer, legal doc download, action triggers | `MOCK_SUBMISSIONS` | Prototype Only | **Mock-data only** |
| `SCR-12A`| `/verifications` | Antrean Verifikasi | `VERIFIKATOR` | Inbound proposal screening queue with SLA badge | `MOCK_SUBMISSIONS` | Prototype Only | **Mock-data only** |
| `SCR-12B`| `/verifications/[id]`| Ruang Telaah Verifikasi | `VERIFIKATOR` | Side-by-side review workspace with Pass / Revision / Reject modal | `MOCK_SUBMISSIONS` | Prototype Only | **Mock-data only** |
| `SCR-13` | `/submissions/[id]/revision` | Respon Revisi | `USER` | Operator response form to verifier notes with payload diff | `MOCK_SUBMISSIONS` | Prototype Only | **Mock-data only** |
| `SCR-14` | `/notifications` | Pusat Notifikasi | All | Workflow, security, and master data notification list | `MOCK_NOTIFICATIONS` | Prototype Only | **Mock-data only** |
| `SCR-15` | `/analytics` | Intelijensi Data | `SESDEP`, `ADMIN`| Delayering ratio, readiness index, turnaround speed charts | `MOCK_KPIS`, `MOCK_ECHELON_DISTRIBUTION` | Prototype Only | **Mock-data only** |
| `SCR-16` | `/audit-logs` | Audit Trail | `ADMIN`, `SESDEP` | Append-only forensic log table with JSON before/after modal | `MOCK_AUDIT_LOGS` | Prototype Only | **Mock-data only** |

---

## 4. Frontend Service Inventory

Audit of all 8 service classes in `SIGMA-K/src/services/api/`:

| Service Name | Method Name | Expected Request | Expected Response | Current Data Source | Backend REST Endpoint Equivalent | Auth / Scope Requirement | Status |
|:---|:---|:---|:---|:---|:---|:---|:---|
| `AuthService` | `getUsers()` | None | `User[]` | `MOCK_USERS` | `GET /api/v1/auth/me` (Individual) | Authenticated | **Mock-data only** |
| | `getUserById()` | `id: string` | `User \| null` | `MOCK_USERS` | None (Direct profile query) | Authenticated | **Mock-data only** |
| `InstitutionService` | `getInstitutions()` | `{ search, type, status }` | `Institution[]` | `MOCK_INSTITUTIONS` | `GET /api/v1/institutions` | Authenticated + Scope | **Mock-data only** |
| | `getInstitutionById()` | `id: string` | `Institution \| null` | `MOCK_INSTITUTIONS` | `GET /api/v1/institutions/:id` | Authenticated + Scope | **Mock-data only** |
| `CabinetService` | `getCabinets()` | None | `Cabinet[]` | `MOCK_CABINETS` | *No Backend Endpoint* | Unauthenticated | **Unbacked Mock** |
| | `getCabinetById()` | `id: string` | `Cabinet \| null` | `MOCK_CABINETS` | *No Backend Endpoint* | Unauthenticated | **Unbacked Mock** |
| | `getCabinetMemberships()`| `periodId?: string` | `CabinetMembership[]` | `MOCK_CABINET_MEMBERSHIPS` | *No Backend Endpoint* | Unauthenticated | **Unbacked Mock** |
| | `getCabinetComparison()` | `baseId, targetId` | `CabinetComparisonSummary` | `MOCK_CABINET_COMPARISON` | *No Backend Endpoint* | Unauthenticated | **Unbacked Mock** |
| `OrganizationService`| `getOrgUnitsByInstitutionId()`| `institutionId: string`| `OrganizationUnit[]` | `MOCK_ORG_UNITS` | `GET /api/v1/institutions/:id/units` | Authenticated + Scope | **Mock-data only** |
| | `getTupoksiByInstitutionId()`| `institutionId: string`| `TupoksiItem[]` | `MOCK_TUPOKSI` | *No Backend Endpoint* | Authenticated + Scope | **Unbacked Mock** |
| `SubmissionService` | `getSubmissions()` | `{ status, search }` | `SubmissionTicket[]` | `currentSubmissions` | `GET /api/v1/submissions` | Authenticated + Scope | **Mock-data only** |
| | `getSubmissionById()` | `id: string` | `SubmissionTicket \| null` | `currentSubmissions` | `GET /api/v1/submissions/:id` | Authenticated + Scope | **Mock-data only** |
| | `updateStatus()` | `id, status, notes, verifier` | `SubmissionTicket \| null` | `currentSubmissions` | Specialized workflow endpoints (see Section 5) | Role + State + SoD | **Contract Mismatch** |
| `NotificationService`| `getNotifications()` | None | `NotificationItem[]` | `currentNotifs` | *No Backend Endpoint* | Authenticated | **Unbacked Mock** |
| `AnalyticsService` | `getKPIs()` | None | `KPICandidate[]` | `MOCK_KPIS` | `GET /api/v1/reports/summary` | Authenticated + Scope | **Contract Mismatch** |
| `AuditService` | `getAuditLogs()` | `{ search, action, instId }` | `AuditLogEntry[]` | `MOCK_AUDIT_LOGS` | `GET /api/v1/audit-logs` | `audit:read` Permission | **Contract Mismatch** |

---

## 5. Backend API Inventory

Audit of all 38 REST endpoints implemented in `KemenPANRB/app/Config/Routes.php`:

| Method | Endpoint | Controller::Method | Auth Filter | Required Permission / Scope | Request Body / Query | Status / Test Coverage | Frontend Consumer |
|:---:|:---|:---|:---:|:---|:---|:---:|:---|
| `GET` | `/api/v1/health` | `HealthController::index` | None | Public | None | PASS (Step 1) | None (Infra) |
| `POST`| `/api/v1/auth/login` | `AuthController::login` | None | Public | `{ username, password }` | PASS (Step 2) | Missing in UI |
| `GET` | `/api/v1/auth/me` | `AuthController::me` | `auth` | Authenticated | None | PASS (Step 2) | Missing in UI |
| `POST`| `/api/v1/auth/logout` | `AuthController::logout` | `auth` | Authenticated | None | PASS (Step 2) | Missing in UI |
| `GET` | `/api/v1/institutions` | `InstitutionController::index` | `auth` | `institution:read` / Scope | `?search=&type=&status=` | PASS (Step 3) | `InstitutionService` |
| `GET` | `/api/v1/institutions/(:num)` | `InstitutionController::show/$1` | `auth` | `institution:read` / Scope | None | PASS (Step 3) | `InstitutionService` |
| `GET` | `/api/v1/institutions/(:num)/units` | `OrganizationalUnitController::getInstitutionTree/$1` | `auth` | `institution:read` / Scope | None | PASS (Step 4) | `OrganizationService` |
| `GET` | `/api/v1/units/(:num)` | `OrganizationalUnitController::show/$1` | `auth` | `institution:read` / Scope | None | PASS (Step 4) | None (Direct) |
| `GET` | `/api/v1/units/(:num)/positions` | `PositionController::getByUnit/$1` | `auth` | `institution:read` / Scope | None | PASS (Step 4) | None (Direct) |
| `GET` | `/api/v1/positions/(:num)` | `PositionController::show/$1` | `auth` | `institution:read` / Scope | None | PASS (Step 4) | None (Direct) |
| `GET` | `/api/v1/me/scopes` | `ScopeController::myScopes` | `auth` | Authenticated | None | PASS (Step 3) | Missing in UI |
| `GET` | `/api/v1/me/access-grants` | `AccessGrantController::myGrants` | `auth` | Authenticated | None | PASS (Step 3) | Missing in UI |
| `POST`| `/api/v1/access-requests` | `AccessRequestController::create` | `auth` | Authenticated | `{ target_institution_id, reason }` | PASS (Step 3) | Missing in UI |
| `GET` | `/api/v1/admin/submissions/queue` | `AdminWorkflowController::queue` | `auth` | `ADMIN` Role / Gate 1 | `?status=&search=` | PASS (Step 6) | `verifications/page.tsx` |
| `GET` | `/api/v1/verifier/submissions/assigned` | `VerifierWorkflowController::assigned` | `auth` | `VERIFIER` Role / Gate 2 | None | PASS (Step 7) | `verifications/page.tsx` |
| `POST`| `/api/v1/submissions` | `SubmissionController::create` | `auth` | `submission:create` / `USER` | `{ institution_id, title, submission_year }` | PASS (Step 5) | `submissions/page.tsx` |
| `GET` | `/api/v1/submissions` | `SubmissionController::index` | `auth` | `submission:read` / Scope | `?status=&search=` | PASS (Step 5) | `SubmissionService` |
| `GET` | `/api/v1/submissions/(:num)` | `SubmissionController::show/$1` | `auth` | `submission:read` / Scope | None | PASS (Step 5) | `SubmissionService` |
| `POST`| `/api/v1/submissions/(:num)/submit` | `SubmissionController::submit/$1` | `auth` | `submission:submit` / Author | `{ notes }` | PASS (Step 5) | `SubmissionService` |
| `POST`| `/api/v1/submissions/(:num)/units` | `SubmissionUnitController::create/$1` | `auth` | `submission:update` / Draft | Unit Change Payload | PASS (Step 5) | Missing in UI |
| `PUT` | `/api/v1/submissions/(:num)/units/(:num)` | `SubmissionUnitController::update/$1/$2` | `auth` | `submission:update` / Draft | Unit Update Payload | PASS (Step 5) | Missing in UI |
| `DELETE`| `/api/v1/submissions/(:num)/units/(:num)` | `SubmissionUnitController::delete/$1/$2` | `auth` | `submission:update` / Draft | None | PASS (Step 5) | Missing in UI |
| `POST`| `/api/v1/submissions/(:num)/positions` | `SubmissionPositionController::create/$1` | `auth` | `submission:update` / Draft | Position Change Payload | PASS (Step 5) | Missing in UI |
| `PUT` | `/api/v1/submissions/(:num)/positions/(:num)` | `SubmissionPositionController::update/$1/$2` | `auth` | `submission:update` / Draft | Position Update Payload | PASS (Step 5) | Missing in UI |
| `DELETE`| `/api/v1/submissions/(:num)/positions/(:num)` | `SubmissionPositionController::delete/$1/$2` | `auth` | `submission:update` / Draft | None | PASS (Step 5) | Missing in UI |
| `POST`| `/api/v1/submissions/(:num)/admin-review/accept` | `AdminWorkflowController::accept/$1` | `auth` | `ADMIN` Role / Gate 1 | `{ notes }` | PASS (Step 6) | Missing in UI |
| `POST`| `/api/v1/submissions/(:num)/admin-review/return` | `AdminWorkflowController::returnRevision/$1` | `auth` | `ADMIN` Role / Gate 1 | `{ reason }` | PASS (Step 6) | Missing in UI |
| `POST`| `/api/v1/submissions/(:num)/assign-verifier` | `AdminWorkflowController::assignVerifier/$1` | `auth` | `ADMIN` Role / Gate 1 | `{ verifier_id, notes }` | PASS (Step 6) | Missing in UI |
| `POST`| `/api/v1/submissions/(:num)/verifier-review/start` | `VerifierWorkflowController::start/$1` | `auth` | Assigned `VERIFIER` | `{ notes }` | PASS (Step 7) | `verifications/[id]` |
| `POST`| `/api/v1/submissions/(:num)/verifier-review/notes` | `VerifierWorkflowController::addNote/$1` | `auth` | Assigned `VERIFIER` | `{ version_unit_id, issue_description }`| PASS (Step 7) | `verifications/[id]` |
| `POST`| `/api/v1/submissions/(:num)/verifier-review/return` | `VerifierWorkflowController::returnRevision/$1` | `auth` | Assigned `VERIFIER` | `{ notes }` | PASS (Step 7) | `verifications/[id]` |
| `POST`| `/api/v1/submissions/(:num)/verifier-review/approve` | `VerifierWorkflowController::approve/$1` | `auth` | Assigned `VERIFIER` | `{ recommendation_summary, notes }` | PASS (Step 9) | `verifications/[id]` |
| `GET` | `/api/v1/submissions/(:num)/revision` | `RevisionController::show/$1` | `auth` | `USER` / Author | None | PASS (Step 8) | `submissions/[id]/revision` |
| `POST`| `/api/v1/submissions/(:num)/revision` | `RevisionController::start/$1` | `auth` | `USER` / Author | `{ notes }` | PASS (Step 8) | `submissions/[id]/revision` |
| `POST`| `/api/v1/submissions/(:num)/resubmit` | `RevisionController::resubmit/$1` | `auth` | `USER` / Author | `{ notes }` | PASS (Step 8) | `submissions/[id]/revision` |
| `POST`| `/api/v1/submissions/(:num)/approve` | `ApprovalWorkflowController::approve/$1` | `auth` | Assigned `VERIFIER` | `{ approval_number, notes }` | PASS (Step 10) | `submissions/[id]` |
| `POST`| `/api/v1/submissions/(:num)/promote` | `ApprovalWorkflowController::promote/$1` | `auth` | Assigned `VERIFIER` | None | PASS (Step 10) | `submissions/[id]` |
| `GET` | `/api/v1/audit-logs` | `AuditLogController::index` | `auth` | `audit:read` / Scope | `?search=&action_event=&limit=&page=` | PASS (Step 11) | `AuditService` |
| `GET` | `/api/v1/reports/summary` | `ReportController::summary` | `auth` | `report:read` / Scope | None | PASS (Step 11) | `AnalyticsService` |
| `GET` | `/api/v1/reports/export` | `ReportController::export` | `auth` | `report:read` / Scope | `?type=submissions&format=csv` | PASS (Step 11) | Missing in UI |

---

## 6. Frontend ↔ Backend Contract Matrix

| Feature Area | Frontend Service / UI Hook | Target Backend Endpoint | Status / Finding | Classification |
|:---|:---|:---|:---|:---:|
| **Authentication** | `RoleContext.switchRole()` | `POST /api/v1/auth/login` | Frontend has no login screen or JWT token storage. Uses mock array. | `P0 — BLOCKER` |
| **User Profile** | `AuthService.getUserById()` | `GET /api/v1/auth/me` | Mismatch: Frontend fetches static user objects; Backend returns token claims + scope context. | `P1 — CRITICAL` |
| **Institution List** | `InstitutionService.getInstitutions()` | `GET /api/v1/institutions` | Field mappings: `code` $\leftrightarrow$ `institution_code`, `shortName` $\leftrightarrow$ `short_name`. | `P2 — IMPORTANT` |
| **Org Structure Tree**| `OrganizationService.getOrgUnitsByInstitutionId()` | `GET /api/v1/institutions/:id/units` | Match: Backend tree returns hierarchical Adjacency List matching React Flow. | `P2 — IMPORTANT` |
| **Draft Creation** | New Submission Modal in `submissions/page.tsx` | `POST /api/v1/submissions` | Mismatch: UI creates in-memory ticket with arbitrary items; Backend requires snapshot versioning. | `P0 — BLOCKER` |
| **Unit Changes** | Modal form in `structure/page.tsx` | `POST /api/v1/submissions/:id/units` | Frontend does not invoke granular unit CRUD endpoints. | `P1 — CRITICAL` |
| **Gate 1 Screening** | Missing in UI | `POST /api/v1/submissions/:id/admin-review/accept` | Frontend collapses Gate 1 and Gate 2 into a single generic "Verifikasi" screen. | `P1 — CRITICAL` |
| **Gate 2 Substantive**| `VerificationWorkspacePage` | `POST /api/v1/submissions/:id/verifier-review/approve` | Mismatch: UI calls generic `updateStatus('VERIFIED')`; Backend requires substantive recommendation. | `P1 — CRITICAL` |
| **Final Approval** | `handleAdminApprove` in `submissions/[id]` | `POST /api/v1/submissions/:id/approve` | **CRITICAL BUSINESS DISCREPANCY**: UI triggers final approval as `ADMIN`; Backend requires `VERIFIER`. | `P0 — BLOCKER` |
| **Master Promotion** | Embedded in UI Approval Button | `POST /api/v1/submissions/:id/promote` | Backend provides explicit, non-destructive reconciliation endpoint. | `P1 — CRITICAL` |
| **Audit Logs** | `AuditService.getAuditLogs()` | `GET /api/v1/audit-logs` | Field mappings: `actorName` $\leftrightarrow$ `actor_id` join, `action` $\leftrightarrow$ `action_event`. | `P2 — IMPORTANT` |
| **Executive KPIs** | `AnalyticsService.getKPIs()` | `GET /api/v1/reports/summary` | Mismatch: UI expects static candidate KPIs; Backend returns real pipeline counts. | `P2 — IMPORTANT` |
| **Cabinet Silsilah**| `CabinetService.getCabinetComparison()` | *None (Unbacked)* | Frontend displays mock diff (+7 baru, 3 split, 1 merge). Backend schema has no cabinet tables. | `INFO / DEMO DATA` |
| **Tupoksi** | `OrganizationService.getAllTupoksi()` | *None (Unbacked)* | Frontend displays legal articles. Backend schema has no `tupoksi` table. | `INFO / DEMO DATA` |

---

## 7. Domain Model Mapping

| Domain Entity | Frontend TypeScript Interface | Backend PHP Entity | Database Table (`eskld_db`) | Field Compatibility | Breaking Differences / Findings |
|:---|:---|:---|:---|:---|:---|
| **User** | `User` (`src/types/auth.ts`) | `UserEntity` | `users` | Partial Match | Frontend uses `string` UUID for `id`, `nip`, `fullName`; Backend uses `BIGINT UNSIGNED` `id`, `password_hash`, `full_name`, `home_institution_id`. |
| **Role** | `UserRole` (`'USER' \| 'VERIFIKATOR' \| 'ADMIN' \| 'SESDEP'`) | `RoleModel` / RBAC | `roles` | Mismatch | Frontend uses Indonesian spelling `'VERIFIKATOR'` and prototype persona `'SESDEP'`; Backend strictly uses `'USER'`, `'ADMIN'`, `'VERIFIER'`, `'SUPER_ADMIN'`. |
| **Institution** | `Institution` (`src/types/institution.ts`) | `InstitutionEntity` | `institutions` | Good Match | `code` $\leftrightarrow$ `institution_code`, `name` $\leftrightarrow$ `name`, `shortName` $\leftrightarrow$ `short_name`, `status` $\leftrightarrow$ `status`. |
| **Org Unit** | `OrganizationUnit` (`src/types/organization.ts`) | `OrganizationalUnitEntity` | `organizational_units` | Good Match | `parentId` $\leftrightarrow$ `parent_id`, `unitCode` $\leftrightarrow$ `unit_code`, `unitName` $\leftrightarrow$ `unit_name`, `sortOrder` $\leftrightarrow$ `sort_order`, `hierarchyLevel` $\leftrightarrow$ `hierarchy_level`. |
| **Position** | Inlined in `OrganizationUnit` (`staffCount`) | `PositionEntity` | `positions` | Mismatch | Backend has rich `positions` table (`position_name`, `position_type`, `formation_count`, `echelon_level`); Frontend only tracks scalar `staffCount`. |
| **Submission** | `SubmissionTicket` (`src/types/submission.ts`) | `SubmissionEntity` | `submissions` | Mismatch | Frontend uses single flat ticket object with inlined `items[]`; Backend uses versioned relational model (`submissions` $\rightarrow$ `submission_versions` $\rightarrow$ `submission_units`). |
| **Workflow State**| `WorkflowStatus` (`DRAFT`, `SUBMITTED`, `IN_REVIEW`, `VERIFIED`, `APPROVED`, etc.) | `SubmissionEntity::$current_state` | `submissions.current_state` | Mismatch | Backend has formal 11-state machine (`SUBMITTED_TO_ADMIN`, `ASSIGNED_TO_VERIFIER`, `READY_FOR_FINAL_DECISION`, `PROMOTED`). |
| **Verification** | `VerificationLog` (`src/types/submission.ts`) | `VerificationRecordEntity` | `verification_records` | Good Match | `verifierUserId` $\leftrightarrow$ `verifier_id`, `decision` $\leftrightarrow$ `decision`, `notes` $\leftrightarrow$ `notes`. |
| **Audit Log** | `AuditLogEntry` (`src/types/audit.ts`) | `AuditLogEntity` | `audit_logs` | Good Match | `actorId` $\leftrightarrow$ `actor_id`, `action` $\leftrightarrow$ `action_event`, `oldValues` $\leftrightarrow$ `old_payload`, `newValues` $\leftrightarrow$ `new_payload`. |
| **Cabinet / Lineage**| `CabinetComparisonSummary` (`src/types/cabinet.ts`) | *None* | *None* | **Unbacked** | 100% Demo/Prototype data in frontend. No database table exists. |
| **Tupoksi** | `TupoksiItem` (`src/types/organization.ts`) | *None* | *None* | **Unbacked** | 100% Demo/Prototype data in frontend. No database table exists. |

---

## 8. Database Mapping

Comparison between Frontend Prisma Schema (`schema.prisma`) and Backend MySQL Schema (`01_schema.sql`):

| Database Table (`eskld_db`) | Prisma Entity (`schema.prisma`) | Storage Engine & Type | Alignment Finding |
|:---|:---|:---|:---|
| `roles` | `Role` | MySQL InnoDB / `BIGINT` | Matched conceptually; Prisma modeled permissions as JSON array, MySQL uses relational `role_permissions`. |
| `permissions` | Inlined in `Role` | MySQL InnoDB / `BIGINT` | MySQL implements normalized atomic permissions (21 permissions). |
| `role_permissions` | Inlined in `Role` | MySQL InnoDB / `BIGINT` | Normalized junction table in MySQL. |
| `institutions` | `Institution` | MySQL InnoDB / `BIGINT` | Matched. Prisma modeled UUID PK; MySQL uses auto-increment `BIGINT UNSIGNED`. |
| `users` | `User` | MySQL InnoDB / `BIGINT` | Matched. MySQL uses `home_institution_id` FK and `password_hash`. |
| `user_scopes` | `UserInstitutionScope` | MySQL InnoDB / `BIGINT` | Matched multi-tenant boundary model. |
| `access_grants` | None in Prisma | MySQL InnoDB / `BIGINT` | Backend provides temporal delegative access grants with TTL. |
| `access_requests` | None in Prisma | MySQL InnoDB / `BIGINT` | Backend provides formal access request & approval workflow. |
| `organizational_units` | `OrganizationUnit` | MySQL InnoDB / `BIGINT` | Matched. Adjacency list tree structure (`parent_id`). |
| `positions` | None in Prisma | MySQL InnoDB / `BIGINT` | Backend models granular position formations and echelon ranks. |
| `submissions` | `SubmissionTicket` | MySQL InnoDB / `BIGINT` | Backend separates proposal metadata from immutable snapshot versions. |
| `submission_versions` | None in Prisma | MySQL InnoDB / `BIGINT` | Backend implements immutable version snapshotting (`v1`, `v2`, etc.). |
| `submission_units` | `SubmissionItem` | MySQL InnoDB / `BIGINT` | Backend isolates proposed unit mutations (`NEW`, `UPDATE`, `DELETE`, `UNCHANGED`). |
| `submission_positions` | `SubmissionItem` | MySQL InnoDB / `BIGINT` | Backend isolates proposed position mutations and formation deltas. |
| `admin_reviews` | Inlined in `SubmissionTicket` | MySQL InnoDB / `BIGINT` | Backend tracks Gate 1 administrative screening records. |
| `verifier_assignments` | None in Prisma | MySQL InnoDB / `BIGINT` | Backend tracks workload distribution and active verifier assignment. |
| `verification_records` | `VerificationLog` | MySQL InnoDB / `BIGINT` | Matched substantive evaluation findings. |
| `revision_notes` | `SubmissionRevision` | MySQL InnoDB / `BIGINT` | Matched inline revision notes. |
| `recommendation_records`| None in Prisma | MySQL InnoDB / `BIGINT` | Backend tracks formal technical recommendation before final decree. |
| `approval_records` | Inlined in `SubmissionTicket` | MySQL InnoDB / `BIGINT` | Backend tracks formal SK decree numbers and approval metadata. |
| `audit_logs` | `AuditLog` | MySQL InnoDB / `BIGINT` | Matched append-only forensic event log. |
| *None* (Unbacked in DB) | `Cabinet`, `CabinetPeriod`, `CabinetMembership`, `InstitutionLineage` | PostgreSQL (Prisma Prototype) | Frontend demo tables not present in MySQL schema. |
| *None* (Unbacked in DB) | `TupoksiItem` | PostgreSQL (Prisma Prototype) | Frontend demo tables not present in MySQL schema. |

---

## 9. Workflow Alignment

Comparison of Frontend Prototype State Machine (`workflow.config.ts`) vs Backend Implementation:

```mermaid
graph TD
    DRAFT["DRAFT (Operator)"] -->|Submit Draft| S_ADM["SUBMITTED_TO_ADMIN"]
    S_ADM -->|Gate 1 Return| REV_ADM["REVISION_REQUIRED"]
    S_ADM -->|Gate 1 Accept| REV_IN_ADM["IN_REVIEW_BY_ADMIN"]
    REV_ADM -->|Branch v2 & Resubmit| S_ADM
    REV_IN_ADM -->|Assign Verifier| ASG_VER["ASSIGNED_TO_VERIFIER"]
    ASG_VER -->|Start Gate 2| IN_VER["IN_REVIEW_BY_VERIFIER"]
    IN_VER -->|Gate 2 Return Notes| REV_VER["REVISION_REQUIRED_BY_VERIFIER"]
    REV_VER -->|Branch v2 & Resubmit| RESUB["RESUBMITTED"]
    RESUB -->|Resume Review| IN_VER
    IN_VER -->|Substantive Approval| RDY_DEC["READY_FOR_FINAL_DECISION"]
    RDY_DEC -->|Record SK Decree (Verifier)| APP["APPROVED"]
    APP -->|Promote to Master (Verifier)| PROM["PROMOTED"]
```

### State-Machine Alignment Table:

| Current State | Action | Actor | Next State | Frontend Prototype State | Backend Implementation State | Match? / Discrepancy |
|:---|:---|:---:|:---|:---|:---|:---:|
| `DRAFT` | Submit Draft | `USER` | `SUBMITTED_TO_ADMIN` | `SUBMITTED` | `SUBMITTED_TO_ADMIN` | **Naming Mismatch** (Conceptual Match) |
| `SUBMITTED_TO_ADMIN` | Admin Accept Screening | `ADMIN` | `IN_REVIEW_BY_ADMIN` | *Missing in UI* | `IN_REVIEW_BY_ADMIN` | **Missing in Frontend UI** |
| `SUBMITTED_TO_ADMIN` | Admin Return for Revision | `ADMIN` | `REVISION_REQUIRED` | `REVISION_REQUIRED` | `REVISION_REQUIRED` | Match |
| `IN_REVIEW_BY_ADMIN` | Assign Verifier | `ADMIN` | `ASSIGNED_TO_VERIFIER` | *Missing in UI* | `ASSIGNED_TO_VERIFIER` | **Missing in Frontend UI** |
| `ASSIGNED_TO_VERIFIER`| Start Substantive Review | `VERIFIER` | `IN_REVIEW_BY_VERIFIER` | `IN_REVIEW` | `IN_REVIEW_BY_VERIFIER` | Match |
| `IN_REVIEW_BY_VERIFIER`| Return with Revision Notes| `VERIFIER` | `REVISION_REQUIRED_BY_VERIFIER`| `REVISION_REQUIRED` | `REVISION_REQUIRED_BY_VERIFIER`| Match |
| `REVISION_REQUIRED_BY_VERIFIER`| Resubmit Version 2 | `USER` | `RESUBMITTED` | `SUBMITTED` | `RESUBMITTED` | Match |
| `IN_REVIEW_BY_VERIFIER`| Substantive Approval | `VERIFIER` | `READY_FOR_FINAL_DECISION` | `VERIFIED` | `READY_FOR_FINAL_DECISION` | **Naming Mismatch** (Conceptual Match) |
| `READY_FOR_FINAL_DECISION`| Final SK Decree Approval | **`VERIFIER`** | `APPROVED` | `APPROVED` (Executed by `ADMIN` in UI) | `APPROVED` (Executed by `VERIFIER`) | **CRITICAL ROLE MISMATCH** |
| `APPROVED` | Master Data Promotion | **`VERIFIER`** | `PROMOTED` | Embedded in Approval | `PROMOTED` | Match |

---

## 10. Role & Authorization Alignment

| Role | Frontend Visibility | Frontend Action Availability | Backend Permission Required | Backend Scope Enforcement | Separation of Duties (SoD) | Match / Mismatch Finding |
|:---|:---|:---|:---|:---|:---|:---:|
| `USER` | Own institution drafts & revisions | Create draft, edit units/positions, resubmit | `submission:create`, `submission:update`, `submission:submit` | Limited to Home Institution + Scopes | Cannot review, verify, or approve own submission | **Match** |
| `ADMIN` | All submissions, screening queue | Gate 1 Accept, Gate 1 Return, Verifier Assignment | `admin:review`, `admin:assign` | Cross-institutional screening | Cannot verify substantive; cannot final approve | **Mismatch in UI**: UI gave `ADMIN` final approval button |
| `VERIFIER` | Assigned queue, review workspace | Substantive review, revision notes, approval, promote | `verifier:review`, `submission:approve`, `submission:promote` | Assigned submissions only | Anti-Self-Review: Cannot verify if proposal author | **Mismatch in UI**: UI lacked formal SK number input |
| `SUPER_ADMIN` | Executive dashboard, reports, audit logs | Full visibility, scope delegation, audit export | `audit:read`, `report:read`, `scope:manage` | Global system-wide | Subject to SoD guardrails | **Match** |
| `SESDEP` | Executive dashboard, analytics, audit logs | Prototype persona (View-only executive KPIs) | *Not a production RBAC role* | N/A | Prototype persona | **Prototype Persona** (Subject to stakeholder decision) |

---

## 11. Cabinet / Institution Alignment

- **Category**: `FACT` / `DEMO DATA`
- **Frontend Presentation**: The frontend features a rich cabinet management and silsilah comparison screen (`/cabinets/compare`) displaying delta stats (**+7 Kementerian Baru, 3 Split, 1 Merge, 5 Rename, 22 Tetap**), contrasting *Kabinet Indonesia Maju* with *Kabinet Merah Putih*.
- **Backend Technical Reality**: The backend database schema (`eskld_db`) contains the **`institutions`** table (master K/L/D catalog) with `institution_code`, `name`, `short_name`, `category`, and `status`. It does **not** contain `cabinets`, `cabinet_periods`, `cabinet_memberships`, or `institution_lineage` tables.
- **Finding**: Cabinet comparison metrics and silsilah trees are currently **100% DEMO / PROTOTYPE DATA**. Future integration will either map existing institution records into a lightweight read-model or maintain silsilah as a future phase requirement.

---

## 12. Tupoksi Alignment

- **Category**: `FACT` / `DEMO DATA`
- **Frontend Presentation**: The frontend includes a dedicated Tugas dan Fungsi catalog (`/tupoksi`) displaying tasks and functions mapped to legal articles (e.g. *Perpres No. 147/2024 Pasal 4 & 5* for Kemenko Pangan).
- **Backend Technical Reality**: The backend database schema (`eskld_db`) focuses on organizational hierarchy (`organizational_units`) and position formations (`positions`). It does **not** contain a `tupoksi_items` table.
- **Finding**: Tupoksi data in the frontend prototype is **100% DEMO / PROTOTYPE DATA**.

---

## 13. Organization Structure Alignment

- **Category**: `FACT` / `IMPLEMENTATION`
- **Frontend Implementation**: The interactive organizational chart (`/structure`) is powered by React Flow (`@xyflow/react`) in [`OrgChartCanvas.tsx`](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/src/components/features/organization/OrgChartCanvas.tsx).
- **Backend Implementation**: The backend implements [`OrganizationalUnitController::getInstitutionTree`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/app/Controllers/Api/OrganizationalUnitController.php), which retrieves a recursive hierarchical tree using an Adjacency List model (`parent_id`) validated against circular dependencies.
- **Feature Capability Matrix**:
  - **Zoom / Pan / Fit View**: Fully Implemented (native React Flow controls).
  - **Search & Highlight**: Fully Implemented (in-canvas unit search).
  - **Node Detail Drawer**: Fully Implemented (displays unit name, code, echelon, leader title, and staff count).
  - **Expand / Collapse**: Partially Implemented (renders all levels statically).
  - **Position Detail**: Partially Implemented (shows scalar staff count; backend has full `positions` table).

---

## 14. Submission Lifecycle Alignment

Complete traceability of the 14-step proposal lifecycle:

| Step | Action | Frontend Implementation | Backend API | Database Persistence | Audit Event | Test Coverage |
|:---:|:---|:---:|:---:|:---:|:---:|:---:|
| 1 | Create Draft | Modal in `submissions/page.tsx` | `POST /api/v1/submissions` | `submissions`, `submission_versions` | `CREATE_SUBMISSION` | PASS (Step 5) |
| 2 | Edit Draft Metadata | In-memory form state | `PUT /api/v1/submissions/:id` | `submissions` | `UPDATE_SUBMISSION` | PASS (Step 5) |
| 3 | Add Proposed Units | Modal in `structure/page.tsx` | `POST /api/v1/submissions/:id/units` | `submission_units` | `CREATE_SUBMISSION_UNIT` | PASS (Step 5) |
| 4 | Add Proposed Positions | Missing in UI | `POST /api/v1/submissions/:id/positions` | `submission_positions` | `CREATE_SUBMISSION_POSITION`| PASS (Step 5) |
| 5 | Save / Snapshot Version| In-memory state | `POST /api/v1/submissions/:id/versions` | `submission_versions` | `CREATE_VERSION` | PASS (Step 5) |
| 6 | Submit to Gate 1 | Button in `submissions/[id]` | `POST /api/v1/submissions/:id/submit` | `submissions` (`SUBMITTED_TO_ADMIN`)| `SUBMIT_SUBMISSION` | PASS (Step 5) |
| 7 | Admin Gate 1 Accept | Missing in UI | `POST /api/v1/submissions/:id/admin-review/accept` | `admin_reviews`, `submissions` | `ADMIN_REVIEW_ACCEPT` | PASS (Step 6) |
| 8 | Admin Gate 1 Return | Missing in UI | `POST /api/v1/submissions/:id/admin-review/return` | `admin_reviews`, `submissions` | `ADMIN_REVIEW_RETURN` | PASS (Step 6) |
| 9 | Verifier Assignment | Missing in UI | `POST /api/v1/submissions/:id/assign-verifier` | `verifier_assignments`, `submissions` | `ASSIGN_VERIFIER` | PASS (Step 6) |
| 10| Verifier Start Review | Button in `verifications/[id]` | `POST /api/v1/submissions/:id/verifier-review/start` | `verification_records`, `submissions` | `VERIFIER_REVIEW_START` | PASS (Step 7) |
| 11| Verifier Add Notes | Notes input in `verifications/[id]`| `POST /api/v1/submissions/:id/verifier-review/notes` | `revision_notes` | `ADD_REVISION_NOTE` | PASS (Step 7) |
| 12| Verifier Substantive OK| Modal in `verifications/[id]` | `POST /api/v1/submissions/:id/verifier-review/approve` | `recommendation_records`, `submissions` | `VERIFIER_SUBSTANTIVE_APPROVED`| PASS (Step 9) |
| 13| Branch v2 & Resubmit | `submissions/[id]/revision` | `POST /api/v1/submissions/:id/revision` & `/resubmit`| `submission_versions` (v2), `submissions`| `START_REVISION`, `RESUBMIT` | PASS (Step 8) |
| 14| Verifier Final SK & Promo| Button in `submissions/[id]` | `POST /api/v1/submissions/:id/approve` & `/promote` | `approval_records`, `org_units`, `positions`| `SUBMISSION_FINAL_APPROVED`, `PROMOTED`| PASS (Step 10) |

---

## 15. Notification Alignment

- **Category**: `FACT` / `FUTURE REQUIREMENT`
- **Frontend**: Features a notification drawer and dedicated page (`/notifications`) with categories (`WORKFLOW`, `MASTER_DATA`, `SECURITY`, `SYSTEM`) powered by `MOCK_NOTIFICATIONS`.
- **Backend**: Emits detailed audit events for every state mutation via `AuditService`, but does not maintain a user-targeted inbox table in `eskld_db`.
- **Realtime Presence**: `OPEN-005` (Realtime presence / WebSocket / SSE) remains explicitly classified as a **FUTURE REQUIREMENT (Phase 2)**.

---

## 16. Analytics Alignment

- **Category**: `FACT` / `IMPLEMENTATION`
- **Frontend**: Displays Delayering Ratio (68.4%), Readiness Index (87.5%), Turnaround Speed (1.8 Days), and Echelon Distribution using mock data (`MOCK_KPIS`).
- **Backend**: Implements real executive reporting endpoints:
  - `GET /api/v1/reports/summary`: Real counts of total submissions, active drafts, in-review, approved, promoted, and average turnaround time.
  - `GET /api/v1/reports/submissions`, `/institutions`, `/approvals`, `/promotions`.
  - `GET /api/v1/reports/export`: Streaming CSV/JSON export.
- **Finding**: Frontend KPI cards should be mapped directly to `GET /api/v1/reports/summary` during integration.

---

## 17. Audit Trail Alignment

- **Category**: `FACT` / `IMPLEMENTATION`
- **Frontend**: Implements `/audit-logs` table with search, action filtering, and a JSON before/after diff modal.
- **Backend**: Implements `GET /api/v1/audit-logs` and `GET /api/v1/audit-logs/export` backed by the append-only `audit_logs` table.
- **Alignment Status**: High alignment. Minor field name mapping required:
  - `actorName` $\rightarrow$ join from `users` on `actor_id`.
  - `action` $\rightarrow$ `action_event`.
  - `oldValues` $\rightarrow$ `old_payload` (JSON).
  - `newValues` $\rightarrow$ `new_payload` (JSON).

---

## 18. Error Handling Contract

- **Backend Response Standard**: Compliant with **RFC 7807 (Problem Details for HTTP APIs)**:
  ```json
  {
    "type": "https://api.eskld.menpan.go.id/errors/FORBIDDEN",
    "title": "Forbidden",
    "status": 403,
    "detail": "Cross-institution submission access is forbidden.",
    "instance": "/api/v1/submissions/42",
    "invalid_params": []
  }
  ```
- **Frontend State**: Currently lacks an HTTP interceptor or centralized API error handler.
- **Requirement**: Integration must introduce an Axios / Fetch wrapper that unwraps RFC 7807 `detail` and `invalid_params` into UI toast alerts and inline form validation errors.

---

## 19. Security Findings

All security boundaries were audited across both codebases:

| Security Domain | Finding & Verification | Classification |
|:---|:---|:---:|
| **Authentication Enforcement** | Backend strictly validates JWT Bearer tokens; missing or invalid tokens return `401 Unauthorized`. Frontend currently lacks token storage mechanism. | `P0 — BLOCKER` |
| **BOLA / IDOR Protection** | Backend `ScopeResolver` enforces fail-closed checks on all resource queries (`403 Forbidden` on cross-tenant access). | **SECURE (Backend)** |
| **Separation of Duties (SoD)** | Backend enforces Anti-Self-Review, Anti-Self-Verification, and Anti-Self-Approval. Frontend UI currently lacks author checks for button visibility. | `P1 — CRITICAL` |
| **State Machine Bypass** | Backend rejects out-of-order transitions with `409 Conflict`. Frontend UI currently enables buttons based on mock workflow config. | `P2 — IMPORTANT` |
| **Audit Trail Immutability** | Backend `AuditLogModel` forbids `update()` and `delete()` via `BadMethodCallException`. | **SECURE (Backend)** |

---

## 20. Documentation Consistency

| Documentation Artifact | Documented Intent | Technical Reality | Alignment Status |
|:---|:---|:---|:---:|
| `SIGMA-K/docs/prototype/08_API_INTEGRATION_MAPPING.md` | Target Backend: NestJS / PostgreSQL | Verified Backend: CodeIgniter 4 / MySQL | **DOCUMENTATION OUTDATED** |
| `SIGMA-K/src/config/workflow.config.ts` | Final Approval by `ADMIN` | Final Approval strictly by `VERIFIER` | **DOCUMENTATION CONTRADICTS BUSINESS RULE** |
| `KemenPANRB/backend/docs/API_SPECIFICATION_v1.0.0.md` | 38 REST Endpoints in CodeIgniter 4 | Matches Routes.php and Controllers exactly | **DOCUMENTATION MATCH** |
| `KemenPANRB/backend/docs/OPEN_DECISIONS_REGISTER.md` | Tracks OPEN-001 through OPEN-007 | Matches codebase architectural state | **DOCUMENTATION MATCH** |

---

## 21. Open Decisions Register

| Decision ID | Topic | Current Classification | Recommended Path |
|:---|:---|:---:|:---|
| **OPEN-001** | `SESDEP` Role in Production RBAC | `PROVISIONAL / PROTOTYPE PERSONA` | Retain as UI Perspective / Filter for executive dashboards; authenticate under standard `SUPER_ADMIN` or read-only executive scope. |
| **OPEN-002** | Configurable Workflow Engine | `CONFIRMED` | Workflow is currently code-enforced via Zero-Trust state machine in CodeIgniter 4 services. |
| **OPEN-003** | Production Authentication Provider | `OPEN DECISION` | Internal JWT (`HS256`) is technically complete and verified for v1.0.0-RC1. National SSO / OIDC integration is classified as `PROPOSED / UNCONFIRMED`. |
| **OPEN-004** | Production Document / File Storage | `CONFIRMED` | Stored via `writable/uploads/` with metadata in database; S3/MinIO driver can be attached in future phase without breaking API contracts. |
| **OPEN-005** | Realtime Collaborative Presence | `FUTURE REQUIREMENT (PHASE 2)` | Concurrency control is handled deterministically via database row locks and atomic state validation in v1.0.0-RC1. |
| **OPEN-006** | National SSO / OIDC Integration | `PROPOSED / UNCONFIRMED` | Unconfirmed requirement. |
| **OPEN-007** | External API Gateway & Rate Limiting | `PROPOSED / UNCONFIRMED` | Handled at Nginx web server layer in v1.0.0-RC1. |

---

## 22. Gap Register & Prioritization

### P0 — BLOCKERS (Must resolve before any live end-to-end integration):
1. **GAP-01: Authentication & Token Lifecycle Integration**
   - *Reason*: Frontend has no login screen, no token refresh loop, and no `Authorization: Bearer <JWT>` header injection in API requests.
2. **GAP-02: Final Approval Authority Alignment**
   - *Reason*: Frontend UI awards final approval action to `ADMIN`, whereas backend strictly enforces `VERIFIER` final approval. Must align frontend buttons and action triggers to `VERIFIER`.
3. **GAP-03: Relational Submission Payload Construction**
   - *Reason*: Frontend creates monolithic tickets in memory; must construct structured relational snapshots (`submissions` $\rightarrow$ `submission_units` $\rightarrow$ `submission_positions`) matching backend API contracts.

### P1 — CRITICAL BEFORE INTEGRATION:
4. **GAP-04: Gate 1 Admin Screening UI Integration**
   - *Reason*: Frontend merges Gate 1 and Gate 2 into a single screen; must provide dedicated Gate 1 screening actions (`Accept`, `Return`, `Assign Verifier`) for `ADMIN`.
5. **GAP-05: Substantive Verification & Revision Flow Integration**
   - *Reason*: Frontend `/verifications/[id]` must invoke `POST /verifier-review/approve` with substantive recommendation payload.
6. **GAP-06: Separation of Duties (SoD) UI State Reflection**
   - *Reason*: Proposal authors must not see review/approval buttons on their own submissions in the UI.

### P2 — IMPORTANT (Contract & UX Polish):
7. **GAP-07: Field Name & Role Nomenclature Alignment**
   - *Reason*: Harmonize `VERIFIKATOR` $\rightarrow$ `VERIFIER`, `ticketNumber` $\rightarrow$ `submission_id`, camelCase $\leftrightarrow$ snake_case in API client layer.
8. **GAP-08: Executive Reporting API Wiring**
   - *Reason*: Replace mock KPIs in `SCR-01` and `SCR-15` with live data from `GET /api/v1/reports/summary`.
9. **GAP-09: Centralized RFC 7807 Error Interceptor**
   - *Reason*: Handle backend validation errors and display contextual form alerts.

### P3 — ENHANCEMENTS:
10. **GAP-10: CSV / JSON Streaming Export Triggers**
    - *Reason*: Wire export buttons in UI to `GET /api/v1/reports/export?format=csv`.
11. **GAP-11: React Flow Position Formation Badges**
    - *Reason*: Display granular position formations from `GET /units/:id/positions` in unit node drawer.

### INFO — DOCUMENTATION / DEMO DATA OBSERVATIONS:
12. **GAP-12: Unbacked Cabinet Comparison & Tupoksi Mock Screens**
    - *Reason*: `/cabinets/compare` and `/tupoksi` screens use mock data not backed by `eskld_db` schema. Document as demo showcases.

---

## 23. Recommended Implementation Order (For Future Phases)

```text
Phase 14A: API Client & Authentication Layer
  ├── 1. Build centralized Axios / Fetch HTTP client with JWT interceptor & RFC 7807 error handler
  ├── 2. Implement Login screen and AuthContext token storage (replacing mock RoleContext)
  └── 3. Wire /api/v1/auth/me to populate active user profile and institution scopes

Phase 14B: Master Data & Read-Model Integration
  ├── 4. Wire InstitutionService to GET /api/v1/institutions
  ├── 5. Wire OrganizationService & React Flow to GET /api/v1/institutions/:id/units
  └── 6. Wire AuditService to GET /api/v1/audit-logs and AnalyticsService to GET /api/v1/reports/summary

Phase 14C: Submission Drafting & Versioning Integration
  ├── 7. Wire submission draft creation modal to POST /api/v1/submissions
  ├── 8. Wire unit and position change forms to POST/PUT/DELETE /submissions/:id/units and /positions
  └── 9. Wire submission finalization to POST /submissions/:id/submit

Phase 14D: Workflow Gates & Role-Based Actions Integration
  ├── 10. Implement Gate 1 Admin Screening UI (Accept, Return, Assign Verifier)
  ├── 11. Implement Gate 2 Verifier Workspace (Start, Notes, Substantive Approve)
  ├── 12. Implement Operator Revision Form (Branch v2, Update, Resubmit)
  └── 13. Implement Verifier Final SK Approval & Master Data Promotion
```

---

*(Audit completed in strict adherence to read-only constraints. 0 source files altered, 0 database changes made.)*
