# E-SKLD Open Decisions & Future Architecture Register

**System**: E-SKLD Backend Release v1.0.0-RC1  
**Maintained By**: E-SKLD Architecture & Engineering Team  
**Last Updated**: August 26, 2026  
**Baseline Status**: `TECHNICAL VERIFICATION COMPLETE`  

---

## 1. Register Overview

This register tracks all architectural and operational items, categorizing their exact status as **CONFIRMED**, **PROVISIONAL**, **PROPOSED / UNCONFIRMED**, **OPEN DECISION**, or **FUTURE REQUIREMENT**.

---

## 2. Tracked Architectural Items

### DEC-001: Final Approval Authority
- **Classification**: `CONFIRMED`
- **Context**: Final business approval and SK decree recording for organizational proposals.
- **Decision**: Final formal approval authority strictly belongs to the assigned **VERIFIER** (Gate 2). The system does not introduce separate approver roles, SuperAdmin overrides, or external authorities.

### DEC-002: Realtime Presence Infrastructure (OPEN-005)
- **Classification**: `FUTURE REQUIREMENT` (Deferred to Phase 2)
- **Context**: Realtime presence indicators (e.g. showing active concurrent draft viewers) require WebSocket or Server-Sent Events (SSE) gateway and Redis pub/sub.
- **Decision**: Deferred beyond v1.0.0-RC1. Concurrency control and state locking in v1.0.0-RC1 are handled deterministically at the database layer via row-level locks and atomic state transitions (`LOCKED` on mismatch).

### DEC-003: National SSO / OIDC Integration (OPEN-006)
- **Classification**: `PROPOSED / UNCONFIRMED`
- **Context**: Potential integration with National Civil Service Agency (BKN) or Government Single Sign-On (SSO / INAgov).
- **Decision**: Unconfirmed stakeholder requirement. Currently, authentication is handled via secure internal JWT authentication (`HS256`).

### DEC-004: External API Gateway & Rate Limiting Mesh (OPEN-007)
- **Classification**: `PROPOSED / UNCONFIRMED`
- **Context**: Potential deployment behind a centralized government API Gateway (e.g., Kong, KrakenD, or WSO2).
- **Decision**: Unconfirmed infrastructure requirement. In v1.0.0-RC1, rate limiting and IP access filtering can be configured at the Nginx web server layer.

### DEC-005: Electronic Signature (TTE / BSrE) Integration
- **Classification**: `FUTURE REQUIREMENT`
- **Context**: Official electronic signatures on decree documents via BSSN / BSrE.
- **Decision**: `ApprovalRecordModel` and `FinalApprovalService` store official decree metadata (`approval_number`, `approval_notes`, `approver_id`, `approved_at`). BSrE signing API integration will hook into `FinalApprovalService::approveSubmission` in future phases.

### DEC-006: Asynchronous Queue Workers for Heavy Batch Reports
- **Classification**: `PROPOSED / UNCONFIRMED`
- **Context**: Handling multi-gigabyte or asynchronous background export jobs.
- **Decision**: In v1.0.0-RC1, streaming CSV (`text/csv`) and JSON (`application/json`) exports execute efficiently within memory boundaries using `php://temp` stream buffers.
