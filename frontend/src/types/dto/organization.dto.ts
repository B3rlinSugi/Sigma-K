/**
 * Organization Unit & Position DTOs matching CodeIgniter 4 / MySQL backend responses
 */

export interface OrgUnitTreeNodeDto {
  id: number;
  parentId: number | null;
  unitCode: string;
  unitName: string;
  unitLevel: number;
  orderIndex: number;
  status: string;
  children?: OrgUnitTreeNodeDto[];
}

export interface InstitutionHierarchyResponseDto {
  institutionId: number;
  institutionCode: string;
  institutionName: string;
  totalUnits: number;
  tree: OrgUnitTreeNodeDto[];
}

// Fallback legacy shape
export interface OrgUnitTreeDto {
  id: number;
  institution_id?: number;
  institutionId?: number;
  parent_id?: number | null;
  parentId?: number | null;
  unit_code?: string;
  unitCode?: string;
  unit_name?: string;
  unitName?: string;
  unit_type?: string;
  unitLevel?: number;
  hierarchy_level?: number;
  orderIndex?: number;
  sort_order?: number;
  status: string;
  created_at?: string;
  updated_at?: string;
  children?: (OrgUnitTreeDto | OrgUnitTreeNodeDto)[];
}

export interface PositionDto {
  id: number;
  unit_id?: number;
  unitId?: number;
  unit_code?: string;
  unitCode?: string;
  unit_name?: string;
  unitName?: string;
  unitLevel?: number;
  institution_id?: number;
  institutionId?: number;
  institution_code?: string;
  institutionCode?: string;
  institution_name?: string;
  institutionName?: string;
  position_name?: string;
  positionName?: string;
  position_type?: 'STRUKTURAL' | 'FUNGSIONAL' | 'PELAKSANA' | string;
  positionType?: 'STRUKTURAL' | 'FUNGSIONAL' | 'PELAKSANA' | string;
  echelon?: string | null;
  echelon_level?: string | null;
  formation_count?: number;
  formationCount?: number;
  status: string;
  created_at?: string;
  createdAt?: string;
  updated_at?: string;
  updatedAt?: string;
}

export interface UnitDetailResponseDto {
  id: number;
  institutionId: number;
  institutionCode: string;
  institutionName: string;
  parentUnitId: number | null;
  parentUnitName?: string | null;
  parentUnitCode?: string | null;
  unitCode: string;
  unitName: string;
  unitLevel: number;
  orderIndex: number;
  status: string;
  createdAt: string;
  updatedAt: string;
  children: OrgUnitTreeNodeDto[] | any[];
  positions: PositionDto[];
}

export interface UnitPositionsResponseDto {
  unitId: number;
  unitCode: string;
  unitName: string;
  institutionId: number;
  institutionCode: string;
  institutionName: string;
  totalPositions: number;
  positions: PositionDto[];
}
