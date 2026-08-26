import {
  Injectable,
  NotFoundException,
  ForbiddenException,
  UnprocessableEntityException,
  Inject,
} from '@nestjs/common';
import { EventEmitter2 } from '@nestjs/event-emitter';
import { PrismaService } from '../../infrastructure/database/prisma.service';
import {
  CreateSubmissionTicketDto,
  TransitionWorkflowDto,
  ResubmitRevisionDto,
} from './dto/submission.dto';
import { StateMachineEngine } from './domain/state-machine.engine';
import { AuthenticatedUser } from '../../common/interfaces/auth-payload.interface';
import { NOTIFICATION_DISPATCHER_TOKEN } from '../../infrastructure/realtime/realtime.module';
import { NotificationDispatcher } from '../../infrastructure/realtime/notification-dispatcher.interface';

const DEMO_SUBMISSIONS: any[] = [
  {
    id: 'sub-001',
    ticketNumber: 'TKT-20260825-0042',
    institutionId: 'inst-kemenko-pangan',
    institutionName: 'Kementerian Koordinator Bidang Pangan',
    institutionCode: 'KL-042',
    submissionType: 'STRUKTUR_ORGANISASI',
    title: 'Pembentukan Struktur Organisasi & Tata Kerja (SOTK) Kemenko Pangan',
    submissionNotes: 'Usulan penataan struktur eselon I dan II berdasar mandat Perpres No. 147 Tahun 2024.',
    legalDocName: 'Perpres_No_147_Tahun_2024_Kemenko_Pangan.pdf',
    status: 'IN_REVIEW',
    priority: 'HIGH',
    submitterUserId: 'usr-operator-01',
    submitterName: 'Budi Santoso, S.AP.',
    submittedAt: '2026-08-25T08:30:00Z',
    updatedAt: '2026-08-25T08:30:00Z',
    itemsCount: 2,
    items: [
      {
        id: 'item-01',
        submissionTicketId: 'sub-001',
        targetEntityType: 'ORGANIZATION_UNIT',
        actionType: 'CREATE',
        label: 'Deputi Bidang Kedaulatan & Ketersediaan Pangan',
        payloadBefore: null,
        payloadAfter: {
          unitCode: 'DEP-1-PGN',
          unitName: 'Deputi Bidang Kedaulatan & Ketersediaan Pangan (Deputi I)',
          echelonLevel: 'I.a',
          hierarchyLevel: 2,
        },
      },
      {
        id: 'item-02',
        submissionTicketId: 'sub-001',
        targetEntityType: 'ORGANIZATION_UNIT',
        actionType: 'CREATE',
        label: 'Deputi Bidang Distribusi, Cadangan & Akses Pangan',
        payloadBefore: null,
        payloadAfter: {
          unitCode: 'DEP-2-PGN',
          unitName: 'Deputi Bidang Distribusi, Cadangan & Akses Pangan (Deputi II)',
          echelonLevel: 'I.a',
          hierarchyLevel: 2,
        },
      },
    ],
    verificationLogs: [
      {
        id: 'vlog-01',
        submissionTicketId: 'sub-001',
        verifierUserId: 'usr-verifikator-01',
        verifierName: 'Siti Rahmawati, S.STP, M.AP',
        decision: 'PASS',
        notes: 'Dokumen dasar hukum dan nomenklatur unit organisasi telah diteliti sesuai kaidah penataan kementerian.',
        verifiedAt: '2026-08-25T11:00:00Z',
      },
    ],
  },
  {
    id: 'sub-002',
    ticketNumber: 'TKT-20260824-0038',
    institutionId: 'inst-kemendikdasmen',
    institutionName: 'Kementerian Pendidikan Dasar dan Menengah',
    institutionCode: 'KL-043',
    submissionType: 'TUGAS_FUNGSI',
    title: 'Penyesuaian Butir Tugas dan Rincian Fungsi Pasca Pemisahan Kemendikbudristek',
    submissionNotes: 'Pemisahan butir mandat tugas dan rincian fungsi pengelolaan PAUD, SD, SMP, SMA.',
    legalDocName: 'Perpres_No_188_Tahun_2024_Kemendikdasmen.pdf',
    status: 'VERIFIED',
    priority: 'HIGH',
    submitterUserId: 'usr-operator-02',
    submitterName: 'Rina Wijaya, M.Pd.',
    submittedAt: '2026-08-24T14:15:00Z',
    updatedAt: '2026-08-25T09:45:00Z',
    itemsCount: 1,
    items: [
      {
        id: 'item-03',
        submissionTicketId: 'sub-002',
        targetEntityType: 'TUPOKSI',
        actionType: 'CREATE',
        label: 'Mandat Pengelolaan Guru Pendidikan Dasar',
        payloadBefore: null,
        payloadAfter: {
          type: 'FUNCTION',
          contentText: 'Perumusan dan pelaksanaan kebijakan di bidang pembinaan pendidik dan tenaga kependidikan dasar dan menengah.',
          legalArticleReference: 'Perpres No. 188/2024 Pasal 6',
        },
      },
    ],
    verificationLogs: [
      {
        id: 'vlog-02',
        submissionTicketId: 'sub-002',
        verifierUserId: 'usr-verifikator-01',
        verifierName: 'Siti Rahmawati, S.STP, M.AP',
        decision: 'PASS',
        notes: 'Mandat telah sesuai dengan pembagian urusan kementerian baru.',
        verifiedAt: '2026-08-25T09:45:00Z',
      },
    ],
  },
  {
    id: 'sub-003',
    ticketNumber: 'TKT-20260823-0029',
    institutionId: 'inst-kemenbud',
    institutionName: 'Kementerian Kebudayaan',
    institutionCode: 'KL-045',
    submissionType: 'STRUKTUR_ORGANISASI',
    title: 'Usulan Pembentukan Direktorat Standardisasi Objek Pemajuan Kebudayaan',
    submissionNotes: 'Penambahan unit eselon II baru pada Direktorat Jenderal Pelestarian Tradisi.',
    legalDocName: 'Rancangan_Permen_Kebudayaan_SOTK.pdf',
    status: 'REVISION_REQUIRED',
    priority: 'NORMAL',
    submitterUserId: 'usr-operator-03',
    submitterName: 'Hendra Gunawan, S.Sn.',
    submittedAt: '2026-08-23T10:00:00Z',
    updatedAt: '2026-08-24T16:20:00Z',
    itemsCount: 1,
    items: [
      {
        id: 'item-04',
        submissionTicketId: 'sub-003',
        targetEntityType: 'ORGANIZATION_UNIT',
        actionType: 'CREATE',
        label: 'Direktorat Standardisasi Kebudayaan',
        payloadBefore: null,
        payloadAfter: {
          unitCode: 'DIT-STD-BUD',
          unitName: 'Direktorat Standardisasi Kebudayaan',
          legalArticleReference: 'Perpres No. 190/2024 Pasal 5 ayat (2) huruf a',
          contentText: 'Standardisasi objek pemajuan kebudayaan',
        },
      },
    ],
    verificationLogs: [
      {
        id: 'vlog-03',
        submissionTicketId: 'sub-003',
        verifierUserId: 'usr-verifikator-01',
        verifierName: 'Siti Rahmawati, S.STP, M.AP',
        decision: 'REVISION_REQUIRED',
        notes: 'Mohon lengkapi rujukan pasal regulasi Perpres No. 190/2024 dan sesuaikan nomenklatur unit organisasi.',
        verifiedAt: '2026-08-24T16:20:00Z',
      },
    ],
  },
];

let inMemorySubmissions = [...DEMO_SUBMISSIONS];

@Injectable()
export class WorkflowsService {
  constructor(
    private prisma: PrismaService,
    private eventEmitter: EventEmitter2,
    @Inject(NOTIFICATION_DISPATCHER_TOKEN)
    private notifDispatcher: NotificationDispatcher,
  ) {}

  async findAll(query?: { status?: string; institutionId?: string; search?: string; page?: number; pageSize?: number }, user?: AuthenticatedUser) {
    const page = Number(query?.page) || 1;
    const pageSize = Number(query?.pageSize) || 20;

    let result = [...inMemorySubmissions];

    // Scoping for USER
    if (user?.role === 'USER' && user.institutionId) {
      result = result.filter((s) => s.institutionId === user.institutionId);
    } else if (query?.institutionId) {
      result = result.filter((s) => s.institutionId === query.institutionId);
    }

    if (query?.status) {
      result = result.filter((s) => s.status === query.status);
    }

    if (query?.search) {
      const s = query.search.toLowerCase();
      result = result.filter(
        (sub) =>
          sub.title.toLowerCase().includes(s) ||
          sub.ticketNumber.toLowerCase().includes(s) ||
          sub.institutionName.toLowerCase().includes(s),
      );
    }

    const total = result.length;
    const totalPages = Math.ceil(total / pageSize) || 1;
    const paginated = result.slice((page - 1) * pageSize, page * pageSize);

    return {
      data: paginated,
      meta: {
        page,
        pageSize,
        total,
        totalPages,
        hasNextPage: page < totalPages,
        hasPreviousPage: page > 1,
      },
    };
  }

  async findById(id: string, user?: AuthenticatedUser) {
    const found = inMemorySubmissions.find((s) => s.id === id || s.ticketNumber === id);
    if (!found) {
      throw new NotFoundException(`Tiket pengajuan dengan ID '${id}' tidak ditemukan.`);
    }

    // Scoping check
    if (user?.role === 'USER' && user.institutionId && found.institutionId !== user.institutionId) {
      throw new ForbiddenException('Akses ditolak: Anda tidak memiliki izin untuk melihat tiket instansi lain.');
    }

    return found;
  }

  async create(dto: CreateSubmissionTicketDto, user: AuthenticatedUser) {
    // Scoping validation
    if (user.role === 'USER' && user.institutionId && dto.institutionId !== user.institutionId) {
      throw new ForbiddenException('Pelanggaran Scope: Anda hanya berhak membuat usulan untuk instansi Anda sendiri.');
    }

    const ticketNumber = `TKT-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}-${Math.floor(1000 + Math.random() * 9000)}`;

    const newTicket: any = {
      id: `sub-${Date.now()}`,
      ticketNumber,
      institutionId: dto.institutionId,
      institutionName: user.institutionName || 'Kementerian/Lembaga',
      institutionCode: 'KL-000',
      submissionType: dto.submissionType,
      title: dto.title,
      submissionNotes: dto.submissionNotes,
      legalDocName: dto.legalDocName || 'Lampiran_Dasar_Hukum.pdf',
      status: 'SUBMITTED',
      priority: 'NORMAL',
      submitterUserId: user.id,
      submitterName: user.fullName,
      submittedAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      itemsCount: dto.items?.length || 0,
      items: dto.items?.map((item, idx) => ({
        id: `item-${Date.now()}-${idx}`,
        targetEntityType: item.targetEntityType,
        actionType: item.actionType,
        label: item.label,
        payloadBefore: item.payloadBefore || null,
        payloadAfter: item.payloadAfter || null,
      })) || [],
      verificationLogs: [],
    };

    inMemorySubmissions.unshift(newTicket);

    // Emit event
    this.eventEmitter.emit('submission.submitted', {
      ticketId: newTicket.id,
      ticketNumber: newTicket.ticketNumber,
      institutionId: newTicket.institutionId,
    });

    return newTicket;
  }

  async transitionWorkflow(id: string, dto: TransitionWorkflowDto, user: AuthenticatedUser) {
    const submission = await this.findById(id, user);

    // Validate state machine rule
    const validation = StateMachineEngine.validateTransition(
      submission.status,
      dto.targetState,
      user.role,
      dto.notes,
    );

    if (!validation.allowed) {
      throw new UnprocessableEntityException(validation.reason);
    }

    submission.status = dto.targetState;
    submission.updatedAt = new Date().toISOString();

    if (dto.notes) {
      submission.verificationLogs = submission.verificationLogs || [];
      submission.verificationLogs.push({
        id: `vlog-${Date.now()}`,
        submissionTicketId: id,
        verifierUserId: user.id,
        verifierName: user.fullName,
        decision: dto.targetState === 'VERIFIED' ? 'PASS' : dto.targetState === 'REVISION_REQUIRED' ? 'REVISION_REQUIRED' : 'REJECT',
        notes: dto.notes,
        verifiedAt: new Date().toISOString(),
      });
    }

    // Emit events based on target state
    if (dto.targetState === 'VERIFIED') {
      this.eventEmitter.emit('verification.pass', { ticketId: id });
    } else if (dto.targetState === 'REVISION_REQUIRED') {
      this.eventEmitter.emit('verification.revision_requested', { ticketId: id, notes: dto.notes });
    } else if (dto.targetState === 'APPROVED') {
      this.eventEmitter.emit('submission.approved', { ticketId: id });
    }

    return submission;
  }

  async resubmitRevision(id: string, dto: ResubmitRevisionDto, user: AuthenticatedUser) {
    const submission = await this.findById(id, user);

    if (submission.status !== 'REVISION_REQUIRED') {
      throw new UnprocessableEntityException(
        `Pengiriman perbaikan hanya dapat dilakukan pada tiket berstatus 'REVISION_REQUIRED'. Status saat ini: '${submission.status}'`,
      );
    }

    submission.status = 'RESUBMITTED';
    submission.updatedAt = new Date().toISOString();

    submission.verificationLogs = submission.verificationLogs || [];
    submission.verificationLogs.push({
      id: `vlog-${Date.now()}`,
      submissionTicketId: id,
      verifierUserId: user.id,
      verifierName: user.fullName,
      decision: 'REVISION_REQUIRED',
      notes: `Operator mengirimkan perbaikan: ${dto.operatorResponseNote}`,
      verifiedAt: new Date().toISOString(),
    });

    this.eventEmitter.emit('submission.resubmitted', {
      ticketId: id,
      ticketNumber: submission.ticketNumber,
      operatorResponse: dto.operatorResponseNote,
    });

    return submission;
  }

  async approveSubmissionToMaster(id: string, user: AuthenticatedUser) {
    const submission = await this.findById(id, user);

    if (submission.status !== 'VERIFIED') {
      throw new UnprocessableEntityException(
        `Pengesahan hanya dapat dilakukan jika usulan telah lolos verifikasi (Status: 'VERIFIED'). Status saat ini: '${submission.status}'`,
      );
    }

    if (user.role !== 'ADMIN') {
      throw new ForbiddenException('Hanya Administrator Pusat yang berwenang mengesahkan usulan ke Master Data aktif.');
    }

    // ATOMIC UNIT OF WORK SIMULATION / PRISMA TRANSACTION
    submission.status = 'APPROVED';
    submission.approvedAt = new Date().toISOString();
    submission.approvedByUserName = user.fullName;
    submission.updatedAt = new Date().toISOString();

    // Broadcast domain events & refresh analytics cache
    this.eventEmitter.emit('submission.approved', {
      ticketId: id,
      ticketNumber: submission.ticketNumber,
      institutionId: submission.institutionId,
      approvedBy: user.fullName,
    });

    return submission;
  }
}
