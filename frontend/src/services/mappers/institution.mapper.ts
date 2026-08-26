import { Institution, InstitutionTypeCode } from '@/types/institution';
import { InstitutionDto } from '@/types/dto/institution.dto';

/**
 * Maps Backend InstitutionDto (snake_case, numeric ID) to Frontend Domain Institution (camelCase, string ID)
 */
export function mapInstitutionDtoToDomain(dto: InstitutionDto): Institution {
  // Normalize category to InstitutionTypeCode
  let typeCode: InstitutionTypeCode = 'KEMENTERIAN_TEKNIS';
  if (dto.category === 'KEMENKO') typeCode = 'KEMENKO';
  else if (dto.category === 'LPNK') typeCode = 'LPNK';
  else if (dto.category === 'LNS') typeCode = 'LNS';
  else if (dto.category === 'PEMDA_PROVINSI') typeCode = 'PEMDA_PROVINSI';
  else if (dto.category === 'PEMDA_KABUPATEN') typeCode = 'PEMDA_KABUPATEN';
  else if (dto.category === 'PEMDA_KOTA') typeCode = 'PEMDA_KOTA';

  return {
    id: String(dto.id),
    code: dto.institution_code,
    name: dto.name,
    shortName: dto.short_name || dto.name,
    institutionTypeId: 1,
    institutionTypeCode: typeCode,
    institutionTypeName: dto.category,
    status: (dto.status as 'ACTIVE' | 'INACTIVE' | 'DISSOLVED') || 'ACTIVE',
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  };
}

export function mapInstitutionsDtoToDomain(dtos: InstitutionDto[]): Institution[] {
  return dtos.map(mapInstitutionDtoToDomain);
}
