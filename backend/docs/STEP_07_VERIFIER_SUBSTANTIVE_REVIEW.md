# E-SKLD BACKEND DOCUMENTATION — STEP 7
## GATE 2 VERIFIER SUBSTANTIVE REVIEW & REVISION WORKFLOW

---

## 1. Objective

Step 7 mengimplementasikan tahap verifikasi substantif Gerbang 2 (*Gate 2 Verifier Substantive Review*) setelah penugasan verifikator dari Gerbang 1, yang mencakup:
1. Antrean berkas penugasan Verifikator (`GET /api/v1/verifier/submissions/assigned`).
2. Mulai telaah substantif oleh Verifikator (`ASSIGNED_TO_VERIFIER` $\rightarrow$ `IN_REVIEW_BY_VERIFIER`).
3. Pencatatan catatan koreksi/revisi per-unit/per-jabatan pada draf versi aktif (`POST /api/v1/submissions/{id}/verifier-review/notes`).
4. Pengembalian usulan untuk revisi substantif (`IN_REVIEW_BY_VERIFIER` $\rightarrow$ `REVISION_REQUIRED_BY_VERIFIER`).
5. Penegakan wewenang *Zero-Trust Authorization* dan *Separation of Duties (SoD)*.
6. Proteksi konkurensi, transaksi atomik, dan pencatatan jejak audit (*Audit Trail*).

---

## 2. Gate 2 Workflow & State Machine

```
              [ GATE 1 ADMIN ASSIGNED ]
                         │
                         ▼
             ┌───────────────────────┐
             │ ASSIGNED_TO_VERIFIER  │ ───► [Verifier Queue: GET /verifier/submissions/assigned]
             └───────────┬───────────┘
                         │
                         │ (POST /verifier-review/start)
                         ▼
             ┌───────────────────────┐
             │ IN_REVIEW_BY_VERIFIER │ ───► [Add Notes: POST /verifier-review/notes]
             └───────────┬───────────┘
                         │
                         │ (POST /verifier-review/return + reason)
                         ▼
     ┌────────────────────────────────┐
     │ REVISION_REQUIRED_BY_VERIFIER  │
     └────────────────────────────────┘
```

---

## 3. Endpoints Created

| Method | Endpoint | Filter | Deskripsi & Otorisasi |
|---|---|:---:|---|
| `GET` | `/api/v1/verifier/submissions/assigned` | `auth` | Mengambil daftar usulan yang ditugaskan khusus kepada Verifikator terotentikasi. Role: `VERIFIER` aktif. |
| `POST` | `/api/v1/submissions/{id}/verifier-review/start` | `auth` | Memulai telaah substantif (`ASSIGNED_TO_VERIFIER` $\rightarrow$ `IN_REVIEW_BY_VERIFIER`). Izin: `REVIEW`. |
| `POST` | `/api/v1/submissions/{id}/verifier-review/notes` | `auth` | Mencatat catatan telaah substantif (per unit/umum) ke `revision_notes`. Izin: `REVIEW`. |
| `POST` | `/api/v1/submissions/{id}/verifier-review/return` | `auth` | Mengembalikan berkas untuk revisi substantif (`REVISION_REQUIRED_BY_VERIFIER`). Izin: `RETURN_REVISION`. |

---

## 4. Separation of Duties (SoD) & Role Enforcement

1. **Anti-Self-Verification**:
   - Pembuat usulan (*Submission Author*) dilarang keras memulai telaah, mencatat catatan revisi, atau mengembalikan berkasnya sendiri sebagai Verifikator.
   - Pelanggaran ditolak secara otomatis dengan HTTP `403 Forbidden` (`SOD_AUTHOR_CANNOT_VERIFY`).
2. **Assignment Isolation**:
   - Verifikator hanya berhak meninjau berkas yang secara aktif ditugaskan kepadanya di tabel `verifier_assignments`.
   - Percobaan penelaahan oleh Verifikator lain ditolak dengan HTTP `403 Forbidden` (`WRONG_VERIFIER`).
3. **Role & Status Enforcement**:
   - Hanya pengguna dengan peran `VERIFIER` (atau `SUPER_ADMIN`) dengan status `ACTIVE` yang dapat mengakses antrean dan melakukan telaah. Pengguna dengan peran `USER` atau `ADMIN` ditolak dengan HTTP `403 Forbidden`.

---

## 5. Substantive Revision Notes Data Model

Catatan revisi memanfaatkan tabel eksisting `revision_notes` dan `verification_records`:
- `verification_records`: Mencatat rekaman telaah Gate 2 (`gate_level = 'GATE_2'`, `verification_result = 'IN_REVIEW'` atau `'RETURNED_FOR_REVISION'`).
- `revision_notes`: Menghubungkan rekaman verifikasi dengan unit usulan tertentu (`version_unit_id`, opsional) beserta deskripsi permasalahan (`issue_description`) dan status penyelesaian (`is_resolved = 0`).

---

## 6. Concurrency Protection & Transaction Safety

1. **Anti-Double-Start**: Percobaan memulai telaah ulang pada berkas yang sudah dalam telaah (`IN_REVIEW_BY_VERIFIER`) ditolak dengan HTTP `409 Conflict`.
2. **Anti-Premature Return**: Percobaan mengembalikan berkas langsung dari status `ASSIGNED_TO_VERIFIER` tanpa memulai telaah terlebih dahulu ditolak dengan HTTP `409 Conflict`.
3. **Anti-Race Condition**: Seluruh mutasi dibungkus dalam transaksi atomik (`transBegin` / `transCommit` / `transRollback`) dengan penguncian baris (`FOR UPDATE`) untuk mencegah *race condition*.

---

## 7. Master Data Immutability Verification

- Tidak ada mutasi pada tabel `organizational_units`, `positions`, `institutions`, maupun `users`.
- Seluruh data master tetap utuh dan berstatus *read-only*.

---

## 8. Test Coverage

- **Suite Uji Baru**: [`tests/unit/VerifierWorkflowTest.php`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/tests/unit/VerifierWorkflowTest.php) (21 test methods mencakup 26 skenario `VERIFIER-01` s/d `VERIFIER-26`).
- **Total Uji Terakumulasi**: **112 tests, 331 assertions, 0 errors, 0 failures (100% PASS)**.

---

## 9. Known Limitations & Open Decisions

- **OPEN DECISION**: Alur kelulusan substantif Gate 2 (misalnya rekomendasi persetujuan / verifikasi substantif tuntas menuju keputusan final KemenPANRB) belum diimplementasikan pada Step 7 sesuai batasan instruksi dan siap dimatangkan pada Step berikutnya.
