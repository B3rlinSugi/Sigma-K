# SIGMA-K — TRANSACTION BOUNDARIES & ACID CONSISTENCY SPECIFICATION

> **Dokumen:** `10_TRANSACTION_BOUNDARIES.md`  
> **Status:** `ACID TRANSACTION BOUNDARIES (PHASE 5A DESIGN)`  
> **Pola Implementasi:** Prisma Interactive Transactions (`prisma.$transaction`) / Unit of Work Pattern  

---

## 1. Identifikasi Batas Transaksi Kritis (Critical Transaction Boundaries)

Untuk menjamin integritas data tingkat tinggi dan mencegah kondisi anomali data parsial (*partial writes*), operasi-operasi berikut diwajibkan berada dalam satu batas transaksi basis data:

```
+-----------------------------------------------------------------------------------------+
|                  CONTOH BATAS TRANSAKSI ATOMIK: PENGESAHAN MASTER DATA                  |
|                                                                                         |
|  BEGIN TRANSACTION;                                                                     |
|    1. SELECT ... FOR UPDATE (Lock Tiket Usulan, Validasi Status === 'VERIFIED')         |
|    2. UPSERT / UPDATE Master Data Aktif (organization_units / tupoksi_items / dll.)     |
|    3. UPDATE submission_tickets SET status = 'APPROVED', approved_at = NOW();          |
|    4. INSERT INTO audit_logs (Actor, Action, JSONB Snapshot Old/New);                   |
|  COMMIT;  --> (Jika salah satu gagal: ROLLBACK SELURUHNYA TANPA SISA)                  |
|                                                                                         |
|  EMIT DOMAIN EVENT: 'SubmissionApprovedEvent' (Memicu Notifikasi Realtime)              |
+-----------------------------------------------------------------------------------------+
```

---

## 2. Rincian Batas Transaksi per Operasi Bisnis

### A. Pengesahan Usulan ke Master Data (`ApproveSubmissionToMaster`)
- **Tingkat Kekritisan:** **SANGAT KRITIS (CRITICAL)**
- **Alasan Transaksi:** Data draf yang diajukan operator kementerian harus diterapkan secara simultan ke tabel master aktif. Kegagalan parsial akan menyebabkan inkonsistensi struktur kementerian nasional.
- **Langkah dalam Transaksi:**
  1. Kunci baris tiket usulan (`SELECT FOR UPDATE`) dan pastikan status saat ini adalah `VERIFIED`.
  2. Iterasi seluruh `submission_items` dan lakukan mutasi (Insert/Update/Delete) pada tabel master terkait (`organization_units`, `tupoksi_items`, dsb).
  3. Perbarui status tiket menjadi `APPROVED` serta catat `approved_by_user_id` dan `approved_at`.
  4. Tulis entri log audit forensik ke `audit_logs` dengan snapshot nilai sebelum dan sesudah perubahan.

---

### B. Pembuatan Usulan Tiket Beserta Butir Perubahan (`CreateSubmissionTicket`)
- **Tingkat Kekritisan:** **TINGGI**
- **Alasan Transaksi:** Tiket pengajuan tidak boleh berstatus *orphan* (ada tiket tanpa item perubahan, atau ada item perubahan tanpa induk tiket).
- **Langkah dalam Transaksi:**
  1. Insert record induk `submission_tickets`.
  2. Bulk insert seluruh `submission_items` yang terkait dengan tiket tersebut.
  3. Tulis log inisiasi draf ke `audit_logs`.

---

### C. Perekaman Keputusan Verifikasi (`RecordVerificationDecision`)
- **Tingkat Kekritisan:** **TINGGI**
- **Alasan Transaksi:** Perubahan status tiket (`VERIFIED`, `REVISION_REQUIRED`, `REJECTED`) wajib sinkron dengan rekam jejak telaah verifikator.
- **Langkah dalam Transaksi:**
  1. Insert catatan telaah resmi verifikator ke `verification_logs`.
  2. Update status `submission_tickets` sesuai keputusan (`VERIFIED` / `REVISION_REQUIRED` / `REJECTED`).
  3. Tulis aktivitas telaah ke `audit_logs`.

---

### D. Pengiriman Tanggapan Revisi Operator (`SubmitRevisionResponse`)
- **Tingkat Kekritisan:** **SEDANG-TINGGI**
- **Alasan Transaksi:** Butir penyesuaian baru, tanggapan naratif operator, dan nomor iterasi revisi harus tersimpan atomik saat status tiket dikembalikan ke antrean (`SUBMITTED` / `RESUBMITTED`).
- **Langkah dalam Transaksi:**
  1. Insert rekam revisi ke `submission_revisions`.
  2. Update payload draf pada `submission_items`.
  3. Update status tiket menjadi `RESUBMITTED`.

---

### E. Pemindahan Unit Organisasi & Pembaruan Hierarki (`MoveOrganizationUnit`)
- **Tingkat Kekritisan:** **TINGGI**
- **Alasan Transaksi:** Pencegahan struktur pohon patah (*broken tree*) saat memindahkan atasan unit kerja (`parent_id`).
- **Langkah dalam Transaksi:**
  1. Eksekusi validasi **Anti-Circular DFS**.
  2. Update `parent_id` unit yang dipindahkan.
  3. Recalculate dan update nilai `hierarchy_level` seluruh unit turunan di bawahnya secara atomik.

---

## 3. Strategi Pengendalian Konkurensi (*Concurrency Control*)
Untuk mencegah konflik penulisan data secara bersamaan (*Race Condition* / *Double Approval*):
- Menerapkan **Pessimistic Row-Level Locking (`FOR UPDATE`)** pada transaksi pengesahan tiket usulan.
- Menerapkan **Optimistic Locking (`version` column)** pada tabel master instansi untuk mendeteksi konflik pembaruan data secara simultan oleh dua sesi admin yang berbeda.
