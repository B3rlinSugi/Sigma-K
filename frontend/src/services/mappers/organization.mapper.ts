import { OrganizationUnit, OrganizationUnitDetail, Position } from '@/types/organization';
import { 
  OrgUnitTreeNodeDto, 
  OrgUnitTreeDto, 
  InstitutionHierarchyResponseDto, 
  PositionDto, 
  UnitDetailResponseDto 
} from '@/types/dto/organization.dto';

/**
 * Maps Backend PositionDto to Frontend Position Domain Model
 */
export function mapPositionDtoToDomain(dto: PositionDto): Position {
  const d = dto as any;
  return {
    id: String(dto.id),
    unitId: String(d.unitId || d.unit_id || 0),
    unitCode: d.unitCode || d.unit_code,
    unitName: d.unitName || d.unit_name,
    unitLevel: d.unitLevel,
    institutionId: d.institutionId || d.institution_id ? String(d.institutionId || d.institution_id) : undefined,
    institutionCode: d.institutionCode || d.institution_code,
    institutionName: d.institutionName || d.institution_name,
    positionName: d.positionName || d.position_name || 'Jabatan',
    positionType: (d.positionType || d.position_type || 'STRUKTURAL') as 'STRUKTURAL' | 'FUNGSIONAL' | 'PELAKSANA',
    echelon: d.echelon || d.echelon_level || null,
    formationCount: d.formationCount ?? d.formation_count ?? 1,
    status: d.status || 'ACTIVE',
    createdAt: d.createdAt || d.created_at,
    updatedAt: d.updatedAt || d.updated_at,
  };
}

export function mapPositionsDtoToDomain(dtos: PositionDto[]): Position[] {
  return (dtos || []).map(mapPositionDtoToDomain);
}

/**
 * Maps Backend OrgUnitTreeNodeDto or OrgUnitTreeDto to Frontend OrganizationUnit Domain Model
 */
export function mapOrgUnitDtoToDomain(
  dto: OrgUnitTreeNodeDto | OrgUnitTreeDto, 
  institutionId?: string | number,
  institutionName?: string
): OrganizationUnit {
  const d = dto as any;
  const rawParentId = d.parentId !== undefined ? d.parentId : d.parent_id;
  const rawUnitCode: string = d.unitCode || d.unit_code || '';
  const rawUnitName: string = d.unitName || d.unit_name || '';
  const rawLevel: number = d.unitLevel !== undefined ? d.unitLevel : (d.hierarchy_level || 1);
  const rawOrder: number = d.orderIndex !== undefined ? d.orderIndex : (d.sort_order || 0);
  const rawInstId = d.institutionId !== undefined ? d.institutionId : (d.institution_id !== undefined ? d.institution_id : institutionId);

  return {
    id: String(dto.id),
    institutionId: rawInstId ? String(rawInstId) : '1',
    institutionName: institutionName,
    parentId: rawParentId !== null && rawParentId !== undefined ? String(rawParentId) : undefined,
    unitCode: rawUnitCode,
    unitName: rawUnitName,
    hierarchyLevel: rawLevel,
    sortOrder: rawOrder,
    isActive: dto.status === 'ACTIVE',
    children: dto.children ? dto.children.map((c) => mapOrgUnitDtoToDomain(c, rawInstId, institutionName)) : undefined,
  };
}

/**
 * Maps UnitDetailResponseDto to OrganizationUnitDetail Domain Model
 */
export function mapUnitDetailDtoToDomain(dto: UnitDetailResponseDto): OrganizationUnitDetail {
  return {
    id: String(dto.id),
    institutionId: String(dto.institutionId),
    institutionCode: dto.institutionCode,
    institutionName: dto.institutionName,
    parentId: dto.parentUnitId ? String(dto.parentUnitId) : undefined,
    parentUnitName: dto.parentUnitName || undefined,
    parentUnitCode: dto.parentUnitCode || undefined,
    unitCode: dto.unitCode,
    unitName: dto.unitName,
    hierarchyLevel: dto.unitLevel,
    sortOrder: dto.orderIndex,
    isActive: dto.status === 'ACTIVE',
    createdAt: dto.createdAt,
    updatedAt: dto.updatedAt,
    children: (dto.children || []).map((c) => mapOrgUnitDtoToDomain(c, dto.institutionId, dto.institutionName)),
    positions: mapPositionsDtoToDomain(dto.positions || []),
  };
}

/**
 * Flattens recursive backend tree to flat array of OrganizationUnits for React Flow canvas
 */
export function flattenOrgUnitTree(
  treeData: InstitutionHierarchyResponseDto | OrgUnitTreeNodeDto[] | OrgUnitTreeDto[] | null | undefined
): OrganizationUnit[] {
  if (!treeData) return [];

  let nodesToTraverse: (OrgUnitTreeNodeDto | OrgUnitTreeDto)[] = [];
  let instId: number | undefined = undefined;
  let instName: string | undefined = undefined;

  if (typeof treeData === 'object' && !Array.isArray(treeData) && 'tree' in treeData) {
    nodesToTraverse = (treeData as InstitutionHierarchyResponseDto).tree || [];
    instId = (treeData as InstitutionHierarchyResponseDto).institutionId;
    instName = (treeData as InstitutionHierarchyResponseDto).institutionName;
  } else if (Array.isArray(treeData)) {
    nodesToTraverse = treeData;
  }

  const result: OrganizationUnit[] = [];

  function traverse(nodes: (OrgUnitTreeNodeDto | OrgUnitTreeDto)[]) {
    nodes.forEach((node) => {
      result.push(mapOrgUnitDtoToDomain(node, instId, instName));
      if (node.children && node.children.length > 0) {
        traverse(node.children);
      }
    });
  }

  traverse(nodesToTraverse);
  return result;
}
