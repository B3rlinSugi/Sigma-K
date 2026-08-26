import { Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../../infrastructure/database/prisma.service';

const DEMO_AUDIT_LOGS = [
  {
    id: 'aud-001',
    actorName: 'Ahmad Fauzi, S.Kom, M.TI',
    actorRole: 'ADMIN',
    action: 'APPROVE',
    entityType: 'SUBMISSION_TICKET',
    entityId: 'sub-002',
    entityName: 'Penyesuaian Butir Tugas dan Fungsi Kemendikdasmen',
    oldValues: {
      status: 'VERIFIED',
      approvedAt: null,
    },
    newValues: {
      status: 'APPROVED',
      approvedAt: '2026-08-25T09:45:00Z',
      approvedBy: 'Ahmad Fauzi',
    },
    ipAddress: '10.14.20.105',
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SIGMA-K/Client',
    createdAt: '2026-08-25T09:45:00Z',
  },
  {
    id: 'aud-002',
    actorName: 'Siti Rahmawati, S.STP, M.AP',
    actorRole: 'VERIFIKATOR',
    action: 'VERIFY',
    entityType: 'SUBMISSION_TICKET',
    entityId: 'sub-001',
    entityName: 'Pembentukan SOTK Kemenko Pangan',
    oldValues: {
      status: 'IN_REVIEW',
    },
    newValues: {
      status: 'VERIFIED',
      verifiedAt: '2026-08-25T11:00:00Z',
      decision: 'PASS',
      notes: 'Dokumen dasar hukum dan nomenklatur unit organisasi telah diteliti sesuai kaidah penataan kementerian.',
    },
    ipAddress: '10.14.20.112',
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SIGMA-K/Client',
    createdAt: '2026-08-25T11:00:00Z',
  },
  {
    id: 'aud-003',
    actorName: 'Budi Santoso, S.AP.',
    actorRole: 'USER',
    action: 'SUBMIT',
    entityType: 'SUBMISSION_TICKET',
    entityId: 'sub-001',
    entityName: 'Pembentukan SOTK Kemenko Pangan',
    oldValues: {
      status: 'DRAFT',
    },
    newValues: {
      status: 'SUBMITTED',
      submittedAt: '2026-08-25T08:30:00Z',
    },
    ipAddress: '10.12.5.40',
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SIGMA-K/Client',
    createdAt: '2026-08-25T08:30:00Z',
  },
];

let inMemoryAuditLogs: any[] = [...DEMO_AUDIT_LOGS];

@Injectable()
export class AuditService {
  constructor(private prisma: PrismaService) {}

  async findAll(query?: { search?: string; action?: string; entityType?: string; page?: number; pageSize?: number }) {
    const page = Number(query?.page) || 1;
    const pageSize = Number(query?.pageSize) || 20;

    try {
      const where: any = {};
      if (query?.action) where.action = query.action;
      if (query?.entityType) where.entityType = query.entityType;
      if (query?.search) {
        where.OR = [
          { entityName: { contains: query.search, mode: 'insensitive' } },
          { actorName: { contains: query.search, mode: 'insensitive' } },
          { entityId: { contains: query.search, mode: 'insensitive' } },
        ];
      }

      const [total, items] = await Promise.all([
        this.prisma.auditLog.count({ where }),
        this.prisma.auditLog.findMany({
          where,
          skip: (page - 1) * pageSize,
          take: pageSize,
          orderBy: { createdAt: 'desc' },
        }),
      ]);

      const totalPages = Math.ceil(total / pageSize) || 1;

      return {
        data: items,
        meta: {
          page,
          pageSize,
          total,
          totalPages,
          hasNextPage: page < totalPages,
          hasPreviousPage: page > 1,
        },
      };
    } catch {}

    let result = [...inMemoryAuditLogs];
    if (query?.action) result = result.filter((a) => a.action === query.action);
    if (query?.entityType) result = result.filter((a) => a.entityType === query.entityType);
    if (query?.search) {
      const s = query.search.toLowerCase();
      result = result.filter(
        (a) =>
          a.entityName.toLowerCase().includes(s) ||
          a.actorName.toLowerCase().includes(s) ||
          a.entityId.toLowerCase().includes(s),
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

  async findById(id: string) {
    try {
      const log = await this.prisma.auditLog.findUnique({ where: { id } });
      if (log) return log;
    } catch {}

    const found = inMemoryAuditLogs.find((a) => a.id === id);
    if (!found) throw new NotFoundException(`Audit log dengan ID '${id}' tidak ditemukan.`);
    return found;
  }

  async recordLog(entry: {
    actorUserId?: string;
    actorName: string;
    actorRole: string;
    action: string;
    entityType: string;
    entityId: string;
    entityName?: string;
    oldValues?: Record<string, any>;
    newValues?: Record<string, any>;
    ipAddress?: string;
    userAgent?: string;
  }) {
    try {
      return await this.prisma.auditLog.create({
        data: {
          actorUserId: entry.actorUserId,
          actorName: entry.actorName,
          actorRole: entry.actorRole,
          action: entry.action,
          entityType: entry.entityType,
          entityId: entry.entityId,
          entityName: entry.entityName,
          oldValues: entry.oldValues,
          newValues: entry.newValues,
          ipAddress: entry.ipAddress,
          userAgent: entry.userAgent,
        },
      });
    } catch {
      const newEntry = {
        id: `aud-${Date.now()}`,
        ...entry,
        createdAt: new Date().toISOString(),
      };
      inMemoryAuditLogs.unshift(newEntry);
      return newEntry;
    }
  }
}
