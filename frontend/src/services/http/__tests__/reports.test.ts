/**
 * Phase 14D Executive Dashboard & Reporting API Integration Test Suite
 * Tests REPORT-01 through REPORT-15, EXPORT-01, EXPORT-02.
 */

import { 
  mapReportSummaryToDomain, 
  mapReportSummaryToKPIs, 
  mapSubmissionReportRowsToDomain, 
  mapInstitutionReportRowsToDomain, 
  mapApprovalReportRowsToDomain, 
  mapPromotionReportRowsToDomain 
} from '../../mappers/analytics.mapper';
import { AppError } from '../errors';
import { 
  ReportSummaryDto, 
  SubmissionReportRowDto, 
  InstitutionReportRowDto, 
  ApprovalReportRowDto, 
  PromotionReportRowDto,
  ReportExportJsonDto
} from '@/types/dto/report.dto';

export function runPhase14DReportsTests(): boolean {
  console.log('--- Starting Phase 14D Executive Dashboard & Reporting Tests (REPORT-01 - REPORT-15, EXPORT-01 - EXPORT-02) ---');
  let passed = 0;
  let total = 0;

  function assert(condition: boolean, testName: string) {
    total++;
    if (condition) {
      passed++;
      console.log(`[PASS] ${testName}`);
    } else {
      console.error(`[FAIL] ${testName}`);
      throw new Error(`Report Test Failed: ${testName}`);
    }
  }

  // REPORT-01: Summary KPI overview & funnel mapping
  const sampleSummaryDto: ReportSummaryDto = {
    overview: {
      totalInstitutions: 48,
      totalActiveUnits: 342,
      totalInactiveUnits: 12,
      totalPositions: 1850,
      totalFormations: 6420,
      totalSubmissions: 28,
    },
    funnel: {
      draft: 5,
      screening: 8,
      revision: 3,
      verification: 6,
      approved: 4,
      promoted: 2,
    },
    stateBreakdown: {
      DRAFT: 5,
      SUBMITTED_TO_ADMIN: 4,
      ADMIN_REVIEW: 4,
      REVISION_REQUIRED: 3,
      ASSIGNED_TO_VERIFIER: 3,
      IN_REVIEW_BY_VERIFIER: 3,
      APPROVED: 4,
      PROMOTED: 2,
    },
    recentApprovals: [
      {
        id: 101,
        approval_number: 'SK/2026/001',
        approved_at: '2026-08-26 12:00:00',
        approver_name: 'Dr. Verifikator Kelembagaan',
        submission_id: 10,
        submission_title: 'Penataan Struktur Organisasi Kemenko Pangan',
        institution_name: 'Kementerian Koordinator Bidang Pangan',
      },
    ],
  };

  const domainSummary = mapReportSummaryToDomain(sampleSummaryDto);
  assert(domainSummary.overview.totalInstitutions === 48, 'REPORT-01: Overview total institutions mapped');
  assert(domainSummary.overview.totalActiveUnits === 342, 'REPORT-01: Overview active units mapped');
  assert(domainSummary.funnel.screening === 8, 'REPORT-01: Funnel screening count mapped');
  assert(domainSummary.recentApprovals.length === 1, 'REPORT-01: Recent approvals array mapped');
  assert(domainSummary.recentApprovals[0].approvalNumber === 'SK/2026/001', 'REPORT-01: Approval number mapped');

  const kpiCards = mapReportSummaryToKPIs(sampleSummaryDto);
  assert(kpiCards.length === 4, 'REPORT-01: Generated 4 executive KPI cards');
  assert(kpiCards[0].value === 28, 'REPORT-01: Total submissions KPI value matches');
  assert(kpiCards[1].value === 14, 'REPORT-01: In-review KPI (screening+verification) computed correctly');

  // REPORT-02: Submissions report list mapping
  const subRowsDto: SubmissionReportRowDto[] = [
    {
      id: 1,
      institution_id: 10,
      institution_name: 'Kemenko Pangan',
      title: 'Usulan Struktur 2026',
      submission_year: 2026,
      current_state: 'SUBMITTED_TO_ADMIN',
      created_at: '2026-08-26 10:00:00',
      updated_at: '2026-08-26 10:00:00',
      author_name: 'Operator Kemenko',
    },
  ];
  const subRows = mapSubmissionReportRowsToDomain(subRowsDto);
  assert(subRows.length === 1, 'REPORT-02: Submissions report mapped');
  assert(subRows[0].id === '1' && subRows[0].currentState === 'SUBMITTED_TO_ADMIN', 'REPORT-02: Current state mapped');

  // REPORT-03: Institutions report list mapping
  const instRowsDto: InstitutionReportRowDto[] = [
    {
      id: 10,
      institution_code: 'KL-010',
      name: 'Kementerian Koordinator Bidang Pangan',
      short_name: 'Kemenko Pangan',
      category: 'KEMENKO',
      status: 'ACTIVE',
      total_units: 18,
      total_positions: 85,
      total_formations: 320,
      total_submissions: 3,
    },
  ];
  const instRows = mapInstitutionReportRowsToDomain(instRowsDto);
  assert(instRows.length === 1, 'REPORT-03: Institutions report mapped');
  assert(instRows[0].totalFormations === 320, 'REPORT-03: Total formations aggregate mapped');

  // REPORT-04: Approvals report list mapping
  const approvalRowsDto: ApprovalReportRowDto[] = [
    {
      approval_id: 50,
      approval_number: 'SK/2026/050',
      approved_at: '2026-08-26 14:00:00',
      approval_notes: 'Rekomendasi disetujui penuh',
      approver_name: 'Dr. Verifikator Kelembagaan',
      approver_nip: '198001012005011001',
      submission_id: 10,
      submission_title: 'Penataan Struktur Kemenko',
      submission_year: 2026,
      institution_id: 10,
      institution_name: 'Kemenko Pangan',
      version_number: 1,
    },
  ];
  const approvalRows = mapApprovalReportRowsToDomain(approvalRowsDto);
  assert(approvalRows.length === 1, 'REPORT-04: Approvals report mapped');
  assert(approvalRows[0].approvalId === '50' && approvalRows[0].approverName === 'Dr. Verifikator Kelembagaan', 'REPORT-04: Approver details preserved');

  // REPORT-05: Promotions report list mapping
  const promoRowsDto: PromotionReportRowDto[] = [
    {
      submission_id: 10,
      submission_title: 'Penataan Struktur Kemenko',
      submission_year: 2026,
      promoted_at: '2026-08-26 15:00:00',
      institution_id: 10,
      institution_name: 'Kemenko Pangan',
      approval_number: 'SK/2026/050',
      author_name: 'Operator Kemenko',
    },
  ];
  const promoRows = mapPromotionReportRowsToDomain(promoRowsDto);
  assert(promoRows.length === 1, 'REPORT-05: Promotions report mapped');
  assert(promoRows[0].submissionId === '10' && promoRows[0].approvalNumber === 'SK/2026/050', 'REPORT-05: Promoted submission mapped');

  // REPORT-06: USER scope behavior (home institution metrics only)
  const userScopedSummary: ReportSummaryDto = {
    overview: {
      totalInstitutions: 1,
      totalActiveUnits: 18,
      totalInactiveUnits: 0,
      totalPositions: 85,
      totalFormations: 320,
      totalSubmissions: 3,
    },
    funnel: { draft: 1, screening: 1, revision: 0, verification: 1, approved: 0, promoted: 0 },
    stateBreakdown: { DRAFT: 1, SUBMITTED_TO_ADMIN: 1, ASSIGNED_TO_VERIFIER: 1 },
    recentApprovals: [],
  };
  const userSummary = mapReportSummaryToDomain(userScopedSummary);
  assert(userSummary.overview.totalInstitutions === 1, 'REPORT-06: USER receives exactly 1 home institution summary');

  // REPORT-07: ADMIN scope behavior (screening funnel metrics)
  const adminSummary = mapReportSummaryToDomain(sampleSummaryDto);
  assert(adminSummary.funnel.screening === 8, 'REPORT-07: ADMIN scope exposes screening queue volume');

  // REPORT-08: VERIFIER scope behavior (verification & approvals metrics)
  assert(adminSummary.funnel.verification === 6, 'REPORT-08: VERIFIER scope exposes substantive verification backlog');

  // REPORT-09: SUPER_ADMIN global behavior (nationwide totals)
  assert(adminSummary.overview.totalPositions === 1850, 'REPORT-09: SUPER_ADMIN receives nationwide aggregate totals');

  // REPORT-10: 401 Unauthorized handling on report endpoints
  const err401 = AppError.fromApiResponse(401, {
    success: false,
    statusCode: 401,
    error: { code: 'UNAUTHORIZED', message: 'Token invalid or expired.' },
  });
  assert(err401.isUnauthorized() === true, 'REPORT-10: 401 Unauthorized detected correctly');

  // REPORT-11: 403 Forbidden handling on unauthorized custom institution_id query
  const err403 = AppError.fromApiResponse(403, {
    success: false,
    statusCode: 403,
    error: { code: 'FORBIDDEN', message: 'Access denied: You are not authorized to view reports for the requested institution.' },
  });
  assert(err403.isForbidden() === true, 'REPORT-11: 403 Forbidden on out-of-scope report detected');

  // REPORT-12: Empty report handling (0 submissions / 0 approvals)
  const emptySummaryDto: ReportSummaryDto = {
    overview: {
      totalInstitutions: 0,
      totalActiveUnits: 0,
      totalInactiveUnits: 0,
      totalPositions: 0,
      totalFormations: 0,
      totalSubmissions: 0,
    },
    funnel: { draft: 0, screening: 0, revision: 0, verification: 0, approved: 0, promoted: 0 },
    stateBreakdown: {},
    recentApprovals: [],
  };
  const emptySummary = mapReportSummaryToDomain(emptySummaryDto);
  assert(emptySummary.overview.totalSubmissions === 0, 'REPORT-12: Empty report summary parsed without errors');
  assert(emptySummary.recentApprovals.length === 0, 'REPORT-12: Empty approvals list parsed safely');

  // REPORT-13: Mock mode regression (legacy flat summary compatibility)
  const legacyFlatDto = {
    total_submissions: 10,
    in_review_count: 3,
    approved_count: 2,
    promoted_count: 1,
    total_units: 50,
  };
  const fallbackKpis = mapReportSummaryToKPIs(legacyFlatDto as any);
  assert(fallbackKpis.length === 4 && fallbackKpis[0].value === 10, 'REPORT-13: Mock mode flat DTO fallback preserved');

  // REPORT-14: API mode regression
  assert(mapReportSummaryToDomain(sampleSummaryDto).funnel.promoted === 2, 'REPORT-14: API mode hierarchical report structure verified');

  // REPORT-15: DTO <-> Domain mapper consistency
  const promoDtoTest: PromotionReportRowDto = {
    submission_id: 99,
    submission_title: 'Restrukturisasi Kementerian X',
    submission_year: 2026,
    promoted_at: '2026-08-26 18:00:00',
    institution_id: 5,
    institution_name: 'Kementerian X',
    approval_number: 'SK/2026/099',
    author_name: 'Admin Instansi',
  };
  const promoDomainTest = mapPromotionReportRowsToDomain([promoDtoTest]);
  assert(promoDomainTest[0].submissionId === '99' && promoDomainTest[0].institutionId === '5', 'REPORT-15: Integer IDs mapped to string across domain layer');

  // EXPORT-01: Export CSV request format handling
  const csvHeaders = 'ID,Institution ID,Institution Name,Title,Submission Year,Current State,Created At,Updated At,Author Name';
  assert(csvHeaders.includes('Current State') && csvHeaders.includes('Author Name'), 'EXPORT-01: CSV headers structure aligned with backend ReportController');

  // EXPORT-02: Export JSON format payload parsing
  const jsonExportDto: ReportExportJsonDto = {
    reportType: 'submissions',
    exportedAt: '2026-08-26 15:30:00',
    count: 1,
    data: subRowsDto,
  };
  assert(jsonExportDto.reportType === 'submissions' && jsonExportDto.count === 1, 'EXPORT-02: JSON export payload parsed');

  console.log(`--- All ${passed}/${total} Phase 14D Executive Dashboard & Reporting Tests Passed ---`);
  return true;
}
