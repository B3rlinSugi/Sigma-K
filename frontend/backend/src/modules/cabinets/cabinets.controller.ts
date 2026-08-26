import { Controller, Get, Param, Query } from '@nestjs/common';
import { CabinetsService } from './cabinets.service';

@Controller('cabinets')
export class CabinetsController {
  constructor(private readonly cabinetsService: CabinetsService) {}

  @Get()
  async findAll() {
    return this.cabinetsService.findAll();
  }

  @Get('compare')
  async compareCabinets(
    @Query('baseCabinetId') baseCabinetId?: string,
    @Query('targetCabinetId') targetCabinetId?: string,
  ) {
    return this.cabinetsService.compareCabinets(
      baseCabinetId || 'cab-indonesia-maju',
      targetCabinetId || 'cab-merah-putih',
    );
  }

  @Get(':id')
  async findById(@Param('id') id: string) {
    return this.cabinetsService.findById(id);
  }

  @Get(':id/memberships')
  async getMemberships(
    @Param('id') id: string,
    @Query('category') category?: string,
  ) {
    return this.cabinetsService.getMemberships(id, category);
  }

  @Get(':id/lineage')
  async getLineages(@Param('id') id: string) {
    return this.cabinetsService.getLineages(id);
  }
}
