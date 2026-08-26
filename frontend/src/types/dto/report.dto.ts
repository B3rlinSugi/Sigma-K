/**
 * Executive Report & Summary DTOs matching CodeIgniter 4 / MySQL backend responses
 */

export interface ReportOverviewDto {
  totalInstitutions: number;
  totalActiveUnits: number;
  totalInactiveUnits: number;
  totalPositions: number;
  totalFormations: number;
  totalSubmissions: number;
}

export interface ReportFunnelDto {
  draft: number;
  screening: number;
  revision: number;
  verification: number;
  approved: number;
  promoted: number;
}

export interface ReportRecentApprovalDto {
  id: number;
  approval_number: string;
  approved_at: string;
  approver_name: string;
  submission_id: number;
  submission_title: string;
  institution_name: string;
}

export interface ReportSummaryDto {
  overview: ReportOverviewDto;
  funnel: ReportFunnelDto;
  stateBreakdown: Record<string, number>;
  recentApprovals: ReportRecentApprovalDto[];
}

export interface SubmissionReportRowDto {
  id: number;
  institution_id: number;
  institution_name: string;
  title: string;
  submission_year: number;
  current_state: string;
  created_at: string;
  updated_at: string;
  author_name: string;
}

export interface InstitutionReportRowDto {
  id: number;
  institution_code: string;
  name: string;
  short_name: string;
  category: string;
  status: string;
  total_units: number;
  total_positions: number;
  total_formations: number;
  total_submissions: number;
}

export interface ApprovalReportRowDto {
  approval_id: number;
  approval_number: string;
  approved_at: string;
  approval_notes?: string | null;
  approver_name: string;
  approver_nip?: string | null;
  submission_id: number;
  submission_title: string;
  submission_year: number;
  institution_id: number;
  institution_name: string;
  version_number: number;
}

export interface PromotionReportRowDto {
  submission_id: number;
  submission_title: string;
  submission_year: number;
  promoted_at: string;
  institution_id: number;
  institution_name: string;
  approval_number?: string | null;
  author_name: string;
}

export interface ReportExportJsonDto {
  reportType: string;
  exportedAt: string;
  count: number;
  data: (SubmissionReportRowDto | InstitutionReportRowDto | ApprovalReportRowDto | PromotionReportRowDto)[];
}
