# E-SKLD BACKEND DOCUMENTATION — STEP 5
## CORE SUBMISSION DRAFTING & VERSIONING SNAPSHOT

---

## 1. Objective

Step 5 mengimplementasikan domain inti penyusunan usulan penataan kelembagaan (*Core Submission Drafting*), manajemen usulan perubahan unit organisasi (*Proposed Unit Changes*) dan jabatan (*Proposed Position Changes*), pembuatan snapshot versi *immutable* (*Submission Versions*), serta penguncian draf saat diajukan ke gerbang verifikasi Admin (*Submit to Admin Gate*).

---

## 2. Submission Lifecycle (Step 5 Scope)

```
       [ USER ]
          │
          ▼
   POST /submissions
          │
          ▼
     ┌─────────┐
     │  DRAFT  │◄─── [ADD / UPDATE / DELETE Unit Proposals]
     └────┬────┘◄─── [ADD / UPDATE / DELETE Position Proposals]
          │     ◄─── [POST /versions -> Snapshot Version++]
          │
          ▼  POST /submissions/{id}/submit
┌─────────────────────┐
│ SUBMITTED_TO_ADMIN  │ ───► [LOCKED: Mutation Rejected with 409 Conflict]
└─────────────────────┘
```

---

## 3. Endpoints Created

| Method | Endpoint | Filter | Deskripsi & Otorisasi |
|---|---|:---:|---|
| `POST` | `/api/v1/submissions` | `auth` | Membuat usulan kelembagaan baru dalam status `DRAFT` dan menginisialisasi Versi 1. Evaluasi izin `CREATE` + scope instansi. |
| `GET` | `/api/v1/submissions` | `auth` | Mengambil daftar usulan yang dapat diakses pengguna terotentikasi (scope-aware pagination). |
| `GET` | `/api/v1/submissions/{id}` | `auth` | Mengambil detail lengkap usulan, unit usulan, jabatan usulan, dan riwayat snapshot versi. Evaluasi izin `VIEW`. |
| `POST` | `/api/v1/submissions/{id}/submit` | `auth` | Mengajukan draf usulan ke gerbang verifikasi Admin (`DRAFT` $\rightarrow$ `SUBMITTED_TO_ADMIN`). Evaluasi izin `SUBMIT`. |
| `POST` | `/api/v1/submissions/{id}/versions` | `auth` | Membuat snapshot versi *deep copy* baru yang independen dan *immutable*. Evaluasi izin `EDIT` dalam state `DRAFT`. |
| `POST` | `/api/v1/submissions/{id}/units` | `auth` | Menambahkan usulan perubahan unit (`NEW`, `UPDATE`, `DELETE`, `UNCHANGED`) pada versi aktif draf. |
| `PUT` | `/api/v1/submissions/{id}/units/{unitId}` | `auth` | Memperbarui usulan perubahan unit pada status `DRAFT`. |
| `DELETE` | `/api/v1/submissions/{id}/units/{unitId}` | `auth` | Menghapus usulan perubahan unit pada status `DRAFT`. |
| `POST` | `/api/v1/submissions/{id}/positions` | `auth` | Menambahkan usulan perubahan jabatan (`NEW`, `UPDATE`, `DELETE`, `UNCHANGED`) pada unit versi aktif draf. |
| `PUT` | `/api/v1/submissions/{id}/positions/{posId}` | `auth` | Memperbarui usulan perubahan jabatan pada status `DRAFT`. |
| `DELETE` | `/api/v1/submissions/{id}/positions/{posId}` | `auth` | Menghapus usulan perubahan jabatan pada status `DRAFT`. |

---

## 4. Draft Mutation & Locking Rules

1. **State `DRAFT`**:
   - Pengguna berwenang diperbolehkan menambah, mengubah, menghapus usulan unit dan jabatan.
   - Pengguna diperbolehkan membuat snapshot versi baru (`version_number++`).
2. **State `SUBMITTED_TO_ADMIN` (Locked)**:
   - Segala bentuk mutasi draf (tambah/ubah/hapus unit atau jabatan, pembuatan snapshot versi, submit ulang) **DITOLAK SECARA DEFENSIVE** dengan kode HTTP `409 Conflict`.
   - Menjamin integritas data saat berkas sedang berada dalam antrean peninjauan Admin (Gate 1).

---

## 5. Master Data Immutability & Proposal Separation

- **Data Master Organisasi (`organizational_units`, `positions`, `institutions`)**: **TIDAK DIUBAH SAMA SEKALI** oleh operasi penyusunan draf.
- Data usulan disimpan secara eksklusif pada tabel proposal:
  - `submissions` (`current_state = 'DRAFT' | 'SUBMITTED_TO_ADMIN'`)
  - `submission_versions` (`version_number`, `submitted_at`, `notes`)
  - `submission_units` (`version_id`, `change_type`, `temp_parent_id`, `source_unit_id`)
  - `submission_positions` (`version_unit_id`, `change_type`, `source_position_id`)
- Penerapan perubahan ke tabel master data hanya akan terjadi pada tahap *Final Approval* (Gate 2) di tahapan rilis mendatang.

---

## 6. Version Snapshot Algorithm (Deep Copy)

Snapshot versi dibuat melalui [`SubmissionVersionService::createSnapshot()`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/app/Services/Submission/SubmissionVersionService.php) secara atomik dalam transaksi basis data:
1. Membaca versi aktif tertinggi dan menghitung `next_version = current_version + 1`.
2. Menyisipkan rekaman header baru pada `submission_versions`.
3. Melakukan *deep copy* seluruh rekaman `submission_units` dengan pemetaan ID lama $\rightarrow$ ID baru guna memastikan relasi hierarki `temp_parent_id` terikat akurat pada simpul versi baru.
4. Melakukan *deep copy* seluruh rekaman `submission_positions` yang terikat pada `version_unit_id` baru.
5. Mencatat audit event `CREATE_SUBMISSION_VERSION`.

---

## 7. Zero-Trust Authorization & Anti-BOLA/IDOR

- Validasi wewenang dieksekusi secara ketat melalui `AuthorizationService::can($user, $permission, $institutionId, $state)`.
- Akses lintas instansi tanpa *Access Grant* aktif otomatis ditolak dengan `403 Forbidden`.
- Referensi entitas silang instansi (*cross-institution references* pada `source_unit_id` atau `source_position_id`) divalidasi dan ditolak dengan `422 Unprocessable Entity`.

---

## 8. Concurrency & Transaction Safety

- **Proteksi Double-Submit**: Transaksi memeriksa status `current_state === 'DRAFT'`. Jika terdapat request konkuren yang mencoba submit berkas yang sama, request kedua akan mendeteksi status telah berubah dan menolaknya dengan `409 Conflict`.
- **Atomic Rollback**: Jika terjadi kesalahan validasi data atau anomali basis data, seluruh operasi di-rollback secara utuh tanpa meninggalkan rekam jejak kotor (*dirty records*).

---

## 9. Test Suite Verification

- **Suite Uji Baru**: [`tests/unit/SubmissionDraftingTest.php`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/tests/unit/SubmissionDraftingTest.php) (15 methods mencakup 26 skenario `SUB-01` s/d `SUB-26`).
- **Total Uji Terakumulasi**: **70 tests, 231 assertions, 0 errors, 0 failures (100% PASS)**.
