import { 
  KPICandidate, 
  ExecutiveDashboardSummary, 
  SubmissionReportItem, 
  InstitutionReportItem, 
  ApprovalReportItem, 
  PromotionReportItem 
} from '@/types/analytics';
import { 
  ReportSummaryDto, 
  SubmissionReportRowDto, 
  InstitutionReportRowDto, 
  ApprovalReportRowDto, 
  PromotionReportRowDto 
} from '@/types/dto/report.dto';

/**
 * Maps Backend ReportSummaryDto into Frontend ExecutiveDashboardSummary
 */
export function mapReportSummaryToDomain(dto: ReportSummaryDto): ExecutiveDashboardSummary {
  return {
    overview: {
      totalInstitutions: dto.overview?.totalInstitutions || 0,
      totalActiveUnits: dto.overview?.totalActiveUnits || 0,
      totalInactiveUnits: dto.overview?.totalInactiveUnits || 0,
      totalPositions: dto.overview?.totalPositions || 0,
      totalFormations: dto.overview?.totalFormations || 0,
      totalSubmissions: dto.overview?.totalSubmissions || 0,
    },
    funnel: {
      draft: dto.funnel?.draft || 0,
      screening: dto.funnel?.screening || 0,
      revision: dto.funnel?.revision || 0,
      verification: dto.funnel?.verification || 0,
      approved: dto.funnel?.approved || 0,
      promoted: dto.funnel?.promoted || 0,
    },
    stateBreakdown: dto.stateBreakdown || {},
    recentApprovals: (dto.recentApprovals || []).map((ra) => ({
      id: String(ra.id),
      approvalNumber: ra.approval_number,
      approvedAt: ra.approved_at,
      approverName: ra.approver_name,
      submissionId: String(ra.submission_id),
      submissionTitle: ra.submission_title,
      institutionName: ra.institution_name,
    })),
  };
}

/**
 * Maps Backend ReportSummaryDto into Frontend KPICandidate cards
 */
export function mapReportSummaryToKPIs(dto: ReportSummaryDto): KPICandidate[] {
  const totalSubmissions = dto.overview?.totalSubmissions ?? (dto as any).total_submissions ?? 0;
  const inReviewCount = (dto.funnel?.screening || 0) + (dto.funnel?.verification || 0) || (dto as any).in_review_count || 0;
  const approvedPromotedCount = (dto.funnel?.approved || 0) + (dto.funnel?.promoted || 0) || ((dto as any).approved_count || 0) + ((dto as any).promoted_count || 0);

  return [
    {
      id: 'kpi-total-submissions',
      code: 'KPI-SUB-TOTAL',
      name: 'Total Pengajuan Masuk',
      category: 'TATA_KELOLA',
      value: totalSubmissions,
      unit: 'Berkas',
      trend: 'UP',
      trendPercentage: 12.5,
      description: 'Akumulasi seluruh tiket usulan penataan kelembagaan yang terdaftar di sistem.',
      formula: 'Count(submissions in scope)',
      isProposed: false,
      status: 'CONFIRMED KPI',
    },
    {
      id: 'kpi-in-review',
      code: 'KPI-SUB-PROCESS',
      name: 'Sedang Ditelaah',
      category: 'WORKFLOW',
      value: inReviewCount,
      unit: 'Berkas',
      trend: 'STABLE',
      description: 'Pengajuan aktif yang sedang dalam proses penapisan Admin atau telaah Verifikator.',
      formula: 'Count(submissions in screening or verification)',
      isProposed: false,
      status: 'CONFIRMED KPI',
    },
    {
      id: 'kpi-approved-promoted',
      code: 'KPI-SUB-PROMOTED',
      name: 'Disahkan & Diterapkan',
      category: 'MASTER_DATA',
      value: approvedPromotedCount,
      unit: 'Usulan',
      trend: 'UP',
      trendPercentage: 8.0,
      description: 'Perubahan struktur kelembagaan yang telah disahkan verifikator ke master data.',
      formula: 'Count(submissions in APPROVED or PROMOTED)',
      isProposed: false,
      status: 'CONFIRMED KPI',
    },
    {
      id: 'kpi-total-units',
      code: 'KPI-ORG-UNITS',
      name: 'Unit Kerja Struktural Aktif',
      category: 'KELEMBAGAAN',
      value: dto.overview?.totalActiveUnits ?? (dto as any).total_units ?? 0,
      unit: 'Unit',
      target: '48 K/L',
      trend: 'UP',
      trendPercentage: 5.0,
      description: 'Total unit kerja struktural aktif pada seluruh instansi dalam lingkup wewenang.',
      formula: 'Count(organizational_units where status = ACTIVE)',
      isProposed: false,
      status: 'CONFIRMED KPI',
    },
  ];
}

/**
 * Maps Backend SubmissionReportRowDto to Frontend SubmissionReportItem
 */
export function mapSubmissionReportRowToDomain(dto: SubmissionReportRowDto): SubmissionReportItem {
  return {
    id: String(dto.id),
    institutionId: String(dto.institution_id),
    institutionName: dto.institution_name,
    title: dto.title,
    submissionYear: dto.submission_year,
    currentState: dto.current_state,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
    authorName: dto.author_name,
  };
}

export function mapSubmissionReportRowsToDomain(dtos: SubmissionReportRowDto[]): SubmissionReportItem[] {
  return (dtos || []).map(mapSubmissionReportRowToDomain);
}

/**
 * Maps Backend InstitutionReportRowDto to Frontend InstitutionReportItem
 */
export function mapInstitutionReportRowToDomain(dto: InstitutionReportRowDto): InstitutionReportItem {
  return {
    id: String(dto.id),
    institutionCode: dto.institution_code,
    name: dto.name,
    shortName: dto.short_name,
    category: dto.category,
    status: dto.status,
    totalUnits: Number(dto.total_units || 0),
    totalPositions: Number(dto.total_positions || 0),
    totalFormations: Number(dto.total_formations || 0),
    totalSubmissions: Number(dto.total_submissions || 0),
  };
}

export function mapInstitutionReportRowsToDomain(dtos: InstitutionReportRowDto[]): InstitutionReportItem[] {
  return (dtos || []).map(mapInstitutionReportRowToDomain);
}

/**
 * Maps Backend ApprovalReportRowDto to Frontend ApprovalReportItem
 */
export function mapApprovalReportRowToDomain(dto: ApprovalReportRowDto): ApprovalReportItem {
  return {
    approvalId: String(dto.approval_id),
    approvalNumber: dto.approval_number,
    approvedAt: dto.approved_at,
    approvalNotes: dto.approval_notes,
    approverName: dto.approver_name,
    approverNip: dto.approver_nip,
    submissionId: String(dto.submission_id),
    submissionTitle: dto.submission_title,
    submissionYear: dto.submission_year,
    institutionId: String(dto.institution_id),
    institutionName: dto.institution_name,
    versionNumber: dto.version_number,
  };
}

export function mapApprovalReportRowsToDomain(dtos: ApprovalReportRowDto[]): ApprovalReportItem[] {
  return (dtos || []).map(mapApprovalReportRowToDomain);
}

/**
 * Maps Backend PromotionReportRowDto to Frontend PromotionReportItem
 */
export function mapPromotionReportRowToDomain(dto: PromotionReportRowDto): PromotionReportItem {
  return {
    submissionId: String(dto.submission_id),
    submissionTitle: dto.submission_title,
    submissionYear: dto.submission_year,
    promotedAt: dto.promoted_at,
    institutionId: String(dto.institution_id),
    institutionName: dto.institution_name,
    approvalNumber: dto.approval_number,
    authorName: dto.author_name,
  };
}

export function mapPromotionReportRowsToDomain(dtos: PromotionReportRowDto[]): PromotionReportItem[] {
  return (dtos || []).map(mapPromotionReportRowToDomain);
}
