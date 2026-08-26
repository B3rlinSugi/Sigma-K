export type NotificationCategory = 'WORKFLOW' | 'MASTER_DATA' | 'SECURITY' | 'SYSTEM';

export interface NotificationItem {
  id: string;
  userId: string;
  title: string;
  message: string;
  category: NotificationCategory;
  actionUrl?: string;
  isRead: boolean;
  createdAt: string;
  metadata?: Record<string, any>;
}
