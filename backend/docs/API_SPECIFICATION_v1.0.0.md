# E-SKLD REST API Specification — Version 1.0.0

**Base URL**: `https://api.eskld.menpan.go.id/api/v1` (Production) / `http://localhost:8080/api/v1` (Local Dev)  
**Standard Response Envelope**:
```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": { ... },
  "timestamp": "2026-08-26T12:00:00+07:00"
}
```
**Standard Error Envelope (RFC 7807 compliant)**:
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "You are not authorized to access this resource."
  },
  "timestamp": "2026-08-26T12:00:00+07:00"
}
```

---

## 1. Authentication & Identity Endpoints

### 1.1 `POST /auth/login`
- **Auth**: Public
- **Description**: Authenticates user via username and password; returns JWT access token.
- **Request Body**:
  ```json
  {
    "username": "user_kl",
    "password": "Password123!"
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "success": true,
    "data": {
      "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
      "user": {
        "id": 10,
        "username": "user_kl",
        "email": "user@kemenpanrb.go.id",
        "role": "USER",
        "homeInstitutionId": 1
      }
    }
  }
  ```

### 1.2 `GET /auth/me`
- **Auth**: Bearer JWT
- **Description**: Retrieves current authenticated user profile and active scopes.

---

## 2. Core Submission Drafting Endpoints

### 2.1 `POST /submissions`
- **Auth**: Bearer JWT (`USER`, `SUPER_ADMIN`)
- **Description**: Creates a new proposal draft with initial version snapshot `v1`.
- **Request Body**:
  ```json
  {
    "institution_id": 1,
    "title": "Usulan Penataan SOTK 2026",
    "submission_year": 2026,
    "description": "Pengusulan restrukturisasi unit kerja."
  }
  ```
- **Response `201 Created`**:
  ```json
  {
    "success": true,
    "data": {
      "id": 105,
      "currentState": "DRAFT",
      "versionId": 210,
      "currentVersion": 1
    }
  }
  ```

### 2.2 `GET /submissions`
- **Auth**: Bearer JWT
- **Query Params**: `page`, `per_page`, `status`
- **Description**: Lists submissions scoped strictly to user's authorized institutions.

### 2.3 `GET /submissions/{id}`
- **Auth**: Bearer JWT
- **Description**: Retrieves detailed proposal structure including proposed units and positions.

### 2.4 `POST /submissions/{id}/submit`
- **Auth**: Bearer JWT (`USER`, `SUPER_ADMIN`)
- **Description**: Finalizes draft snapshot and transitions state `DRAFT` $\rightarrow$ `SUBMITTED_TO_ADMIN`.

---

## 3. Proposed Organizational Units & Positions Endpoints

### 3.1 `POST /submissions/{id}/units`
- **Auth**: Bearer JWT (`USER`, `SUPER_ADMIN`)
- **Request Body**:
  ```json
  {
    "parent_version_unit_id": null,
    "source_master_unit_id": null,
    "unit_name": "Direktorat Transformasi Digital",
    "unit_code": "DIR-TD-01",
    "unit_type": "DIREKTORAT",
    "change_type": "NEW"
  }
  ```
- **Response `201 Created`**

### 3.2 `POST /submissions/{id}/positions`
- **Auth**: Bearer JWT (`USER`, `SUPER_ADMIN`)
- **Request Body**:
  ```json
  {
    "version_unit_id": 405,
    "position_name": "Analis Kebijakan Ahli Muda",
    "position_type": "FUNGSIONAL",
    "echelon": "NON_ESELON",
    "formation_count": 4,
    "change_type": "NEW"
  }
  ```
- **Response `201 Created`**

---

## 4. Gate 1 Admin Screening & Verifier Assignment Endpoints

### 4.1 `GET /admin/queue`
- **Auth**: Bearer JWT (`ADMIN`, `SUPER_ADMIN`)
- **Description**: Retrieves submissions pending administrative screening (`SUBMITTED_TO_ADMIN`).

### 4.2 `POST /submissions/{id}/admin-review/accept`
- **Auth**: Bearer JWT (`ADMIN`, `SUPER_ADMIN`)
- **Description**: Accepts screening and transitions state `SUBMITTED_TO_ADMIN` $\rightarrow$ `IN_REVIEW_BY_ADMIN`.

### 4.3 `POST /submissions/{id}/admin-review/return`
- **Auth**: Bearer JWT (`ADMIN`, `SUPER_ADMIN`)
- **Request Body**:
  ```json
  {
    "reason": "Dokumen pendukung SK Organisasi belum dilampirkan.",
    "version_unit_id": null
  }
  ```
- **Description**: Returns submission for administrative revision (`REVISION_REQUIRED`).

### 4.4 `POST /submissions/{id}/assign-verifier`
- **Auth**: Bearer JWT (`ADMIN`, `SUPER_ADMIN`)
- **Request Body**:
  ```json
  {
    "verifier_id": 5,
    "notes": "Penugasan verifikasi substantif kepada Tim Analis SOTK."
  }
  ```
- **Description**: Assigns verified actor and transitions `IN_REVIEW_BY_ADMIN` $\rightarrow$ `ASSIGNED_TO_VERIFIER`.

---

## 5. Gate 2 Verifier Substantive Review & Approval Endpoints

### 5.1 `POST /submissions/{id}/verifier-review/start`
- **Auth**: Bearer JWT (`VERIFIER`, `SUPER_ADMIN`)
- **Description**: Starts substantive review; transitions `ASSIGNED_TO_VERIFIER` / `RESUBMITTED` $\rightarrow$ `IN_REVIEW_BY_VERIFIER`.

### 5.2 `POST /submissions/{id}/verifier-review/notes`
- **Auth**: Bearer JWT (`VERIFIER`, `SUPER_ADMIN`)
- **Request Body**:
  ```json
  {
    "version_unit_id": 405,
    "issue_description": "Nomenklatur unit kerja perlu diselaraskan dengan PermenPANRB 1/2023."
  }
  ```

### 5.3 `POST /submissions/{id}/verifier-review/return`
- **Auth**: Bearer JWT (`VERIFIER`, `SUPER_ADMIN`)
- **Description**: Returns submission for substantive revision (`REVISION_REQUIRED_BY_VERIFIER`).

### 5.4 `POST /submissions/{id}/verifier-review/approve`
- **Auth**: Bearer JWT (`VERIFIER`, `SUPER_ADMIN`)
- **Request Body**:
  ```json
  {
    "recommendation_summary": "Rekomendasi teknis disetujui penuh tanpa catatan.",
    "substantive_findings": "Analisis beban kerja memenuhi standar formasi.",
    "regulatory_considerations": "Sesuai dengan ketentuan perundangan kelembagaan.",
    "recommended_action": "PROCEED_TO_FINAL_APPROVAL",
    "resolve_all_notes": true
  }
  ```
- **Description**: Records substantive verification pass; transitions `IN_REVIEW_BY_VERIFIER` $\rightarrow$ `READY_FOR_FINAL_DECISION`.

---

## 6. Revision Lifecycle & Resubmission Endpoints

### 6.1 `GET /submissions/{id}/revision`
- **Auth**: Bearer JWT (`USER`, `SUPER_ADMIN`)
- **Description**: Inspects open revision notes and requirements.

### 6.2 `POST /submissions/{id}/revision`
- **Auth**: Bearer JWT (`USER`, `SUPER_ADMIN`)
- **Description**: Branches a new immutable revision version (`v2`, `v3`).

### 6.3 `POST /submissions/{id}/resubmit`
- **Auth**: Bearer JWT (`USER`, `SUPER_ADMIN`)
- **Request Body**:
  ```json
  {
    "notes": "Telah dilakukan perbaikan nomenklatur sesuai arahan Verifikator."
  }
  ```
- **Description**: Finalizes revision version and transitions state $\rightarrow$ `RESUBMITTED`.

---

## 7. Final Approval & Master Data Promotion Endpoints

### 7.1 `POST /submissions/{id}/approve`
- **Auth**: Bearer JWT (`VERIFIER`, `SUPER_ADMIN`)
- **Request Body**:
  ```json
  {
    "approval_number": "SK-PANRB/2026/08/042",
    "notes": "Persetujuan resmi kelembagaan Menteri PANRB."
  }
  ```
- **Description**: Records formal SK approval; transitions `READY_FOR_FINAL_DECISION` $\rightarrow$ `APPROVED`.

### 7.2 `POST /submissions/{id}/promote`
- **Auth**: Bearer JWT (`VERIFIER`, `ADMIN`, `SUPER_ADMIN`)
- **Description**: Reconciles snapshot into master tables (`organizational_units`, `positions`) and transitions `APPROVED` $\rightarrow$ `PROMOTED`.

---

## 8. Audit Logs & Executive Reporting Endpoints

### 8.1 `GET /audit-logs`
- **Auth**: Bearer JWT (`ADMIN`, `VERIFIER`, `SUPER_ADMIN`)
- **Query Params**: `page`, `per_page`, `actor_id`, `resource_entity`, `resource_id`, `action_event`, `date_from`, `date_to`
- **Description**: Retrieves scoped audit event logs.

### 8.2 `GET /audit-logs/export`
- **Auth**: Bearer JWT (`ADMIN`, `VERIFIER`, `SUPER_ADMIN`)
- **Query Params**: `format` (`csv` \| `json`)
- **Description**: Streams audit event logs in memory-efficient chunks.

### 8.3 `GET /reports/summary`
- **Auth**: Bearer JWT (`ADMIN`, `VERIFIER`, `SUPER_ADMIN`)
- **Description**: High-level executive KPI overview, throughput, and conversion funnel.

### 8.4 `GET /reports/submissions`
- **Auth**: Bearer JWT (`ADMIN`, `VERIFIER`, `SUPER_ADMIN`)
- **Description**: Multi-dimensional submission breakdown with filtering.

### 8.5 `GET /reports/export`
- **Auth**: Bearer JWT (`ADMIN`, `VERIFIER`, `SUPER_ADMIN`)
- **Query Params**: `type` (`summary` \| `submissions` \| `institutions` \| `approvals` \| `promotions`), `format` (`csv` \| `json`)
- **Description**: Streams executive management report datasets.
