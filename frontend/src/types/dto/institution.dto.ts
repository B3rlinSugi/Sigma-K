/**
 * Institution DTOs matching CodeIgniter 4 / MySQL backend responses
 */

export interface InstitutionDto {
  id: number;
  institution_code: string;
  name: string;
  short_name: string | null;
  category: string;
  status: 'ACTIVE' | 'INACTIVE' | 'DISSOLVED' | string;
  created_at: string;
  updated_at: string;
}

export interface InstitutionListMetaDto {
  total: number;
  page: number;
  perPage: number;
  totalPages: number;
}
