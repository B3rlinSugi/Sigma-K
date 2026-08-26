import { Controller, Get, Post, Param, Query, Body, UseGuards } from '@nestjs/common';
import { TupoksiService } from './tupoksi.service';
import { CreateTupoksiDto } from './dto/tupoksi.dto';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { Roles } from '../../common/decorators/roles.decorator';

@Controller('tupoksi')
export class TupoksiController {
  constructor(private readonly tupoksiService: TupoksiService) {}

  @Get()
  async findAll(
    @Query('institutionId') institutionId?: string,
    @Query('type') type?: string,
    @Query('search') search?: string,
  ) {
    return this.tupoksiService.findAll(institutionId, type, search);
  }

  @Get(':id')
  async findById(@Param('id') id: string) {
    return this.tupoksiService.findById(id);
  }

  @Post()
  @UseGuards(JwtAuthGuard, RolesGuard)
  @Roles('ADMIN')
  async create(@Body() dto: CreateTupoksiDto) {
    return this.tupoksiService.create(dto);
  }
}
