# 08. AUTHORIZATION & RBAC ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Security Architect & Principal Engineer  

Dokumen ini mendefinisikan arsitektur otorisasi (*Authorization Architecture*) SIGMA-K yang menggabungkan **Role-Based Access Control (RBAC)** dan **Scope-Based Authorization (ABAC Scoping)** guna mencegah akses tidak sah antar-instansi kementerian/lembaga.

---

## 1. Model Otorisasi Berlapis (Layered Authorization Model)

```
[ INCOMING REQUEST ]
        │
        ▼
+-----------------------------------------------------------------------------------+
| 1. AUTHENTICATION GUARD (JwtAuthGuard)                                            |
|    - Memvalidasi keabsahan token JWT dan identitas pengguna.                      |
+-----------------------------------------------------------------------------------+
        │
        ▼
+-----------------------------------------------------------------------------------+
| 2. ROLE & PERMISSION GUARD (RolesGuard / PermissionsGuard)                        |
|    - Memeriksa apakah role pengguna memiliki izin untuk aksi tersebut.            |
|    - Contoh: Apakah role memiliki izin 'EDIT_INSTITUTION_DRAFT'?                  |
+-----------------------------------------------------------------------------------+
        │
        ▼
+-----------------------------------------------------------------------------------+
| 3. INSTITUTION SCOPING GUARD (InstitutionScopeGuard / BOLA-IDOR Protection)       |
|    - Jika Aktor adalah 'USER', periksa apakah user.institution_id == target.id.   |
|    - Mencegah operator Kementerian A mengedit data Kementerian B!                 |
+-----------------------------------------------------------------------------------+
        │
        ▼
[ ACCESS GRANTED TO SERVICE LAYER ]
```

---

## 2. Matriks Izin Peran Komprehensif (Permission Matrix)

| Kode Izin (*Permission Code*) | USER (Operator) | VERIFIKATOR | ADMIN (Pusat) | PIMPINAN (SESDEP) | DATA ANALYST |
|---|:---:|:---:|:---:|:---:|:---:|
| `VIEW_DASHBOARD_EXECUTIVE` | Terbatas | Terbatas | Full | **Full** | Full |
| `READ_INSTITUTION_CATALOG` | Yes | Yes | Yes | Yes | Yes |
| `READ_INSTITUTION_DETAIL` | Yes (All) | Yes (All) | Yes (All) | Yes (All) | Yes (All) |
| `CREATE_MASTER_INSTITUTION`| No | No | **Yes** | No | No |
| `EDIT_INSTITUTION_DRAFT` | **Scoped Only** | No | Yes | No | No |
| `MANAGE_ORG_TREE_DRAFT` | **Scoped Only** | No | Yes | No | No |
| `MANAGE_TUPOKSI_DRAFT` | **Scoped Only** | No | Yes | No | No |
| `SUBMIT_CHANGE_TICKET` | **Scoped Only** | No | Yes | No | No |
| `REVIEW_VERIFICATION_QUEUE`| No | **Yes** | Yes | No | No |
| `VERIFY_SUBMISSION` | No | **Yes** | Yes | No | No |
| `APPROVE_SUBMISSION` | No | No | **Yes** | No | No |
| `MANAGE_CABINETS_MASTER` | No | No | **Yes** | No | No |
| `MANAGE_CABINET_MEMBERSHIP`| No | No | **Yes** | No | No |
| `VIEW_AUDIT_LOGS` | No | No | **Yes** | View Summary | No |
| `MANAGE_USERS_AND_ROLES` | No | No | **Yes** | No | No |
| `VIEW_ANALYTICS_ADVANCED` | No | No | Yes | **Yes** | **Yes** |
| `EXPORT_REPORT_DATASET` | Scoped | Yes | Yes | **Yes** | **Yes** |

---

## 3. Mitigasi BOLA / IDOR (Broken Object Level Authorization)

Salah satu risiko keamanan terbesar pada sistem pemerintahan multi-instansi adalah **BOLA / IDOR** (di mana operator Kementerian Agama mencoba mengedit draf Kementerian Keuangan dengan mengubah parameter URL ID).

### Mekanisme Proteksi Scoping:
1. **Decorator `@ScopedInstitution()`:**
   - Ditempelkan pada seluruh endpoint mutasi data instansi.
   - Guard secara otomatis mengekstrak `institutionId` dari token JWT user.
   - Jika role adalah `USER` dan ID instansi target berbeda dengan ID token pengguna, request **otomatis digagalkan dengan HTTP 403 Forbidden** dan insiden dicatat di log audit keamanan.
2. **Global Query Scoping di Repository:**
   - Pada lapisan data access, seluruh query mutasi yang dieksekusi oleh `USER` secara otomatis diinjeksi klausa: `WHERE institution_id = :currentUserInstitutionId`.
