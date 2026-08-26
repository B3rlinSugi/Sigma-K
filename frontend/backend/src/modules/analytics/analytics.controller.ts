import { Controller, Get, Query } from '@nestjs/common';
import { AnalyticsService } from './analytics.service';

@Controller('analytics')
export class AnalyticsController {
  constructor(private readonly analyticsService: AnalyticsService) {}

  @Get('kpis')
  async getKPIs() {
    return this.analyticsService.getKPIs();
  }

  @Get('organization')
  async getOrganizationPosture() {
    return this.analyticsService.getEchelonDistribution();
  }

  @Get('cabinets')
  async getCabinetComposition(@Query('cabinetId') cabinetId?: string) {
    return this.analyticsService.getCabinetComposition(cabinetId);
  }

  @Get('submissions')
  async getSubmissionTurnaround() {
    return this.analyticsService.getSubmissionTurnaround();
  }
}
