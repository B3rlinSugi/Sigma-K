# E-SKLD BACKEND DOCUMENTATION — STEP 8
## SUBMISSION REVISION CYCLE & RESUBMISSION WORKFLOW

---

## 1. Objective

Step 8 mengimplementasikan siklus perbaikan usulan pada sisi pembuat usulan (*Author / USER*) setelah menerima catatan pengembalian dari Gerbang 1 (Admin) maupun Gerbang 2 (Verifikator), yang mencakup:
1. Inspeksi catatan revisi dan riwayat verifikasi (`GET /api/v1/submissions/{id}/revision`).
2. Pembuatan draf versi baru secara *branching* tanpa menimpa versi sebelumnya (`POST /api/v1/submissions/{id}/revision` $\rightarrow$ `v2`, `v3`, dst.).
3. Penyesuaian/perbaikan data usulan (*proposed units & positions*) pada draf versi aktif.
4. Pengajuan kembali versi yang telah diperbaiki (*Resubmission*) ke alur kerja peninjauan (`POST /api/v1/submissions/{id}/resubmit` $\rightarrow$ `RESUBMITTED`).
5. Penegakan wewenang *Zero-Trust Authorization* dan *Separation of Duties (SoD)*.
6. Proteksi konkurensi, transaksi atomik, dan pencatatan jejak audit (*Audit Trail*).

---

## 2. Revision Cycle & State Machine

```
              ┌────────────────────────────────┐
              │ REVISION_REQUIRED_BY_VERIFIER  │ (or REVISION_BY_ADMIN)
              └───────────────┬────────────────┘
                              │
                              │ 1. GET /submissions/{id}/revision
                              │ 2. POST /submissions/{id}/revision (Create v2 / v3)
                              ▼
                     [ USER CORRECTION ]
             (POST/PUT/DELETE /units & /positions)
                              │
                              │ 3. POST /submissions/{id}/resubmit
                              ▼
                    ┌───────────────────┐
                    │    RESUBMITTED    │ ───► [Return Gate: Admin / Verifier Review]
                    └───────────────────┘
```

---

## 3. Endpoints Created / Extended

| Method | Endpoint | Filter | Deskripsi & Otorisasi |
|---|---|:---:|---|
| `GET` | `/api/v1/submissions/{id}/revision` | `auth` | Mengambil detail inspeksi revisi, catatan verifikasi, daftar usulan terdampak, dan riwayat versi. Author / Reviewer. |
| `POST` | `/api/v1/submissions/{id}/revision` | `auth` | Menginisialisasi draf versi revisi baru (`v2`, `v3`) dengan *deep copy* dari versi sebelumnya. Izin: `EDIT`. |
| `POST` | `/api/v1/submissions/{id}/resubmit` | `auth` | Mengunci versi perbaikan dan mengajukan kembali usulan (`RESUBMITTED`). Izin: `SUBMIT`. |
| `POST/PUT/DELETE` | `/api/v1/submissions/{id}/units` | `auth` | Diperluas untuk mengizinkan modifikasi unit usulan pada status revisi aktif. |
| `POST/PUT/DELETE` | `/api/v1/submissions/{id}/positions` | `auth` | Diperluas untuk mengizinkan modifikasi jabatan usulan pada status revisi aktif. |

---

## 4. Versioning Model & Immutability Rules

1. **Anti-Overwrite Protection**: Versi yang telah diajukan (`submitted_at IS NOT NULL`) berstatus **IMMUTABLE** (tidak dapat dimodifikasi atau dihapus secara fisik).
2. **Branching Model**:
   - Pembuatan revisi baru menyalin seluruh unit usulan (*proposed units*) beserta relasi hierarkinya dan formasi jabatannya (*proposed positions*) dari versi terakhir ke versi baru (`version_number = N + 1`).
   - Versi baru berstatus draf (`submitted_at = NULL`) hingga diajukan kembali.
3. **Resubmission Finalization**: Saat endpoint `resubmit` dipanggil, versi draf diisi dengan timestamp `submitted_at = NOW()` dan dikunci secara permanen. Pengajuan kembali berikutnya memerlukan inisialisasi versi baru (`v3`, `v4`).

---

## 5. Separation of Duties (SoD) & Role Enforcement

1. **Ownership / Author Isolation**:
   - Hanya pembuat usulan asli (*Submission Author*) atau `SUPER_ADMIN` yang dapat melihat inspeksi revisi privat, membuat versi baru, memodifikasi unit/jabatan draf, dan mengajukan kembali (*resubmit*).
   - Pengguna `USER` lain yang mencoba mengakses atau memodifikasi ditolak seketika dengan HTTP `403 Forbidden`.
2. **Reviewer Prohibition**:
   - Verifikator maupun Admin tidak dapat mengubah data usulan pemohon secara langsung; perubahan draf hanya dapat dilakukan oleh pembuat usulan melalui siklus revisi.
3. **Master Data Protection**:
   - Seluruh mutasi hanya mempengaruhi tabel transaksi usulan (`submission_units`, `submission_positions`, `submission_versions`).
   - Data master instansi (`institutions`), unit master (`organizational_units`), dan jabatan master (`positions`) berstatus *read-only* dan tidak dapat dimodifikasi.

---

## 6. Concurrency Protection & Transaction Safety

1. **Anti-Resubmit Without New Version**: Percobaan pengajuan kembali tanpa membuat versi revisi baru (atau jika versi aktif telah diajukan) ditolak dengan HTTP `409 Conflict`.
2. **Anti-Premature Resubmission**: Percobaan pengajuan kembali dari status selain revisi (misalnya draf awal atau usulan yang sedang dalam antrean verifikasi) ditolak dengan HTTP `409 Conflict`.
3. **Double Resubmission Prevention**: Panggilan ganda pada `resubmit` dibungkus dengan transaksi atomik dan penguncian baris (`FOR UPDATE`), sehingga hanya panggilan pertama yang berhasil dan panggilan kedua ditolak dengan HTTP `409 Conflict`.

---

## 7. Master Data Immutability Verification

- Tidak ada mutasi pada tabel `organizational_units`, `positions`, `institutions`, maupun `users`.
- Seluruh data master tetap utuh dan berstatus *read-only*.

---

## 8. Test Coverage

- **Suite Uji Baru**: [`tests/unit/RevisionWorkflowTest.php`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/tests/unit/RevisionWorkflowTest.php) (15 test methods mencakup 22 skenario `REVISION-01` s/d `REVISION-22`).
- **Total Uji Terakumulasi**: **127 tests, 369 assertions, 0 errors, 0 failures (100% PASS)**.

---

## 9. Known Limitations & Open Decisions

- **OPEN DECISION**: Alur routing setelah `RESUBMITTED` (apakah selalu kembali ke Gate 1 Admin untuk re-skrining administrasi, atau langsung kembali ke Verifikator Gate 2 yang ditugaskan sebelumnya jika revisi berasal dari Gate 2). Saat ini status diubah menjadi `RESUBMITTED` yang dapat diakses oleh Admin maupun Verifikator sesuai konfigurasi kebijakan instansi.
