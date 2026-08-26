import { IsNotEmpty, IsString, IsOptional, IsBoolean } from 'class-validator';

export class CreateCabinetDto {
  @IsString()
  @IsNotEmpty({ message: 'Nama kabinet wajib diisi.' })
  name: string;

  @IsString()
  @IsNotEmpty({ message: 'Nama presiden wajib diisi.' })
  presidentName: string;

  @IsString()
  @IsNotEmpty({ message: 'Nama wakil presiden wajib diisi.' })
  vicePresidentName: string;

  @IsString()
  @IsOptional()
  description?: string;

  @IsBoolean()
  @IsOptional()
  isActive?: boolean;
}

export class AddCabinetMembershipDto {
  @IsString()
  @IsNotEmpty({ message: 'ID instansi wajib diisi.' })
  institutionId: string;

  @IsString()
  @IsNotEmpty({ message: 'Kategori keanggotaan wajib diisi (KEMENKO, TEKNIS, LPNK, LNS).' })
  category: string;

  @IsString()
  @IsNotEmpty({ message: 'Tanggal bergabung wajib diisi.' })
  joinedDate: string;
}
