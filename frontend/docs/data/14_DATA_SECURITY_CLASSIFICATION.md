# 14. DATA SECURITY & PRIVACY CLASSIFICATION: SIGMA-K

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Data Governance Architect & DevSecOps Lead  
> **Dasar Standar:** Standar Kemanan Informasi SPBE & Kebijakan Satu Data Indonesia  

Dokumen ini menetapkan tingkat klasifikasi keamanan dan privasi data (*Data Security & Classification Policy*) untuk seluruh entitas data yang dikelola dalam sistem SIGMA-K.

---

## 1. Taksonomi Tingkat Kerahasiaan Data (4-Tier Security Levels)

```
[ LEVEL 1: PUBLIC ] ───────> Terbuka untuk umum (Katalog K/L, Profil, Bagan Resmi)
        │
[ LEVEL 2: INTERNAL ] ─────> Terbatas untuk Aparatur Sipil Negara / Operator Instansi
        │
[ LEVEL 3: CONFIDENTIAL ] ─> Terbatas untuk Pejabat Berwenang & Tim Verifikator
        │
[ LEVEL 4: RESTRICTED ] ───> Akses Sangat Ketat / Sistem & Administrator Tertinggi
```

---

## 2. Matriks Klasifikasi Keamanan Entitas Data

| Entitas Data & Atribut | Tingkat Klasifikasi Keamanan | Hak Akses (*Access Control*) | Kebijakan Enkripsi (*Encryption at Rest & Transit*) | Kebijakan Retensi (*Retention Policy*) | Status Kebijakan Pemerintah |
|---|:---:|---|---|---|:---:|
| **Master Instansi (`institutions`, `code`, `name`)** | **PUBLIC** | Semua Pengguna & Publik | TLS 1.3 in transit | Permanen / Abadi | **CONFIRMED** |
| **Bagan Organisasi Disahkan (`organization_units`)** | **PUBLIC** | Semua Pengguna & Publik | TLS 1.3 in transit | Permanen (Bersejarah) | **CONFIRMED** |
| **Dasar Hukum Terbit (PDF Regulasi Lembaran Negara)** | **PUBLIC** | Semua Pengguna & Publik | TLS 1.3 in transit | Permanen | **CONFIRMED** |
| **Draf Usulan Perubahan (`submission_tickets`, `items`)** | **INTERNAL** | Operator Instansi Terkait & Verifikator | TLS 1.3 + Scoped Guard | 5 Tahun pasca pengesahan | **CONFIRMED** |
| **Catatan Telaah Verifikator (`verification_logs`)** | **CONFIDENTIAL** | Tim Verifikator & Admin Pusat | TLS 1.3 + Disk Encryption | 10 Tahun | **CONFIRMED** |
| **Profil Pengguna (Nama, NIP, Email .go.id)** | **INTERNAL** | Pengguna Terautentikasi | TLS 1.3 + Masking NIP publik | Selama akun aktif | **CONFIRMED** |
| **Kredensial Pengguna (`password_hash`, `refresh_token`)** | **RESTRICTED** | Hanya Service Auth Backend | Bcrypt Hash + Redis TLS | Dihapus saat logout/reset | **CONFIRMED** |
| **Log Audit Forensik (`audit_logs`, `old_values`, `ip`)** | **RESTRICTED** | Administrator Sistem & Auditor Resmi | AES-256 at Rest + Read-Only | Minimal 10 Tahun (SPBE) | **CONFIRMED** |
| **Draf Regulasi Internal Belum Diundangkan (PDF)** | **CONFIDENTIAL** | Operator Pengusul & Pimpinan SESDEP | MinIO Signed URL (15 mins TTL) | Hingga diterbitkan Keppres | `TBD — POLICY VALIDATION REQUIRED` |
| **Data Agregasi Postur ASN Tertentu (Data Intelijen)** | **CONFIDENTIAL** | SESDEP & Data Analyst Terotorisasi | TLS 1.3 + Role Pimpinan | Sesuai Masa Renstra | `TBD — POLICY VALIDATION REQUIRED` |

---

## 3. Kebijakan Privasi & Perlindungan Data Pribadi (UU PDP Compliance)
- **Masking NIP & Kontak Pribadi:** Nomor Identitas Pegawai (NIP) dan nomor kontak pribadi operator instansi disamarkan (*data masking*) pada antarmuka publik atau log teknis.
- **Prinsip Need-to-Know:** Hanya pengguna yang memiliki relasi penugasan tiket yang diizinkan melihat draf internal sebelum dokumen disahkan menjadi data terbuka publik.
