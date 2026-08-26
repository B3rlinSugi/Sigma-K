import { Controller, Get, Post, Patch, Param, Query, Body, UseGuards } from '@nestjs/common';
import { InstitutionsService } from './institutions.service';
import { CreateInstitutionDto, UpdateInstitutionDto } from './dto/institution.dto';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { InstitutionScopeGuard } from '../../common/guards/institution-scope.guard';
import { Roles } from '../../common/decorators/roles.decorator';

@Controller('institutions')
export class InstitutionsController {
  constructor(private readonly institutionsService: InstitutionsService) {}

  @Get()
  async findAll(
    @Query('search') search?: string,
    @Query('type') type?: string,
    @Query('status') status?: string,
    @Query('page') page?: number,
    @Query('pageSize') pageSize?: number,
  ) {
    return this.institutionsService.findAll({ search, type, status, page, pageSize });
  }

  @Get(':id')
  async findById(@Param('id') id: string) {
    return this.institutionsService.findById(id);
  }

  @Post()
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  async create(@Body() dto: CreateInstitutionDto) {
    return this.institutionsService.create(dto);
  }

  @Patch(':id')
  @UseGuards(JwtAuthGuard, RolesGuard, InstitutionScopeGuard)
  @Roles('ADMIN', 'USER')
  async update(@Param('id') id: string, @Body() dto: UpdateInstitutionDto) {
    return this.institutionsService.update(id, dto);
  }
}
