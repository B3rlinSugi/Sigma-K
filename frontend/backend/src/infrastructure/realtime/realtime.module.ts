import { Global, Module } from '@nestjs/common';
import { InternalNotificationDispatcher } from './internal-notification.dispatcher';

export const NOTIFICATION_DISPATCHER_TOKEN = 'NOTIFICATION_DISPATCHER';

@Global()
@Module({
  providers: [
    {
      provide: NOTIFICATION_DISPATCHER_TOKEN,
      useClass: InternalNotificationDispatcher,
    },
    InternalNotificationDispatcher,
  ],
  exports: [NOTIFICATION_DISPATCHER_TOKEN, InternalNotificationDispatcher],
})
export class RealtimeModule {}
