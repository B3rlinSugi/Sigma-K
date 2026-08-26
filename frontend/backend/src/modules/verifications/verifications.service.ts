import { Injectable, NotFoundException } from '@nestjs/common';
import { WorkflowsService } from '../workflows/workflows.service';
import { AuthenticatedUser } from '../../common/interfaces/auth-payload.interface';

@Injectable()
export class VerificationsService {
  constructor(private workflowsService: WorkflowsService) {}

  async getQueue(user: AuthenticatedUser) {
    const res = await this.workflowsService.findAll({}, user);
    // Verifiers focus on SUBMITTED, IN_REVIEW, RESUBMITTED
    const queue = res.data.filter(
      (s: any) =>
        s.status === 'SUBMITTED' ||
        s.status === 'IN_REVIEW' ||
        s.status === 'RESUBMITTED' ||
        s.status === 'REVISION_REQUIRED' ||
        s.status === 'VERIFIED',
    );
    return queue;
  }

  async getWorkspace(id: string, user: AuthenticatedUser) {
    return this.workflowsService.findById(id, user);
  }

  async verify(id: string, notes: string, user: AuthenticatedUser) {
    return this.workflowsService.transitionWorkflow(
      id,
      { targetState: 'VERIFIED', notes: notes || 'Usulan telah diteliti dan disetujui substantif.' },
      user,
    );
  }

  async requestRevision(id: string, notes: string, user: AuthenticatedUser) {
    return this.workflowsService.transitionWorkflow(
      id,
      { targetState: 'REVISION_REQUIRED', notes },
      user,
    );
  }

  async reject(id: string, notes: string, user: AuthenticatedUser) {
    return this.workflowsService.transitionWorkflow(
      id,
      { targetState: 'REJECTED', notes },
      user,
    );
  }
}
