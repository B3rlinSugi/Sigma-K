# SIGMA-K — FRONTEND SERVICE TO BACKEND REST API MAPPING

> **Dokumen:** `08_FRONTEND_BACKEND_MAPPING.md`  
> **Status:** `FRONTEND-BACKEND INTEGRATION CONTRACT (PHASE 5A DESIGN - REVIEWED)`  
> **Tujuan:** Menjadi panduan integrasi langsung saat menghubungkan lapisan frontend Next.js 14 ke backend NestJS.  

---

## 1. Matriks Pemetaan Lengkap Servis Frontend ke Endpoint REST API

| Frontend Service (`src/services/api/`) | Frontend Method | REST Endpoint Backend | HTTP Verb | Request DTO / Query Params | Response DTO | Kebutuhan Otorisasi | Status Code Sukses |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- | :---: |
| **`AuthService`** | `getUsers()` | `/api/v1/users` | `GET` | `?role=&institutionId=` | `UserResponseDto[]` | Authenticated | `200 OK` |
| **`AuthService`** | `getUserById(id)` | `/api/v1/users/:id` | `GET` | Path param `id` | `UserResponseDto` | Authenticated | `200 OK` |
| **`AuthService`** | `getUserByRole(role)`| `/api/v1/users?role=:role` | `GET` | Query param `role` | `UserResponseDto[]` | Authenticated | `200 OK` |
| **`InstitutionService`** | `getInstitutions(filter)` | `/api/v1/institutions` | `GET` | `?search=&type=&page=&pageSize=` | `Paginated<InstitutionResponseDto>` | All Roles | `200 OK` |
| **`InstitutionService`** | `getInstitutionById(id)` | `/api/v1/institutions/:id` | `GET` | Path param `id` | `InstitutionDetailResponseDto` | All Roles | `200 OK` |
| **`CabinetService`** | `getCabinets()` | `/api/v1/cabinets` | `GET` | `?status=ACTIVE` | `CabinetResponseDto[]` | All Roles | `200 OK` |
| **`CabinetService`** | `getCabinetById(id)` | `/api/v1/cabinets/:id` | `GET` | Path param `id` | `CabinetResponseDto` | All Roles | `200 OK` |
| **`CabinetService`** | `getCabinetMemberships(cabId)` | `/api/v1/cabinets/:id/memberships` | `GET` | Path param `id`, `?category=` | `CabinetMembershipResponseDto[]` | All Roles | `200 OK` |
| **`CabinetService`** | `getCabinetComparison(base, target)` | `/api/v1/cabinets/compare` | `GET` | `?baseCabinetId=&targetCabinetId=` | `CabinetComparisonResponseDto` | All Roles | `200 OK` |
| **`OrganizationService`** | `getUnitsByInstitution(instId)` | `/api/v1/institutions/:id/units` | `GET` | Path param `id` | `OrgUnitResponseDto[]` / Tree | All Roles | `200 OK` |
| **`OrganizationService`** | `getAllUnits()` | `/api/v1/organization-units` | `GET` | `?search=&echelon=` | `OrgUnitResponseDto[]` | All Roles | `200 OK` |
| **`OrganizationService`** | `getTupoksiByInstitution(instId)`| `/api/v1/tupoksi` | `GET` | `?institutionId=` | `TupoksiResponseDto[]` | All Roles | `200 OK` |
| **`SubmissionService`** | `getSubmissions(filter)` | `/api/v1/submissions` | `GET` | `?status=&institutionId=&page=` | `Paginated<SubmissionTicketResponseDto>` | Scoped | `200 OK` |
| **`SubmissionService`** | `getSubmissionById(id)` | `/api/v1/submissions/:id` | `GET` | Path param `id` | `SubmissionDetailResponseDto` | Scoped | `200 OK` |
| **`SubmissionService`** | `createSubmission(payload)` | `/api/v1/submissions` | `POST` | `CreateSubmissionTicketDto` | `SubmissionTicketResponseDto` | USER, ADMIN | `201 Created` |
| **`SubmissionService`** | `updateStatus(id, status, note, user)` | `/api/v1/submissions/:id/transition` | `POST` | `TransitionWorkflowDto` | `SubmissionTicketResponseDto` | RBAC & Transition Rule | `200 OK` |
| **`SubmissionService`** | `resubmitRevision(id, payload)` | `/api/v1/submissions/:id/resubmit` | `POST` | `ResubmitRevisionDto` | `SubmissionTicketResponseDto` | USER (Owner), ADMIN | `200 OK` |
| **`NotificationService`** | `getNotifications(filter)` | `/api/v1/notifications` | `GET` | `?category=&isRead=` | `NotificationResponseDto[]` | Authenticated | `200 OK` |
| **`NotificationService`** | `markAsRead(id)` | `/api/v1/notifications/:id/read` | `PATCH` | Path param `id` | `NotificationResponseDto` | Authenticated (Owner) | `200 OK` |
| **`AnalyticsService`** | `getKPIs()` | `/api/v1/analytics/kpis` | `GET` | N/A | `KPICandidateResponseDto[]` | All Roles | `200 OK` |
| **`AnalyticsService`** | `getEchelonDistribution()` | `/api/v1/analytics/organization` | `GET` | N/A | `EchelonDistributionResponseDto[]`| All Roles | `200 OK` |
| **`AnalyticsService`** | `getCabinetComposition()` | `/api/v1/analytics/cabinets` | `GET` | `?cabinetId=` | `CabinetCompositionStatsDto[]` | All Roles | `200 OK` |
| **`AnalyticsService`** | `getSubmissionTurnaround()` | `/api/v1/analytics/submissions` | `GET` | N/A | `TurnaroundMetricResponseDto[]` | All Roles | `200 OK` |
| **`AuditService`** | `getAuditLogs(filter)` | `/api/v1/audit-logs` | `GET` | `?search=&action=&page=&pageSize=` | `Paginated<AuditLogResponseDto>` | ADMIN, SESDEP | `200 OK` |
| **`AuditService`** | `getAuditLogById(id)` | `/api/v1/audit-logs/:id` | `GET` | Path param `id` | `AuditLogDetailResponseDto` | ADMIN, SESDEP | `200 OK` |

---

## 2. Kesimpulan Kesiapan Integrasi Frontend

Seluruh 24 metode pada lapisan servis antarmuka pengguna Phase 4 telah memiliki representasi 1:1 terhadap endpoint REST API backend. Pada saat Phase 5B (implementasi backend) selesai, penggantian driver mock pada Next.js dapat dilakukan dengan hanya mengubah konfigurasi URL basis API dan menyuntikkan *HTTP Client* tanpa perlu merombak komponen UI ataupun state management yang telah ada.
