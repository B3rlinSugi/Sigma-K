# SIGMA-K — API ERROR CONTRACT & STATUS CODES SPECIFICATION

> **Dokumen:** `04_API_ERROR_CONTRACT.md`  
> **Status:** `ERROR SPECIFICATION (PHASE 5A DESIGN)`  
> **Standar Respons:** RFC 7807 (Problem Details for HTTP APIs Compatible)  

---

## 1. Standar Format Respons Kesalahan (Error Envelope)

Seluruh kesalahan yang terjadi di backend NestJS wajib ditransformasi secara konsisten oleh `HttpExceptionFilter` ke dalam format JSON berikut:

```json
{
  "success": false,
  "statusCode": 400,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validasi input data gagal diproses.",
    "details": [
      {
        "field": "legalDocDate",
        "issue": "Tanggal dokumen hukum tidak boleh di masa depan."
      }
    ]
  },
  "meta": {
    "timestamp": "2026-08-25T14:32:00.000Z",
    "requestId": "req-error-45a8-9921",
    "path": "/api/v1/institutions/inst-001"
  }
}
```

---

## 2. Katalog Kode Kesalahan Standar (*Error Code Catalog*)

| HTTP Status | Error Code (`error.code`) | Skenario Terjadinya Kesalahan | Contoh Pesan Pengguna (`message`) |
| :---: | :--- | :--- | :--- |
| **`400`** | `VALIDATION_ERROR` | Payload request gagal melewati class-validator DTO. | *"Format input tidak valid. Periksa kembali data yang dikirimkan."* |
| **`400`** | `INVALID_QUERY_PARAM` | Nilai parameter query di luar rentang yang diizinkan. | *"Parameter pageSize harus berada dalam rentang 1 hingga 100."* |
| **`401`** | `UNAUTHORIZED` | Header `Authorization` tidak ada, token kadaluarsa, atau tanda tangan JWT tidak sah. | *"Sesi masuk Anda telah berakhir. Silakan login kembali."* |
| **`403`** | `INSUFFICIENT_ROLE` | Peran pengguna tidak memiliki hak untuk memanggil endpoint. | *"Peran Anda tidak memiliki kewenangan untuk mengakses sumber daya ini."* |
| **`403`** | `INSTITUTION_SCOPE_VIOLATION` | Operator mencoba mengakses atau mengubah data instansi kementerian lain (*Anti-BOLA/IDOR*). | *"Anda hanya berhak mengelola data instansi kementerian yang Anda ampu."* |
| **`404`** | `RESOURCE_NOT_FOUND` | Data dengan ID yang diminta tidak ditemukan di basis data. | *"Instansi/Tiket dengan ID tersebut tidak ditemukan di sistem."* |
| **`409`** | `DUPLICATE_CODE` | Kode unik (kode kementerian, kode unit) sudah digunakan oleh entitas lain. | *"Kode instansi 'KL-007' sudah terdaftar dalam sistem."* |
| **`409`** | `CIRCULAR_DEPENDENCY_DETECTED` | Pemindahan `parent_id` unit organisasi menyebabkan relasi siklis atasan-bawahan melingkar. | *"Struktur hierarki tidak valid: Unit kerja tidak dapat menjadi bawahan dari bawahannya sendiri."* |
| **`422`** | `INVALID_STATE_TRANSITION` | Perubahan status alur kerja tidak diizinkan oleh State Machine. | *"Tiket berstatus 'APPROVED' tidak dapat diubah kembali menjadi 'DRAFT'."* |
| **`422`** | `MISSING_VERIFICATION_NOTE` | Verifikator meminta revisi atau menolak usulan tanpa menyertakan catatan telaah resmi. | *"Catatan telaah wajib diisi ketika meminta revisi atau menolak pengajuan."* |
| **`422`** | `INVALID_FILE_FORMAT` | Format berkas lampiran bukan PDF resmi atau melebihi batas ukuran 10 MB. | *"Berkas harus berformat PDF resmi dengan ukuran maksimal 10 MB."* |
| **`500`** | `INTERNAL_SERVER_ERROR` | Terjadi kesalahan runtime atau kegagalan koneksi database. | *"Terjadi kesalahan internal server. Silakan hubungi Administrator Sistem."* |

---

## 3. Pencegahan Kebocoran Informasi Sensitif (*Security Error Masking*)
Untuk memenuhi standar keamanan [BRULE-022](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md) dan arsitektur DevSecOps:
- Pada lingkungan produksi (*Production Environment*), detail *stack trace* internal Prisma ORM, struktur query SQL mentah, dan pesan *exception* mesin runtime **DI-MASKING SECARA MUTLAK** dan hanya dicatat ke log server internal terenkripsi.
- Klien hanya menerima kode kesalahan terstandarisasi dan pesan ramah pengguna (*safe sanitized error message*).
