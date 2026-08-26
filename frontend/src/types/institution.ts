export type InstitutionTypeCode = 
  | 'KEMENKO'
  | 'KEMENTERIAN_TEKNIS'
  | 'LPNK'
  | 'LNS'
  | 'PEMDA_PROVINSI'
  | 'PEMDA_KABUPATEN'
  | 'PEMDA_KOTA';

export interface InstitutionType {
  id: number;
  code: InstitutionTypeCode;
  name: string;
}

export interface Region {
  id: number;
  code: string;
  name: string;
  level: 'PROVINSI' | 'KABUPATEN' | 'KOTA';
}

export interface InstitutionProfile {
  id: string;
  institutionId: string;
  address: string;
  phone: string;
  email: string;
  websiteUrl: string;
  logoPath: string;
  visionStatement?: string;
  missionStatement?: string;
  legalBasisSummary: string;
  legalDocNumber?: string;
  legalDocDate?: string;
  legalDocPdfUrl?: string;
}

export interface Institution {
  id: string;
  code: string;
  name: string;
  shortName: string;
  institutionTypeId: number;
  institutionTypeCode: InstitutionTypeCode;
  institutionTypeName: string;
  regionId?: number;
  regionName?: string;
  status: 'ACTIVE' | 'INACTIVE' | 'DISSOLVED';
  currentCabinetName?: string;
  createdAt: string;
  updatedAt: string;
  profile?: InstitutionProfile;
  totalOrgUnits?: number;
  totalPositions?: number;
}
