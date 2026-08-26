import { Global, Module } from '@nestjs/common';
import { LocalStorageDriver } from './local-storage.driver';

export const STORAGE_DRIVER_TOKEN = 'STORAGE_DRIVER';

@Global()
@Module({
  providers: [
    {
      provide: STORAGE_DRIVER_TOKEN,
      useClass: LocalStorageDriver,
    },
    LocalStorageDriver,
  ],
  exports: [STORAGE_DRIVER_TOKEN, LocalStorageDriver],
})
export class StorageModule {}
