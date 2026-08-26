export interface EchelonLevel {
  id: number;
  code: string;
  name: string;
  rankOrder: number;
}

export interface Position {
  id: string;
  unitId: string;
  unitCode?: string;
  unitName?: string;
  unitLevel?: number;
  institutionId?: string;
  institutionCode?: string;
  institutionName?: string;
  positionName: string;
  positionType: 'STRUKTURAL' | 'FUNGSIONAL' | 'PELAKSANA' | string;
  echelon?: string | null;
  formationCount: number;
  status: 'ACTIVE' | 'INACTIVE' | string;
  createdAt?: string;
  updatedAt?: string;
}

export interface OrganizationUnit {
  id: string;
  institutionId: string;
  institutionCode?: string;
  institutionName?: string;
  parentId?: string;
  parentUnitName?: string;
  parentUnitCode?: string;
  unitCode: string;
  unitName: string;
  echelonLevelId?: number;
  echelonName?: string;
  hierarchyLevel: number; // 1 = Pimpinan, 2 = Eselon I, 3 = Eselon II, etc.
  sortOrder: number;
  isActive: boolean;
  leaderTitle?: string;
  leaderName?: string;
  staffCount?: number;
  tupoksiCount?: number;
  createdAt?: string;
  updatedAt?: string;
  children?: OrganizationUnit[];
  positions?: Position[];
}

export type OrganizationUnitDetail = OrganizationUnit;

export interface TupoksiItem {
  id: string;
  institutionId: string;
  organizationUnitId?: string;
  organizationUnitName?: string;
  type: 'DUTY' | 'FUNCTION'; // DUTY = Tugas Pokok, FUNCTION = Rincian Fungsi
  contentText: string;
  legalArticleReference?: string;
  sequenceNumber: number;
  version: string;
  updatedAt: string;
}

// Alias for domain consistency
export type Tupoksi = TupoksiItem;
