import { Controller, Get, Post, Patch, Param, Query, Body, UseGuards } from '@nestjs/common';
import { OrganizationsService } from './organizations.service';
import { CreateOrganizationUnitDto, UpdateOrganizationUnitDto } from './dto/organization.dto';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { Roles } from '../../common/decorators/roles.decorator';

@Controller()
export class OrganizationsController {
  constructor(private readonly organizationsService: OrganizationsService) {}

  @Get('institutions/:institutionId/units')
  async findByInstitution(@Param('institutionId') institutionId: string) {
    return this.organizationsService.findByInstitution(institutionId);
  }

  @Get('organization-units')
  async findAll(
    @Query('search') search?: string,
    @Query('echelon') echelon?: string,
  ) {
    return this.organizationsService.findAll(search, echelon);
  }

  @Get('organization-units/:id')
  async findById(@Param('id') id: string) {
    return this.organizationsService.findById(id);
  }

  @Post('organization-units')
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  async create(@Body() dto: CreateOrganizationUnitDto) {
    return this.organizationsService.create(dto);
  }

  @Patch('organization-units/:id')
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  async update(
    @Param('id') id: string,
    @Body() dto: UpdateOrganizationUnitDto,
  ) {
    return this.organizationsService.update(id, dto);
  }
}
