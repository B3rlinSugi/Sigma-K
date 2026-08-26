import { Controller, Get, Param, Query, UseGuards } from '@nestjs/common';
import { AuditService } from './audit.service';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { Roles } from '../../common/decorators/roles.decorator';

@Controller('audit-logs')
@UseGuards(JwtAuthGuard, RolesGuard)
export class AuditController {
  constructor(private readonly auditService: AuditService) {}

  @Get()
  @Roles('ADMIN', 'SESDEP')
  async findAll(
    @Query('search') search?: string,
    @Query('action') action?: string,
    @Query('entityType') entityType?: string,
    @Query('page') page?: number,
    @Query('pageSize') pageSize?: number,
  ) {
    return this.auditService.findAll({ search, action, entityType, page, pageSize });
  }

  @Get(':id')
  @Roles('ADMIN', 'SESDEP')
  async findById(@Param('id') id: string) {
    return this.auditService.findById(id);
  }
}
