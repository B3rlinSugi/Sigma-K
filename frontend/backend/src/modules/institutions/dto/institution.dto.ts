import { IsNotEmpty, IsString, IsOptional, IsInt } from 'class-validator';

export class CreateInstitutionDto {
  @IsString()
  @IsNotEmpty({ message: 'Kode instansi wajib diisi.' })
  code: string;

  @IsString()
  @IsNotEmpty({ message: 'Nama instansi wajib diisi.' })
  name: string;

  @IsString()
  @IsNotEmpty({ message: 'Nama singkatan instansi wajib diisi.' })
  shortName: string;

  @IsInt()
  typeId: number;

  @IsString()
  @IsOptional()
  regionId?: string;

  @IsString()
  @IsOptional()
  status?: string;

  @IsString()
  @IsOptional()
  legalBasisNumber?: string;

  @IsString()
  @IsOptional()
  phone?: string;

  @IsString()
  @IsOptional()
  email?: string;

  @IsString()
  @IsOptional()
  website?: string;
}

export class UpdateInstitutionDto {
  @IsString()
  @IsOptional()
  name?: string;

  @IsString()
  @IsOptional()
  shortName?: string;

  @IsString()
  @IsOptional()
  status?: string;

  @IsString()
  @IsOptional()
  phone?: string;

  @IsString()
  @IsOptional()
  email?: string;

  @IsString()
  @IsOptional()
  website?: string;

  @IsString()
  @IsOptional()
  address?: string;
}
