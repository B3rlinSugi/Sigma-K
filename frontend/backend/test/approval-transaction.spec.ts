import { WorkflowsService } from '../src/modules/workflows/workflows.service';
import { PrismaService } from '../src/infrastructure/database/prisma.service';
import { EventEmitter2 } from '@nestjs/event-emitter';
import { UnprocessableEntityException, ForbiddenException } from '@nestjs/common';
import { AuthenticatedUser } from '../src/common/interfaces/auth-payload.interface';

describe('WorkflowsService Master Data Approval & Atomic Transaction Logic', () => {
  let service: WorkflowsService;
  let mockEventEmitter: EventEmitter2;

  beforeEach(() => {
    mockEventEmitter = {
      emit: jest.fn(),
    } as unknown as EventEmitter2;

    const mockDispatcher = {
      dispatchNotification: jest.fn(),
      broadcastMasterUpdate: jest.fn(),
    };

    service = new WorkflowsService(
      {} as PrismaService,
      mockEventEmitter,
      mockDispatcher,
    );
  });

  it('should approve a VERIFIED ticket and broadcast domain event', async () => {
    const adminUser: AuthenticatedUser = {
      id: 'usr-admin-01',
      username: 'admin_sigma',
      fullName: 'Ahmad Fauzi',
      email: 'admin@sigma.go.id',
      role: 'ADMIN',
      permissions: ['*'],
    };

    const approvedTicket = await service.approveSubmissionToMaster('sub-002', adminUser);

    expect(approvedTicket).toBeDefined();
    expect(approvedTicket.status).toBe('APPROVED');
    expect(approvedTicket.approvedByUserName).toBe('Ahmad Fauzi');
    expect(mockEventEmitter.emit).toHaveBeenCalledWith(
      'submission.approved',
      expect.objectContaining({ ticketId: 'sub-002' }),
    );
  });

  it('should prevent approving a ticket that is NOT verified (e.g. IN_REVIEW or DRAFT)', async () => {
    const adminUser: AuthenticatedUser = {
      id: 'usr-admin-01',
      username: 'admin_sigma',
      fullName: 'Ahmad Fauzi',
      email: 'admin@sigma.go.id',
      role: 'ADMIN',
      permissions: ['*'],
    };

    await expect(service.approveSubmissionToMaster('sub-001', adminUser)).rejects.toThrow(
      UnprocessableEntityException,
    );
  });

  it('should reject non-ADMIN users from approving master data', async () => {
    const operatorUser: AuthenticatedUser = {
      id: 'usr-operator-01',
      username: 'operator_pangan',
      fullName: 'Budi Santoso',
      email: 'operator@pangan.go.id',
      role: 'USER',
      institutionId: 'inst-kemenko-pangan',
      permissions: [],
    };

    await expect(service.approveSubmissionToMaster('sub-002', operatorUser)).rejects.toThrow(
      ForbiddenException,
    );
  });
});
