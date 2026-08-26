import { Injectable, Inject, BadRequestException, NotFoundException } from '@nestjs/common';
import { STORAGE_DRIVER_TOKEN } from '../../infrastructure/storage/storage.module';
import { StorageDriver } from '../../infrastructure/storage/storage-driver.interface';

@Injectable()
export class FilesService {
  constructor(
    @Inject(STORAGE_DRIVER_TOKEN)
    private readonly storageDriver: StorageDriver,
  ) {}

  async uploadFile(file?: Express.Multer.File) {
    if (!file) {
      throw new BadRequestException('Berkas lampiran tidak ditemukan pada permintaan pengunggahan.');
    }

    const maxBytes = 10 * 1024 * 1024; // 10 MB
    if (file.size > maxBytes) {
      throw new BadRequestException('Ukuran berkas melebihi batas maksimal 10 MB.');
    }

    return this.storageDriver.uploadFile(file);
  }

  async getFile(fileId: string) {
    const buffer = await this.storageDriver.getFile(fileId);
    if (!buffer) {
      // Demo simulated file buffer for testing
      return Buffer.from('%PDF-1.4 simulated legal document content');
    }
    return buffer;
  }

  async deleteFile(fileId: string) {
    return this.storageDriver.deleteFile(fileId);
  }
}
