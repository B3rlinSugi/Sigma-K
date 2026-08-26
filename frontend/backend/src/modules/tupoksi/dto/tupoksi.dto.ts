import { IsNotEmpty, IsString, IsOptional, IsInt, IsEnum } from 'class-validator';

export class CreateTupoksiDto {
  @IsString()
  @IsNotEmpty({ message: 'ID Instansi wajib diisi.' })
  institutionId: string;

  @IsString()
  @IsOptional()
  organizationUnitId?: string;

  @IsEnum(['DUTY', 'FUNCTION'])
  type: 'DUTY' | 'FUNCTION';

  @IsString()
  @IsNotEmpty({ message: 'Teks butir tugas/fungsi wajib diisi.' })
  contentText: string;

  @IsString()
  @IsOptional()
  legalArticleReference?: string;

  @IsInt()
  @IsOptional()
  sequenceNumber?: number;
}
