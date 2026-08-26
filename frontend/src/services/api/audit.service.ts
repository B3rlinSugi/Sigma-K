import { AuditLogEntry } from '@/types/audit';
import { MOCK_AUDIT_LOGS } from '@/data/mock/auditLogs';
import { envConfig } from '@/config/env.config';
import { httpClient } from '@/services/http/client';
import { AuditLogDto } from '@/types/dto/audit.dto';
import { mapAuditLogsDtoToDomain } from '@/services/mappers/audit.mapper';

const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Mock Implementation for isolated UI demo
 */
class MockAuditService {
  static async getAuditLogs(params?: { search?: string; action?: string; institutionId?: string }): Promise<AuditLogEntry[]> {
    await delay(120);
    let result = [...MOCK_AUDIT_LOGS];
    if (params?.search) {
      const q = params.search.toLowerCase();
      result = result.filter(
        (l) => l.actorName.toLowerCase().includes(q) || l.description.toLowerCase().includes(q) || l.entity.toLowerCase().includes(q)
      );
    }
    if (params?.action && params.action !== 'ALL') {
      result = result.filter((l) => l.action === params.action);
    }
    if (params?.institutionId) {
      result = result.filter((l) => l.institutionId === params.institutionId);
    }
    return result;
  }
}

/**
 * API Implementation connected to CodeIgniter 4 backend
 */
class ApiAuditService {
  static async getAuditLogs(params?: { search?: string; action?: string; institutionId?: string }): Promise<AuditLogEntry[]> {
    const dtos = await httpClient.get<AuditLogDto[]>('audit-logs', {
      params: {
        search: params?.search,
        action_event: params?.action !== 'ALL' ? params?.action : undefined,
        institution_id: params?.institutionId,
        limit: 50,
      },
    });
    return mapAuditLogsDtoToDomain(dtos || []);
  }
}

/**
 * Unified Facade dispatching based on environment mode
 */
export class AuditService {
  static async getAuditLogs(params?: { search?: string; action?: string; institutionId?: string }): Promise<AuditLogEntry[]> {
    if (envConfig.isApiMode) {
      try {
        return await ApiAuditService.getAuditLogs(params);
      } catch (err) {
        console.warn('API error in getAuditLogs, falling back to mock:', err);
        return MockAuditService.getAuditLogs(params);
      }
    }
    return MockAuditService.getAuditLogs(params);
  }
}
