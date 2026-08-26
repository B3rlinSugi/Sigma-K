# E-SKLD FUNCTIONAL TEST REPORT

- **Database Engine**: MySQL 8.x / MariaDB 10.4 (InnoDB)
- **Database Name**: `eskld_db`
- **Execution Date**: 2026-08-24 UTC
- **Test Script**: `database/sql/04_functional_test.sql`

---

## 1. Summary

| Metric | Count | Percentage | Status |
|---|:---:|:---:|:---:|
| **Total Test Cases** | **34** | 100% | - |
| **Passed** | **34** | 100% | **PASS** |
| **Failed** | **0** | 0% | - |
| **Blocked** | **0** | 0% | - |

---

## 2. Workflow Test (TC-01 .. TC-16)

| Test Code | Title | Expected Result | Actual Result | Status | Verification Details |
|---|---|---|---|:---:|---|
| **TC-01** | USER membuat submission baru | `DRAFT` | `DRAFT` | **PASS** | Inisiasi berkas pengajuan baru dengan header versi 1 dan draf unit. |
| **TC-02** | USER mengedit submission DRAFT | `Sekretariat Utama Test (Revisi Draf)` | `Sekretariat Utama Test (Revisi Draf)` | **PASS** | Pembuat usulan dapat memutakhirkan unit draf selama status DRAFT. |
| **TC-03** | USER submit submission ke Gate 1 | `SUBMITTED_TO_ADMIN` | `SUBMITTED_TO_ADMIN` | **PASS** | Berkas masuk antrean Gate 1 dan timestamp `submitted_at` tercatat. |
| **TC-04** | ADMIN melakukan review Gate 1 | `ADMIN_REVIEW` | `ADMIN_REVIEW` | **PASS** | Status alur berpindah ke pemeriksaan dan log `verification_records` terbuat. |
| **TC-05** | ADMIN kembalikan submission revisi | `REVISION_BY_ADMIN` | `REVISION_BY_ADMIN` | **PASS** | Catatan koreksi dicatat di `revision_notes` dan status dialihkan ke User. |
| **TC-06** | USER perbaiki & resolve issue | `1` (Resolved) | `1` | **PASS** | User memperbarui draf unit dan menandai `is_resolved = 1`. |
| **TC-07** | USER submit ulang setelah perbaikan | `SUBMITTED_TO_ADMIN` | `SUBMITTED_TO_ADMIN` | **PASS** | Berkas diajukan ulang ke antrean Admin Gate 1. |
| **TC-08** | ADMIN meloloskan Gate 1 | `ADMIN_PASSED` | `ADMIN_PASSED` | **PASS** | Admin menerbitkan verifikasi `PASSED` pada Gate 1. |
| **TC-09** | ADMIN assign ke VERIFIER | `ASSIGNED` | `ASSIGNED` | **PASS** | Rekaman penugasan dibuat di `verifier_assignments` untuk Verifikator resmi. |
| **TC-10** | VERIFIER mulai telaah Gate 2 | `VERIFIER_REVIEW` | `VERIFIER_REVIEW` | **PASS** | Status alur berpindah ke Gate 2 dan penugasan menjadi `IN_REVIEW`. |
| **TC-11** | VERIFIER kembalikan revisi | `REVISION_BY_VERIFIER` | `REVISION_BY_VERIFIER` | **PASS** | Catatan koreksi Gate 2 dicatat dan status dialihkan ke meja Admin. |
| **TC-12** | User perbaiki via Admin & resubmit | `SUBMITTED_TO_ADMIN` | `SUBMITTED_TO_ADMIN` | **PASS** | Alur berjenjang `Verifier -> Admin -> User -> Admin -> Verifier` ditaati. |
| **TC-13** | ADMIN review ulang & loloskan | `ADMIN_PASSED` | `ADMIN_PASSED` | **PASS** | Admin Gate 1 menelaah kelengkapan perbaikan dan meloloskan ke Gate 2. |
| **TC-14** | VERIFIER final approve & promote | `APPROVED (1 Approval Record)` | `APPROVED (1 Approval Record)` | **PASS** | SK persetujuan dicatat di `approval_records` dan snapshot dipromosikan ke active master. |
| **TC-15** | USER coba EDIT APPROVED | `DENIED (0 Allowed)` | `DENIED (0 Allowed)` | **PASS** | State-aware engine mengunci berkas APPROVED dari modifikasi. |
| **TC-16** | USER coba DELETE APPROVED | `DENIED (FK 1451 Restricted)` | `DENIED (FK 1451 Restricted)` | **PASS** | Foreign Key `ON DELETE RESTRICT` pada `approval_records` memblokir hard delete di level basis data. |

---

## 3. Access Grant Test (TC-17 .. TC-22)

| Test Code | Title | Expected Result | Actual Result | Status | Verification Details |
|---|---|---|---|:---:|---|
| **TC-17** | User A VIEW Instansi B tanpa grant | `DENIED (0)` | `DENIED (0)` | **PASS** | Akses lintas instansi diblokir total (*Zero-Trust Deny by Default*). |
| **TC-18** | Grant VIEW Instansi B (VIEW only) | `VIEW: 1, EDIT: 0` | `VIEW: 1, EDIT: 0` | **PASS** | Izin atomik VIEW diizinkan, sedangkan EDIT strictly diblokir. |
| **TC-19** | Grant VIEW + EDIT Instansi B | `VIEW: 1, EDIT: 1` | `VIEW: 1, EDIT: 1` | **PASS** | Multi-izin atomik terbukti sah dievaluasi secara dinamis. |
| **TC-20** | Grant melewati End Date (Expired) | `DENIED (0)` | `DENIED (0)` | **PASS** | Filter `CURRENT_DATE BETWEEN start_date AND end_date` otomatis menolak grant kadaluarsa. |
| **TC-21** | Admin REVOKE grant aktif | `Status: REVOKED, Access: 0` | `Status: REVOKED, Access: 0` | **PASS** | Akses dicabut seketika tanpa menghapus fisik riwayat grant. |
| **TC-22** | User/Admin coba self-grant | `DENIED (Check Constraint 4025)` | `DENIED (Check Constraint 4025)` | **PASS** | MySQL 8 CHECK `chk_access_grants_no_self_grant (granted_by != user_id)` memblokir self-grant. |

---

## 4. Role Security & RBAC Test (TC-23 .. TC-29)

| Test Code | Title | Expected Result | Actual Result | Status | Verification Details |
|---|---|---|---|:---:|---|
| **TC-23** | USER mencoba hak `VERIFY` | `DENIED (0)` | `DENIED (0)` | **PASS** | Role USER tidak memiliki permission `VERIFY`. |
| **TC-24** | USER mencoba hak `APPROVE` | `DENIED (0)` | `DENIED (0)` | **PASS** | Role USER tidak memiliki permission `APPROVE`. |
| **TC-25** | ADMIN mencoba hak `VERIFY` | `DENIED (0)` | `DENIED (0)` | **PASS** | Role ADMIN terisolasi dari hak `VERIFY` (Gate 1 separation). |
| **TC-26** | ADMIN mencoba hak `APPROVE` | `DENIED (0)` | `DENIED (0)` | **PASS** | Role ADMIN dilarang menerbitkan persetujuan akhir. |
| **TC-27** | VERIFIER melakukan `VERIFY` | `ALLOWED (1)` | `ALLOWED (1)` | **PASS** | Role VERIFIER memiliki wewenang telaah teknis substansi. |
| **TC-28** | VERIFIER melakukan `APPROVE` | `ALLOWED (1)` | `ALLOWED (1)` | **PASS** | Role VERIFIER memiliki wewenang eksklusif final persetujuan. |
| **TC-29** | SUPER_ADMIN mencoba `APPROVE` | `DENIED (0)` | `DENIED (0)` | **PASS** | SUPER_ADMIN terisolasi dari business approver (Separation of Duties). |

---

## 5. Audit Trail Verification

| Test Code | Title | Expected Result | Actual Result | Status | Verification Details |
|---|---|---|---|:---:|---|
| **TC-AUDIT** | Pencatatan Audit Trail Events | `Events >= 3 Recorded` | `3 Unique Events Recorded` | **PASS** | Mutasi data (`DRAFT_CREATED`, `SUBMITTED`, `FINAL_APPROVED`) tercatat lengkap dengan aktor, entity, JSON payload, dan UTC timestamp. |

---

## 6. Hierarchy Business Rules Test

| Test Code | Title | Expected Result | Actual Result | Status | Verification Details |
|---|---|---|---|:---:|---|
| **TC-H1** | Pencegahan `parent_unit_id = id` | `DENIED` | `DENIED` | **PASS** | Aturan anti self-reference tervalidasi pada service layer model. |
| **TC-H2** | Parent unit tidak eksis di DB | `DENIED (FK 1452 Constraint)` | `DENIED (FK 1452 Constraint)` | **PASS** | Foreign Key `fk_org_units_parent` memblokir parent ID yang tidak valid. |
| **TC-H3** | Parent dari instansi berbeda | `0 Violations` | `0 Violations` | **PASS** | Pohon organisasi terisolasi murni dalam instansi pemilik. |
| **TC-H4** | Pencegahan Circular Hierarchy | `DENIED` | `DENIED` | **PASS** | Deteksi siklus pohon (*Cycle Detection*) tervalidasi. |

---

## 7. Failed / Blocked Cases

> **TIDAK ADA KASUS GAGAL ATAU TERBLOKIR (0 Failed, 0 Blocked)**.

---

## 8. Final Decision

# 🟢 **READY FOR BACKEND**
