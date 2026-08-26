# SIGMA-K — REST API CONTRACT SPECIFICATION (v1)

> **Dokumen:** `03_API_CONTRACT.md`  
> **Status:** `API CONTRACT SPECIFICATION (PHASE 5A DESIGN - REVIEWED)`  
> **Base Path:** `/api/v1`  
> **Format Data:** `application/json` (UTF-8)  
> **Otentikasi:** `Authorization: Bearer <PROVISIONAL_JWT_TOKEN>` (Provisional Candidate, lihat OPEN-003)  

---

## 1. Standar Envelope Respons API

Seluruh endpoint REST API SIGMA-K menggunakan format pembungkus (*response envelope*) standar yang seragam:

### A. Format Sukses (*Standard Success Envelope*)
```json
{
  "success": true,
  "statusCode": 200,
  "message": "Operasi berhasil dieksekusi",
  "data": {},
  "meta": {
    "timestamp": "2026-08-25T14:30:00.000Z",
    "requestId": "req-9a8b7c-1234"
  }
}
```

### B. Format Sukses Berpaginasi (*Paginated Collection Envelope*)
```json
{
  "success": true,
  "statusCode": 200,
  "data": [],
  "meta": {
    "page": 1,
    "pageSize": 20,
    "total": 48,
    "totalPages": 3,
    "hasNextPage": true,
    "hasPreviousPage": false,
    "timestamp": "2026-08-25T14:30:00.000Z",
    "requestId": "req-9a8b7c-1234"
  }
}
```

---

## 2. Inventaris Lengkap Endpoint REST API v1

### A. Modul Otentikasi & Pengguna (`/api/v1/auth` & `/api/v1/users`)
> *Catatan: Skema JWT internal berikut merupakan kandidat arsitektur sementara (Provisional Architecture Candidate) sebelum penetapan IdP resmi KemenPANRB / SSO Nasional (OPEN-003).*

| Method | Endpoint | Deskripsi & Tujuan | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `POST` | `/api/v1/auth/login` | Masuk ke sistem dan memperoleh token sementara (Access & Refresh). | Publik |
| `POST` | `/api/v1/auth/refresh` | Memperbarui Access Token menggunakan Refresh Token valid. | Publik (dengan Refresh Token) |
| `POST` | `/api/v1/auth/logout` | Mengakhiri sesi dan mencabut (*revoke*) Refresh Token. | Authenticated |
| `GET` | `/api/v1/auth/me` | Mengambil data profil, role, dan scope instansi pengguna aktif. | Authenticated |
| `GET` | `/api/v1/users` | Mengambil daftar pengguna sistem.<br>**Query:** `?role=&institutionId=` | Authenticated |
| `GET` | `/api/v1/users/:id` | Mengambil detail profil satu pengguna. | Authenticated |

---

### B. Modul Master Instansi Pemerintah (`/api/v1/institutions`)

| Method | Endpoint | Deskripsi & Parameter | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `GET` | `/api/v1/institutions` | Mengambil katalog master K/L dan Pemda.<br>**Query:** `?page=1&pageSize=20&search=&type=KEMENKO&status=ACTIVE` | All Roles |
| `POST` | `/api/v1/institutions` | Mendaftarkan instansi kementerian/pemda baru. | ADMIN |
| `GET` | `/api/v1/institutions/:id` | Mengambil detail profil lengkap instansi, kontak, dan regulasi. | All Roles |
| `PATCH`| `/api/v1/institutions/:id` | Memperbarui profil, alamat, atau kontak resmi instansi. | ADMIN (atau USER scoped) |

---

### C. Modul Kabinet & Silsilah Transformasi (`/api/v1/cabinets`)

| Method | Endpoint | Deskripsi & Parameter | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `GET` | `/api/v1/cabinets` | Mengambil daftar era kabinet kepresidenan.<br>**Query:** `?search=&status=ACTIVE` | All Roles |
| `POST` | `/api/v1/cabinets` | Mendaftarkan era kabinet baru berbasis Keppres. | ADMIN |
| `GET` | `/api/v1/cabinets/:id` | Mengambil detail identitas kabinet dan masa jabatan. | All Roles |
| `PATCH`| `/api/v1/cabinets/:id` | Memperbarui keterangan atau masa jabatan kabinet. | ADMIN |
| `GET` | `/api/v1/cabinets/:id/memberships` | Mengambil daftar 48 K/L anggota kabinet tertentu.<br>**Query:** `?category=KEMENKO` | All Roles |
| `POST` | `/api/v1/cabinets/:id/memberships` | Menambahkan instansi ke dalam keanggotaan kabinet. | ADMIN |
| `GET` | `/api/v1/cabinets/compare` | **Komparasi Antar-Kabinet:** Menghitung delta perubahan silsilah (*Diff Engine*).<br>**Query:** `?baseCabinetId=cab-2019&targetCabinetId=cab-2024&type=SPLIT` | All Roles |
| `GET` | `/api/v1/cabinets/:id/lineage` | Mengambil pohon silsilah transformasi instansi pada era kabinet. | All Roles |

---

### D. Modul Struktur Organisasi (`/api/v1/organization-units` & `/api/v1/institutions/:id/units`)

| Method | Endpoint | Deskripsi & Parameter | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `GET` | `/api/v1/institutions/:id/units` | Mengambil pohon hierarki unit kerja instansi (Adjacency List). | All Roles |
| `GET` | `/api/v1/organization-units` | Mengambil daftar seluruh unit kerja dengan filter.<br>**Query:** `?search=&echelon=` | All Roles |
| `GET` | `/api/v1/organization-units/:id` | Mengambil detail satu unit kerja, pejabat pimpinan, dan alokasi staf. | All Roles |
| `POST` | `/api/v1/organization-units` | Menambahkan unit kerja struktural baru (*Validasi Anti-Circular*). | ADMIN |
| `PATCH`| `/api/v1/organization-units/:id` | Memperbarui nama unit, eselon, pejabat, atau memindahkan atasan (`parent_id`). | ADMIN |

---

### E. Modul Tugas dan Fungsi (`/api/v1/tupoksi`)

| Method | Endpoint | Deskripsi & Parameter | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `GET` | `/api/v1/tupoksi` | Mengambil katalog butir tugas dan fungsi.<br>**Query:** `?institutionId=&type=DUTY&search=` | All Roles |
| `GET` | `/api/v1/tupoksi/:id` | Mengambil rincian satu butir tugas/fungsi beserta rujukan pasal. | All Roles |
| `POST` | `/api/v1/tupoksi` | Menambahkan butir mandat tugas atau fungsi baru berdasar regulasi. | ADMIN |

---

### F. Modul Pengajuan Usulan Perubahan (`/api/v1/submissions`)

| Method | Endpoint | Deskripsi & Parameter | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `GET` | `/api/v1/submissions` | Mengambil daftar tiket usulan perubahan.<br>**Query:** `?status=IN_REVIEW&institutionId=&search=&page=1` | All Roles (Scoped) |
| `POST` | `/api/v1/submissions` | Membuat draf tiket usulan perubahan baru beserta delta items. | USER, ADMIN |
| `GET` | `/api/v1/submissions/:id` | Mengambil rincian tiket usulan, snapshot komparasi, dan riwayat telaah. | All Roles (Scoped) |
| `PATCH`| `/api/v1/submissions/:id` | Memperbarui catatan atau butir perubahan pada tiket berstatus `DRAFT`. | USER (Pemilik Tiket) |
| `POST` | `/api/v1/submissions/:id/submit` | Mengirimkan draf usulan baru (`DRAFT` $\rightarrow$ `SUBMITTED`). | USER (Pemilik Tiket) |
| `POST` | `/api/v1/submissions/:id/revision` | Menyimpan draf butir perbaikan revisi secara parsial. | USER (Pemilik Tiket) |
| `POST` | `/api/v1/submissions/:id/resubmit` | Mengirimkan kembali berkas perbaikan ke antrean telaah (`REVISION_REQUIRED` $\rightarrow$ `RESUBMITTED`). | USER (Pemilik Tiket) |
| `POST` | `/api/v1/submissions/:id/approve` | Mengesahkan usulan yang lolos verifikasi langsung ke Master Data aktif secara atomik (`VERIFIED` $\rightarrow$ `APPROVED`). | ADMIN |

---

### G. Modul Verifikasi & Telaah Kelembagaan (`/api/v1/verifications`)

| Method | Endpoint | Deskripsi & Parameter | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `GET` | `/api/v1/verifications` | Mengambil antrean berkas pengajuan masuk yang perlu ditelaah. | VERIFIKATOR, ADMIN, SESDEP |
| `GET` | `/api/v1/verifications/:id` | Mengambil ruang kerja telaah berdampingan (*Side-by-Side Review*). | VERIFIKATOR, ADMIN |
| `POST` | `/api/v1/verifications/:id/verify` | Menyetujui usulan (*Pass*) dengan catatan telaah resmi (`IN_REVIEW` $\rightarrow$ `VERIFIED`). | VERIFIKATOR, ADMIN |
| `POST` | `/api/v1/verifications/:id/request-revision` | Mengembalikan berkas ke operator dengan catatan poin perbaikan (`IN_REVIEW` $\rightarrow$ `REVISION_REQUIRED`). | VERIFIKATOR, ADMIN |
| `POST` | `/api/v1/verifications/:id/reject` | Menolak berkas usulan dengan alasan substantif resmi (`IN_REVIEW` $\rightarrow$ `REJECTED`). | VERIFIKATOR, ADMIN |

---

### H. Modul Konfigurasi Mesin Status Workflow (`/api/v1/workflow`)

| Method | Endpoint | Deskripsi & Parameter | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `GET` | `/api/v1/workflow/config` | Mengambil metadata konfigurasi alur kerja aktif (*Configurable State Machine Profile*). | All Roles |
| `GET` | `/api/v1/workflow/states` | Mengambil daftar seluruh status workflow yang valid. | All Roles |
| `GET` | `/api/v1/workflow/transitions` | Mengambil daftar transisi status yang diizinkan berdasarkan role pengguna aktif. | Authenticated |

---

### I. Modul Notifikasi Realtime (`/api/v1/notifications`)

| Method | Endpoint | Deskripsi & Parameter | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `GET` | `/api/v1/notifications` | Mengambil daftar notifikasi pengguna.<br>**Query:** `?category=WORKFLOW&isRead=false` | Authenticated |
| `PATCH`| `/api/v1/notifications/:id/read` | Menandai satu notifikasi telah dibaca. | Authenticated (Owner) |
| `PATCH`| `/api/v1/notifications/read-all` | Menandai seluruh notifikasi pengguna aktif telah dibaca. | Authenticated (Owner) |

---

### J. Modul Intelijensi Analitik Data (`/api/v1/analytics`)

| Method | Endpoint | Deskripsi & Parameter | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `GET` | `/api/v1/analytics/kpis` | Mengambil metrik Proposed KPIs SESDEP (delayering, kesiapan K/L, turnaround SLA). | All Roles |
| `GET` | `/api/v1/analytics/organization` | Mengambil sebaran postur formasi ASN (Menteri, Eselon I, II, Fungsional). | All Roles |
| `GET` | `/api/v1/analytics/cabinets` | Mengambil statistik komposisi kementerian pada kabinet aktif. | All Roles |
| `GET` | `/api/v1/analytics/submissions` | Mengambil metrik rata-rata durasi kecepatan penyelesaian verifikasi per jenis usulan. | All Roles |

---

### K. Modul Audit Trail Forensik (`/api/v1/audit-logs`)

| Method | Endpoint | Deskripsi & Parameter | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `GET` | `/api/v1/audit-logs` | Mengambil rekam jejak mutasi data tak-terhapuskan.<br>**Query:** `?search=&action=APPROVE&entity=SUBMISSION_TICKET&page=1` | ADMIN, SESDEP |
| `GET` | `/api/v1/audit-logs/:id` | Mengambil snapshot forensik lengkap (`old_values` vs `new_values` JSON). | ADMIN, SESDEP |

---

### L. Modul Pengelolaan Berkas Regulasi (`/api/v1/files`)

| Method | Endpoint | Deskripsi & Parameter | Akses Role / Scope |
| :---: | :--- | :--- | :--- |
| `POST` | `/api/v1/files` | Mengunggah salinan PDF dasar hukum regulasi (*Multipart Form Data*, maks 10MB). | USER, VERIFIKATOR, ADMIN |
| `GET` | `/api/v1/files/:id` | Mengunduh salinan berkas PDF atau gambar logo resmi instansi. | All Roles |
| `DELETE`| `/api/v1/files/:id` | Menghapus berkas draf yang belum disahkan. | ADMIN (atau Scoped Owner) |
