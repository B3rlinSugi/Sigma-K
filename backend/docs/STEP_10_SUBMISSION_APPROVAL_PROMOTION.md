# E-SKLD BACKEND DOCUMENTATION — STEP 10
## CORE SUBMISSION FINAL APPROVAL, APPROVAL RECORDING & MASTER DATA PROMOTION

---

## 1. Objective

Step 10 melengkapi siklus hidup penuh usulan kelembagaan (*End-to-End Submission Lifecycle*) dengan mengimplementasikan:
1. **Persetujuan Akhir Bisnis (*Final Business Approval*)**: Dilakukan oleh Verifikator Gerbang 2 yang ditugaskan (`READY_FOR_FINAL_DECISION` $\rightarrow$ `APPROVED`).
2. **Pencatatan Rekaman Persetujuan (*Approval Recording*)**: Pencatatan data persetujuan ke tabel `approval_records` dengan dukungan nomor SK/surat persetujuan opsional (`approval_number`).
3. **Pemisahan Konseptual Persetujuan dan Promosi (*Approval vs. Promotion Separation*)**: Memisahkan status persetujuan eksplisit (`APPROVED`) dari mutasi fisik data master (`PROMOTED`).
4. **Rekonsiliasi Promosi Data Master (*Master Data Promotion Reconciliation*)**: Promosi snapshot usulan ke tabel master (`organizational_units` & `positions`) menggunakan semantik *non-destructive reconciliation* (`NEW` $\rightarrow$ INSERT, `UPDATE` $\rightarrow$ UPDATE, `DELETE` $\rightarrow$ DEACTIVATE, `UNCHANGED` $\rightarrow$ PRESERVE).
5. **Jaminan Atomisitas & Idempotensi**: Eksekusi mutasi dalam transaksi database dengan penguncian baris (`FOR UPDATE`) dan proteksi eksekusi ganda.
6. **Penegakan Wewenang & SoD (*Separation of Duties*)**: Pembuat usulan dilarang menyetujui usulan sendiri; hanya verifikator penugasan (atau SuperAdmin) yang berhak menyetujui.

---

## 2. Complete Submission Lifecycle & State Machine

```
              ┌────────────────────────────────┐
              │    READY_FOR_FINAL_DECISION    │
              └───────────────┬────────────────┘
                              │
                              │ POST /api/v1/submissions/{id}/approve
                              │ (By Assigned Verifier)
                              ▼
              ┌────────────────────────────────┐
              │            APPROVED            │ ◄── [Approval Record Created, Snapshot Immutable]
              └───────────────┬────────────────┘
                              │
                              │ POST /api/v1/submissions/{id}/promote
                              │ (Master Data Reconciliation Engine)
                              ▼
              ┌────────────────────────────────┐
              │            PROMOTED            │ ◄── [Master Units & Positions Activated]
              └────────────────────────────────┘
```

---

## 3. Endpoints Created / Extended

| Method | Endpoint | Filter | Deskripsi & Otorisasi |
|---|---|:---:|---|
| `POST` | `/api/v1/submissions/{id}/approve` | `auth` | Persetujuan bisnis final oleh Verifikator penugasan (`READY_FOR_FINAL_DECISION` $\rightarrow$ `APPROVED`). Izin: `APPROVE`. |
| `POST` | `/api/v1/submissions/{id}/promote` | `auth` | Eksekusi promosi snapshot usulan yang telah disetujui menjadi data master aktif (`APPROVED` $\rightarrow$ `PROMOTED`). Izin: `PROMOTE`. |
| `GET` | `/api/v1/submissions/{id}/approval` | `auth` | Mengambil status persetujuan, rekaman `approval_records`, dan ringkasan promosi usulan. Izin: `VIEW`. |

---

## 4. Separation of Final Approval & Master Data Promotion

1. **Tahap 1: Final Approval (`POST /approve`)**:
   - Memastikan usulan telah lolos verifikasi substantif Gerbang 2 (`SUBSTANTIVE_PASSED`).
   - Menyimpan rekaman persetujuan ke tabel `approval_records`.
   - Mengubah status penugasan verifikator di `verifier_assignments` menjadi `'COMPLETED'`.
   - Mengubah status usulan menjadi `'APPROVED'`.
   - Snapshot versi usulan dikunci permanen (*Immutable*).
2. **Tahap 2: Master Promotion (`POST /promote`)**:
   - Beroperasi hanya pada usulan berstatus `'APPROVED'`.
   - Mengambil data snapshot versi yang disetujui sebagai *single source of truth*.
   - Merekonsiliasi unit dan jabatan ke tabel master `organizational_units` dan `positions`.
   - Mengubah status usulan menjadi `'PROMOTED'`.

---

## 5. Master Data Reconciliation Semantics

Mesin promosi tidak menghapus atau menimpa tabel master secara destruktif, melainkan menerapkan rekonsiliasi berbasis tipe perubahan:

| Tipe Perubahan | Aksi pada Unit Organisasi Master (`organizational_units`) | Aksi pada Jabatan Master (`positions`) |
|---|---|---|
| **`NEW`** | Insert baris baru dengan `status = 'ACTIVE'` dan menautkan `parent_unit_id` yang sesuai hierarki. | Insert baris baru dengan `unit_id` hasil pemetaan unit master dan `status = 'ACTIVE'`. |
| **`UPDATE`** | Update baris master eksisting (`id = source_unit_id`) pada kolom `unit_code`, `unit_name`, `unit_level`, `order_index`. | Update baris master eksisting (`id = source_position_id`) pada nama, tipe, eselon, dan formasi. |
| **`DELETE`** | Soft deactivation: Update `status = 'INACTIVE'` pada baris master eksisting. | Soft deactivation: Update `status = 'INACTIVE'` pada baris master eksisting. |
| **`UNCHANGED`** | Mempertahankan baris master eksisting tanpa modifikasi. | Mempertahankan baris master eksisting tanpa modifikasi. |

---

## 6. Approval Record & Government SK Number Handling

- Tabel eksisting `approval_records` dimanfaatkan tanpa mengubah skema database:
  - `version_id`: ID versi usulan yang disetujui.
  - `approver_id`: ID Verifikator yang menyetujui.
  - `approval_number`: Nomor SK / Surat Keputusan resmi (opsional, nullable).
  - `approval_notes`: Catatan persetujuan resmi.
  - `approved_at`: Timestamp persetujuan sistem.
- Sistem membedakan secara tegas antara **Rekaman Persetujuan Internal Sistem** (`id`, `approved_at`, `approver_id`) dengan **Nomor Dokumen/SK Resmi Pemerintah** (`approval_number`).

---

## 7. Zero-Trust Authorization & Separation of Duties (SoD)

1. **Verifier Authority**: Persetujuan final hanya dapat dieksekusi oleh Verifikator yang bertugas (atau `SUPER_ADMIN`). Pengguna dengan peran `USER` atau `ADMIN` ditolak dengan HTTP `403 Forbidden`.
2. **Anti-Self-Approval**: Pembuat usulan dilarang menyetujui usulannya sendiri (`SOD_AUTHOR_CANNOT_APPROVE` $\rightarrow$ `403 Forbidden`).
3. **Assignment Isolation**: Verifikator lain yang tidak ditugaskan pada berkas ditolak dengan HTTP `403 Forbidden` (`WRONG_VERIFIER`).
4. **Immutable Snapshot**: Draf usulan yang telah disetujui atau dipromosikan terkunci dan tidak dapat diubah oleh siapapun (`409 Conflict`).

---

## 8. Concurrency Protection & Transaction Safety

1. **Idempotensi Promosi**: Usulan yang telah dipromosikan (`PROMOTED`) menolak eksekusi promosi ulang dengan HTTP `409 Conflict` (`ALREADY_PROMOTED`).
2. **Anti-Duplicate Approval**: Usulan yang telah disetujui (`APPROVED`) menolak persetujuan ganda dengan HTTP `409 Conflict` (`ALREADY_APPROVED`).
3. **Atomic Rollback**: Kegagalan pada salah satu tahap rekonsiliasi membatalkan seluruh transaksi secara atomik dan mencatat audit log `SUBMISSION_PROMOTION_FAILED`.

---

## 9. Audit Trail Implementation

Seluruh peristiwa penting dicatat secara permanen melalui `AuditService`:
1. `SUBMISSION_FINAL_APPROVED`: Saat Verifikator menyetujui usulan secara final.
2. `SUBMISSION_PROMOTION_STARTED`: Saat proses rekonsiliasi data master dimulai.
3. `SUBMISSION_PROMOTED`: Saat promosi data master selesai dengan ringkasan jumlah unit/jabatan.
4. `SUBMISSION_PROMOTION_FAILED`: Jika terjadi kegagalan transaksi promosi.

---

## 10. Database Schema Immutability

- **Perubahan Skema / DDL**: **0 (NIL)** — Tidak ada tabel baru, tidak ada kolom baru, tidak ada migrasi.
- Struktur tabel `approval_records`, `organizational_units`, `positions`, `submissions`, `submission_versions`, `submission_units`, dan `submission_positions` dimanfaatkan 100% sesuai skema awal.

---

## 11. Online User / Realtime Presence (Future Architecture)

- Sesuai arahan Step 10, infrastruktur WebSocket/SSE atau Redis tidak diimplementasikan secara prematur.
- Kontrak arsitektur untuk penanganan kehadiran daring (*Realtime Presence / Online Status*) disiapkan untuk integrasi mendatang (misal: `last_seen_at`, presence event bridge).

---

## 12. Test Coverage

- **Suite Uji Baru**: [`tests/unit/FinalApprovalPromotionTest.php`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/tests/unit/FinalApprovalPromotionTest.php) (17 test methods mencakup skenario `APPROVAL-01..10`, `PROMOTION-01..12`, dan regresi).
- **Total Uji Terakumulasi**: **160 tests, 449 assertions, 0 errors, 0 failures (100% PASS)**.
