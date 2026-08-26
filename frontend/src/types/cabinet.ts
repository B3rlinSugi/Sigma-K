export interface Cabinet {
  id: string;
  name: string;
  presidentName: string;
  vicePresidentName: string;
  description: string;
  isActive: boolean;
  periodsCount?: number;
  totalMembers?: number;
  createdAt: string;
}

export interface CabinetPeriod {
  id: string;
  cabinetId: string;
  cabinetName: string;
  startDate: string;
  endDate?: string;
  legalDecreeNumber: string;
  status: 'ACTIVE' | 'CONCLUDED';
  totalMinistries: number;
}

export interface CabinetMembership {
  id: string;
  cabinetPeriodId: string;
  institutionId: string;
  institutionCode: string;
  institutionName: string;
  institutionShortName: string;
  category: 'KEMENKO' | 'TEKNIS' | 'LPNK' | 'LNS';
  joinedDate: string;
  endedDate?: string;
  isActiveInCabinet: boolean;
}

export type LineageTransitionType = 
  | 'SPLIT' 
  | 'MERGE' 
  | 'MERGED'
  | 'RENAME' 
  | 'RENAMED'
  | 'NEW' 
  | 'ADDED'
  | 'REMOVED'
  | 'DISSOLVED' 
  | 'UNCHANGED';

export interface InstitutionLineage {
  id: string;
  predecessorInstitutionId?: string;
  predecessorName?: string;
  successorInstitutionId: string;
  successorName: string;
  cabinetPeriodId: string;
  transitionType: LineageTransitionType;
  notes: string;
  effectiveDate: string;
  legalDecreeRef?: string;
}

export interface CabinetComparisonItem {
  institutionId: string;
  code: string;
  name: string;
  shortName: string;
  category: string;
  statusA: 'PRESENT' | 'ABSENT';
  statusB: 'PRESENT' | 'ABSENT';
  transitionType: LineageTransitionType;
  details: string;
  predecessors?: string[];
  successors?: string[];
}

export interface CabinetComparisonSummary {
  baseCabinet: { id: string; name: string; period: string };
  targetCabinet: { id: string; name: string; period: string };
  totalBase: number;
  totalTarget: number;
  addedCount: number;
  removedCount: number;
  splitCount: number;
  mergedCount: number;
  renamedCount: number;
  unchangedCount: number;
  items: CabinetComparisonItem[];
}
