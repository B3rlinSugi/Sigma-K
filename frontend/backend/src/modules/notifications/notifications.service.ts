import { Injectable } from '@nestjs/common';
import { PrismaService } from '../../infrastructure/database/prisma.service';

const DEMO_NOTIFICATIONS = [
  {
    id: 'notif-01',
    userId: 'usr-operator-01',
    title: 'Catatan Revisi dari Verifikator',
    message: 'Pengajuan SOTK (TKT-20260823-0029) memerlukan perbaikan pada rujukan pasal regulasi.',
    category: 'WORKFLOW',
    actionUrl: '/submissions/sub-003/revision',
    isRead: false,
    createdAt: new Date(Date.now() - 3600000).toISOString(),
  },
  {
    id: 'notif-02',
    userId: 'usr-operator-01',
    title: 'Pengajuan Masuk Antrean Telaah',
    message: 'Pengajuan (TKT-20260825-0042) telah berhasil dikirimkan ke KemenPANRB.',
    category: 'WORKFLOW',
    actionUrl: '/submissions/sub-001',
    isRead: true,
    createdAt: new Date(Date.now() - 7200000).toISOString(),
  },
  {
    id: 'notif-03',
    userId: 'usr-operator-01',
    title: 'Pembaruan Master Data Instansi',
    message: 'Perubahan dasar hukum Kemendikdasmen telah disahkan ke Master Data aktif.',
    category: 'MASTER_DATA',
    actionUrl: '/institutions/inst-kemendikdasmen',
    isRead: true,
    createdAt: new Date(Date.now() - 86400000).toISOString(),
  },
];

let inMemoryNotifications = [...DEMO_NOTIFICATIONS];

@Injectable()
export class NotificationsService {
  constructor(private prisma: PrismaService) {}

  async findAll(userId: string, category?: string, isRead?: boolean) {
    try {
      const where: any = { userId };
      if (category) where.category = category;
      if (isRead !== undefined) where.isRead = isRead;

      const notifs = await this.prisma.notification.findMany({
        where,
        orderBy: { createdAt: 'desc' },
      });
      if (notifs.length > 0) return notifs;
    } catch {}

    let result = [...inMemoryNotifications];
    if (category) result = result.filter((n) => n.category === category);
    if (isRead !== undefined) result = result.filter((n) => n.isRead === isRead);
    return result;
  }

  async markAsRead(id: string, userId: string) {
    try {
      return await this.prisma.notification.update({
        where: { id },
        data: { isRead: true },
      });
    } catch {
      const found = inMemoryNotifications.find((n) => n.id === id);
      if (found) found.isRead = true;
      return found;
    }
  }

  async markAllAsRead(userId: string) {
    try {
      await this.prisma.notification.updateMany({
        where: { userId },
        data: { isRead: true },
      });
    } catch {
      inMemoryNotifications.forEach((n) => (n.isRead = true));
    }
    return { success: true, message: 'Seluruh notifikasi telah ditandai dibaca.' };
  }
}
