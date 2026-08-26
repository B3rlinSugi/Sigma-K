import {
  Controller,
  Get,
  Post,
  Patch,
  Param,
  Query,
  Body,
  UseGuards,
} from '@nestjs/common';
import { WorkflowsService } from './workflows.service';
import {
  CreateSubmissionTicketDto,
  TransitionWorkflowDto,
  ResubmitRevisionDto,
} from './dto/submission.dto';
import { JwtAuthGuard } from '../../common/guards/jwt-auth.guard';
import { RolesGuard } from '../../common/guards/roles.guard';
import { InstitutionScopeGuard } from '../../common/guards/institution-scope.guard';
import { Roles } from '../../common/decorators/roles.decorator';
import { CurrentUser } from '../../common/decorators/current-user.decorator';
import { AuthenticatedUser } from '../../common/interfaces/auth-payload.interface';
import { WORKFLOW_PROFILES } from '../../config/workflow.config';

@Controller()
@UseGuards(JwtAuthGuard, RolesGuard)
export class WorkflowsController {
  constructor(private readonly workflowsService: WorkflowsService) {}

  @Get('submissions')
  async findAll(
    @Query('status') status?: string,
    @Query('institutionId') institutionId?: string,
    @Query('search') search?: string,
    @Query('page') page?: number,
    @Query('pageSize') pageSize?: number,
    @CurrentUser() user?: AuthenticatedUser,
  ) {
    return this.workflowsService.findAll({ status, institutionId, search, page, pageSize }, user);
  }

  @Get('submissions/:id')
  async findById(
    @Param('id') id: string,
    @CurrentUser() user: AuthenticatedUser,
  ) {
    return this.workflowsService.findById(id, user);
  }

  @Post('submissions')
  @Roles('USER', 'ADMIN')
  async create(
    @Body() dto: CreateSubmissionTicketDto,
    @CurrentUser() user: AuthenticatedUser,
  ) {
    return this.workflowsService.create(dto, user);
  }

  @Post('submissions/:id/transition')
  async transition(
    @Param('id') id: string,
    @Body() dto: TransitionWorkflowDto,
    @CurrentUser() user: AuthenticatedUser,
  ) {
    return this.workflowsService.transitionWorkflow(id, dto, user);
  }

  @Post('submissions/:id/resubmit')
  @Roles('USER', 'ADMIN')
  async resubmit(
    @Param('id') id: string,
    @Body() dto: ResubmitRevisionDto,
    @CurrentUser() user: AuthenticatedUser,
  ) {
    return this.workflowsService.resubmitRevision(id, dto, user);
  }

  @Post('submissions/:id/approve')
  @Roles('ADMIN')
  async approve(
    @Param('id') id: string,
    @CurrentUser() user: AuthenticatedUser,
  ) {
    return this.workflowsService.approveSubmissionToMaster(id, user);
  }

  @Get('workflow/config')
  async getWorkflowConfig() {
    return WORKFLOW_PROFILES;
  }

  @Get('workflow/states')
  async getWorkflowStates() {
    return WORKFLOW_PROFILES.STANDARD_WORKFLOW.states;
  }

  @Get('workflow/transitions')
  async getWorkflowTransitions(@Query('profile') profile?: string) {
    const selected = profile === 'ADMIN_TRIAGE_WORKFLOW' ? WORKFLOW_PROFILES.ADMIN_TRIAGE_WORKFLOW : WORKFLOW_PROFILES.STANDARD_WORKFLOW;
    return selected.transitions;
  }
}
