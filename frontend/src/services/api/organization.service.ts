import { OrganizationUnit, OrganizationUnitDetail, Position, TupoksiItem } from '@/types/organization';
import { MOCK_ORG_UNITS } from '@/data/mock/organizations';
import { MOCK_TUPOKSI } from '@/data/mock/tupoksi';
import { envConfig } from '@/config/env.config';
import { httpClient } from '@/services/http/client';
import { 
  InstitutionHierarchyResponseDto, 
  UnitDetailResponseDto, 
  UnitPositionsResponseDto, 
  PositionDto 
} from '@/types/dto/organization.dto';
import { 
  flattenOrgUnitTree, 
  mapUnitDetailDtoToDomain, 
  mapPositionsDtoToDomain, 
  mapPositionDtoToDomain 
} from '@/services/mappers/organization.mapper';

const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

const MOCK_POSITIONS: Position[] = [
  {
    id: 'pos-1',
    unitId: 'unit-1',
    unitCode: 'MENKO',
    unitName: 'Menteri Koordinator',
    positionName: 'Menteri Koordinator Bidang Pangan',
    positionType: 'STRUKTURAL',
    echelon: 'Menteri',
    formationCount: 1,
    status: 'ACTIVE',
  },
  {
    id: 'pos-2',
    unitId: 'unit-2',
    unitCode: 'SESMENKO',
    unitName: 'Sekretariat Kementerian Koordinator',
    positionName: 'Sekretaris Kementerian Koordinator',
    positionType: 'STRUKTURAL',
    echelon: 'I.a',
    formationCount: 1,
    status: 'ACTIVE',
  },
  {
    id: 'pos-3',
    unitId: 'unit-2',
    unitCode: 'SESMENKO',
    unitName: 'Sekretariat Kementerian Koordinator',
    positionName: 'Analis Kebijakan Ahli Utama',
    positionType: 'FUNGSIONAL',
    formationCount: 3,
    status: 'ACTIVE',
  },
];

/**
 * Mock Implementation for isolated UI demo
 */
class MockOrganizationService {
  static async getOrgUnitsByInstitutionId(institutionId: string): Promise<OrganizationUnit[]> {
    await delay(150);
    return MOCK_ORG_UNITS.filter((u) => u.institutionId === institutionId || institutionId === 'inst-kemenko-pangan');
  }

  static async getAllOrgUnits(): Promise<OrganizationUnit[]> {
    await delay(120);
    return [...MOCK_ORG_UNITS];
  }

  static async getUnitDetail(unitId: string): Promise<OrganizationUnitDetail | null> {
    await delay(100);
    const unit = MOCK_ORG_UNITS.find((u) => u.id === unitId) || MOCK_ORG_UNITS[0];
    if (!unit) return null;
    const positions = MOCK_POSITIONS.filter((p) => p.unitId === unitId);
    return {
      ...unit,
      positions,
    };
  }

  static async getPositionsByUnitId(unitId: string): Promise<Position[]> {
    await delay(100);
    return MOCK_POSITIONS.filter((p) => p.unitId === unitId || p.unitId === 'unit-1');
  }

  static async getPositionById(positionId: string): Promise<Position | null> {
    await delay(80);
    return MOCK_POSITIONS.find((p) => p.id === positionId) || null;
  }

  static async getTupoksiByInstitutionId(institutionId: string): Promise<TupoksiItem[]> {
    await delay(120);
    return MOCK_TUPOKSI.filter((t) => t.institutionId === institutionId || institutionId === 'inst-kemenko-pangan');
  }

  static async getAllTupoksi(): Promise<TupoksiItem[]> {
    await delay(100);
    return [...MOCK_TUPOKSI];
  }
}

/**
 * API Implementation connected to CodeIgniter 4 backend
 */
class ApiOrganizationService {
  static async getOrgUnitsByInstitutionId(institutionId: string): Promise<OrganizationUnit[]> {
    const cleanId = institutionId.replace(/[^0-9]/g, '') || '1';
    const hierarchyDto = await httpClient.get<InstitutionHierarchyResponseDto>(`institutions/${cleanId}/units`);
    return flattenOrgUnitTree(hierarchyDto);
  }

  static async getUnitDetail(unitId: string): Promise<OrganizationUnitDetail | null> {
    const cleanId = unitId.replace(/[^0-9]/g, '') || '1';
    const dto = await httpClient.get<UnitDetailResponseDto>(`units/${cleanId}`);
    return dto ? mapUnitDetailDtoToDomain(dto) : null;
  }

  static async getPositionsByUnitId(unitId: string): Promise<Position[]> {
    const cleanId = unitId.replace(/[^0-9]/g, '') || '1';
    const dto = await httpClient.get<UnitPositionsResponseDto>(`units/${cleanId}/positions`);
    return dto && dto.positions ? mapPositionsDtoToDomain(dto.positions) : [];
  }

  static async getPositionById(positionId: string): Promise<Position | null> {
    const cleanId = positionId.replace(/[^0-9]/g, '') || '1';
    const dto = await httpClient.get<PositionDto>(`positions/${cleanId}`);
    return dto ? mapPositionDtoToDomain(dto) : null;
  }

  static async getAllOrgUnits(): Promise<OrganizationUnit[]> {
    return MockOrganizationService.getAllOrgUnits();
  }

  static async getTupoksiByInstitutionId(institutionId: string): Promise<TupoksiItem[]> {
    return MockOrganizationService.getTupoksiByInstitutionId(institutionId);
  }

  static async getAllTupoksi(): Promise<TupoksiItem[]> {
    return MockOrganizationService.getAllTupoksi();
  }
}

/**
 * Unified Facade dispatching based on environment mode
 */
export class OrganizationService {
  static async getOrgUnitsByInstitutionId(institutionId: string): Promise<OrganizationUnit[]> {
    if (envConfig.isApiMode) {
      return ApiOrganizationService.getOrgUnitsByInstitutionId(institutionId);
    }
    return MockOrganizationService.getOrgUnitsByInstitutionId(institutionId);
  }

  static async getUnitDetail(unitId: string): Promise<OrganizationUnitDetail | null> {
    if (envConfig.isApiMode) {
      return ApiOrganizationService.getUnitDetail(unitId);
    }
    return MockOrganizationService.getUnitDetail(unitId);
  }

  static async getPositionsByUnitId(unitId: string): Promise<Position[]> {
    if (envConfig.isApiMode) {
      return ApiOrganizationService.getPositionsByUnitId(unitId);
    }
    return MockOrganizationService.getPositionsByUnitId(unitId);
  }

  static async getPositionById(positionId: string): Promise<Position | null> {
    if (envConfig.isApiMode) {
      return ApiOrganizationService.getPositionById(positionId);
    }
    return MockOrganizationService.getPositionById(positionId);
  }

  static async getAllOrgUnits(): Promise<OrganizationUnit[]> {
    return MockOrganizationService.getAllOrgUnits();
  }

  static async getTupoksiByInstitutionId(institutionId: string): Promise<TupoksiItem[]> {
    return MockOrganizationService.getTupoksiByInstitutionId(institutionId);
  }

  static async getAllTupoksi(): Promise<TupoksiItem[]> {
    return MockOrganizationService.getAllTupoksi();
  }
}
