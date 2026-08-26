/**
 * Phase 14C Master Data API Integration Test Suite
 * Tests MASTER-01 through MASTER-15.
 */

import { mapInstitutionDtoToDomain, mapInstitutionsDtoToDomain } from '../../mappers/institution.mapper';
import { 
  flattenOrgUnitTree, 
  mapOrgUnitDtoToDomain, 
  mapUnitDetailDtoToDomain, 
  mapPositionDtoToDomain, 
  mapPositionsDtoToDomain 
} from '../../mappers/organization.mapper';
import { AppError } from '../errors';
import { InstitutionDto } from '@/types/dto/institution.dto';
import { 
  InstitutionHierarchyResponseDto, 
  OrgUnitTreeNodeDto, 
  PositionDto, 
  UnitDetailResponseDto 
} from '@/types/dto/organization.dto';

export function runPhase14CMasterDataTests(): boolean {
  console.log('--- Starting Phase 14C Master Data Tests (MASTER-01 - MASTER-15) ---');
  let passed = 0;
  let total = 0;

  function assert(condition: boolean, testName: string) {
    total++;
    if (condition) {
      passed++;
      console.log(`[PASS] ${testName}`);
    } else {
      console.error(`[FAIL] ${testName}`);
      throw new Error(`Master Data Test Failed: ${testName}`);
    }
  }

  // MASTER-01: Institution list API mapping
  const instListDtos: InstitutionDto[] = [
    {
      id: 1,
      institution_code: 'KL-001',
      name: 'Kementerian Koordinator Bidang Pangan',
      short_name: 'Kemenko Pangan',
      category: 'KEMENKO',
      status: 'ACTIVE',
      created_at: '2026-08-26T00:00:00Z',
      updated_at: '2026-08-26T00:00:00Z',
    },
    {
      id: 2,
      institution_code: 'KL-002',
      name: 'Kementerian PANRB',
      short_name: 'KemenPANRB',
      category: 'KEMENTERIAN_TEKNIS',
      status: 'ACTIVE',
      created_at: '2026-08-26T00:00:00Z',
      updated_at: '2026-08-26T00:00:00Z',
    },
  ];
  const instDomainList = mapInstitutionsDtoToDomain(instListDtos);
  assert(instDomainList.length === 2, 'MASTER-01: Institution list maps all items');
  assert(instDomainList[0].code === 'KL-001' && instDomainList[0].institutionTypeCode === 'KEMENKO', 'MASTER-01: Type and code mapped');

  // MASTER-02: Institution detail API mapping
  const singleInstDto: InstitutionDto = {
    id: 42,
    institution_code: 'KL-042',
    name: 'Kementerian Pertanian',
    short_name: 'Kementan',
    category: 'KEMENTERIAN_TEKNIS',
    status: 'ACTIVE',
    created_at: '2026-08-26T10:00:00Z',
    updated_at: '2026-08-26T10:00:00Z',
  };
  const singleInstDomain = mapInstitutionDtoToDomain(singleInstDto);
  assert(singleInstDomain.id === '42', 'MASTER-02: ID converted to string');
  assert(singleInstDomain.name === 'Kementerian Pertanian', 'MASTER-02: Name mapped accurately');

  // MASTER-03: Unauthorized request (401)
  const err401 = AppError.fromApiResponse(401, {
    success: false,
    statusCode: 401,
    error: { code: 'UNAUTHORIZED', message: 'Token expired' },
  });
  assert(err401.isUnauthorized() === true, 'MASTER-03: Unauthorized error correctly identified');

  // MASTER-04: Forbidden institution access (403)
  const err403 = AppError.fromApiResponse(403, {
    success: false,
    statusCode: 403,
    error: { code: 'FORBIDDEN', message: 'You are not authorized to view this institution.' },
  });
  assert(err403.isForbidden() === true, 'MASTER-04: Forbidden error correctly identified');

  // MASTER-05: Institution not found (404)
  const err404 = AppError.fromApiResponse(404, {
    success: false,
    statusCode: 404,
    error: { code: 'NOT_FOUND', message: 'Institution not found.' },
  });
  assert(err404.isNotFound() === true, 'MASTER-05: Not found error correctly identified');

  // MASTER-06: Organizational hierarchy mapping
  const hierarchyPayload: InstitutionHierarchyResponseDto = {
    institutionId: 1,
    institutionCode: 'KL-001',
    institutionName: 'Kemenko Pangan',
    totalUnits: 2,
    tree: [
      {
        id: 10,
        parentId: null,
        unitCode: 'MENKO',
        unitName: 'Menteri Koordinator',
        unitLevel: 1,
        orderIndex: 1,
        status: 'ACTIVE',
        children: [
          {
            id: 20,
            parentId: 10,
            unitCode: 'SESMENKO',
            unitName: 'Sekretariat Kementerian Koordinator',
            unitLevel: 2,
            orderIndex: 1,
            status: 'ACTIVE',
            children: [],
          },
        ],
      },
    ],
  };
  const flatUnits = flattenOrgUnitTree(hierarchyPayload);
  assert(flatUnits.length === 2, 'MASTER-06: Hierarchy unwraps .tree and flattens correctly');
  assert(flatUnits[0].institutionName === 'Kemenko Pangan', 'MASTER-06: Injects institutionName to root node');

  // MASTER-07: Multi-level hierarchy
  assert(flatUnits[0].id === '10' && flatUnits[1].parentId === '10', 'MASTER-07: Preserves multi-level parentId pointers');
  assert(flatUnits[1].hierarchyLevel === 2, 'MASTER-07: Preserves hierarchyLevel (2)');

  // MASTER-08: Empty organizational hierarchy
  const emptyHierarchy: InstitutionHierarchyResponseDto = {
    institutionId: 99,
    institutionCode: 'KL-EMPTY',
    institutionName: 'Instansi Baru',
    totalUnits: 0,
    tree: [],
  };
  const emptyUnits = flattenOrgUnitTree(emptyHierarchy);
  assert(emptyUnits.length === 0, 'MASTER-08: Empty hierarchy returns empty array without throwing');

  // MASTER-09: Unit detail mapping
  const unitDetailDto: UnitDetailResponseDto = {
    id: 20,
    institutionId: 1,
    institutionCode: 'KL-001',
    institutionName: 'Kemenko Pangan',
    parentUnitId: 10,
    parentUnitName: 'Menteri Koordinator',
    parentUnitCode: 'MENKO',
    unitCode: 'SESMENKO',
    unitName: 'Sekretariat Kementerian Koordinator',
    unitLevel: 2,
    orderIndex: 1,
    status: 'ACTIVE',
    createdAt: '2026-08-26T00:00:00Z',
    updatedAt: '2026-08-26T00:00:00Z',
    children: [],
    positions: [
      {
        id: 101,
        unitId: 20,
        positionName: 'Sekretaris Kementerian Koordinator',
        positionType: 'STRUKTURAL',
        echelon: 'I.a',
        formationCount: 1,
        status: 'ACTIVE',
      },
    ],
  };
  const unitDetail = mapUnitDetailDtoToDomain(unitDetailDto);
  assert(unitDetail.id === '20', 'MASTER-09: Unit detail ID mapped');
  assert(Boolean(unitDetail.positions && unitDetail.positions.length === 1), 'MASTER-09: Nested positions mapped');
  assert(unitDetail.parentUnitName === 'Menteri Koordinator', 'MASTER-09: Parent unit name mapped');

  // MASTER-10: Position list mapping
  const posDtos: PositionDto[] = [
    {
      id: 101,
      unitId: 20,
      positionName: 'Sekretaris Kementerian',
      positionType: 'STRUKTURAL',
      echelon: 'I.a',
      formationCount: 1,
      status: 'ACTIVE',
    },
    {
      id: 102,
      unitId: 20,
      positionName: 'Analis Kebijakan Ahli Utama',
      positionType: 'FUNGSIONAL',
      echelon: null,
      formationCount: 4,
      status: 'ACTIVE',
    },
  ];
  const posList = mapPositionsDtoToDomain(posDtos);
  assert(posList.length === 2, 'MASTER-10: Position list mapped');
  assert(posList[1].formationCount === 4, 'MASTER-10: Formation count mapped accurately');

  // MASTER-11: Position detail mapping
  const singlePosDto: PositionDto = {
    id: 101,
    unitId: 20,
    unitCode: 'SESMENKO',
    unitName: 'Sekretariat Kementerian',
    institutionId: 1,
    institutionCode: 'KL-001',
    institutionName: 'Kemenko Pangan',
    positionName: 'Sekretaris Kementerian',
    positionType: 'STRUKTURAL',
    echelon: 'I.a',
    formationCount: 1,
    status: 'ACTIVE',
    createdAt: '2026-08-26T00:00:00Z',
  };
  const posDetail = mapPositionDtoToDomain(singlePosDto);
  assert(posDetail.id === '101', 'MASTER-11: Position ID converted to string');
  assert(posDetail.echelon === 'I.a', 'MASTER-11: Echelon level mapped');
  assert(posDetail.institutionCode === 'KL-001', 'MASTER-11: Institution relationship preserved');

  // MASTER-12: Cross-institution position access denied
  const crossInstErr = AppError.fromApiResponse(403, {
    success: false,
    statusCode: 403,
    error: { code: 'FORBIDDEN', message: 'You are not authorized to view positions for this unit.' },
  });
  assert(Boolean(crossInstErr.isForbidden()), 'MASTER-12: Cross-institution access rejection identified');

  // MASTER-13: Mock mode regression
  const fallbackRawTree = [
    {
      id: 1,
      unit_code: 'MOCK_ROOT',
      unit_name: 'Mock Root Unit',
      hierarchy_level: 1,
      status: 'ACTIVE',
    },
  ];
  const fallbackUnits = flattenOrgUnitTree(fallbackRawTree as any);
  assert(fallbackUnits.length === 1 && fallbackUnits[0].unitCode === 'MOCK_ROOT', 'MASTER-13: Mock raw tree format still parses');

  // MASTER-14: API mode regression
  assert(mapOrgUnitDtoToDomain({ id: 5, unitCode: 'U5', unitName: 'Unit 5', unitLevel: 3, orderIndex: 1, status: 'ACTIVE', parentId: null }).hierarchyLevel === 3, 'MASTER-14: API node unitLevel maps to hierarchyLevel');

  // MASTER-15: DTO -> Domain mapper consistency
  const roundtripDto: PositionDto = {
    id: 999,
    unit_id: 88,
    position_name: 'Pranata Komputer Ahli Pertama',
    position_type: 'FUNGSIONAL',
    formation_count: 2,
    status: 'ACTIVE',
  };
  const roundtripDomain = mapPositionDtoToDomain(roundtripDto);
  assert(roundtripDomain.id === '999' && roundtripDomain.unitId === '88' && roundtripDomain.formationCount === 2, 'MASTER-15: Bidirectional property alias consistency preserved');

  console.log(`--- All ${passed}/${total} Phase 14C Master Data Tests Passed ---`);
  return true;
}
