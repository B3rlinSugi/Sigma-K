import { Injectable } from '@nestjs/common';
import * as fs from 'fs';
import * as path from 'path';
import { StorageDriver, StoredFileMetadata } from './storage-driver.interface';

@Injectable()
export class LocalStorageDriver implements StorageDriver {
  private readonly uploadDir: string;

  constructor() {
    this.uploadDir = path.resolve(process.env.LOCAL_STORAGE_PATH || './uploads');
    if (!fs.existsSync(this.uploadDir)) {
      fs.mkdirSync(this.uploadDir, { recursive: true });
    }
  }

  async uploadFile(file: Express.Multer.File): Promise<StoredFileMetadata> {
    const fileId = `file-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
    const extension = path.extname(file.originalname);
    const safeFileName = `${fileId}${extension}`;
    const targetPath = path.join(this.uploadDir, safeFileName);

    await fs.promises.writeFile(targetPath, file.buffer);

    return {
      id: fileId,
      originalFileName: file.originalname,
      mimeType: file.mimetype,
      sizeBytes: file.size,
      storagePath: targetPath,
      publicUrl: `/api/v1/files/${fileId}`,
      uploadedAt: new Date(),
    };
  }

  async getFile(fileId: string): Promise<Buffer | null> {
    try {
      const files = await fs.promises.readdir(this.uploadDir);
      const match = files.find((f) => f.startsWith(fileId));
      if (!match) return null;
      return await fs.promises.readFile(path.join(this.uploadDir, match));
    } catch {
      return null;
    }
  }

  async deleteFile(fileId: string): Promise<boolean> {
    try {
      const files = await fs.promises.readdir(this.uploadDir);
      const match = files.find((f) => f.startsWith(fileId));
      if (!match) return false;
      await fs.promises.unlink(path.join(this.uploadDir, match));
      return true;
    } catch {
      return false;
    }
  }

  async getDownloadUrl(fileId: string): Promise<string> {
    return `/api/v1/files/${fileId}`;
  }
}
