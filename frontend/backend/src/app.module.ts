import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { EventEmitterModule } from '@nestjs/event-emitter';
import { appConfig } from './config/app.config';
import { PrismaModule } from './infrastructure/database/prisma.module';
import { StorageModule } from './infrastructure/storage/storage.module';
import { RealtimeModule } from './infrastructure/realtime/realtime.module';

// 10 Domain Bounded Modules
import { AuthModule } from './modules/auth/auth.module';
import { UsersModule } from './modules/users/users.module';
import { InstitutionsModule } from './modules/institutions/institutions.module';
import { CabinetsModule } from './modules/cabinets/cabinets.module';
import { OrganizationsModule } from './modules/organizations/organizations.module';
import { TupoksiModule } from './modules/tupoksi/tupoksi.module';
import { WorkflowsModule } from './modules/workflows/workflows.module';
import { VerificationsModule } from './modules/verifications/verifications.module';
import { NotificationsModule } from './modules/notifications/notifications.module';
import { AnalyticsModule } from './modules/analytics/analytics.module';
import { AuditModule } from './modules/audit/audit.module';

// 1 Shared Infrastructure Module
import { FilesModule } from './modules/files/files.module';

@Module({
  imports: [
    // Core Infrastructure
    ConfigModule.forRoot({
      isGlobal: true,
      load: [appConfig],
    }),
    EventEmitterModule.forRoot(),
    PrismaModule,
    StorageModule,
    RealtimeModule,

    // 10 Domain Bounded Modules
    AuthModule,
    UsersModule,
    InstitutionsModule,
    CabinetsModule,
    OrganizationsModule,
    TupoksiModule,
    WorkflowsModule,
    VerificationsModule,
    NotificationsModule,
    AnalyticsModule,
    AuditModule,

    // 1 Shared Infrastructure Module
    FilesModule,
  ],
})
export class AppModule {}
