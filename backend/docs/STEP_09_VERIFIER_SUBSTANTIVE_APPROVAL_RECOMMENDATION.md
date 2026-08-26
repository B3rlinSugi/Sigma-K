# E-SKLD BACKEND DOCUMENTATION — STEP 9
## GATE 2 VERIFIER SUBSTANTIVE VERIFICATION APPROVAL & RECOMMENDATION WORKFLOW

---

## 1. Objective

Step 9 mengimplementasikan tahap kelulusan telaah substantif Gerbang 2 (*Gate 2 Substantive Verification Approval*) dan penerbitan rekomendasi teknis kelembagaan oleh Verifikator (*Technical Recommendation*), yang mencakup:
1. Inspeksi mendalam draf usulan yang diajukan kembali (*Resubmitted submission review*) beserta perbandingan versi dan riwayat catatan revisi (`GET /api/v1/verifier/submissions/{id}/review`).
2. Validasi penuntasan catatan revisi (*Revision Completion Check*): memastikan seluruh temuan telah diselesaikan sebelum persetujuan substantif dapat diterbitkan.
3. Penetapan hasil telaah substantif (*Substantive Verification Passed*) dan pencatatan rekomendasi teknis (`POST /api/v1/submissions/{id}/verifier-review/approve` $\rightarrow$ `READY_FOR_FINAL_DECISION`).
4. Konsumsi data rekomendasi teknis terverifikasi (`GET /api/v1/submissions/{id}/recommendation`).
5. Penegakan wewenang *Zero-Trust Authorization* dan *Separation of Duties (SoD)*.
6. Proteksi konkurensi, transaksi atomik, dan pencatatan jejak audit (*Audit Trail*).

> **Batasan Ketat Step 9**: Step 9 **TIDAK** mengimplementasikan penetapan SK final, penerbitan surat persetujuan MenPANRB/Sesdep, ataupun mutasi data master kelembagaan. Step 9 memposisikan usulan pada status `READY_FOR_FINAL_DECISION` untuk gerbang keputusan akhir.

---

## 2. Gate 2 Verification & Recommendation State Machine

```
              ┌────────────────────────────────┐
              │          RESUBMITTED           │ (or IN_REVIEW_BY_VERIFIER)
              └───────────────┬────────────────┘
                              │
                              │ 1. GET /verifier/submissions/{id}/review
                              │ 2. Revision Completion Validation
                              │ 3. POST /verifier-review/approve + Recommendation
                              ▼
              ┌────────────────────────────────┐
              │   READY_FOR_FINAL_DECISION     │ ───► [Gate 3 / Final Decision Gate]
              └────────────────────────────────┘
```

---

## 3. Endpoints Created / Extended

| Method | Endpoint | Filter | Deskripsi & Otorisasi |
|---|---|:---:|---|
| `GET` | `/api/v1/verifier/submissions/{id}/review` | `auth` | Mengambil detail inspeksi komprehensif berkas untuk Verifikator penugasan (versi, unit, jabatan, riwayat revisi). Verifier aktif. |
| `POST` | `/api/v1/submissions/{id}/verifier-review/approve` | `auth` | Menyetujui verifikasi substantif (`SUBSTANTIVE_PASSED`), menerbitkan rekomendasi teknis, dan mentransisikan status ke `READY_FOR_FINAL_DECISION`. Izin: `APPROVE` / `VERIFY`. |
| `GET` | `/api/v1/submissions/{id}/recommendation` | `auth` | Mengambil rekomendasi teknis resmi hasil telaah substantif Gerbang 2 untuk usulan terkait. |

---

## 4. Verification Result & Recommendation Data Model

1. **Verification Record (`verification_records`)**:
   - `version_id`: ID versi spesifik yang ditelaah dan diverifikasi (`v2`, `v3`).
   - `reviewer_id`: ID Verifikator yang bertugas.
   - `gate_level`: `'GATE_2'`.
   - `verification_result`: `'SUBSTANTIVE_PASSED'`.
   - `general_notes`: Serialized JSON yang memuat struktur rekomendasi teknis lengkap.
   - `verified_at`: Timestamp verifikasi substantif.
2. **Technical Recommendation Payload Structure**:
   - `recommendation_summary`: Ringkasan rekomendasi teknis penataan organisasi.
   - `substantive_findings`: Temuan substantif terkait analisis beban kerja, peta jabatan, dan rentang kendali.
   - `regulatory_considerations`: Rujukan regulasi dan dasar hukum kesesuaian kelembagaan.
   - `recommended_action`: Tindak lanjut rekomendasi (misal: `'PROCEED_TO_FINAL_APPROVAL'`).
   - `verifier_id` & `verifier_username`: Identitas verifikator resmi.
   - `version_id` & `version_number`: Penomoran versi yang diverifikasi.
   - `verified_at`: Timestamp penerbitan rekomendasi.

---

## 5. Revision Completion Validation

Sebelum menyetujui verifikasi substantif, sistem melakukan pemeriksaan ketat terhadap catatan revisi:
- Jika masih terdapat catatan revisi aktif yang belum diselesaikan (`is_resolved = 0`) dan verifikator tidak menandai penyelesaian catatan tersebut, sistem menolak permintaan dengan HTTP `422 Unprocessable Entity` (`UNRESOLVED_REVISIONS`).
- Verifikator dapat mengonfirmasi penyelesaian seluruh catatan revisi secara eksplisit melalui parameter `resolve_all_notes: true` atau daftar `resolved_note_ids`.

---

## 6. Separation of Duties (SoD) & Role Enforcement

1. **Anti-Self-Verification**: Pembuat usulan (*Submission Author*) dilarang keras memverifikasi, menyetujui telaah substantif, atau membuat rekomendasi teknis sebagai Verifikator untuk usulannya sendiri (`SOD_AUTHOR_CANNOT_VERIFY` $\rightarrow$ `403 Forbidden`).
2. **Assignment Isolation**: Hanya Verifikator yang ditugaskan secara aktif pada `verifier_assignments` (atau `SUPER_ADMIN`) yang berhak melakukan penelaahan dan persetujuan substantif (`WRONG_VERIFIER` $\rightarrow$ `403 Forbidden`).
3. **Immutability of Reviewed Version**: Verifikator tidak dapat memodifikasi draf unit/jabatan usulan secara langsung; verifikator hanya menelaah dan menerbitkan rekomendasi teknis.

---

## 7. Audit Trail Implementation

Dua rekaman audit diterbitkan secara atomik saat verifikasi substantif disetujui:
1. `VERIFIER_SUBSTANTIVE_APPROVED`: Mencatat kelulusan Gerbang 2 dengan transisi status dari `RESUBMITTED` / `IN_REVIEW_BY_VERIFIER` menuju `READY_FOR_FINAL_DECISION`.
2. `VERIFIER_RECOMMENDATION_CREATED`: Mencatat rincian rekomendasi teknis, temuan substantif, dan dasar pertimbangan regulasi kelembagaan.

---

## 8. Concurrency Protection & Transaction Safety

1. **Anti-Duplicate Approval**: Permintaan persetujuan substantif ganda pada berkas yang sama ditolak dengan HTTP `409 Conflict`.
2. **Row Locking**: Seluruh proses validasi versi, pembaruan catatan revisi, pencatatan verifikasi, dan transisi status dilindungi dengan *row locking* (`SELECT ... FOR UPDATE`) dalam transaksi atomik (`transBegin` / `transCommit` / `transRollback`).

---

## 9. Master Data Immutability Verification

- Tidak ada mutasi pada tabel master `organizational_units`, `positions`, `institutions`, maupun `users`.
- Seluruh data master tetap utuh dan berstatus *read-only*.

---

## 10. Test Coverage

- **Suite Uji Baru**: [`tests/unit/SubstantiveApprovalTest.php`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/tests/unit/SubstantiveApprovalTest.php) (16 test methods mencakup 24 skenario `VERIFICATION-01` s/d `VERIFICATION-24`).
- **Total Uji Terakumulasi**: **143 tests, 410 assertions, 0 errors, 0 failures (100% PASS)**.

---

## 11. Known Limitations & Open Decisions

- **OPEN DECISION**: Struktur penetapan keputusan final pada Gerbang 3 (misalnya penomoran SK, penandatangan Sesdep/MenPANRB, dan mekanisme promosi otomatis snapshot unit/jabatan usulan menjadi data master aktif).
