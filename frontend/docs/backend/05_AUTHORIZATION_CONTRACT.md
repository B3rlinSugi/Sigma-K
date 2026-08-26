# SIGMA-K — AUTHORIZATION & INSTITUTION SCOPING CONTRACT

> **Dokumen:** `05_AUTHORIZATION_CONTRACT.md`  
> **Status:** `SECURITY & AUTHORIZATION SPECIFICATION (PHASE 5A DESIGN - REVIEWED)`  
> **Model Otorisasi:** RBAC (*Role-Based Access Control*) + ABAC Scope (*Institution-Scoped Context*)  

---

## 1. Matriks Peran & Hak Akses (RBAC Matrix)

Sistem mengimplementasikan pemisahan tugas (*Separation of Duties*) yang ketat antar-peran fungsional:

| Fitur / Tindakan Sistem | USER (Operator K/L) | VERIFIKATOR (Analis PANRB) | ADMIN (Admin Pusat) | SESDEP / PIMPINAN (Executive Persona)* |
| :--- | :---: | :---: | :---: | :---: |
| **Lihat Katalog Master K/L & Pemda** | Ya (Global) | Ya (Global) | Ya (Global) | Ya (Global) |
| **Lihat & Bandingkan Kabinet** | Ya (Global) | Ya (Global) | Ya (Global) | Ya (Global) |
| **Lihat Bagan Struktur Organisasi** | Ya (Global) | Ya (Global) | Ya (Global) | Ya (Global) |
| **Lihat Dashboard Eksekutif & Analitik**| Ya (Ringkas) | Ya (Ringkas) | Ya (Lengkap) | **Ya (Lengkap + Executive KPIs)** |
| **Buat Draf Usulan Perubahan** | **Ya (Hanya Instansi Sendiri)** | Tidak | Ya (Bantuan Pusat) | Tidak |
| **Kirim Usulan & Tanggapi Revisi** | **Ya (Hanya Instansi Sendiri)** | Tidak | Ya | Tidak |
| **Telaah Verifikasi (Pass/Revisi/Tolak)**| Tidak | **Ya (Seluruh Pengajuan K/L)** | Ya | Tidak (Hanya Monitoring) |
| **Pengesahan ke Master Data (Approve)** | Tidak | Tidak | **Ya (Hak Tunggal Admin)** | Tidak |
| **Manajemen Pengguna & Konfigurasi** | Tidak | Tidak | **Ya** | Tidak |
| **Akses Rekam Forensik Audit Trail** | Tidak | Tidak | **Ya** | **Ya (Supervisory View)** |

> *\*) Catatan Status SESDEP:* Persona SESDEP/Pimpinan pada matriks di atas merupakan representasi *Executive Perspective / Prototype Persona*. Model otorisasi produksi definitif tetap bergantung pada resolusi **`OPEN-001`**.

---

## 2. Arsitektur Pembatasan Scope Instansi (*Institution Scoping & Anti-BOLA/IDOR*)

Untuk mencegah kerentanan keamanan *Broken Object-Level Authorization* (BOLA) dan *Insecure Direct Object References* (IDOR):

```
                                  [HTTP INCOMING REQUEST]
                                             │
                                             ▼
                             [JwtAuthGuard: Validasi Token]
                                             │
                                             ▼
                             [RolesGuard: Validasi Peran]
                                             │
                                             ▼
                        [InstitutionScopeGuard: Validasi Scope]
                                             │
             ┌───────────────────────────────┴───────────────────────────────┐
             ▼                                                               ▼
    [Role === 'USER' (Operator)]                                 [Role === 'VERIFIKATOR' / 'ADMIN' / 'SESDEP']
             │                                                               │
   Apakah Target InstitutionId                                        Scope: GLOBAL
     === User.institutionId ?                                        (Lolos ke Application Service)
     ┌───────┴───────┐
     ▼               ▼
  [YA: PASS]    [TIDAK: 403 FORBIDDEN]
```

### Aturan Penegakan Scope pada Lapisan Repository:
1. **Operator Instansi (`USER`):** Setiap kueri mutasi data (pembuatan usulan, unggah dokumen, penarikan tiket) secara otomatis disuntikkan filter klausa `WHERE institution_id = req.user.institution_id`. Operator tidak dapat memanipulasi parameter ID instansi di URL untuk mengakses data instansi lain.
2. **Verifikator (`VERIFIKATOR`):** Memiliki hak baca global atas seluruh pengajuan yang berstatus `SUBMITTED`, `IN_REVIEW`, dan `RESUBMITTED` untuk keperluan telaah substantif lintas kementerian.
3. **Administrator (`ADMIN`):** Memiliki kewenangan administratif penuh atas seluruh modul data kelembagaan nasional.

---

## 3. Catatan Terbuka: Model Otorisasi Produksi SESDEP / PIMPINAN (Open Decision #1)

Sesuai arahan keselamatan bisnis:
- Pada prototipe Phase 4, persona SESDEP dimodelkan sebagai **"Executive Perspective / Prototype Persona"**.
- Pada perancangan backend ini, arsitektur mendukung dua opsi implementasi produksi yang menunggu konfirmasi stakeholder:
  - **Opsi A (Dedicated RBAC Role `SESDEP`):** Diberikan role mandiri dengan set hak akses *read-all*, analitik intelijensi pimpinan, dan pemantauan audit trail.
  - **Opsi B (Permission-Based Supervisory Model):** Menggunakan role standar dengan flag izin `PERM_SUPERVISORY_AUDIT` dan `PERM_EXECUTIVE_ANALYTICS` tanpa membuat role khusus di database.
