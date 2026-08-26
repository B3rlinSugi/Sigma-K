import { IsNotEmpty, IsString, MinLength } from 'class-validator';

export class LoginDto {
  @IsString()
  @IsNotEmpty({ message: 'Username atau NIP wajib diisi.' })
  username: string;

  @IsString()
  @MinLength(6, { message: 'Kata sandi minimal 6 karakter.' })
  password: string;
}

export class RefreshTokenDto {
  @IsString()
  @IsNotEmpty({ message: 'Refresh token wajib diisi.' })
  refreshToken: string;
}
