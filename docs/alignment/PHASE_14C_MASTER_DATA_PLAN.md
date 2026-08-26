# PHASE 14C — MASTER DATA API INTEGRATION IMPLEMENTATION PLAN

**Project**: E-SKLD / SIGMA-K (Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan)  
**Organization**: Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (KemenPANRB)  
**Milestone**: PHASE 14C — Master Data API Integration  
**Backend Framework**: CodeIgniter 4.4.8 + MySQL 8.x (`eskld_db`)  
**Frontend Framework**: Next.js 14.2.15 + TypeScript 5.6.2  

---

## 1. Executive Summary & Audit Overview

Phase 14C integrates the frontend prototype with the **verified CodeIgniter 4 Master Data REST APIs** for:
1. **Institutions**: Scope-aware listing and institution detail view (`/institutions`, `/institutions/[id]`).
2. **Organizational Hierarchy**: Recursive tree retrieval and React Flow canvas rendering (`/structure`).
3. **Unit Details & Positions**: Unit details with positions drawer on node selection (`GET /api/v1/units/{id}`, `GET /api/v1/units/{id}/positions`, `GET /api/v1/positions/{id}`).

The integration replaces mock data in API mode (`NEXT_PUBLIC_DATA_MODE=api`) while preserving full mock mode functionality (`NEXT_PUBLIC_DATA_MODE=mock`) for standalone demonstration.

---

## 2. Current Frontend State Audit

| Screen / Component | Current State | Required Integration Changes |
|---|---|---|
| **Katalog Instansi** (`/institutions`) | Consumes `InstitutionService.getInstitutions()` | Support dynamic backend pagination metadata; add 401/403/500 error banners. |
| **Profil Instansi** (`/institutions/[id]`) | Consumes `InstitutionService.getInstitutionById()` & `OrganizationService.getOrgUnitsByInstitutionId()` | Parse numeric IDs; propagate 403 Forbidden and 404 Not Found explicitly without mock mask. |
| **Bagan Struktur Organisasi** (`/structure`) | Dropdown currently has hardcoded mock options | Populate dropdown dynamically from `InstitutionService.getInstitutions()`; handle empty trees and 403 errors. |
| **Kanvas Graf & Drawer** (`OrgChartCanvas.tsx`) | Renders React Flow nodes/edges; drawer uses mock unit fields | When a node is clicked, fetch real unit details and positions via `OrganizationService.getUnitDetail()` / `getPositionsByUnitId()`. |
| **DTOs & Mappers** | `src/types/dto/` & `src/services/mappers/` | Refine hierarchy response envelope unwrapping (`{ institutionId, tree: [...] }`) and position mapping. |

---

## 3. Verified Backend API Contracts

1. **`GET /api/v1/institutions`**:
   - Query: `page`, `perPage`, `search`
   - Envelope: `{ success: true, statusCode: 200, data: InstitutionDto[], meta: { page, perPage, total, totalPages } }`
   - Scope: `SUPER_ADMIN` sees all; `ADMIN`/`VERIFIER` see scoped; `USER` sees home institution.
2. **`GET /api/v1/institutions/{id}`**:
   - Returns: `{ success: true, statusCode: 200, data: InstitutionDto }`
   - Errors: 401 Unauthorized, 403 Forbidden, 404 Not Found.
3. **`GET /api/v1/institutions/{id}/units`**:
   - Returns: `{ success: true, statusCode: 200, data: { institutionId, institutionCode, institutionName, totalUnits, tree: OrgUnitTreeNodeDto[] } }`
   - Errors: 401 Unauthorized, 403 Forbidden, 404 Not Found.
4. **`GET /api/v1/units/{id}`**:
   - Returns: `{ success: true, statusCode: 200, data: UnitDetailDto (with children and positions) }`
5. **`GET /api/v1/units/{id}/positions`**:
   - Returns: `{ success: true, statusCode: 200, data: { unitId, totalPositions, positions: PositionDto[] } }`
6. **`GET /api/v1/positions/{id}`**:
   - Returns: `{ success: true, statusCode: 200, data: PositionDetailDto }`

---

## 4. API $\rightarrow$ DTO $\rightarrow$ Mapper $\rightarrow$ Domain $\rightarrow$ UI Architecture

```text
Backend CodeIgniter 4 REST API
               │
               ▼ (HTTP JSON via Bearer JWT)
      src/services/http/client.ts
               │
               ▼ (Raw DTO / Envelopes)
       src/types/dto/
  ├── institution.dto.ts
  └── organization.dto.ts
               │
               ▼ (Transform snake_case & BIGINT → camelCase & string)
     src/services/mappers/
  ├── institution.mapper.ts
  └── organization.mapper.ts
               │
               ▼ (Typed Domain Models)
  ├── Institution
  ├── OrganizationUnit
  └── Position
               │
               ▼ (UI Presentation Layer)
  ├── /institutions (Table + Pagination)
  ├── /institutions/[id] (Profile + Detail Cards)
  └── /structure (React Flow Hierarchy Graph + Detail Drawer)
```

---

## 5. Proposed Changes & Implementation Steps

### Step 1: DTOs & Mappers Enhancement
- [`frontend/src/types/dto/organization.dto.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/types/dto/organization.dto.ts):
  - Add `InstitutionHierarchyResponseDto`, `UnitDetailDto`, `UnitPositionsDto`.
- [`frontend/src/services/mappers/organization.mapper.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/mappers/organization.mapper.ts):
  - Update `flattenOrgUnitTree` to safely extract `.tree` array from backend envelope.
  - Implement `mapUnitDetailDtoToDomain` and `mapPositionDtoToDomain`.

### Step 2: Service Layer Expansion
- [`frontend/src/services/api/organization.service.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/api/organization.service.ts):
  - Implement `getUnitDetail(unitId: string)` calling `GET /api/v1/units/{id}`.
  - Implement `getPositionsByUnitId(unitId: string)` calling `GET /api/v1/units/{id}/positions`.
  - Implement `getPositionById(positionId: string)` calling `GET /api/v1/positions/{id}`.
- [`frontend/src/services/api/institution.service.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/api/institution.service.ts):
  - Propagate `AppError` (401, 403, 404) directly without silent mock fallback.

### Step 3: UI Pages & Components Integration
- [`frontend/src/app/(dashboard)/institutions/page.tsx`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/app/(dashboard)/institutions/page.tsx):
  - Add error banner for 403 Forbidden / 401 Unauthorized.
- [`frontend/src/app/(dashboard)/institutions/[id]/page.tsx`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/app/(dashboard)/institutions/[id]/page.tsx):
  - Display 403 Forbidden banner when user has no access.
  - Display 404 Not Found when institution ID is invalid.
- [`frontend/src/app/(dashboard)/structure/page.tsx`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/app/(dashboard)/structure/page.tsx):
  - Populate dropdown dynamically with institutions from `InstitutionService.getInstitutions()`.
  - Handle empty tree states and 403 errors.
- [`frontend/src/components/features/organization/OrgChartCanvas.tsx`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/components/features/organization/OrgChartCanvas.tsx):
  - Wire node click to live unit positions via `OrganizationService.getUnitDetail()`.

---

## 6. Verification & Test Plan

Create [`frontend/src/services/http/__tests__/master-data.test.ts`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/frontend/src/services/http/__tests__/master-data.test.ts) covering 15 test scenarios:
- **MASTER-01**: Institution list mapping.
- **MASTER-02**: Institution detail mapping.
- **MASTER-03**: Unauthorized request (401).
- **MASTER-04**: Forbidden institution access (403).
- **MASTER-05**: Institution not found (404).
- **MASTER-06**: Organizational hierarchy mapping.
- **MASTER-07**: Multi-level hierarchy parent-child links.
- **MASTER-08**: Empty organizational hierarchy.
- **MASTER-09**: Unit detail mapping with positions.
- **MASTER-10**: Position list mapping.
- **MASTER-11**: Position detail mapping.
- **MASTER-12**: Cross-institution position access denied.
- **MASTER-13**: Mock mode regression.
- **MASTER-14**: API mode regression.
- **MASTER-15**: DTO $\leftrightarrow$ Domain mapper consistency.

### Verification Commands:
```bash
npx tsx tests/run-master-tests.ts
npx tsc --noEmit
npm run lint
npm run build
vendor/bin/phpunit
```

---

## 7. Explicit Non-Goals

- Do NOT implement submission workflow actions (Phase 14D).
- Do NOT implement Gate 1 Admin screening or Gate 2 Verifier review workspaces (Phase 14E).
- Do NOT alter database schema or create migrations.
