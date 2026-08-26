import { Controller, Get, Post, Param, Body, UseGuards } from '@nestjs/common';
import { VerificationsService } from './verifications.service';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { Roles } from '../../common/decorators/roles.decorator';
import { CurrentUser } from '../../common/decorators/current-user.decorator';
import { AuthenticatedUser } from '../../common/interfaces/auth-payload.interface';

@Controller('verifications')
@UseGuards(JwtAuthGuard, RolesGuard)
export class VerificationsController {
  constructor(private readonly verificationsService: VerificationsService) {}

  @Get()
  @Roles('VERIFIKATOR', 'ADMIN', 'SESDEP')
  async getQueue(@CurrentUser() user: AuthenticatedUser) {
    return this.verificationsService.getQueue(user);
  }

  @Get(':id')
  @Roles('VERIFIKATOR', 'ADMIN')
  async getWorkspace(
    @Param('id') id: string,
    @CurrentUser() user: AuthenticatedUser,
  ) {
    return this.verificationsService.getWorkspace(id, user);
  }

  @Post(':id/verify')
  @Roles('VERIFIKATOR', 'ADMIN')
  async verify(
    @Param('id') id: string,
    @Body('notes') notes: string,
    @CurrentUser() user: AuthenticatedUser,
  ) {
    return this.verificationsService.verify(id, notes, user);
  }

  @Post(':id/request-revision')
  @Roles('VERIFIKATOR', 'ADMIN')
  async requestRevision(
    @Param('id') id: string,
    @Body('notes') notes: string,
    @CurrentUser() user: AuthenticatedUser,
  ) {
    return this.verificationsService.requestRevision(id, notes, user);
  }

  @Post(':id/reject')
  @Roles('VERIFIKATOR', 'ADMIN')
  async reject(
    @Param('id') id: string,
    @Body('notes') notes: string,
    @CurrentUser() user: AuthenticatedUser,
  ) {
    return this.verificationsService.reject(id, notes, user);
  }
}
