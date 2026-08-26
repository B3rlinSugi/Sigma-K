# REQUIREMENT TRACEABILITY MATRIX: SIGMA-K

> **Status:** REQUIREMENT ENGINEERING BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Project:** SIGMA-K  
> **Author:** Lead Requirements Engineer & Software Architect  

Matriks Keterlacakan Kebutuhan (*Traceability Matrix*) ini memastikan seluruh kebutuhan bisnis (*Business Requirements*) dapat ditelusuri secara terpadu (*end-to-end*) ke Kebutuhan Fungsional, User Story, Use Case, Business Rule, Acceptance Criteria, Entitas Data, dan Kemampuan API.

---

## 1. Matriks Keterlacakan Komprehensif (End-to-End Traceability)

| Base REQ | Business Req (BR) | Functional Req (FR) | User Story (US) | Use Case (UC) | Business Rule (BRULE) | Acceptance Criteria (AC) | Data Entity (DATA) | API Capability (API-CAP) | Traceability Status |
|---|---|---|---|---|---|---|---|---|:---:|
| **REQ-001** | BR-002 | FR-CAB-001, FR-CAB-002, FR-CAB-003 | US-009 | UC-006 | BRULE-003, BRULE-008 | AC-009 | `Cabinet` | API-CAP-010, API-CAP-011 | **COMPLETE** |
| **REQ-002** | BR-002 | FR-PER-001, FR-PER-002, FR-PER-003 | US-009 | UC-007 | BRULE-009 | AC-009 | `CabinetPeriod` | API-CAP-012 | **COMPLETE** |
| **REQ-003** | BR-003 | FR-MEM-001, FR-MEM-002 | US-010 | UC-008, UC-009 | BRULE-010 | AC-010 | `CabinetMembership` | API-CAP-013, API-CAP-014, API-CAP-015 | **COMPLETE** |
| **REQ-004** | BR-004 | FR-MEM-003, FR-MEM-004 | US-014 | UC-010 | BRULE-011 | AC-014 | `CabinetMembership`, `InstitutionLineage` | API-CAP-016 | **COMPLETE** |
| **REQ-005** | BR-001 | FR-INST-001, FR-INST-002, FR-INST-003 | US-001 | UC-003, UC-004 | BRULE-004, BRULE-005 | AC-001 | `Institution` | API-CAP-005, API-CAP-007, API-CAP-008 | **COMPLETE** |
| **REQ-006** | BR-001 | FR-PROF-001, FR-PROF-002 | US-001 | UC-003, UC-005 | BRULE-005, BRULE-007 | AC-001 | `InstitutionProfile` | API-CAP-006, API-CAP-009 | **COMPLETE** |
| **REQ-007** | BR-005 | FR-TUP-001, FR-TUP-002 | US-003 | UC-011 | BRULE-012 | AC-003 | `TugasFungsi` | API-CAP-020, API-CAP-021 | **COMPLETE** |
| **REQ-008** | BR-006 | FR-ORG-001, FR-ORG-002, FR-ORG-004, FR-POS-001, FR-POS-002 | US-002 | UC-012 | BRULE-013 | AC-002 | `OrganizationUnit`, `Position/Echelon` | API-CAP-017, API-CAP-018, API-CAP-019 | **COMPLETE** |
| **REQ-009** | BR-001, BR-007 | FR-USR-001, FR-USR-002, FR-USR-003, FR-RBAC-001, FR-RBAC-003 | US-001, US-006, US-009 | UC-001 | BRULE-001, BRULE-002 | AC-001 | `User`, `Role`, `Permission` | API-CAP-001, API-CAP-002, API-CAP-003, API-CAP-004 | **COMPLETE** |
| **REQ-010** | BR-007 | FR-SUB-001 s.d 004, FR-VER-001, FR-VER-003, FR-APP-001 s.d 003, FR-REV-001 s.d 003 | US-004, US-005, US-006, US-008, US-011 | UC-013, UC-014, UC-015, UC-016 | BRULE-005, BRULE-007, BRULE-014, BRULE-015, BRULE-016, BRULE-017 | AC-004, AC-005, AC-006, AC-008, AC-011 | `SubmissionTicket`, `SubmissionItem`, `VerificationLog` | API-CAP-022, API-CAP-023, API-CAP-025, API-CAP-026, API-CAP-027, API-CAP-028 | **COMPLETE** |
| **REQ-011** | BR-008 | FR-NOT-001, FR-NOT-002, FR-NOT-003 | US-016 | UC-017 | BRULE-018 | AC-016 | `Notification` | API-CAP-029, API-CAP-030, API-CAP-031 | **COMPLETE** |
| **REQ-012** | BR-010 | FR-DSH-001, FR-DSH-002, FR-DSH-003, FR-DSH-004 | US-013 | UC-002 | BRULE-003, BRULE-020 | AC-013 | `Cabinet`, `Institution`, View Aggregates | API-CAP-033 | **COMPLETE** |
| **REQ-013** | BR-010 | FR-ANA-001, FR-ANA-002 | US-015 | UC-019 | BRULE-020 | AC-015 | `PosturASNAnalytics`, View Aggregates | API-CAP-034 | **COMPLETE** |
| **REQ-014** | BR-009 | FR-AUD-001, FR-AUD-002, FR-AUD-003 | US-012 | UC-018 | BRULE-019 | AC-012 | `AuditLog` | API-CAP-032 | **COMPLETE** |
| **REQ-015** | BR-011 | FR-DSH-001, FR-MEM-004, FR-ORG-002 | US-013, US-014 | UC-002, UC-010, UC-012 | BRULE-020 | AC-013, AC-014 | All Core Entities (Prototype Layer) | All Core Read APIs | **COMPLETE** |
| **REQ-016** | BR-001 | FR-INST-001, FR-POS-001 | - | UC-003 | BRULE-004, BRULE-006 | AC-001 | `Institution`, `Region`, `Echelon` | API-CAP-005 | **COMPLETE** |
| **REQ-017** | BR-012 | FR-INST-001, NFR-SCL-001, NFR-SCL-002 | - | UC-003 | BRULE-004 | AC-001 | Master Database Layer | Scalable API Layer | **COMPLETE** |
| **REQ-018** | BR-001 s.d 012 | All FRs, All NFRs | All US | All UCs | All BRULEs | All ACs | Full Domain Schema | Full API Suite | **COMPLETE** |
| **REQ-019** | BR-007 | FR-VER-002 | US-007 | UC-014 | BRULE-015 | AC-007 | `SubmissionItem` (Diff Payload) | API-CAP-024 | **COMPLETE** |
| **REQ-020** | BR-006 | FR-ORG-003 | US-002 | UC-012 | BRULE-013 | AC-002 | `OrganizationUnit` | API-CAP-018 | **COMPLETE** |
| **REQ-021** | BR-007 | FR-PROF-003, FR-SUB-002 | US-004 | UC-013 | BRULE-014 | AC-004 | `SubmissionTicket` (File Attachment) | API-CAP-022 | **COMPLETE** |
| **REQ-022** | BR-010 | FR-ANA-003 | US-015 | UC-003, UC-019 | BRULE-020 | AC-015 | Export Dataset | API-CAP-035 | **COMPLETE** |
| **REQ-023** | BR-001 | FR-INST-004, NFR-DAT-002 | - | UC-004, UC-009 | BRULE-006 | AC-001 | `Institution` (Soft Delete) | API-CAP-008 | **COMPLETE** |
| **REQ-024** | BR-010 | NFR-UX-001 | US-013 | UC-002 | - | AC-013 | UI State Store | UI Theme Config | **COMPLETE** |
| **REQ-025** | BR-009 | NFR-API-002, NFR-SEC-005 | - | - | BRULE-001 | - | Gateway / Security Layer | API Throttling Filter | **COMPLETE** |
| **REQ-026** | BR-013 | NFR-SEC-002 | US-001 | UC-001 | BRULE-001 | AC-001 | Identity Provider Claim | OAuth2 / SSO Endpoint | **OPEN GAP [TBD]** |
| **REQ-027** | BR-015 | FR-NOT-004 | US-016 | UC-017 | BRULE-018 | AC-016 | Notification Dispatch Queue | External Mail/WA Gateway | **OPEN GAP [TBD]** |
| **REQ-028** | BR-014 | FR-VER-004 | US-006 | UC-014 | BRULE-015 | AC-006 | Multi-tier Approval State | Multi-tier State Route | **OPEN GAP [TBD]** |
| **REQ-029** | BR-010 | NFR-UX-002 | US-013 | UC-002 | - | AC-013 | Geo-spatial Coordinate Data | GIS Mapping Endpoint | **OPEN GAP [TBD]** |
| **REQ-030** | BR-010 | FR-TUP-003, FR-ANA-004 | US-015 | UC-019 | - | AC-015 | NLP Semantic Embeddings | Semantic Similarity Search | **OPEN GAP [TBD]** |

---

## 2. Ringkasan Status Keterlacakan
- **Total Kebutuhan Tervalidasi (Complete Trace):** 25 / 30 Requirements ($83.3\%$)
- **Total Kebutuhan Terbuka (Open Gaps [TBD]):** 5 / 30 Requirements ($16.7\%$)
- **Keterangan:** Tidak ada kebutuhan yang berstatus *Unmapped* atau *Lost*. Seluruh 5 open gaps terdokumentasi secara transparan pada dokumen Gap Analysis dan Open Questions.
