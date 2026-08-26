# E-SKLD — REST API CONTRACT SPECIFICATION V1

## 1. Global API Conventions

- **Base URL**: `/api/v1`
- **Content-Type**: `application/json`
- **Authentication**: `Authorization: Bearer <JWT_TOKEN>`
- **Character Set**: `UTF-8`
- **Timestamp Standard**: ISO 8601 UTC (`YYYY-MM-DDTHH:mm:ssZ`)

---

## 2. Standardized Response Formats

### A. Success Response (`200 OK` / `201 Created`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Resource retrieved successfully.",
  "data": {
    "id": 1,
    "title": "Usulan SOTK 2026",
    "currentState": "DRAFT"
  },
  "meta": {
    "timestamp": "2026-08-24T15:30:00Z"
  }
}
```

### B. Paginated Success Response (`200 OK`)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Submissions list retrieved successfully.",
  "data": [ ... ],
  "meta": {
    "page": 1,
    "perPage": 20,
    "totalRows": 145,
    "totalPages": 8,
    "timestamp": "2026-08-24T15:30:00Z"
  }
}
```

### C. Standardized Error Response (`4xx` / `5xx`)
```json
{
  "success": false,
  "statusCode": 422,
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "The given input data was invalid.",
    "details": [
      {
        "field": "title",
        "message": "The title field is required and must be between 5 and 255 characters."
      }
    ]
  },
  "meta": {
    "timestamp": "2026-08-24T15:30:00Z"
  }
}
```

### HTTP Status Code Mapping:
- `200 OK`: Successful read or update operation.
- `201 Created`: Successful resource creation.
- `400 Bad Request`: Malformed JSON or invalid syntax.
- `401 Unauthorized`: Missing, invalid, or expired JWT token.
- `403 Forbidden`: Authenticated user lacks Role, Atomic Permission, Scope, or Assignment.
- `404 Not Found`: Resource ID does not exist.
- `409 Conflict`: State Machine transition violation or unique constraint collision.
- `422 Unprocessable Entity`: Input validation failure.
- `500 Internal Server Error`: Unexpected database exception or server error.

---

## 3. API Endpoints Specification

---

### Module 1: Authentication (`/api/v1/auth`)

#### 1. `POST /api/v1/auth/login`
- **Description**: Authenticate user credentials and issue JWT access & refresh tokens.
- **Access**: Public
- **Input**:
  ```json
  {
    "username": "test_user_a",
    "password": "SecretPassword123!"
  }
  ```
- **Output (`200 OK`)**:
  ```json
  {
    "success": true,
    "data": {
      "accessToken": "eyJhbGciOi...",
      "tokenType": "Bearer",
      "expiresIn": 3600,
      "user": {
        "id": 1,
        "username": "test_user_a",
        "fullName": "Budi Santoso",
        "role": "USER",
        "homeInstitution": {
          "id": 10,
          "name": "Kementerian Contoh A"
        }
      }
    }
  }
  ```
- **Errors**: `401 Unauthorized` (Invalid credentials / inactive account), `422 Unprocessable Entity` (Validation failed).

#### 2. `GET /api/v1/auth/me`
- **Description**: Get active profile, current permissions, assigned scopes, and active access grants.
- **Access**: Any Authenticated User
- **Output (`200 OK`)**: Returns user object, permissions list, active scopes, and active grants.

#### 3. `POST /api/v1/auth/logout`
- **Description**: Invalidate active session / blacklist token.
- **Access**: Any Authenticated User

---

### Module 2: Submissions & Versioning (`/api/v1/submissions`)

#### 4. `GET /api/v1/submissions`
- **Description**: List submissions filtered by actor's accessible scope.
- **Permission**: `VIEW`
- **Scope**: Evaluated via Scope Resolver (Home, User Scope, Grant, Global).
- **Query Params**: `page`, `perPage`, `institutionId`, `state`, `year`.
- **Output (`200 OK`)**: Paginated list of submissions.

#### 5. `POST /api/v1/submissions`
- **Description**: Create a new submission draft with Version 1.
- **Roles**: `USER`
- **Permission**: `CREATE`
- **Scope**: `home_institution_id` or active `CREATE` access grant.
- **Input**:
  ```json
  {
    "institutionId": 10,
    "title": "Usulan Penataan Nomenklatur SOTK 2026",
    "submissionYear": 2026,
    "notes": "Pengantar awal usulan",
    "units": [
      {
        "unitCode": "SETAMA",
        "unitName": "Sekretariat Utama",
        "unitLevel": 1,
        "orderIndex": 1,
        "positions": [
          {
            "positionName": "Sekretaris Utama",
            "positionType": "STRUKTURAL",
            "echelon": "I.a",
            "formationCount": 1
          }
        ]
      }
    ]
  }
  ```
- **Output (`201 Created`)**: Returns created submission object with `currentState = "DRAFT"`.
- **Errors**: `403 Forbidden` (Out of scope), `422 Unprocessable Entity`.

#### 6. `GET /api/v1/submissions/{id}`
- **Description**: Retrieve complete submission details including full relational snapshot tree.
- **Permission**: `VIEW`
- **Scope**: Target submission's `institution_id`.
- **Output (`200 OK`)**: Complete submission header, active version details, hierarchical units, positions, verification history, and approval record (if approved).

#### 7. `PUT /api/v1/submissions/{id}`
- **Description**: Update submission draft or revision data.
- **Roles**: `USER`
- **Permission**: `EDIT`
- **State Gate**: Allowed **only** if `currentState IN ('DRAFT', 'REVISION_BY_ADMIN')`.
- **Output (`200 OK`)**: Updated submission details.
- **Errors**: `409 Conflict` (Submission locked in review state), `403 Forbidden`.

#### 8. `DELETE /api/v1/submissions/{id}`
- **Description**: Delete draft submission.
- **Roles**: `USER`
- **Permission**: `DELETE_DRAFT`
- **State Gate**: Allowed **only** if `currentState = 'DRAFT'`.
- **Output (`200 OK`)**: `{ "success": true, "message": "Draft submission deleted." }`
- **Errors**: `409 Conflict` (Cannot delete submitted or reviewed submission).

---

### Module 3: Two-Gate Workflow Operations

#### 9. `POST /api/v1/submissions/{id}/submit`
- **Description**: Submit draft or resubmit revision to Admin Gate 1.
- **Roles**: `USER`
- **Permission**: `SUBMIT`
- **State Gate**: `DRAFT` $\rightarrow$ `SUBMITTED_TO_ADMIN` or `REVISION_BY_ADMIN` $\rightarrow$ `SUBMITTED_TO_ADMIN`.
- **Output (`200 OK`)**: `{ "success": true, "currentState": "SUBMITTED_TO_ADMIN" }`.

#### 10. `POST /api/v1/submissions/{id}/start-review`
- **Description**: Admin opens submission for administrative review.
- **Roles**: `ADMIN`
- **Permission**: `REVIEW`
- **State Gate**: `SUBMITTED_TO_ADMIN` $\rightarrow$ `ADMIN_REVIEW`.
- **Output (`200 OK`)**: `{ "success": true, "currentState": "ADMIN_REVIEW" }`.

#### 11. `POST /api/v1/submissions/{id}/return-revision`
- **Description**: Admin Gate 1 returns submission with revision notes.
- **Roles**: `ADMIN`
- **Permission**: `RETURN_REVISION`
- **State Gate**: `ADMIN_REVIEW` $\rightarrow$ `REVISION_BY_ADMIN`.
- **Input**:
  ```json
  {
    "generalNotes": "Format dokumen lampiran belum lengkap.",
    "issues": [
      {
        "versionUnitId": 45,
        "issueDescription": "Nomenklatur unit belum sesuai PermenPANRB No. 1 Tahun 2024"
      }
    ]
  }
  ```
- **Output (`200 OK`)**: `{ "success": true, "currentState": "REVISION_BY_ADMIN" }`.

#### 12. `POST /api/v1/submissions/{id}/pass-gate1`
- **Description**: Admin passes Gate 1 administrative check and forwards to Verifier.
- **Roles**: `ADMIN`, `SUPER_ADMIN`
- **Permission**: `FORWARD_TO_VERIFIER`
- **State Gate**: `ADMIN_REVIEW` $\rightarrow$ `ADMIN_PASSED`.
- **Input (Optional Assignment)**:
  ```json
  {
    "verifierId": 3,
    "assignmentNotes": "Ditugaskan untuk telaah klaster Kelembagaan Kemen-A"
  }
  ```
- **Output (`200 OK`)**: `{ "success": true, "currentState": "ADMIN_PASSED" }` (or `VERIFIER_REVIEW` if assigned).

#### 13. `POST /api/v1/submissions/{id}/assign-verifier`
- **Description**: Assign / Reassign Verifier Gate 2 to submission.
- **Roles**: `ADMIN` (`ASSIGN_VERIFIER`), `SUPER_ADMIN` (`REASSIGN_VERIFIER`)
- **Input**: `{ "verifierId": 3, "notes": "Penugasan tim verifikator utama" }`
- **Output (`200 OK`)**: Returns assignment record and moves state to `VERIFIER_REVIEW`.

#### 14. `POST /api/v1/submissions/{id}/return-gate2`
- **Description**: Verifier Gate 2 requests substantive revision.
- **Roles**: `VERIFIER` (Must be Active Assignee)
- **Permission**: `RETURN_REVISION`
- **State Gate**: `VERIFIER_REVIEW` $\rightarrow$ `REVISION_BY_VERIFIER`.
- **Output (`200 OK`)**: `{ "success": true, "currentState": "REVISION_BY_VERIFIER" }`.

#### 15. `POST /api/v1/submissions/{id}/forward-revision-to-user`
- **Description**: Admin Gate 1 forwards Verifier's revision notes to User.
- **Roles**: `ADMIN`, `SUPER_ADMIN`
- **Permission**: `FORWARD_TO_USER`
- **State Gate**: `REVISION_BY_VERIFIER` $\rightarrow$ `REVISION_BY_ADMIN`.
- **Output (`200 OK`)**: `{ "success": true, "currentState": "REVISION_BY_ADMIN" }`.

#### 16. `POST /api/v1/submissions/{id}/approve`
- **Description**: Verifier grants official final approval, records SK, and promotes snapshot to Active Master.
- **Roles**: `VERIFIER` (Must be Active Assignee)
- **Permission**: `APPROVE`
- **State Gate**: `VERIFIER_REVIEW` $\rightarrow$ `APPROVED`.
- **Input**:
  ```json
  {
    "approvalNumber": "SK/MENPANRB/2026/08/042",
    "approvalNotes": "Struktur organisasi dan formasi jabatan disetujui penuh."
  }
  ```
- **Output (`200 OK`)**:
  ```json
  {
    "success": true,
    "currentState": "APPROVED",
    "approvalRecord": {
      "id": 1,
      "approvalNumber": "SK/MENPANRB/2026/08/042",
      "approvedAt": "2026-08-24T15:45:00Z"
    }
  }
  ```
- **Errors**: `403 Forbidden` (Actor is not assigned Verifier), `409 Conflict` (Already approved / State is not VERIFIER_REVIEW).

---

### Module 4: Access Grants & Cross-Institution Delegations

#### 17. `POST /api/v1/access-requests`
- **Description**: User requests temporary access to another institution.
- **Roles**: `USER`
- **Input**:
  ```json
  {
    "targetInstitutionId": 12,
    "reason": "Penyusunan naskah akademik bersama",
    "requestedStartDate": "2026-09-01",
    "requestedEndDate": "2026-09-30",
    "permissions": ["VIEW", "EDIT"]
  }
  ```
- **Output (`201 Created`)**: Created access request object with status `PENDING`.

#### 18. `POST /api/v1/access-requests/{id}/review`
- **Description**: Admin approves or rejects cross-institution access request.
- **Roles**: `ADMIN`, `SUPER_ADMIN`
- **Permission**: `GRANT_ACCESS`
- **Input**: `{ "action": "APPROVE", "notes": "Disetujui untuk kegiatan satgas" }`
- **Output (`200 OK`)**: Issues `access_grants` with `ACTIVE` status.

#### 19. `POST /api/v1/access-grants/{id}/revoke`
- **Description**: Revoke active access grant before expiration date.
- **Roles**: `ADMIN`, `SUPER_ADMIN`
- **Permission**: `REVOKE_ACCESS`
- **Input**: `{ "revokeReason": "Penugasan telah berakhir lebih cepat" }`
- **Output (`200 OK`)**: Grant status updated to `REVOKED`. Record preserved in database.

---

### Module 5: National Audit Trail (`/api/v1/audit-logs`)

#### 20. `GET /api/v1/audit-logs`
- **Description**: Query immutable system audit logs.
- **Roles**: `SUPER_ADMIN`
- **Permission**: `VIEW_AUDIT`
- **Query Params**: `page`, `actorId`, `actionEvent`, `resourceEntity`, `startDate`, `endDate`.
- **Output (`200 OK`)**: Paginated audit log records with actor identity and JSON diffs.
