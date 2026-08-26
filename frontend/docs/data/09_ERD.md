# 09. ENTITY RELATIONSHIP DIAGRAM (ERD): SIGMA-K

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Database Architect & Lead Full-Stack Engineer  

Dokumen ini memvisualisasikan diagram relasi entitas (*Entity Relationship Diagram - ERD*) konseptual dan logis untuk sistem SIGMA-K menggunakan notasi standar Mermaid.

---

## 1. Conceptual ERD (High-Level Domain Overview)

```mermaid
erDiagram
    CABINET ||--o{ CABINET_PERIOD : "has periods"
    CABINET_PERIOD ||--o{ CABINET_MEMBERSHIP : "contains"
    INSTITUTION ||--o{ CABINET_MEMBERSHIP : "enrolled in"
    
    INSTITUTION_TYPE ||--o{ INSTITUTION : "classifies"
    REGION ||--o{ INSTITUTION : "located in"
    
    INSTITUTION ||--|| INSTITUTION_PROFILE : "detailed by"
    INSTITUTION ||--o{ ORGANIZATION_UNIT : "structured into"
    ORGANIZATION_UNIT ||--o{ ORGANIZATION_UNIT : "hierarchical parent"
    ORGANIZATION_UNIT ||--o{ TUGAS_FUNGSI : "mandated with"
    POSITION_LEVEL ||--o{ ORGANIZATION_UNIT : "assigned level"
    
    INSTITUTION ||--o{ INSTITUTION_LINEAGE : "predecessor of"
    INSTITUTION ||--o{ INSTITUTION_LINEAGE : "successor of"
    
    INSTITUTION ||--o{ SUBMISSION_TICKET : "submits changes"
    USER ||--o{ SUBMISSION_TICKET : "creates"
    SUBMISSION_TICKET ||--o{ SUBMISSION_ITEM : "contains deltas"
    SUBMISSION_TICKET ||--o{ VERIFICATION_LOG : "reviewed via"
    
    USER ||--o{ USER_INSTITUTION_SCOPE : "scoped to"
    ROLE ||--o{ USER : "assigned to"
    ROLE ||--o{ ROLE_PERMISSION : "grants"
    PERMISSION ||--o{ ROLE_PERMISSION : "defined in"
    
    USER ||--o{ NOTIFICATION : "receives"
    USER ||--o{ AUDIT_LOG : "triggers mutation"
```

---

## 2. Logical ERD (Detailed Physical/Logical Attributes)

```mermaid
erDiagram
    INSTITUTIONS {
        uuid id PK
        varchar code UK
        varchar name
        varchar short_name
        int institution_type_id FK
        int region_id FK
        varchar status
        timestamptz created_at
        timestamptz updated_at
        timestamptz deleted_at
    }

    INSTITUTION_TYPES {
        int id PK
        varchar code UK
        varchar name
    }

    REGIONS {
        int id PK
        varchar code UK
        varchar name
        varchar level
    }

    INSTITUTION_PROFILES {
        uuid id PK
        uuid institution_id FK
        text address
        varchar phone
        varchar email
        varchar website_url
        varchar logo_path
        text vision_statement
        text mission_statement
        text legal_basis_summary
    }

    CABINETS {
        uuid id PK
        varchar name UK
        varchar president_name
        varchar vice_president_name
        text description
        boolean is_active
        timestamptz created_at
    }

    CABINET_PERIODS {
        uuid id PK
        uuid cabinet_id FK
        date start_date
        date end_date
        varchar legal_decree_number
        varchar status
    }

    CABINET_MEMBERSHIPS {
        uuid id PK
        uuid cabinet_period_id FK
        uuid institution_id FK
        varchar category
        date joined_date
        date ended_date
        boolean is_active_in_cabinet
    }

    INSTITUTION_LINEAGES {
        uuid id PK
        uuid predecessor_institution_id FK
        uuid successor_institution_id FK
        uuid cabinet_period_id FK
        varchar transition_type
        text notes
        date effective_date
    }

    ORGANIZATION_UNITS {
        uuid id PK
        uuid institution_id FK
        uuid parent_id FK
        varchar unit_code
        varchar unit_name
        int echelon_level_id FK
        int hierarchy_level
        int sort_order
        boolean is_active
        timestamptz deleted_at
    }

    POSITION_LEVELS {
        int id PK
        varchar code UK
        varchar name
        int rank_order
    }

    TUGAS_FUNGSI {
        uuid id PK
        uuid institution_id FK
        uuid organization_unit_id FK
        varchar type
        text content_text
        varchar legal_article_reference
        int sequence_number
    }

    USERS {
        uuid id PK
        varchar username UK
        varchar email UK
        varchar password_hash
        varchar full_name
        varchar nip
        uuid institution_id FK
        boolean is_active
        timestamptz last_login_at
    }

    ROLES {
        int id PK
        varchar code UK
        varchar name
        text description
    }

    PERMISSIONS {
        int id PK
        varchar code UK
        varchar name
    }

    ROLE_PERMISSIONS {
        int role_id FK
        int permission_id FK
    }

    SUBMISSION_TICKETS {
        uuid id PK
        varchar ticket_number UK
        uuid institution_id FK
        uuid submitter_user_id FK
        varchar status
        text submission_notes
        varchar legal_doc_path
        timestamptz submitted_at
        timestamptz approved_at
        uuid approved_by_user_id FK
    }

    SUBMISSION_ITEMS {
        uuid id PK
        uuid submission_ticket_id FK
        varchar target_entity_type
        uuid target_entity_id
        varchar action_type
        jsonb payload_before
        jsonb payload_after
    }

    VERIFICATION_LOGS {
        uuid id PK
        uuid submission_ticket_id FK
        uuid verifier_user_id FK
        varchar decision
        text notes
        timestamptz verified_at
    }

    NOTIFICATIONS {
        uuid id PK
        uuid user_id FK
        varchar title
        text message
        varchar category
        varchar action_url
        boolean is_read
        timestamptz created_at
    }

    AUDIT_LOGS {
        bigserial id PK
        uuid user_id FK
        varchar action_type
        varchar entity_name
        varchar entity_id
        jsonb old_values
        jsonb new_values
        inet ip_address
        varchar user_agent
        timestamptz created_at
    }

    %% Relationships
    INSTITUTION_TYPES ||--o{ INSTITUTIONS : "classifies"
    REGIONS ||--o{ INSTITUTIONS : "contains"
    INSTITUTIONS ||--|| INSTITUTION_PROFILES : "has profile"
    
    CABINETS ||--o{ CABINET_PERIODS : "has periods"
    CABINET_PERIODS ||--o{ CABINET_MEMBERSHIPS : "enrolled"
    INSTITUTIONS ||--o{ CABINET_MEMBERSHIPS : "belongs to"
    
    INSTITUTIONS ||--o{ INSTITUTION_LINEAGES : "predecessor"
    INSTITUTIONS ||--o{ INSTITUTION_LINEAGES : "successor"
    CABINET_PERIODS ||--o{ INSTITUTION_LINEAGES : "during period"
    
    INSTITUTIONS ||--o{ ORGANIZATION_UNITS : "contains units"
    ORGANIZATION_UNITS ||--o{ ORGANIZATION_UNITS : "parent of"
    POSITION_LEVELS ||--o{ ORGANIZATION_UNITS : "echelon of"
    
    INSTITUTIONS ||--o{ TUGAS_FUNGSI : "has duties"
    ORGANIZATION_UNITS ||--o{ TUGAS_FUNGSI : "assigned duties"
    
    INSTITUTIONS ||--o{ USERS : "assigned scope"
    ROLES ||--o{ USERS : "has role"
    ROLES ||--o{ ROLE_PERMISSIONS : "contains"
    PERMISSIONS ||--o{ ROLE_PERMISSIONS : "granted via"
    
    INSTITUTIONS ||--o{ SUBMISSION_TICKETS : "subject of"
    USERS ||--o{ SUBMISSION_TICKETS : "submitted by"
    SUBMISSION_TICKETS ||--o{ SUBMISSION_ITEMS : "contains items"
    SUBMISSION_TICKETS ||--o{ VERIFICATION_LOGS : "verified via"
    USERS ||--o{ VERIFICATION_LOGS : "verified by"
    
    USERS ||--o{ NOTIFICATIONS : "receives"
    USERS ||--o{ AUDIT_LOGS : "performed by"
```
