import { IsNotEmpty, IsString, IsOptional, IsInt, IsBoolean } from 'class-validator';

export class CreateOrganizationUnitDto {
  @IsString()
  @IsNotEmpty({ message: 'ID Instansi wajib diisi.' })
  institutionId: string;

  @IsString()
  @IsOptional()
  parentId?: string;

  @IsString()
  @IsNotEmpty({ message: 'Kode unit organisasi wajib diisi.' })
  unitCode: string;

  @IsString()
  @IsNotEmpty({ message: 'Nama unit organisasi wajib diisi.' })
  unitName: string;

  @IsInt()
  @IsOptional()
  echelonId?: number;

  @IsInt()
  @IsOptional()
  hierarchyLevel?: number;

  @IsString()
  @IsOptional()
  leaderTitle?: string;

  @IsString()
  @IsOptional()
  leaderName?: string;

  @IsInt()
  @IsOptional()
  staffCount?: number;
}

export class UpdateOrganizationUnitDto {
  @IsString()
  @IsOptional()
  parentId?: string;

  @IsString()
  @IsOptional()
  unitName?: string;

  @IsString()
  @IsOptional()
  unitCode?: string;

  @IsInt()
  @IsOptional()
  echelonId?: number;

  @IsString()
  @IsOptional()
  leaderTitle?: string;

  @IsString()
  @IsOptional()
  leaderName?: string;

  @IsInt()
  @IsOptional()
  staffCount?: number;

  @IsBoolean()
  @IsOptional()
  isActive?: boolean;
}
