import { SubmissionTicket, WorkflowStatus } from '@/types/submission';
import { MOCK_SUBMISSIONS } from '@/data/mock/submissions';
import { envConfig } from '@/config/env.config';
import { httpClient } from '@/services/http/client';
import { SubmissionDto } from '@/types/dto/submission.dto';
import { mapSubmissionDtoToDomain, mapSubmissionsDtoToDomain } from '@/services/mappers/submission.mapper';

const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

let currentSubmissions = [...MOCK_SUBMISSIONS];

/**
 * Mock Implementation for isolated UI demo
 */
class MockSubmissionService {
  static async getSubmissions(params?: { status?: string; search?: string }): Promise<SubmissionTicket[]> {
    await delay(120);
    let result = [...currentSubmissions];
    if (params?.status && params.status !== 'ALL') {
      result = result.filter((s) => s.status === params.status);
    }
    if (params?.search) {
      const q = params.search.toLowerCase();
      result = result.filter(
        (s) => s.title.toLowerCase().includes(q) || s.ticketNumber.toLowerCase().includes(q) || s.institutionName.toLowerCase().includes(q)
      );
    }
    return result;
  }

  static async getSubmissionById(id: string): Promise<SubmissionTicket | null> {
    await delay(100);
    return currentSubmissions.find((s) => s.id === id) || null;
  }

  static async updateStatus(id: string, status: WorkflowStatus, verificationNote?: string, verifierName?: string): Promise<SubmissionTicket | null> {
    await delay(150);
    const target = currentSubmissions.find((s) => s.id === id);
    if (!target) return null;
    
    target.status = status;
    target.updatedAt = new Date().toISOString();

    if (verificationNote && verifierName) {
      if (!target.verificationLogs) target.verificationLogs = [];
      target.verificationLogs.push({
        id: `ver-${Date.now()}`,
        submissionTicketId: id,
        verifierUserId: 'usr-verifikator-01',
        verifierName: verifierName,
        decision: status === 'VERIFIED' ? 'PASS' : status === 'REVISION_REQUIRED' ? 'REVISION_REQUIRED' : 'REJECT',
        notes: verificationNote,
        verifiedAt: new Date().toISOString(),
      });
    }

    if (status === 'APPROVED') {
      target.approvedAt = new Date().toISOString();
      target.approvedByUserName = 'Ahmad Fauzi, S.Kom. (Verifikator Analis Kelembagaan)';
    }

    return { ...target };
  }
}

/**
 * API Implementation connected to CodeIgniter 4 backend
 */
class ApiSubmissionService {
  static async getSubmissions(params?: { status?: string; search?: string }): Promise<SubmissionTicket[]> {
    const dtos = await httpClient.get<SubmissionDto[]>('submissions', {
      params: {
        status: params?.status !== 'ALL' ? params?.status : undefined,
        search: params?.search,
      },
    });
    return mapSubmissionsDtoToDomain(dtos || []);
  }

  static async getSubmissionById(id: string): Promise<SubmissionTicket | null> {
    const cleanId = id.replace(/[^0-9]/g, '') || '1';
    const dto = await httpClient.get<SubmissionDto>(`submissions/${cleanId}`);
    return dto ? mapSubmissionDtoToDomain(dto) : null;
  }

  static async updateStatus(id: string, status: WorkflowStatus, verificationNote?: string, verifierName?: string): Promise<SubmissionTicket | null> {
    const cleanId = id.replace(/[^0-9]/g, '') || '1';
    if (status === 'APPROVED') {
      await httpClient.post(`submissions/${cleanId}/approve`, {
        approval_number: `SK-PANRB/${new Date().getFullYear()}/${String(cleanId).padStart(4, '0')}`,
        notes: verificationNote || 'Disahkan oleh Verifikator',
      });
      await httpClient.post(`submissions/${cleanId}/promote`);
    } else if (status === 'VERIFIED') {
      await httpClient.post(`submissions/${cleanId}/verifier-review/approve`, {
        recommendation_summary: verificationNote || 'Rekomendasi telaah substantif disetujui.',
        notes: 'Semua kriteria verifikasi terpenuhi.',
      });
    } else if (status === 'REVISION_REQUIRED') {
      await httpClient.post(`submissions/${cleanId}/verifier-review/return`, {
        notes: verificationNote || 'Perlu perbaikan berkas usulan.',
      });
    }
    return ApiSubmissionService.getSubmissionById(id);
  }
}

/**
 * Unified Facade dispatching based on environment mode
 */
export class SubmissionService {
  static async getSubmissions(params?: { status?: string; search?: string }): Promise<SubmissionTicket[]> {
    if (envConfig.isApiMode) {
      try {
        return await ApiSubmissionService.getSubmissions(params);
      } catch (err) {
        console.warn('API error in getSubmissions, falling back to mock:', err);
        return MockSubmissionService.getSubmissions(params);
      }
    }
    return MockSubmissionService.getSubmissions(params);
  }

  static async getSubmissionById(id: string): Promise<SubmissionTicket | null> {
    if (envConfig.isApiMode) {
      try {
        return await ApiSubmissionService.getSubmissionById(id);
      } catch (err) {
        console.warn(`API error in getSubmissionById(${id}), falling back to mock:`, err);
        return MockSubmissionService.getSubmissionById(id);
      }
    }
    return MockSubmissionService.getSubmissionById(id);
  }

  static async updateStatus(id: string, status: WorkflowStatus, verificationNote?: string, verifierName?: string): Promise<SubmissionTicket | null> {
    if (envConfig.isApiMode) {
      try {
        return await ApiSubmissionService.updateStatus(id, status, verificationNote, verifierName);
      } catch (err) {
        console.warn(`API error in updateStatus(${id}, ${status}), falling back to mock:`, err);
        return MockSubmissionService.updateStatus(id, status, verificationNote, verifierName);
      }
    }
    return MockSubmissionService.updateStatus(id, status, verificationNote, verifierName);
  }
}
