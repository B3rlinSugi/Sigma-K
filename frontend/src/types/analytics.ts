export interface KPICandidate {
  id: string;
  code: string;
  name: string;
  category: string;
  value: number | string;
  unit: string;
  target?: number | string;
  trend: 'UP' | 'DOWN' | 'STABLE';
  trendPercentage?: number;
  description: string;
  formula: string;
  isProposed: boolean;
  status: 'PROPOSED KPI' | 'CONFIRMED KPI';
}

// Alias for domain consistency
export type AnalyticsMetric = KPICandidate;

export interface EchelonDistribution {
  echelon: string;
  count: number;
  percentage: number;
  color: string;
}

export interface SubmissionTurnaroundMetric {
  submissionType: string;
  averageDays: number;
  totalCompleted: number;
}

export interface CabinetCompositionStats {
  category: string;
  count: number;
  color: string;
}

export interface ExecutiveReportOverview {
  totalInstitutions: number;
  totalActiveUnits: number;
  totalInactiveUnits: number;
  totalPositions: number;
  totalFormations: number;
  totalSubmissions: number;
}

export interface ExecutiveReportFunnel {
  draft: number;
  screening: number;
  revision: number;
  verification: number;
  approved: number;
  promoted: number;
}

export interface ExecutiveRecentApproval {
  id: string;
  approvalNumber: string;
  approvedAt: string;
  approverName: string;
  submissionId: string;
  submissionTitle: string;
  institutionName: string;
}

export interface ExecutiveDashboardSummary {
  overview: ExecutiveReportOverview;
  funnel: ExecutiveReportFunnel;
  stateBreakdown: Record<string, number>;
  recentApprovals: ExecutiveRecentApproval[];
}

export interface SubmissionReportItem {
  id: string;
  institutionId: string;
  institutionName: string;
  title: string;
  submissionYear: number;
  currentState: string;
  createdAt: string;
  updatedAt: string;
  authorName: string;
}

export interface InstitutionReportItem {
  id: string;
  institutionCode: string;
  name: string;
  shortName: string;
  category: string;
  status: string;
  totalUnits: number;
  totalPositions: number;
  totalFormations: number;
  totalSubmissions: number;
}

export interface ApprovalReportItem {
  approvalId: string;
  approvalNumber: string;
  approvedAt: string;
  approvalNotes?: string | null;
  approverName: string;
  approverNip?: string | null;
  submissionId: string;
  submissionTitle: string;
  submissionYear: number;
  institutionId: string;
  institutionName: string;
  versionNumber: number;
}

export interface PromotionReportItem {
  submissionId: string;
  submissionTitle: string;
  submissionYear: number;
  promotedAt: string;
  institutionId: string;
  institutionName: string;
  approvalNumber?: string | null;
  authorName: string;
}
