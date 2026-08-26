# PHASE 14D — EXECUTIVE DASHBOARD & REPORTING API INTEGRATION IMPLEMENTATION PLAN

**Project**: E-SKLD / SIGMA-K (Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan)  
**Organization**: Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (KemenPANRB)  
**Milestone**: PHASE 14D — Executive Dashboard & Reporting API Integration  
**Backend Framework**: CodeIgniter 4.4.8 + MySQL 8.x (`eskld_db`)  
**Frontend Framework**: Next.js 14.2.15 + TypeScript 5.6.2  

---

## 1. Executive Summary & Objective

Phase 14D connects the frontend Executive Dashboard (`/`, SCR-01) and Intelligence & Analytics (`/analytics`, SCR-15) with the **verified backend Reporting APIs** implemented in Step 11 (`ReportController.php` & `ExecutiveReportService.php`).

The integration replaces hardcoded/mock KPI numbers, workflow funnel counts, institution metrics, and export triggers in API mode (`NEXT_PUBLIC_DATA_MODE=api`) with authoritative, scope-aware data from the backend, while preserving complete mock capability (`NEXT_PUBLIC_DATA_MODE=mock`) for offline prototype demonstration.

---

## 2. Current Frontend State Audit

| Screen / Component | Current Data Source | API Readiness | Required Integration Changes |
|---|---|---|---|
| **Dashboard Eksekutif** (`/`, SCR-01) | Static metrics & mock `AnalyticsService.getKPIs()` | Partially Connected | Replace hardcoded 48 K/L, 548 Pemda, and static queue numbers with live `summary.overview` and `summary.funnel` metrics. |
| **Intelijensi Data & Postur ASN** (`/analytics`, SCR-15) | Mock `AnalyticsService` | Partially Connected | Wire 4 Core KPIs to `GET /api/v1/reports/summary`, link export button to `GET /api/v1/reports/export`. |
| **KPI Grid & Widgets** | In-memory mock constants | Needs live DTO alignment | Update `ReportSummaryDto` to match backend `{ overview, funnel, stateBreakdown, recentApprovals }` envelope. |
| **Export Action** | Static mock handler | Disconnected | Wire dataset download to `GET /api/v1/reports/export?type=submissions&format=csv` (with blob trigger). |

---

## 3. Verified Backend Reporting Contracts

### 1. `GET /api/v1/reports/summary`
- **Controller**: `ReportController::summary()`
- **Service**: `ExecutiveReportService::getSummary($user)`
- **Scope Behavior**: Scoped via `ScopeResolver::getAuthorizedInstitutionIds($user, $roleCode)`.
- **Response Structure**:
  ```json
  {
    "success": true,
    "statusCode": 200,
    "message": "Executive report summary retrieved successfully.",
    "data": {
      "overview": {
        "totalInstitutions": 2,
        "totalActiveUnits": 18,
        "totalInactiveUnits": 0,
        "totalPositions": 85,
        "totalFormations": 320,
        "totalSubmissions": 12
      },
      "funnel": {
        "draft": 2,
        "screening": 3,
        "revision": 1,
        "verification": 2,
        "approved": 2,
        "promoted": 2
      },
      "stateBreakdown": {
        "DRAFT": 2,
        "SUBMITTED_TO_ADMIN": 3,
        "REVISION_REQUIRED": 1,
        "ASSIGNED_TO_VERIFIER": 2,
        "APPROVED": 2,
        "PROMOTED": 2
      },
      "recentApprovals": [
        {
          "id": 1,
          "approval_number": "SK/2026/001",
          "approved_at": "2026-08-26 12:00:00",
          "approver_name": "Dr. Verifikator Kelembagaan",
          "submission_id": 10,
          "submission_title": "Penataan Struktur Kemenko",
          "institution_name": "Kemenko Pangan"
        }
      ]
    }
  }
  ```

### 2. `GET /api/v1/reports/submissions`
- **Controller**: `ReportController::submissions()`
- **Query Params**: `institution_id` (int), `status` (string), `year` (int), `limit` (int, default 100, max 500)
- **Response Data Row**: `{ id, institution_id, institution_name, title, submission_year, current_state, created_at, updated_at, author_name }`
- **Error Behavior**: Throws HTTP 403 `FORBIDDEN` if requested `institution_id` is outside user's scope.

### 3. `GET /api/v1/reports/institutions`
- **Controller**: `ReportController::institutions()`
- **Response Data Row**: `{ id, institution_code, name, short_name, category, status, total_units, total_positions, total_formations, total_submissions }`

### 4. `GET /api/v1/reports/approvals`
- **Controller**: `ReportController::approvals()`
- **Response Data Row**: `{ approval_id, approval_number, approved_at, approval_notes, approver_name, approver_nip, submission_id, submission_title, submission_year, institution_id, institution_name, version_number }`

### 5. `GET /api/v1/reports/promotions`
- **Controller**: `ReportController::promotions()`
- **Response Data Row**: `{ submission_id, submission_title, submission_year, promoted_at, institution_id, institution_name, approval_number, author_name }`

### 6. `GET /api/v1/reports/export`
- **Controller**: `ReportController::export()`
- **Query Params**: `type` (`submissions` | `institutions` | `approvals` | `promotions`), `format` (`csv` | `json`)
- **Headers for CSV**: `Content-Type: text/csv; charset=UTF-8`, `Content-Disposition: attachment; filename="report_<type>_<timestamp>.csv"`

---

## 4. UI $\leftrightarrow$ API Data Mapping Architecture

```text
Backend CodeIgniter 4 REST API
  ├── GET /reports/summary
  ├── GET /reports/submissions
  ├── GET /reports/institutions
  ├── GET /reports/approvals
  ├── GET /reports/promotions
  └── GET /reports/export
               │
               ▼ (HTTP JSON via Bearer JWT / Blob for CSV)
      src/services/http/client.ts
               │
               ▼ (Raw DTOs)
       src/types/dto/report.dto.ts
  ├── ReportSummaryDto (overview, funnel, stateBreakdown, recentApprovals)
  ├── SubmissionReportItemDto
  ├── InstitutionReportItemDto
  ├── ApprovalReportItemDto
  └── PromotionReportItemDto
               │
               ▼ (Mappers)
     src/services/mappers/analytics.mapper.ts
  ├── mapReportSummaryToOverviewKPIs()
  ├── mapReportSummaryToFunnel()
  └── mapReportSummaryToExecutiveCards()
               │
               ▼ (Domain Models)
  ├── KPICandidate[]
  ├── ExecutiveDashboardSummary
  └── ReportDatasets
               │
               ▼ (UI Presentation Layer)
  ├── / (Executive Dashboard Overview Cards & Funnel)
  └── /analytics (Analytics Studio & CSV Export)
```

---

## 5. Screen-to-API Mapping Details

| UI Component / Value | Current Mock Source | Target Backend API | Target Field / Model |
|---|---|---|---|
| **Total Instansi Terdaftar** | Static `48 K/L` | `GET /reports/summary` | `data.overview.totalInstitutions` |
| **Total Unit Aktif & Jabatan** | Static string | `GET /reports/summary` | `data.overview.totalActiveUnits` & `totalFormations` |
| **Antrean Telaah (Funnel)** | Filtered `submissions` | `GET /reports/summary` | `data.funnel.screening + data.funnel.verification` |
| **Siap Pengesahan / Selesai** | Filtered `submissions` | `GET /reports/summary` | `data.funnel.approved + data.funnel.promoted` |
| **Recent Approvals Widget** | None / Static list | `GET /reports/summary` | `data.recentApprovals` |
| **KPI 1: Total Pengajuan** | `MOCK_KPIS[0]` | `GET /reports/summary` | `data.overview.totalSubmissions` |
| **KPI 2: Sedang Ditelaah** | `MOCK_KPIS[1]` | `GET /reports/summary` | `data.funnel.screening + data.funnel.verification` |
| **KPI 3: Disahkan & Promosi** | `MOCK_KPIS[2]` | `GET /reports/summary` | `data.funnel.approved + data.funnel.promoted` |
| **Unduh Laporan Analitik** | Dummy button | `GET /reports/export?type=submissions&format=csv` | Trigger browser CSV download |

---

## 6. Proposed Changes & Implementation Sequence

### Step 1: DTOs & Mappers Update
- [`frontend/src/types/dto/report.dto.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/types/dto/report.dto.ts):
  - Refactor `ReportSummaryDto` to exact backend structure (`overview`, `funnel`, `stateBreakdown`, `recentApprovals`).
  - Add `SubmissionReportRowDto`, `InstitutionReportRowDto`, `ApprovalReportRowDto`, `PromotionReportRowDto`.
- [`frontend/src/services/mappers/analytics.mapper.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/mappers/analytics.mapper.ts):
  - Update `mapReportSummaryToKPIs()` to consume the exact `ReportSummaryDto` fields without undefined fallbacks.
  - Add mapper `mapReportSummaryToDashboardOverview()`.

### Step 2: Service Layer Expansion
- [`frontend/src/services/api/analytics.service.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/api/analytics.service.ts):
  - Add `getReportSummary(): Promise<ExecutiveDashboardSummary>` calling `GET /api/v1/reports/summary`.
  - Add `getSubmissionsReport(params)` calling `GET /api/v1/reports/submissions`.
  - Add `getInstitutionsReport(params)` calling `GET /api/v1/reports/institutions`.
  - Add `getApprovalsReport(params)` calling `GET /api/v1/reports/approvals`.
  - Add `getPromotionsReport(params)` calling `GET /api/v1/reports/promotions`.
  - Add `exportReport(type: string, format: 'csv' | 'json')` calling `GET /api/v1/reports/export`.
  - Ensure real error propagation in API mode (401/403/500).

### Step 3: UI Pages Integration
- [`frontend/src/app/(dashboard)/page.tsx`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/app/(dashboard)/page.tsx):
  - Replace hardcoded KPI cards with live `reportSummary.overview` and `reportSummary.funnel`.
  - Add error handling and loading skeletons.
- [`frontend/src/app/(dashboard)/analytics/page.tsx`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/app/(dashboard)/analytics/page.tsx):
  - Wire download button to `AnalyticsService.exportReport('submissions', 'csv')`.
  - Handle 403 Forbidden gracefully when scoped user views restricted aggregate metrics.

---

## 7. Role & Authorization Scoping Strategy

| Role | Backend Scoping Behavior | Frontend Presentation |
|---|---|---|
| **`USER`** | Scoped strictly to `home_institution_id`. | Dashboard shows KPIs, units, and submissions belonging exclusively to user's home K/L/D. |
| **`ADMIN`** | Scoped to assigned institutions in `user_scopes` & `access_grants`. | Dashboard reflects aggregate of assigned K/L/D and screening queue. |
| **`VERIFIER`** | Scoped to assigned verification queue and assigned institutions. | Dashboard reflects verification backlog and substantive approvals made. |
| **`SUPER_ADMIN`** | Global nationwide visibility across all institutions. | Executive dashboard displays full national totals (all K/L/D and submissions). |

---

## 8. Export Integration Strategy

- Endpoint: `GET /api/v1/reports/export?type=${type}&format=csv`
- Request is authenticated via `Authorization: Bearer <token>`.
- The response returns standard CSV text with `Content-Disposition: attachment; filename="report_<type>_<datetime>.csv"`.
- Frontend triggers download via native Blob creation and anchor download trigger.

---

## 9. Comprehensive Test Plan

A dedicated test suite [`frontend/src/services/http/__tests__/reports.test.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/http/__tests__/reports.test.ts) covering **REPORT-01 through REPORT-15, EXPORT-01, EXPORT-02**:

- **`REPORT-01`**: Summary KPI overview & funnel mapping.
- **`REPORT-02`**: Submissions report list mapping.
- **`REPORT-03`**: Institutions report list mapping.
- **`REPORT-04`**: Approvals report list mapping.
- **`REPORT-05`**: Promotions report list mapping.
- **`REPORT-06`**: USER scope behavior (home institution metrics only).
- **`REPORT-07`**: ADMIN scope behavior (screening funnel metrics).
- **`REPORT-08`**: VERIFIER scope behavior (verification & approvals metrics).
- **`REPORT-09`**: SUPER_ADMIN global behavior (nationwide aggregates).
- **`REPORT-10`**: 401 Unauthorized handling on report endpoints.
- **`REPORT-11`**: 403 Forbidden handling on unauthorized custom `institution_id` query.
- **`REPORT-12`**: Empty report handling (0 submissions / 0 approvals).
- **`REPORT-13`**: Mock mode regression (`NEXT_PUBLIC_DATA_MODE=mock`).
- **`REPORT-14`**: API mode regression (`NEXT_PUBLIC_DATA_MODE=api`).
- **`REPORT-15`**: DTO $\leftrightarrow$ Domain mapper consistency.
- **`EXPORT-01`**: Export CSV request and bearer token inclusion.
- **`EXPORT-02`**: Export JSON format payload parsing.

---

## 10. Explicit Non-Goals

- Do NOT implement Realtime Presence (WebSocket, Socket.IO, SSE, Redis) — remains OPEN-005.
- Do NOT alter database schema or create migrations.
- Do NOT modify backend business logic or authorization services.
- Do NOT implement submission editing or workflow transitions (Phase 14E).
