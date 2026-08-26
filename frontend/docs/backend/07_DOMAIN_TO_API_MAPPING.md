# SIGMA-K — DOMAIN MODEL TO API DTO MAPPING

> **Dokumen:** `07_DOMAIN_TO_API_MAPPING.md`  
> **Status:** `DTO SPECIFICATION & DOMAIN MAPPING (PHASE 5A DESIGN)`  
> **Tujuan:** Menjamin koherensi 100% antara Entitas Domain Phase 3, Kontrak Frontend Phase 4, dan DTO Backend Phase 5.  

---

## 1. Tabel Pemetaan Entitas Domain ke DTO API

| Domain Entity (Phase 3/4) | Backend Entity / Interface | Request DTO (Input) | Response DTO (Output) |
| :--- | :--- | :--- | :--- |
| **`Institution`** | `InstitutionEntity` | `CreateInstitutionDto`<br>`UpdateInstitutionDto` | `InstitutionResponseDto`<br>`InstitutionDetailResponseDto` |
| **`InstitutionProfile`** | `InstitutionProfileEntity` | `UpdateInstitutionProfileDto` | `InstitutionProfileResponseDto` |
| **`InstitutionType`** | `InstitutionTypeEntity` | N/A (Master Data) | `InstitutionTypeResponseDto` |
| **`Region`** | `RegionEntity` | N/A (Master Data) | `RegionResponseDto` |
| **`Cabinet`** | `CabinetEntity` | `CreateCabinetDto`<br>`UpdateCabinetDto` | `CabinetResponseDto` |
| **`CabinetPeriod`** | `CabinetPeriodEntity` | `CreateCabinetPeriodDto` | `CabinetPeriodResponseDto` |
| **`CabinetMembership`** | `CabinetMembershipEntity` | `AddCabinetMembershipDto` | `CabinetMembershipResponseDto` |
| **`InstitutionLineage`** | `InstitutionLineageEntity` | `RecordLineageDto` | `InstitutionLineageResponseDto` |
| **`CabinetComparisonSummary`** | N/A (Aggregated Read Model) | N/A (Query Params) | `CabinetComparisonResponseDto` |
| **`OrganizationUnit`** | `OrganizationUnitEntity` | `CreateOrgUnitDto`<br>`UpdateOrgUnitDto`<br>`MoveOrgUnitDto` | `OrgUnitResponseDto`<br>`OrgTreeResponseDto` |
| **`EchelonLevel`** | `EchelonLevelEntity` | N/A (Master Data) | `EchelonLevelResponseDto` |
| **`TupoksiItem` / `Tupoksi`** | `TupoksiItemEntity` | `CreateTupoksiDto`<br>`UpdateTupoksiDto` | `TupoksiResponseDto` |
| **`SubmissionTicket`** | `SubmissionTicketEntity` | `CreateSubmissionTicketDto`<br>`UpdateSubmissionDraftDto` | `SubmissionTicketResponseDto`<br>`SubmissionDetailResponseDto` |
| **`SubmissionItem`** | `SubmissionItemEntity` | `CreateSubmissionItemDto` | `SubmissionItemResponseDto` |
| **`SubmissionRevision`** | `SubmissionRevisionEntity` | `SubmitRevisionResponseDto` | `SubmissionRevisionResponseDto` |
| **`VerificationLog`** | `VerificationLogEntity` | `CreateVerificationDecisionDto` | `VerificationLogResponseDto` |
| **`NotificationItem`** | `NotificationEntity` | N/A (System Generated) | `NotificationResponseDto` |
| **`KPICandidate` / `AnalyticsMetric`**| N/A (OLAP Read Model) | N/A (Aggregated View) | `KPICandidateResponseDto` |
| **`AuditLogEntry`** | `AuditLogEntity` | N/A (Automated Interceptor) | `AuditLogResponseDto` |
| **`User` / `Role` / `UserScope`** | `UserEntity` | `CreateUserDto`<br>`UpdateUserRoleDto` | `UserResponseDto`<br>`AuthTokenResponseDto` |

---

## 2. Struktur Spesifikasi DTO Kunci

### A. DTO Pembuatan Usulan Tiket (`CreateSubmissionTicketDto`)
```typescript
export class CreateSubmissionTicketDto {
  @IsUUID()
  @IsNotEmpty()
  institutionId: string;

  @IsEnum(['STRUKTUR_ORGANISASI', 'TUGAS_FUNGSI', 'PROFIL_INSTANSI', 'KOMPOSISI_KABINET', 'INSTANSI_BARU'])
  submissionType: SubmissionType;

  @IsString()
  @MinLength(5)
  @MaxLength(255)
  title: string;

  @IsString()
  @IsOptional()
  submissionNotes?: string;

  @IsUUID()
  @IsOptional()
  legalDocFileId?: string;

  @IsArray()
  @ValidateNested({ each: true })
  @Type(() => CreateSubmissionItemDto)
  items: CreateSubmissionItemDto[];
}
```

### B. DTO Pohon Unit Organisasi (`OrgTreeResponseDto`)
```typescript
export interface OrgTreeResponseDto {
  id: string;
  institutionId: string;
  parentId: string | null;
  unitCode: string;
  unitName: string;
  hierarchyLevel: number;
  echelonName: string;
  leaderName?: string;
  leaderTitle?: string;
  staffCount: number;
  tupoksiCount: number;
  children?: OrgTreeResponseDto[];
}
```
