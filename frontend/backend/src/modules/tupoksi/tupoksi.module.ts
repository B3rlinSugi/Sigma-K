import { Module } from '@nestjs/common';
import { TupoksiService } from './tupoksi.service';
import { TupoksiController } from './tupoksi.controller';

@Module({
  controllers: [TupoksiController],
  providers: [TupoksiService],
  exports: [TupoksiService],
})
export class TupoksiModule {}
