import { AuthService } from '../src/modules/auth/auth.service';
import { PrismaService } from '../src/infrastructure/database/prisma.service';
import { JwtService } from '@nestjs/jwt';
import { UnauthorizedException } from '@nestjs/common';

describe('AuthService (Provisional JWT Authentication)', () => {
  let service: AuthService;
  let jwtService: JwtService;

  beforeEach(() => {
    jwtService = new JwtService({
      secret: 'sigmak-test-secret',
    });

    service = new AuthService(
      {} as PrismaService,
      jwtService,
    );
  });

  it('should authenticate demo user and return signed access token', async () => {
    const loginResult = await service.login({
      username: 'operator_pangan',
      password: 'ANY_DEV_PASSWORD',
    });

    expect(loginResult).toBeDefined();
    expect(loginResult.accessToken).toBeDefined();
    expect(loginResult.tokenType).toBe('Bearer');
    expect(loginResult.user.role).toBe('USER');
    expect(loginResult.user.institutionId).toBe('inst-kemenko-pangan');

    // Decode token
    const decoded: any = jwtService.decode(loginResult.accessToken);
    expect(decoded.role).toBe('USER');
    expect(decoded.username).toBe('operator_pangan');
  });

  it('should issue tokens for ADMIN and VERIFIKATOR personas', async () => {
    const adminLogin = await service.login({
      username: 'admin',
      password: 'password',
    });
    expect(adminLogin.user.role).toBe('ADMIN');

    const verifLogin = await service.login({
      username: 'verifikator',
      password: 'password',
    });
    expect(verifLogin.user.role).toBe('VERIFIKATOR');
  });

  it('should reject invalid credentials', async () => {
    await expect(
      service.login({
        username: 'unknown_unregistered_user',
        password: 'password',
      }),
    ).rejects.toThrow(UnauthorizedException);
  });
});
