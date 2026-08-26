# SIGMA-K — API INTEGRATION MAPPING (PHASE 5 READINESS)

## 1. Pola Integrasi Service Layer (API-Ready Architecture)
Untuk menjamin transisi mulus dari prototipe Phase 4 menuju implementasi backend NestJS pada Phase 5, seluruh interaksi data dihubungkan melalui *Service Abstraction Layer* (`src/services/api/`):

```mermaid
graph LR
    UI["React Components (App Router)"] --> Hook["React Hooks / Context"]
    Hook --> Service["Service Layer (src/services/api/*)"]
    Service -->|Phase 4 (Current)| Mock["Mock Data Promise Store"]
    Service -.->|Phase 5 (Next)| API["NestJS REST API (/api/v1/*)"]
    API -.-> DB["PostgreSQL 16 Database"]
```

---

## 2. Tabel Pemetaan Servis Prototipe ke Target Endpoint REST API

| Kelas Servis | Metode Servis Prototipe | Parameter Input | Target Endpoint NestJS REST API | HTTP Method |
| :--- | :--- | :--- | :--- | :---: |
| `InstitutionService` | `getInstitutions()` | `{ search, type, status }` | `/api/v1/institutions` | `GET` |
| | `getInstitutionById()` | `id: string` | `/api/v1/institutions/:id` | `GET` |
| `CabinetService` | `getCabinets()` | - | `/api/v1/cabinets` | `GET` |
| | `getCabinetById()` | `id: string` | `/api/v1/cabinets/:id` | `GET` |
| | `getCabinetMemberships()` | `cabinetId?: string` | `/api/v1/cabinets/:id/memberships` | `GET` |
| | `getCabinetComparison()` | `baseId, targetId` | `/api/v1/cabinets/compare?base=:baseId&target=:targetId` | `GET` |
| `OrganizationService`| `getOrgUnitsByInstitutionId()` | `institutionId: string` | `/api/v1/institutions/:id/units` | `GET` |
| | `getAllTupoksi()` | - | `/api/v1/tupoksi` | `GET` |
| | `getTupoksiByInstitutionId()` | `institutionId: string` | `/api/v1/institutions/:id/tupoksi` | `GET` |
| `SubmissionService` | `getSubmissions()` | `{ status, search }` | `/api/v1/submissions` | `GET` |
| | `getSubmissionById()` | `id: string` | `/api/v1/submissions/:id` | `GET` |
| | `updateStatus()` | `id, status, notes, verifier`| `/api/v1/submissions/:id/workflow-transition` | `POST` |
| `NotificationService`| `getNotifications()` | - | `/api/v1/notifications` | `GET` |
| | `markAsRead()` | `id: string` | `/api/v1/notifications/:id/read` | `PATCH` |
| `AnalyticsService` | `getKPIs()` | - | `/api/v1/analytics/kpis` | `GET` |
| | `getEchelonDistribution()` | - | `/api/v1/analytics/postur-asn` | `GET` |
| `AuditService` | `getAuditLogs()` | `{ search, action, instId }`| `/api/v1/audit-logs` | `GET` |
