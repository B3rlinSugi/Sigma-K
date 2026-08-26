import { Module } from '@nestjs/common';
import { VerificationsService } from './verifications.service';
import { VerificationsController } from './verifications.controller';
import { WorkflowsModule } from '../workflows/workflows.module';

@Module({
  imports: [WorkflowsModule],
  controllers: [VerificationsController],
  providers: [VerificationsService],
  exports: [VerificationsService],
})
export class VerificationsModule {}
