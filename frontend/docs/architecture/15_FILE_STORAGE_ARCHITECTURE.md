# 15. DOCUMENT & MEDIA STORAGE ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** DevSecOps Architect & Solutions Architect  
> **Kebutuhan Terkait:** [REQ-021](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [FR-PROF-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/FUNCTIONAL_REQUIREMENTS.md), [BRULE-014](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md)  

Dokumen ini mendefinisikan arsitektur penyimpanan berkas digital (*Document & Object Storage Architecture*) untuk mengelola dokumen regulasi dasar hukum (PDF Perpres/Permen/Perda) dan logo instansi secara aman.

---

## 1. Diagram Alur Penyimpanan Berkas

```
[ OPERATOR UNGGAH PDF DASAR HUKUM ]
                 │
                 │ 1. Multipart Form Upload (Maks 10 MB)
                 ▼
+-----------------------------------------------------------------------------------+
| 1. FILE VALIDATION INTERCEPTOR (Backend Application Layer)                        |
|    - Validasi Ukuran: Ditolak jika > 10 MB.                                       |
|    - Validasi Magic Bytes: Memeriksa header biner `%PDF-` (Mencegah malware).     |
|    - Hashing Nama Berkas: Menghasilkan UUID acak (UUIDv4 + timestamp).            |
+-----------------------------------------------------------------------------------+
                 │
                 │ 2. Stream Upload via Storage Driver Interface
                 ▼
+-----------------------------------------------------------------------------------+
| 2. PLUGGABLE STORAGE DRIVER (Adapter Pattern)                                     |
|    - Interface: `IFileStorageDriver { upload, getSignedUrl, delete }`             |
+-----------------------------------------------------------------------------------+
                 │
                 ├─────────────────────────────────┐
                 ▼ (Dev Environment)               ▼ (Staging / Production)
+----------------------------------+   +----------------------------------+
|   LOCAL DISK STORAGE DRIVER      |   |   MinIO / S3 COMPATIBLE DRIVER   |
|   `./storage/uploads/{uuid}.pdf` |   |   Private Bucket: `sigma-k-docs` |
+----------------------------------+   +----------------------------------+
```

---

## 2. Abstraksi Driver Penyimpanan (*Storage Driver Abstraction*)

Backend mengimplementasikan *Driver Pattern* sehingga target penyimpanan dapat diganti melalui konfigurasi `.env` tanpa mengubah kode aplikasi:

```typescript
export interface IFileStorageDriver {
  uploadFile(fileBuffer: Buffer, fileName: string, mimeType: string, folder: string): Promise<string>;
  getSignedDownloadUrl(filePath: string, expiresInSeconds: number): Promise<string>;
  deleteFile(filePath: string): Promise<boolean>;
}
```

1. **Local Disk Storage Driver:** Digunakan untuk kemudahan *local development* dan pengujian unit tanpa perlu menginstal server MinIO terpisah.
2. **MinIO / S3-Compatible Driver:** Digunakan pada lingkungan Staging dan Produksi di Pusat Data Nasional (PDN) atau server KemenPANRB.

---

## 3. Keamanan Akses Berkas (Private Buckets & Signed URLs)

- **Private by Default:** Seluruh berkas regulasi berstatus *Private*. Berkas tidak dapat diakses langsung melalui public URL statis untuk mencegah akses anonim tanpa otentikasi.
- **Time-Limited Signed URL:** Saat pengguna (Verifikator/Admin/User) ingin melihat atau mengunduh dokumen, backend menerbitkan URL bertanda tangan kriptografis (*Pre-Signed URL*) dengan masa kedaluwarsa singkat (misal: 15 menit).
