import { Injectable, Logger } from '@nestjs/common';
import { EventEmitter2 } from '@nestjs/event-emitter';
import { NotificationDispatcher, NotificationPayload } from './notification-dispatcher.interface';

@Injectable()
export class InternalNotificationDispatcher implements NotificationDispatcher {
  private readonly logger = new Logger(InternalNotificationDispatcher.name);

  constructor(private eventEmitter: EventEmitter2) {}

  async dispatchNotification(payload: NotificationPayload): Promise<void> {
    this.logger.log(`Dispatching internal notification to user: ${payload.userId} [${payload.category}]`);
    this.eventEmitter.emit('notification.created', payload);
  }

  async broadcastMasterUpdate(entityType: string, entityId: string): Promise<void> {
    this.logger.log(`Broadcasting master update: ${entityType}:${entityId}`);
    this.eventEmitter.emit('master.updated', { entityType, entityId, timestamp: new Date() });
  }
}
