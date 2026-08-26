import { Injectable, UnauthorizedException } from '@nestjs/common';
import { PassportStrategy } from '@nestjs/passport';
import { ExtractJwt, Strategy } from 'passport-jwt';
import { JwtPayload, AuthenticatedUser } from '../../../common/interfaces/auth-payload.interface';

@Injectable()
export class JwtStrategy extends PassportStrategy(Strategy) {
  constructor() {
    super({
      jwtFromRequest: ExtractJwt.fromAuthHeaderAsBearerToken(),
      ignoreExpiration: false,
      secretOrKey: process.env.JWT_SECRET || 'sigmak-provisional-secret-key-2026',
    });
  }

  async validate(payload: JwtPayload): Promise<AuthenticatedUser> {
    if (!payload || !payload.sub) {
      throw new UnauthorizedException('Token tidak valid.');
    }

    return {
      id: payload.sub,
      username: payload.username,
      email: payload.email,
      fullName: payload.fullName,
      role: payload.role,
      institutionId: payload.institutionId,
      permissions: payload.permissions || [],
    };
  }
}
