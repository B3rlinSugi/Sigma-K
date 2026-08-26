import { 
  KPICandidate, 
  EchelonDistribution, 
  SubmissionTurnaroundMetric, 
  CabinetCompositionStats,
  ExecutiveDashboardSummary,
  SubmissionReportItem,
  InstitutionReportItem,
  ApprovalReportItem,
  PromotionReportItem
} from '@/types/analytics';
import { 
  MOCK_KPIS, 
  MOCK_ECHELON_DISTRIBUTION, 
  MOCK_CABINET_COMPOSITION, 
  MOCK_SUBMISSION_TURNAROUND 
} from '@/data/mock/analytics';
import { envConfig } from '@/config/env.config';
import { httpClient } from '@/services/http/client';
import { 
  ReportSummaryDto, 
  SubmissionReportRowDto, 
  InstitutionReportRowDto, 
  ApprovalReportRowDto, 
  PromotionReportRowDto 
} from '@/types/dto/report.dto';
import { 
  mapReportSummaryToDomain, 
  mapReportSummaryToKPIs, 
  mapSubmissionReportRowsToDomain, 
  mapInstitutionReportRowsToDomain, 
  mapApprovalReportRowsToDomain, 
  mapPromotionReportRowsToDomain 
} from '@/services/mappers/analytics.mapper';

const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

const MOCK_REPORT_SUMMARY: ExecutiveDashboardSummary = {
  overview: {
    totalInstitutions: 48,
    totalActiveUnits: 342,
    totalInactiveUnits: 14,
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
      id: 'app-1',
      approvalNumber: 'SK/2026/001',
      approvedAt: '2026-08-26 12:00:00',
      approverName: 'Dr. Verifikator Kelembagaan',
      submissionId: '10',
      submissionTitle: 'Penataan Struktur Organisasi Kemenko Pangan',
      institutionName: 'Kementerian Koordinator Bidang Pangan',
    },
  ],
};

/**
 * Mock Implementation for isolated UI demo
 */
class MockAnalyticsService {
  static async getKPIs(): Promise<KPICandidate[]> {
    await delay(100);
    return [...MOCK_KPIS];
  }

  static async getReportSummary(): Promise<ExecutiveDashboardSummary> {
    await delay(120);
    return { ...MOCK_REPORT_SUMMARY };
  }

  static async getSubmissionsReport(): Promise<SubmissionReportItem[]> {
    await delay(100);
    return [];
  }

  static async getInstitutionsReport(): Promise<InstitutionReportItem[]> {
    await delay(100);
    return [];
  }

  static async getApprovalsReport(): Promise<ApprovalReportItem[]> {
    await delay(100);
    return [];
  }

  static async getPromotionsReport(): Promise<PromotionReportItem[]> {
    await delay(100);
    return [];
  }

  static async exportReport(type: string = 'submissions', format: 'csv' | 'json' = 'csv'): Promise<Blob | object> {
    await delay(150);
    if (format === 'json') {
      return { reportType: type, count: 0, data: [] };
    }
    const csvContent = 'ID,Institution,Title,Year,State\n1,Kemenko Pangan,Penataan 2026,2026,APPROVED\n';
    return new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  }

  static async getEchelonDistribution(): Promise<EchelonDistribution[]> {
    await delay(100);
    return [...MOCK_ECHELON_DISTRIBUTION];
  }

  static async getCabinetComposition(): Promise<CabinetCompositionStats[]> {
    await delay(100);
    return [...MOCK_CABINET_COMPOSITION];
  }

  static async getSubmissionTurnaround(): Promise<SubmissionTurnaroundMetric[]> {
    await delay(100);
    return [...MOCK_SUBMISSION_TURNAROUND];
  }
}

/**
 * API Implementation connected to CodeIgniter 4 backend
 */
class ApiAnalyticsService {
  static async getReportSummary(): Promise<ExecutiveDashboardSummary> {
    const summaryDto = await httpClient.get<ReportSummaryDto>('reports/summary');
    return mapReportSummaryToDomain(summaryDto);
  }

  static async getKPIs(): Promise<KPICandidate[]> {
    const summaryDto = await httpClient.get<ReportSummaryDto>('reports/summary');
    return mapReportSummaryToKPIs(summaryDto);
  }

  static async getSubmissionsReport(params?: {
    institution_id?: number | string;
    status?: string;
    year?: number | string;
    limit?: number;
  }): Promise<SubmissionReportItem[]> {
    const rows = await httpClient.get<SubmissionReportRowDto[]>('reports/submissions', {
      params: params as Record<string, string | number>,
    });
    return mapSubmissionReportRowsToDomain(rows || []);
  }

  static async getInstitutionsReport(params?: { limit?: number }): Promise<InstitutionReportItem[]> {
    const rows = await httpClient.get<InstitutionReportRowDto[]>('reports/institutions', {
      params: params as Record<string, string | number>,
    });
    return mapInstitutionReportRowsToDomain(rows || []);
  }

  static async getApprovalsReport(params?: { limit?: number }): Promise<ApprovalReportItem[]> {
    const rows = await httpClient.get<ApprovalReportRowDto[]>('reports/approvals', {
      params: params as Record<string, string | number>,
    });
    return mapApprovalReportRowsToDomain(rows || []);
  }

  static async getPromotionsReport(params?: { limit?: number }): Promise<PromotionReportItem[]> {
    const rows = await httpClient.get<PromotionReportRowDto[]>('reports/promotions', {
      params: params as Record<string, string | number>,
    });
    return mapPromotionReportRowsToDomain(rows || []);
  }

  static async exportReport(type: string = 'submissions', format: 'csv' | 'json' = 'csv'): Promise<Blob | object> {
    if (format === 'json') {
      return httpClient.get<object>('reports/export', {
        params: { type, format: 'json' },
      });
    }
    return httpClient.getBlob('reports/export', {
      params: { type, format: 'csv' },
    });
  }

  static async getEchelonDistribution(): Promise<EchelonDistribution[]> {
    return MockAnalyticsService.getEchelonDistribution();
  }

  static async getCabinetComposition(): Promise<CabinetCompositionStats[]> {
    return MockAnalyticsService.getCabinetComposition();
  }

  static async getSubmissionTurnaround(): Promise<SubmissionTurnaroundMetric[]> {
    return MockAnalyticsService.getSubmissionTurnaround();
  }
}

/**
 * Unified Facade dispatching based on environment mode
 */
export class AnalyticsService {
  static async getReportSummary(): Promise<ExecutiveDashboardSummary> {
    if (envConfig.isApiMode) {
      return ApiAnalyticsService.getReportSummary();
    }
    return MockAnalyticsService.getReportSummary();
  }

  static async getKPIs(): Promise<KPICandidate[]> {
    if (envConfig.isApiMode) {
      return ApiAnalyticsService.getKPIs();
    }
    return MockAnalyticsService.getKPIs();
  }

  static async getSubmissionsReport(params?: {
    institution_id?: number | string;
    status?: string;
    year?: number | string;
    limit?: number;
  }): Promise<SubmissionReportItem[]> {
    if (envConfig.isApiMode) {
      return ApiAnalyticsService.getSubmissionsReport(params);
    }
    return MockAnalyticsService.getSubmissionsReport();
  }

  static async getInstitutionsReport(params?: { limit?: number }): Promise<InstitutionReportItem[]> {
    if (envConfig.isApiMode) {
      return ApiAnalyticsService.getInstitutionsReport(params);
    }
    return MockAnalyticsService.getInstitutionsReport();
  }

  static async getApprovalsReport(params?: { limit?: number }): Promise<ApprovalReportItem[]> {
    if (envConfig.isApiMode) {
      return ApiAnalyticsService.getApprovalsReport(params);
    }
    return MockAnalyticsService.getApprovalsReport();
  }

  static async getPromotionsReport(params?: { limit?: number }): Promise<PromotionReportItem[]> {
    if (envConfig.isApiMode) {
      return ApiAnalyticsService.getPromotionsReport(params);
    }
    return MockAnalyticsService.getPromotionsReport();
  }

  static async exportReport(type: string = 'submissions', format: 'csv' | 'json' = 'csv'): Promise<Blob | object> {
    if (envConfig.isApiMode) {
      return ApiAnalyticsService.exportReport(type, format);
    }
    return MockAnalyticsService.exportReport(type, format);
  }

  static async getEchelonDistribution(): Promise<EchelonDistribution[]> {
    return MockAnalyticsService.getEchelonDistribution();
  }

  static async getCabinetComposition(): Promise<CabinetCompositionStats[]> {
    return MockAnalyticsService.getCabinetComposition();
  }

  static async getSubmissionTurnaround(): Promise<SubmissionTurnaroundMetric[]> {
    return MockAnalyticsService.getSubmissionTurnaround();
  }
}
