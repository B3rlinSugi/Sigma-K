# 11. DATA MIGRATION QUALITY GATES & RECONCILIATION: SIGMA-K

> **Status:** DATA ARCHITECTURE BASELINE  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Data Governance Architect & Migration Lead  
> **Pemeriksa:** Ikhsan (Data Analyst) & Pak Sigit (Mentor Lead Data Analyst)  
> **Prinsip:** *"Migrasi tidak boleh dianggap sukses hanya karena jumlah baris sama."*

Dokumen ini mendefinisikan 10 Gerbang Kualitas Migrasi (*Migration Quality Gates*) yang wajib lulus 100% sebelum data hasil migrasi dinyatakan sah untuk lingkungan produksi.

---

## 1. Sepuluh Gerbang Kualitas Migrasi (10 Migration Quality Gates)

| Gate ID | Nama Quality Gate | Kriteria Keberhasilan (*Acceptance Threshold*) | Kueri / Prosedur Pengujian | Tindakan jika Gagal (*Failure Protocol*) |
|---|---|---|---|---|
| **GATE-01** | **Row Reconciliation** | $100\%$ baris data valid dari legacy terhitung dan terpetakan (K/L + Pemda = Total Target). | Rekonsiliasi `COUNT(*)` tabel sumber vs `COUNT(*)` tabel target + `_migration_quarantine`. | Investigasi selisih baris; migrasi dihentikan. |
| **GATE-02** | **Primary Key Integrity** | $0\%$ duplikasi dan $0\%$ nilai null pada seluruh kolom Primary Key (UUIDv4 / BigSerial). | `SELECT id, COUNT(*) FROM institutions GROUP BY id HAVING COUNT(*) > 1;` | Gagalkan migrasi; perbaiki generator UUID. |
| **GATE-03** | **Foreign Key Integrity** | $0\%$ orphan record pada seluruh relasi relasional (`institution_type_id`, `region_id`, `cabinet_id`, `parent_id`). | `SELECT o.id FROM organization_units o LEFT JOIN institutions i ON o.institution_id = i.id WHERE i.id IS NULL;` | Karantina baris orphan; relasikan ke parent valid. |
| **GATE-04** | **Duplicate Code & Name Detection** | $0\%$ duplikasi kode instansi unik nasional. | `SELECT code, COUNT(*) FROM institutions GROUP BY code HAVING COUNT(*) > 1;` | Koreksi kode instansi yang bentrok; minta validasi Ikhsan. |
| **GATE-05** | **Mandatory Field Null Validation** | $0\%$ nilai null pada kolom wajib (`name`, `status`, `created_at`, `type_id`). | `SELECT COUNT(*) FROM institutions WHERE name IS NULL OR status IS NULL;` | Gagalkan load; perbaiki cleansing transform. |
| **GATE-06** | **Reference Master Validation** | $100\%$ tipe instansi dan eselon memetakan ke ID master yang sah. | `SELECT DISTINCT institution_type_id FROM institutions WHERE institution_type_id NOT IN (SELECT id FROM institution_types);` | Tambahkan master referensi yang hilang. |
| **GATE-07** | **Historical Temporal Consistency** | $100\%$ periode kabinet memiliki `start_date <= end_date` (atau `end_date IS NULL`). | `SELECT COUNT(*) FROM cabinet_periods WHERE end_date IS NOT NULL AND end_date < start_date;` | Koreksi tanggal penetapan Keppres. |
| **GATE-08** | **Cabinet Membership Consistency** | $0\%$ kementerian ganda pada periode kabinet yang sama. | `SELECT cabinet_period_id, institution_id, COUNT(*) FROM cabinet_memberships GROUP BY cabinet_period_id, institution_id HAVING COUNT(*) > 1;` | De-duplikasi keanggotaan kabinet. |
| **GATE-09** | **Org Tree Hierarchy & Cycle Guard** | $0\%$ circular dependency pada bagan organisasi; tepat 1 pimpinan tertinggi (`parent_id IS NULL`) per instansi. | Eksekusi kueri Recursive CTE cycle detector di seluruh instansi. | Perbaiki `parent_id` unit kerja yang melingkar. |
| **GATE-10** | **Business Rule & RBAC Integrity** | Seluruh akun pengguna terikat pada role yang valid dan memiliki hash password terenkripsi aman. | `SELECT COUNT(*) FROM users WHERE password_hash LIKE '$2b$%' = FALSE;` | Re-hash seluruh password ke Bcrypt. |

---

## 2. Format Berita Acara Rekonsiliasi Migrasi (Reconciliation Sign-Off Protocol)
Setelah seluruh GATE-01 s.d GATE-10 dinyatakan **PASS**, Data Analyst (Ikhsan) bersama Lead Engineer (Berlin) menyusun *Migration Validation Report* yang memuat bukti komparasi record sebelum disahkan oleh SESDEP / Pembimbing.
