export interface StoredFileMetadata {
  id: string;
  originalFileName: string;
  mimeType: string;
  sizeBytes: number;
  storagePath: string;
  publicUrl?: string;
  uploadedAt: Date;
}

export interface StorageDriver {
  uploadFile(file: Express.Multer.File): Promise<StoredFileMetadata>;
  getFile(fileId: string): Promise<Buffer | null>;
  deleteFile(fileId: string): Promise<boolean>;
  getDownloadUrl(fileId: string): Promise<string>;
}
