# 05. TARGET DOMAIN MODEL SPECIFICATIONS: SIGMA-K

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Data Architect & Lead Full-Stack Engineer  

Dokumen ini mendefinisikan arsitektur model domain data target (*Target Domain Model*) SIGMA-K yang mencakup 25 entitas domain logis yang terbagi dalam 6 bounded contexts.

---

## 1. Taksonomi Bounded Contexts Domain Data

```
+-----------------------------------------------------------------------------------+
|                            SIGMA-K TARGET DOMAIN MODEL                            |
+-----------------------------------------------------------------------------------+
| 1. INSTITUTION CONTEXT: Institution, InstitutionType, Region,                     |
|                         InstitutionProfile, InstitutionFunction (Tupoksi)         |
| 2. CABINET & LINEAGE CONTEXT: Cabinet, CabinetPeriod, CabinetMembership,          |
|                               InstitutionLineage                                  |
| 3. ORGANIZATION & POSITION CONTEXT: OrganizationUnit, Position, EselonLevel       |
| 4. IAM & SCOPE CONTEXT: User, Role, Permission, UserScope,                        |
|                         AccessRequest, AccessGrant                                |
| 5. WORKFLOW & GOVERNANCE CONTEXT: Submission, SubmissionVersion, SubmissionItem,  |
|                                   Verification, Approval, Revision                |
| 6. AUDIT & COMMUNICATION CONTEXT: Notification, AuditLog                          |
+-----------------------------------------------------------------------------------+
```

---

## 2. Rincian 25 Entitas Domain Logis

### A. Context 1: Master Kelembagaan & Profil (Entities 1 - 5)
1. **`Institution` (Master Instansi):** Entitas inti penyimpan identitas resmi instansi pemerintah pusat (Kementerian, LPNK, LNS) dan daerah (Provinsi, Kab/Kota) dengan kodefikasi unik nasional.
2. **`InstitutionType` (Jenis Instansi):** Klasifikasi standar instansi pemerintah (`KEMENTERIAN_KOORDINATOR`, `KEMENTERIAN_TEKNIS`, `LPNK`, `LNS`, `PEMDA_PROVINSI`, `PEMDA_KABUPATEN`, `PEMDA_KOTA`).
3. **`Region` (Wilayah Administratif):** Referensi wilayah geografis berjenjang (Provinsi, Kab/Kota) bersinkronisasi dengan kode standar nasional.
4. **`InstitutionProfile` (Profil Instansi):** Menyimpan atribut detail operasional instansi (alamat kantor, kontak telepon, email resmi .go.id, website HTTPS, logo, visi, misi, ringkasan dasar hukum).
5. **`InstitutionFunction` / `TugasFungsi` (Tupoksi Kelembagaan):** Menyimpan butir-butir Tugas Pokok dan Rincian Fungsi terstruktur yang terikat pada instansi dan unit kerja beserta rujukan pasal/ayat regulasi hukum.

### B. Context 2: Manajemen Kabinet & Histori Transisi (Entities 6 - 9)
6. **`Cabinet` (Master Kabinet):** Entitas era kepresidenan (misal: "Kabinet Merah Putih", "Kabinet Indonesia Maju") beserta metadata Presiden dan Wakil Presiden.
7. **`CabinetPeriod` (Periode Kabinet):** Menyimpan rentang masa jabatan formal (`start_date`, `end_date`), nomor Keppres/Perpres, dan penanda status kabinet aktif (`is_active`).
8. **`CabinetMembership` (Keanggotaan Instansi dalam Kabinet):** Tabel asosiasi relasional many-to-many antara `CabinetPeriod` dan `Institution` dengan klasifikasi kategori kementerian (`KEMENKO`, `TEKNIS`, `LPNK`, `LNS`) dan tanggal bergabung resmi.
9. **`InstitutionLineage` (Silsilah Transisi Kelembagaan):** Graf pencatat sejarah evolusi kelembagaan (pemecahan kementerian / *split*, merger instansi, perubahan nomenklatur / *rename*, pembentukan baru / *new*, dan pembubaran / *dissolution*).

### C. Context 3: Struktur Organisasi & Jabatan (Entities 10 - 12)
10. **`OrganizationUnit` (Unit Organisasi Hierarkis):** Bagan pohon unit kerja struktural berbasis Adjacency List (`parent_id`) dilengkapi level kedalaman (*depth*) dan validasi *anti-circular dependency*.
11. **`Position` (Jabatan Struktural/Fungsional):** Nomenklatur jabatan resmi yang memimpin atau mengampu unit kerja organisasi.
12. **`EselonLevel` / `PositionLevel` (Tingkat Eselon):** Master tingkatan eselon (Eselon I.a, I.b, II.a, II.b, III, IV, Non-Eselon, Jabatan Fungsional).

### D. Context 4: Keamanan, Identitas & Scoping Akses (Entities 13 - 18)
13. **`User` (Pengguna Sistem):** Akun pengguna dengan password terenkripsi Bcrypt, status aktif, dan profil aparatur (NIP, Nama, Email).
14. **`Role` (Peran Sistem):** Peran baku pengguna (`USER`, `VERIFIKATOR`, `ADMIN`, `PIMPINAN`, `DATA_ANALYST`).
15. **`Permission` (Hak Izin Granular):** Daftar izin operasional spesifik (`EDIT_INSTITUTION_DRAFT`, `VERIFY_SUBMISSION`, `APPROVE_SUBMISSION`, dll).
16. **`UserScope` (Batasan Instansi Pengguna):** Pengikatan akun pengguna terhadap `institution_id` tertentu (Operator hanya boleh mengedit draf instansinya sendiri).
17. **`AccessRequest` (Permintaan Hak Akses - PROPOSED):** Entitas pengajuan permohonan hak kelola instansi baru oleh operator daerah/K/L.
18. **`AccessGrant` (Pemberian Otorisasi - PROPOSED):** Catatan riwayat persetujuan hak akses yang disahkan oleh Administrator KemenPANRB.

### E. Context 5: Tata Kelola Workflow Pengajuan & Verifikasi (Entities 19 - 23)
19. **`Submission` / `SubmissionTicket` (Tiket Pengajuan):** Berkas tiket pengajuan perubahan data kelembagaan dengan nomor unik (`TKT-YYYYMMDD-XXXX`) dan state machine terkontrol.
20. **`SubmissionVersion` / `SubmissionItem` (Item Delta Perubahan):** Menyimpan payload delta perubahan data (sebelum vs sesudah) dalam format JSONB untuk keperluan Diff Viewer.
21. **`Verification` / `VerificationLog` (Log Telaah Verifikator):** Catatan hasil pemeriksaan, rekomendasi, dan butir koreksi revisi oleh Verifikator KemenPANRB.
22. **`Approval` (Pengesahan Akhir Data):** Catatan transaksi atomik persetujuan data draf ke Master Data aktif oleh Administrator.
23. **`Revision` (Riwayat Revisi):** Riwayat iterasi perbaikan data antara Operator dan Verifikator sebelum berkas disahkan.

### F. Context 6: Komunikasi Realtime & Log Kepatuhan (Entities 24 - 25)
24. **`Notification` (Antrean & Riwayat Notifikasi):** Pesan pemberitahuan aktivitas realtime per pengguna dengan status baca (`is_read`) dan URL tindakan cepat.
25. **`AuditLog` (Jejak Audit Permanen):** Log audit tak terhapuskan (*immutable append-only*) yang merekam *who, what, when, IP address, user agent, old_values, new_values* pada setiap mutasi data.
