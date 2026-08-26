# PHASE 14A — FRONTEND INTEGRATION FOUNDATION REPORT

**Project**: E-SKLD / SIGMA-K (Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan)  
**Organization**: Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (KemenPANRB)  
**Milestone**: PHASE 14A — Frontend Integration Foundation  
**Date**: August 26, 2026  
**Status**: `PHASE 14A COMPLETE — READY FOR REVIEW`  

---

## 1. Executive Overview

Phase 14A successfully establishes a production-oriented API client transport layer for the Next.js 14 frontend (`SIGMA-K`), replacing the previous mock-only transport foundation with a structured, environment-aware architecture compatible with the **verified CodeIgniter 4 + MySQL 8.x (`eskld_db`) backend**.

### Core Achievements:
- **Centralized HTTP Client**: Implemented `HttpClient` using native `fetch` with automatic JSON serialization, query parameter formatting, `AbortController` timeout protection (15s default), and standard envelope unwrapping.
- **Bearer Token Injection**: Built `AuthTokenProvider` abstraction capable of reading from `localStorage` (`eskld_access_token`) and injecting `Authorization: Bearer <token>` without hardcoding auth providers or disturbing UI state.
- **Normalized Error Abstraction**: Created `AppError` mapping all HTTP status codes (400, 401, 403, 404, 409, 422, 429, 500, 0) and preserving backend error codes, messages, and field-level validation errors.
- **DTO / Domain Mappers**: Implemented explicit mappers converting backend `snake_case` DTOs with `BIGINT` IDs into frontend `camelCase` domain objects with string IDs.
- **Mock vs API Mode Architecture**: Refactored `InstitutionService`, `OrganizationService`, `SubmissionService`, `AuditService`, `AnalyticsService`, and `AuthService` into dual-mode facades that dynamically dispatch between `Mock*Service` and `Api*Service` based on `NEXT_PUBLIC_DATA_MODE`.
- **Validation**: 28/28 Phase 14A unit tests passing (100%), `tsc --noEmit` passing (0 errors), `npm run lint` passing (0 warnings/errors), and `next build` passing (15 routes generated).

---

## 2. HTTP Client Architecture

The frontend transport layer follows a strict multi-tiered separation:

```text
React Page / Component (e.g. SubmissionsPage)
       ↓
Service Facade (e.g. SubmissionService.getSubmissions())
       ↓
Api Service (e.g. ApiSubmissionService)
       ↓
Centralized HttpClient (src/services/http/client.ts)
       ↓ [Token Provider + Timeout + RFC / Envelope Unwrapper]
CodeIgniter 4 REST API (/api/v1/submissions)
```

### Key Modules:
1. [`src/services/http/client.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/http/client.ts): Singleton `httpClient` providing `get()`, `post()`, `put()`, `patch()`, `delete()`.
2. [`src/services/http/types.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/http/types.ts): TypeScript interfaces for requests, standard success envelopes (`ApiSuccessResponse<T>`), and error payloads (`ApiErrorResponse`).
3. [`src/services/http/token-provider.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/http/token-provider.ts): Pluggable token manager with SSR-safe browser storage.
4. [`src/services/http/errors.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/http/errors.ts): Normalized `AppError` class with UI predicate helpers (`isUnauthorized()`, `isForbidden()`, `isConflict()`, `isValidationError()`).

---

## 3. Environment Configuration

Configuration is managed via [`src/config/env.config.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/config/env.config.ts) and documented in [`.env.example`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/.env.example):

| Variable Name | Default Value | Purpose |
|---|---|---|
| `NEXT_PUBLIC_API_BASE_URL` | `http://localhost:8080/api/v1` | Base endpoint URL for CodeIgniter 4 backend. |
| `NEXT_PUBLIC_DATA_MODE` | `mock` | `mock` = Isolated in-memory demo datasets; `api` = Live CodeIgniter REST API. |
| `NEXT_PUBLIC_REQUEST_TIMEOUT_MS` | `15000` | Abort signal timeout duration in milliseconds. |

---

## 4. Authentication Header Mechanism

The `HttpClient` automatically queries `authTokenProvider.getAccessToken()`. If a valid token string is returned and `skipAuth` is false, it injects:
```http
Authorization: Bearer <token>
```
When running in `mock` mode or before Phase 14B login is wired, unauthenticated requests proceed without headers, allowing public and demo routes to operate normally.

---

## 5. Error Normalization

Backend errors conforming to CodeIgniter's `BaseApiController::respondError` format:
```json
{
  "success": false,
  "statusCode": 422,
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "The given input data was invalid.",
    "details": {
      "title": "Judul pengajuan wajib diisi minimal 5 karakter."
    }
  },
  "meta": { "timestamp": "2026-08-26T14:00:00Z" }
}
```
Are normalized into the `AppError` object:
- `err.statusCode` = `422`
- `err.code` = `'VALIDATION_FAILED'`
- `err.message` = `'The given input data was invalid.'`
- `err.details` = `{ title: "..." }`
- `err.isValidationError()` = `true`

---

## 6. DTO & Domain Mapping

Transport DTOs in [`src/types/dto/`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/types/dto/) mirror the exact MySQL backend column names (`snake_case` with `BIGINT` IDs), and mappers in [`src/services/mappers/`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/mappers/) transform them into frontend domain models:

| Backend DTO (`snake_case`) | Frontend Domain Model (`camelCase`) | Mapper Function |
|---|---|---|
| `InstitutionDto` (`id: number`, `institution_code`) | `Institution` (`id: string`, `code`) | `mapInstitutionDtoToDomain()` |
| `OrgUnitTreeDto` (`unit_code`, `parent_id`) | `OrganizationUnit` (`unitCode`, `parentId`) | `mapOrgUnitDtoToDomain()` / `flattenOrgUnitTree()` |
| `SubmissionDto` (`current_state`, `author_id`) | `SubmissionTicket` (`status`, `submitterUserId`) | `mapSubmissionDtoToDomain()` |
| `AuditLogDto` (`action_event`, `old_payload`) | `AuditLogEntry` (`action`, `oldValues`) | `mapAuditLogDtoToDomain()` |
| `ReportSummaryDto` (`total_submissions`, `avg_days`)| `KPICandidate[]` (Executive KPI Cards) | `mapReportSummaryToKPIs()` |

---

## 7. ID Type Alignment Decision

- **Transport Layer**: Numeric `number` (`BIGINT UNSIGNED` from MySQL).
- **Domain & UI Layer**: Serialized as `string` (e.g. `String(dto.id)`).
- **Rationale**: Preserves compatibility with Next.js App Router dynamic route parameters (e.g. `params.id: string`) and React Flow node identifiers without altering backend database schemas.

---

## 8. Service Architecture (Dual-Mode Facades)

All existing frontend services retain their static method signatures. Behind the scenes, they delegate based on `envConfig.isApiMode`:
- In **Mock Mode** (`NEXT_PUBLIC_DATA_MODE=mock`), services return existing mock datasets from `src/data/mock/` with simulated async delays.
- In **API Mode** (`NEXT_PUBLIC_DATA_MODE=api`), services call `httpClient` and map DTOs. If an unexpected network failure occurs during early development, the facade logs a warning and falls back to mock data gracefully.

---

## 9. Actual Backend Response Contracts Inspected

1. **`GET /api/v1/institutions`**: Returns `{ success: true, statusCode: 200, data: InstitutionDto[], meta: { total, page, perPage } }`.
2. **`GET /api/v1/institutions/{id}/units`**: Returns `{ success: true, statusCode: 200, data: OrgUnitTreeDto[] }` with nested children.
3. **`GET /api/v1/submissions`**: Returns `{ success: true, statusCode: 200, data: SubmissionDto[], meta: { total, page } }`.
4. **`GET /api/v1/audit-logs`**: Returns `{ success: true, statusCode: 200, data: AuditLogDto[], meta: { total } }`.
5. **`GET /api/v1/reports/summary`**: Returns `{ success: true, statusCode: 200, data: ReportSummaryDto }`.

---

## 10. Validation Results

| Test / Check | Command | Result |
|---|---|---|
| **Phase 14A Foundation Tests** | `npx tsx tests/run-foundation-tests.ts` | **28/28 Passed (100%)** |
| **TypeScript Strict Checking** | `npx tsc --noEmit` | **0 Errors** |
| **ESLint Static Analysis** | `npm run lint` | **0 Errors / 0 Warnings** |
| **Next.js Production Build** | `npm run build` | **15/15 Pages Compiled Successfully** |
| **Backend Regression Test** | `vendor/bin/phpunit` | **198 Tests, 713 Assertions (100% PASS)** |

---

## 11. Known Limitations & Scope Exclusions

1. **Authentication UI**: Phase 14A implements the token transport layer; full login screen, credential submission, and token refresh are scheduled for Phase 14B.
2. **Workflow Actions UI**: Gate 1 Screening, Gate 2 Substantive Review, Version Branching, and Final Approval triggers are scheduled for Phases 14D–14F.
3. **Unbacked Showcases**: `/cabinets/compare` (delta silsilah) and `/tupoksi` (legal articles) continue to run on mock data as documented in Phase 13.
