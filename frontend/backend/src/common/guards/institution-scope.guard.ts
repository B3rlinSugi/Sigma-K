import { Injectable, CanActivate, ExecutionContext, ForbiddenException } from '@nestjs/common';

@Injectable()
export class InstitutionScopeGuard implements CanActivate {
  canActivate(context: ExecutionContext): boolean {
    const request = context.switchToHttp().getRequest();
    const user = request.user;

    if (!user) return true;

    // Admin, Verifikator, and SESDEP have global read/review scope
    if (user.role === 'ADMIN' || user.role === 'VERIFIKATOR' || user.role === 'SESDEP') {
      return true;
    }

    // For USER (Operator), enforce institution scope (Anti-BOLA/IDOR)
    const targetInstitutionId =
      request.params?.institutionId ||
      request.query?.institutionId ||
      request.body?.institutionId;

    if (targetInstitutionId && user.institutionId) {
      if (targetInstitutionId !== user.institutionId) {
        throw new ForbiddenException(
          'Pelanggaran Scope: Anda hanya memiliki hak untuk mengakses/mengubah data instansi Anda sendiri.'
        );
      }
    }

    return true;
  }
}
