import { createParamDecorator, ExecutionContext } from '@nestjs/common';
import { SetMetadata } from '@nestjs/common';
import { UserRole } from '../interfaces/auth-payload.interface';
import { ROLES_KEY } from '../guards/roles.guard';

export const CurrentUser = createParamDecorator(
  (data: unknown, ctx: ExecutionContext) => {
    const request = ctx.switchToHttp().getRequest();
    return request.user;
  },
);

export const Roles = (...roles: UserRole[]) => SetMetadata(ROLES_KEY, roles);
