import { NotificationItem } from '@/types/notification';
import { MOCK_NOTIFICATIONS } from '@/data/mock/notifications';

const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

let currentNotifs = [...MOCK_NOTIFICATIONS];

export class NotificationService {
  static async getNotifications(): Promise<NotificationItem[]> {
    await delay(80);
    return [...currentNotifs];
  }

  static async markAsRead(id: string): Promise<boolean> {
    await delay(50);
    const item = currentNotifs.find((n) => n.id === id);
    if (item) {
      item.isRead = true;
      return true;
    }
    return false;
  }

  static async markAllAsRead(): Promise<boolean> {
    await delay(50);
    currentNotifs.forEach((n) => (n.isRead = true));
    return true;
  }
}
