import { InstitutionScopeGuard } from '../src/common/guards/institution-scope.guard';
import { ExecutionContext, ForbiddenException } from '@nestjs/common';

describe('InstitutionScopeGuard (BOLA & Tenant Scope Defense)', () => {
  let guard: InstitutionScopeGuard;

  beforeEach(() => {
    guard = new InstitutionScopeGuard();
  });

  const createMockContext = (user: any, params = {}, query = {}, body = {}): ExecutionContext => {
    return {
      switchToHttp: () => ({
        getRequest: () => ({
          user,
          params,
          query,
          body,
        }),
      }),
    } as unknown as ExecutionContext;
  };

  it('should allow USER to access resources within their own institution scope', () => {
    const user = { role: 'USER', institutionId: 'inst-kemenko-pangan' };
    const context = createMockContext(user, { institutionId: 'inst-kemenko-pangan' });

    expect(guard.canActivate(context)).toBe(true);
  });

  it('should BLOCK USER from accessing/mutating another institution resources (Anti-BOLA/IDOR)', () => {
    const user = { role: 'USER', institutionId: 'inst-kemenko-pangan' };
    const context = createMockContext(user, { institutionId: 'inst-kemendikdasmen' });

    expect(() => guard.canActivate(context)).toThrow(ForbiddenException);
  });

  it('should ALLOW ADMIN, VERIFIKATOR, and SESDEP global scope across all institutions', () => {
    const adminUser = { role: 'ADMIN', institutionId: 'inst-kemenpanrb' };
    const verifierUser = { role: 'VERIFIKATOR', institutionId: 'inst-kemenpanrb' };
    const sesdepUser = { role: 'SESDEP', institutionId: 'inst-kemenpanrb' };

    const contextA = createMockContext(adminUser, { institutionId: 'inst-kemenko-pangan' });
    const contextV = createMockContext(verifierUser, { institutionId: 'inst-kemendikdasmen' });
    const contextS = createMockContext(sesdepUser, { institutionId: 'inst-pemprov-dki' });

    expect(guard.canActivate(contextA)).toBe(true);
    expect(guard.canActivate(contextV)).toBe(true);
    expect(guard.canActivate(contextS)).toBe(true);
  });
});
