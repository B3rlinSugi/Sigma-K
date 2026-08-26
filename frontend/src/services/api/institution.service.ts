import { Institution } from '@/types/institution';
import { MOCK_INSTITUTIONS } from '@/data/mock/institutions';
import { envConfig } from '@/config/env.config';
import { httpClient } from '@/services/http/client';
import { InstitutionDto } from '@/types/dto/institution.dto';
import { mapInstitutionDtoToDomain, mapInstitutionsDtoToDomain } from '@/services/mappers/institution.mapper';

const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Mock Implementation for isolated UI demo development
 */
class MockInstitutionService {
  static async getInstitutions(params?: { search?: string; type?: string; status?: string }): Promise<Institution[]> {
    await delay(120);
    let result = [...MOCK_INSTITUTIONS];
    if (params?.search) {
      const q = params.search.toLowerCase();
      result = result.filter(
        (i) => i.name.toLowerCase().includes(q) || i.code.toLowerCase().includes(q) || i.shortName.toLowerCase().includes(q)
      );
    }
    if (params?.type && params.type !== 'ALL') {
      result = result.filter((i) => i.institutionTypeCode === params.type);
    }
    if (params?.status && params.status !== 'ALL') {
      result = result.filter((i) => i.status === params.status);
    }
    return result;
  }

  static async getInstitutionById(id: string): Promise<Institution | null> {
    await delay(100);
    return MOCK_INSTITUTIONS.find((i) => i.id === id || id === 'inst-kemenko-pangan') || MOCK_INSTITUTIONS[0] || null;
  }
}

/**
 * API Implementation connected to CodeIgniter 4 backend
 */
class ApiInstitutionService {
  static async getInstitutions(params?: { search?: string; type?: string; status?: string }): Promise<Institution[]> {
    const dtos = await httpClient.get<InstitutionDto[]>('institutions', {
      params: {
        search: params?.search,
        status: params?.status !== 'ALL' ? params?.status : undefined,
      },
    });
    return mapInstitutionsDtoToDomain(dtos || []);
  }

  static async getInstitutionById(id: string): Promise<Institution | null> {
    const cleanId = id.replace(/[^0-9]/g, '') || '1';
    const dto = await httpClient.get<InstitutionDto>(`institutions/${cleanId}`);
    return dto ? mapInstitutionDtoToDomain(dto) : null;
  }
}

/**
 * Unified Facade dispatching based on environment mode
 */
export class InstitutionService {
  static async getInstitutions(params?: { search?: string; type?: string; status?: string }): Promise<Institution[]> {
    if (envConfig.isApiMode) {
      return ApiInstitutionService.getInstitutions(params);
    }
    return MockInstitutionService.getInstitutions(params);
  }

  static async getInstitutionById(id: string): Promise<Institution | null> {
    if (envConfig.isApiMode) {
      return ApiInstitutionService.getInstitutionById(id);
    }
    return MockInstitutionService.getInstitutionById(id);
  }
}
