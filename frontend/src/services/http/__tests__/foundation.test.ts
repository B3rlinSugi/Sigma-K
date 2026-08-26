/**
 * Phase 14A Integration Foundation Test Suite
 * Verifies core HTTP types, AppError normalization, token handling, and DTO mappers.
 */

import { AppError } from '../errors';
import { BrowserStorageTokenProvider } from '../token-provider';
import { mapInstitutionDtoToDomain } from '../../mappers/institution.mapper';
import { flattenOrgUnitTree } from '../../mappers/organization.mapper';
import { mapBackendStateToWorkflowStatus, mapSubmissionDtoToDomain } from '../../mappers/submission.mapper';
import { mapAuditLogDtoToDomain } from '../../mappers/audit.mapper';
import { mapReportSummaryToKPIs } from '../../mappers/analytics.mapper';
import { InstitutionDto } from '@/types/dto/institution.dto';
import { OrgUnitTreeDto } from '@/types/dto/organization.dto';
import { SubmissionDto } from '@/types/dto/submission.dto';
import { AuditLogDto } from '@/types/dto/audit.dto';
import { ReportSummaryDto } from '@/types/dto/report.dto';

export function runPhase14AFoundationTests(): boolean {
  console.log('--- Starting Phase 14A Foundation Tests ---');
  let passed = 0;
  let total = 0;

  function assert(condition: boolean, testName: string) {
    total++;
    if (condition) {
      passed++;
      console.log(`[PASS] ${testName}`);
    } else {
      console.error(`[FAIL] ${testName}`);
      throw new Error(`Test Failed: ${testName}`);
    }
  }

  // 1. AppError Normalization Tests
  const err401 = AppError.fromApiResponse(401, {
    success: false,
    statusCode: 401,
    error: { code: 'UNAUTHORIZED', message: 'Token expired' },
  });
  assert(err401.isUnauthorized() === true, 'AppError 401 detects unauthorized');
  assert(err401.code === 'UNAUTHORIZED', 'AppError preserves error code');

  const err403 = AppError.fromApiResponse(403, {
    success: false,
    statusCode: 403,
    error: { code: 'FORBIDDEN', message: 'Zero-Trust access denied' },
  });
  assert(err403.isForbidden() === true, 'AppError 403 detects forbidden');

  const err409 = AppError.fromApiResponse(409, {
    success: false,
    statusCode: 409,
    error: { code: 'CONFLICT', message: 'Resource version mismatch' },
  });
  assert(err409.isConflict() === true, 'AppError 409 detects conflict');

  const err422 = AppError.fromApiResponse(422, {
    success: false,
    statusCode: 422,
    error: {
      code: 'VALIDATION_ERROR',
      message: 'Validation failed',
      details: { unit_code: 'Unit code already taken' },
    },
  });
  assert(err422.isValidationError() === true, 'AppError 422 detects validation error');
  assert(Boolean(err422.details && err422.details.unit_code), 'AppError preserves validation details');

  // 2. Token Provider Tests
  const tokenProvider = new BrowserStorageTokenProvider();
  tokenProvider.setAccessToken('jwt.header.payload');
  assert(tokenProvider.getAccessToken() === 'jwt.header.payload', 'TokenProvider stores and retrieves token');
  tokenProvider.clearAccessToken();
  assert(tokenProvider.getAccessToken() === null, 'TokenProvider clears token correctly');

  // 3. Institution DTO Mapper Tests
  const instDto: InstitutionDto = {
    id: 10,
    institution_code: 'KL-007',
    name: 'Kementerian Koordinator Bidang Pangan',
    short_name: 'Kemenko Pangan',
    category: 'KEMENKO',
    status: 'ACTIVE',
    created_at: '2026-08-26T00:00:00Z',
    updated_at: '2026-08-26T00:00:00Z',
  };
  const instDomain = mapInstitutionDtoToDomain(instDto);
  assert(instDomain.id === '10', 'InstitutionMapper converts numeric ID to string');
  assert(instDomain.code === 'KL-007', 'InstitutionMapper maps institution_code');
  assert(instDomain.shortName === 'Kemenko Pangan', 'InstitutionMapper maps short_name to camelCase');
  assert(instDomain.institutionTypeCode === 'KEMENKO', 'InstitutionMapper maps category to typeCode');

  // 4. Organization DTO Tree Flattener Tests
  const orgTreeDto: OrgUnitTreeDto[] = [
    {
      id: 1,
      institution_id: 10,
      unit_code: 'MENKO',
      unit_name: 'Menteri Koordinator',
      hierarchy_level: 1,
      sort_order: 1,
      status: 'ACTIVE',
      children: [
        {
          id: 2,
          institution_id: 10,
          parent_id: 1,
          unit_code: 'SESMENKO',
          unit_name: 'Sekretariat Kementerian Koordinator',
          hierarchy_level: 2,
          sort_order: 1,
          status: 'ACTIVE',
          children: [],
        },
      ],
    },
  ];
  const flatOrgUnits = flattenOrgUnitTree(orgTreeDto);
  assert(flatOrgUnits.length === 2, 'OrganizationMapper flattens recursive tree correctly');
  assert(flatOrgUnits[1].parentId === '1', 'OrganizationMapper preserves parent-child relations');

  // 5. Submission State & DTO Mapper Tests
  assert(mapBackendStateToWorkflowStatus('DRAFT') === 'DRAFT', 'StateMapper maps DRAFT');
  assert(mapBackendStateToWorkflowStatus('SUBMITTED_TO_ADMIN') === 'SUBMITTED', 'StateMapper maps SUBMITTED_TO_ADMIN');
  assert(mapBackendStateToWorkflowStatus('READY_FOR_FINAL_DECISION') === 'VERIFIED', 'StateMapper maps READY_FOR_FINAL_DECISION');
  assert(mapBackendStateToWorkflowStatus('APPROVED') === 'APPROVED', 'StateMapper maps APPROVED');

  const subDto: SubmissionDto = {
    id: 101,
    institution_id: 10,
    institution_name: 'Kemenko Pangan',
    title: 'Usulan Penataan Struktur 2026',
    submission_year: 2026,
    current_state: 'SUBMITTED_TO_ADMIN',
    units_count: 5,
    positions_count: 12,
    created_at: '2026-08-26T10:00:00Z',
    updated_at: '2026-08-26T10:00:00Z',
    author_id: 1,
    author_name: 'Operator Kemenko',
  };
  const subDomain = mapSubmissionDtoToDomain(subDto);
  assert(subDomain.id === '101', 'SubmissionMapper converts ID to string');
  assert(subDomain.status === 'SUBMITTED', 'SubmissionMapper maps current_state to status');
  assert(subDomain.itemsCount === 17, 'SubmissionMapper sums units and positions count');
  assert(subDomain.ticketNumber === 'TKT-2026-0101', 'SubmissionMapper generates canonical ticketNumber');

  // 6. Audit Log DTO Mapper Tests
  const auditDto: AuditLogDto = {
    id: 501,
    action_event: 'SUBMIT',
    resource_entity: 'SUBMISSION',
    resource_id: 101,
    actor_id: 1,
    actor_name: 'Budi Santoso',
    actor_role: 'OPERATOR',
    institution_id: 10,
    old_payload: null,
    new_payload: null,
    ip_address: '127.0.0.1',
    user_agent: null,
    created_at: '2026-08-26T10:00:00Z',
  };
  const auditDomain = mapAuditLogDtoToDomain(auditDto);
  assert(auditDomain.id === '501', 'AuditMapper converts ID to string');
  assert(auditDomain.action === 'SUBMIT', 'AuditMapper maps action_event to action');
  assert(auditDomain.actorName === 'Budi Santoso', 'AuditMapper maps actor_name');

  // 7. Analytics KPI Mapper Tests
  const reportDto: ReportSummaryDto = {
    overview: {
      totalInstitutions: 48,
      totalActiveUnits: 320,
      totalInactiveUnits: 0,
      totalPositions: 1250,
      totalFormations: 4500,
      totalSubmissions: 15,
    },
    funnel: {
      draft: 3,
      screening: 2,
      revision: 2,
      verification: 2,
      approved: 5,
      promoted: 1,
    },
    stateBreakdown: {
      DRAFT: 3,
      SUBMITTED_TO_ADMIN: 2,
      APPROVED: 5,
      PROMOTED: 1,
    },
    recentApprovals: [],
  };
  const kpiList = mapReportSummaryToKPIs(reportDto);
  assert(kpiList.length === 4, 'AnalyticsMapper generates 4 core executive KPIs');
  assert(kpiList[0].value === 15, 'AnalyticsMapper sets total submissions value');
  assert(kpiList[2].value === 6, 'AnalyticsMapper sums approved and promoted');

  console.log(`--- All ${passed}/${total} Phase 14A Foundation Tests Passed ---`);
  return true;
}
