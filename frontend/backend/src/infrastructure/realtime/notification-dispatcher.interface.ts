export interface NotificationPayload {
  userId: string;
  title: string;
  message: string;
  category: string;
  actionUrl?: string;
}

export interface NotificationDispatcher {
  dispatchNotification(payload: NotificationPayload): Promise<void>;
  broadcastMasterUpdate(entityType: string, entityId: string): Promise<void>;
}
