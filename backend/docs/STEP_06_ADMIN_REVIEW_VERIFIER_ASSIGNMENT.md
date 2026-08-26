# E-SKLD BACKEND DOCUMENTATION — STEP 6
## GATE 1 ADMIN REVIEW & VERIFIER ASSIGNMENT WORKFLOW

---

## 1. Objective

Step 6 mengimplementasikan alur kerja peninjauan administratif Gerbang 1 (*Gate 1 Admin Review*) dan penugasan Verifikator (*Verifier Assignment*) yang mencakup:
1. Antrean peninjauan Admin berbasis wewenang wilayah (*Scope-Aware Admin Queue*).
2. Penerimaan berkas untuk ditelaah administratif (`SUBMITTED_TO_ADMIN` $\rightarrow$ `IN_REVIEW_BY_ADMIN`).
3. Pengembalian berkas untuk revisi kelengkapan (`SUBMITTED_TO_ADMIN` / `IN_REVIEW_BY_ADMIN` $\rightarrow$ `REVISION_REQUIRED`).
4. Penugasan Verifikator substantif Gerbang 2 (`IN_REVIEW_BY_ADMIN` $\rightarrow$ `ASSIGNED_TO_VERIFIER`).
5. Penegakan Prinsip Pemisahan Tugas (*Separation of Duties - SoD*).
6. Proteksi konkurensi dan pencatatan jejak audit (*Audit Trail*).

---

## 2. Gate 1 Workflow & State Machine

```
              [ USER ]
                 │ (POST /submit)
                 ▼
     ┌───────────────────────┐
     │  SUBMITTED_TO_ADMIN   │ ───► [Admin Queue: GET /admin/submissions/queue]
     └───────────┬───────────┘
                 │
                 ├── (POST /admin-review/return + reason)
                 │    ▼
                 │ ┌───────────────────────┐
                 │ │   REVISION_REQUIRED   │
                 │ └───────────────────────┘
                 │
                 │ (POST /admin-review/accept)
                 ▼
     ┌───────────────────────┐
     │  IN_REVIEW_BY_ADMIN   │
     └───────────┬───────────┘
                 │
                 ├── (POST /admin-review/return + reason)
                 │    ▼
                 │ ┌───────────────────────┐
                 │ │   REVISION_REQUIRED   │
                 │ └───────────────────────┘
                 │
                 │ (POST /assign-verifier + verifier_id)
                 ▼
     ┌───────────────────────┐
     │ ASSIGNED_TO_VERIFIER  │ ───► [Gate 1 Selesai -> Siap untuk Verifikator Gate 2]
     └───────────────────────┘
```

---

## 3. Endpoints Created

| Method | Endpoint | Filter | Deskripsi & Otorisasi |
|---|---|:---:|---|
| `GET` | `/api/v1/admin/submissions/queue` | `auth` | Mengambil antrean berkas status `SUBMITTED_TO_ADMIN` dalam scope instansi Admin/SuperAdmin. |
| `POST` | `/api/v1/submissions/{id}/admin-review/accept` | `auth` | Menerima berkas untuk telaah Gate 1 (`SUBMITTED_TO_ADMIN` $\rightarrow$ `IN_REVIEW_BY_ADMIN`). Izin: `REVIEW`. |
| `POST` | `/api/v1/submissions/{id}/admin-review/return` | `auth` | Mengembalikan berkas untuk revisi (`REVISION_REQUIRED`) dengan catatan wajib. Izin: `RETURN_REVISION`. |
| `POST` | `/api/v1/submissions/{id}/assign-verifier` | `auth` | Menugaskan Verifikator yang valid (`ASSIGNED_TO_VERIFIER`). Izin: `ASSIGN_VERIFIER`. |

---

## 4. Separation of Duties (SoD) Enforcement

1. **Anti-Self-Review**:
   - Pembuat usulan (*Submission Author*) dilarang keras meninjau, menerima, atau mengembalikan berkasnya sendiri sebagai Admin.
   - Pelanggaran ditolak secara otomatis dengan HTTP `403 Forbidden` (`SOD_AUTHOR_CANNOT_REVIEW`).
2. **Anti-Self-Verification**:
   - Pembuat usulan dilarang ditugaskan sebagai Verifikator berkas tersebut.
   - Pelanggaran ditolak dengan HTTP `403 Forbidden` (`SOD_AUTHOR_CANNOT_BE_VERIFIER`).
3. **Role & Status Eligibility**:
   - Target verifikator wajib memiliki role `VERIFIER` aktif. Penugasan pengguna non-verifier atau inaktif ditolak dengan HTTP `422 Unprocessable Entity`.

---

## 5. Scope-Aware Queue Filtering

- `SUPER_ADMIN`: Melihat seluruh berkas berstatus `SUBMITTED_TO_ADMIN` secara global lintas instansi.
- `ADMIN`: Hanya melihat berkas pada instansi yang tercakup dalam `home_institution_id`, `user_scopes` aktif, atau `access_grants` aktif.
- `USER` / `VERIFIER`: Ditolak seketika dengan `403 Forbidden`.

---

## 6. Concurrency Protection & Transaction Safety

1. **Anti-Double-Acceptance**: Jika dua Admin mencoba menerima berkas bersamaan, permintaan kedua ditolak dengan `409 Conflict`.
2. **Anti-Race Return vs Accept**: Berkas yang telah dikembalikan tidak dapat diterima untuk ditelaah (menghasilkan `409 Conflict`).
3. **Anti-Double-Assignment**: Berkas yang telah ditugaskan ke verifikator terkunci dari penugasan ulang (`409 Conflict`).
4. **Atomic Transactions**: Seluruh transisi status, pencatatan `verification_records`, `revision_notes`, `verifier_assignments`, dan `audit_logs` dibungkus dalam transaksi basis data atomik dengan *rollback* otomatis jika terjadi kegagalan.

---

## 7. Master Data Immutability Verification

- Tidak ada mutasi pada tabel `organizational_units`, `positions`, `institutions`, maupun `users`.
- Seluruh data master tetap utuh dan stabil.

---

## 8. Test Coverage

- **Suite Uji Baru**: [`tests/unit/AdminWorkflowTest.php`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/tests/unit/AdminWorkflowTest.php) (21 test methods mencakup 25 skenario `ADMIN-01` s/d `ADMIN-25`).
- **Total Uji Terakumulasi**: **91 tests, 277 assertions, 0 errors, 0 failures (100% PASS)**.
