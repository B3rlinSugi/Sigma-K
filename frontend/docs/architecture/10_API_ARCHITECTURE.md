# 10. API ARCHITECTURE & CONTRACT SPECIFICATIONS: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  
> **Catatan:** Dokumen ini mendefinisikan **arsitektur kontrak RESTful API v1** secara komprehensif. **TIDAK** ada penulisan source code implementasi controller pada tahap ini.

---

## 1. Konvensi & Standar Desain REST API

1. **API Versioning:** Seluruh endpoint layanan menggunakan prefix path `/api/v1/`.
2. **Resource Naming:** Menggunakan kata benda jamak (*plural nouns*) dalam format *kebab-case* (misal: `/api/v1/institutions`, `/api/v1/cabinet-periods`, `/api/v1/organization-units`).
3. **HTTP Verbs Standard:**
   - `GET` : Membaca data (Idempotent, Safe).
   - `POST` : Membuat resource baru atau memicu aksi perintah (*action commands*).
   - `PUT` : Mengganti seluruh resource secara menyeluruh.
   - `PATCH` : Memperbarui sebagian atribut (*partial update*) atau mengubah status.
   - `DELETE` : Menandai nonaktif / soft delete resource.
4. **Standard HTTP Status Codes:**
   - `200 OK` (Operasi baca/update sukses).
   - `201 Created` (Resource baru berhasil dibuat).
   - `400 Bad Request` (Validasi input gagal).
   - `401 Unauthorized` (Token otentikasi tidak valid atau expired).
   - `403 Forbidden` (Izin peran tidak cukup atau di luar scope instansi).
   - `404 Not Found` (Resource tidak ditemukan).
   - `409 Conflict` (Duplikasi data unik / circular dependency detected).
   - `422 Unprocessable Entity` (Pelanggaran logika bisnis/state machine).
   - `500 Internal Server Error` (Kesalahan internal server).

---

## 2. Standar Paginasi, Filter, & Pencarian

Seluruh endpoint katalog mendukung parameter query terstandarisasi:
- `?page=1&limit=20` (Paginasi halaman).
- `?search=Kementerian` (Pencarian kata kunci teks).
- `?type=PUSAT_KEMENTERIAN` (Filter jenis instansi).
- `?sortBy=name&sortOrder=ASC` (Pengurutan data).

---

## 3. Sampel Kontrak API Kunci (API Contract Specifications)

### A. Domain: Authentication (`/api/v1/auth`)

#### 1. POST `/api/v1/auth/login`
- **Purpose:** Masuk ke dalam sistem dan menerbitkan token otentikasi.
- **Request Body:**
```json
{
  "username": "operator.pangan",
  "password": "Password123!"
}
```
- **Response (200 OK):**
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Login berhasil",
  "data": {
    "accessToken": "eyJhbGciOiJIUzI1NiIsIn...",
    "user": {
      "id": "usr-8812",
      "username": "operator.pangan",
      "fullName": "Budi Santoso",
      "role": "USER",
      "institution": {
        "id": "inst-pangan-01",
        "code": "KL-042",
        "name": "Kementerian Koordinator Bidang Pangan"
      }
    }
  }
}
```

---

### B. Domain: Cabinets & Komposisi (`/api/v1/cabinets`)

#### 2. GET `/api/v1/cabinets/active/members`
- **Purpose:** Mengambil daftar 48+ kementerian/lembaga anggota Kabinet Merah Putih aktif.
- **Query Params:** `?category=KEMENKO` (opsional)
- **Response (200 OK):**
```json
{
  "success": true,
  "statusCode": 200,
  "data": {
    "cabinet": {
      "id": "cab-merah-putih-01",
      "name": "Kabinet Merah Putih",
      "presidentName": "Prabowo Subianto",
      "period": "2024-2029",
      "totalMembers": 48
    },
    "members": [
      {
        "membershipId": "mem-001",
        "institutionId": "inst-pangan-01",
        "code": "KL-042",
        "name": "Kementerian Koordinator Bidang Pangan",
        "category": "KEMENKO",
        "joinedDate": "2024-10-21",
        "status": "ACTIVE"
      }
    ]
  }
}
```

#### 3. GET `/api/v1/cabinets/compare?baseCabinetId={idA}&targetCabinetId={idB}`
- **Purpose:** Membandingkan delta komposisi instansi antara dua kabinet.
- **Response (200 OK):**
```json
{
  "success": true,
  "statusCode": 200,
  "data": {
    "summary": {
      "newInstitutionsCount": 7,
      "splitInstitutionsCount": 3,
      "mergedInstitutionsCount": 0,
      "renamedCount": 5
    },
    "transitions": [
      {
        "transitionType": "SPLIT",
        "predecessor": { "name": "Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi" },
        "successors": [
          { "name": "Kementerian Pendidikan Dasar dan Menengah" },
          { "name": "Kementerian Pendidikan Tinggi, Sains, dan Teknologi" },
          { "name": "Kementerian Kebudayaan" }
        ]
      }
    ]
  }
}
```

---

### C. Domain: Organization Hierarchy Tree (`/api/v1/institutions/:id/org-tree`)

#### 4. GET `/api/v1/institutions/:id/org-tree`
- **Purpose:** Mengambil seluruh pohon struktur hierarki unit kerja instansi.
- **Response (200 OK):**
```json
{
  "success": true,
  "statusCode": 200,
  "data": {
    "institutionId": "inst-pangan-01",
    "institutionName": "Kementerian Koordinator Bidang Pangan",
    "tree": {
      "id": "unit-01",
      "name": "Menteri Koordinator",
      "echelon": "Menteri",
      "children": [
        {
          "id": "unit-02",
          "name": "Sekretariat Kementerian Koordinator",
          "echelon": "Eselon I.a",
          "children": [
            {
              "id": "unit-03",
              "name": "Biro Perencanaan dan Kerja Sama",
              "echelon": "Eselon II.a",
              "children": []
            }
          ]
        }
      ]
    }
  }
}
```

---

### D. Domain: Workflow Tiket & Verifikasi (`/api/v1/submissions`)

#### 5. POST `/api/v1/submissions`
- **Purpose:** Operator mengajukan berkas perubahan data ke tim verifikator.
- **Request Body (Multipart Form):**
  - `institutionId`: `"inst-pangan-01"`
  - `submissionNotes`: `"Penyesuaian struktur biro sesuai Perpres No. 139/2024"`
  - `legalDocument`: `[file.pdf]`
- **Response (201 Created):**
```json
{
  "success": true,
  "statusCode": 201,
  "message": "Tiket pengajuan berhasil dikirim dan masuk antrean verifikasi",
  "data": {
    "ticketId": "tkt-8812",
    "ticketNumber": "TKT-20260825-0042",
    "status": "SUBMITTED",
    "submittedAt": "2026-08-25T20:20:00.000Z"
  }
}
```

#### 6. POST `/api/v1/verifications/:ticketId/decision`
- **Purpose:** Verifikator menetapkan keputusan peninjauan (`PASS`, `REVISION_REQUIRED`, `REJECTED`).
- **Request Body:**
```json
{
  "decision": "PASS",
  "notes": "Struktur unit dan dasar hukum Perpres telah diverifikasi sesuai dan lengkap."
}
```
- **Response (200 OK):**
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Keputusan verifikasi berhasil disimpan",
  "data": {
    "ticketId": "tkt-8812",
    "status": "VERIFIED",
    "verifiedAt": "2026-08-25T20:25:00.000Z"
  }
}
```
