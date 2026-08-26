# 11. SECURITY & COMPLIANCE ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** DevSecOps Architect & Senior Security Engineer  
> **Prinsip Fundamental:** Zero Trust, Least Privilege, Defense in Depth, Fail Closed, Separation of Duties  

Dokumen ini mendefinisikan arsitektur keamanan (*Security Architecture*) untuk melindungi data kelembagaan nasional pada sistem SIGMA-K dari ancaman siber dan manipulasi tidak sah.

---

## 1. Lapisan Pertahanan Keamanan (Defense in Depth)

```
[ LAYER 1: NETWORK & PERIMETER ]
  - TLS 1.3 Encryption, DDoS Mitigation, Rate Limiting (Redis Window), WAF / Ingress Filtering
                 │
                 ▼
[ LAYER 2: APPLICATION GATEWAY & TRANSPORT ]
  - Security Headers (HSTS, CSP, X-Frame-Options, X-Content-Type-Options)
  - Strict CORS Policy, CSRF Token Protection for Cookie Auth
                 │
                 ▼
[ LAYER 3: IDENTITY & ACCESS MANAGEMENT (IAM) ]
  - Bcrypt Password Hashing, Cryptographic JWT Signing, Refresh Token Rotation
  - Fine-grained RBAC & Scoped Institution Access Guard (BOLA/IDOR Defense)
                 │
                 ▼
[ LAYER 4: DATA ACCESS & STORAGE SECURITY ]
  - Parameterized ORM Queries (Zero SQL Injection), File Magic Bytes Validation
  - Field-level Sanitization (XSS Prevention), Read-Only Database User for Analytics
                 │
                 ▼
[ LAYER 5: GOVERNANCE & AUDIT TRAIL ]
  - Immutable Audit Logging (Snapshot JSONB), Separation of Duties in Approval
```

---

## 2. Mitigasi Terhadap OWASP Top 10

### A. SQL Injection (SQLi) Mitigation
- Seluruh interaksi database wajib menggunakan **Prisma ORM / Parameterized Prepared Statements**.
- Larangan keras terhadap penggunaan raw query string concatenation (`"SELECT * FROM users WHERE name = " + input`).

### B. Cross-Site Scripting (XSS) & Content Security Policy (CSP)
- Framework frontend (React/Next.js) secara default melakukan *automatic string escaping*.
- Server mengirimkan header keamanan `Content-Security-Policy`:
  `default-src 'self'; script-src 'self' 'nonce-...'; object-src 'none'; frame-ancestors 'none';`

### C. Cross-Site Request Forgery (CSRF)
- Refresh token pada cookie dilindungi atribut `SameSite=Strict; Secure; HttpOnly`.
- Setiap mutasi data sensitif via API wajib menyertakan custom header `X-Requested-With: XMLHttpRequest` atau `Authorization: Bearer <token>`.

### D. File Upload Security (PDF Regulasi & Media)
- **MIME Type & Magic Bytes Check:** Backend memvalidasi header biner file (*Magic Bytes: `%PDF-`*) untuk memastikan file benar-benar dokumen PDF, bukan file eksekusi berbahaya (*.exe, .php, .sh*) yang diubah ekstensinya.
- **Ukuran Maksimum:** Dibatasi maksimal **10 MB** per file.
- **Sanitasi Nama Berkas:** Nama file asli dienkripsi ulang menjadi UUID acak (`uploads/{institutionId}/{uuid}.pdf`) untuk mencegah *Path Traversal attack*.
- **Penyimpanan Terisolasi:** Berkas disimpan di MinIO/Object Storage dengan izin *Private*, hanya dapat diakses melalui *Time-Limited Signed URL*.

---

## 3. Prinsip Pemisahan Kewenangan (Separation of Duties)

Sesuai [BRULE-015](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/BUSINESS_RULES.md):
1. **User (Operator):** Hanya dapat mengajukan draf, dilarang menyetujui atau memverifikasi tiketnya sendiri.
2. **Verifikator:** Hanya dapat meneliti dan memberikan rekomendasi, dilarang melakukan final publish ke master data.
3. **Admin:** Berwenang melakukan final approval, namun seluruh tindakan terekam di log audit dengan identitas user pengesah.

---

## 4. Manajemen Rahasia & Kredensial (Secrets Management)
- Seluruh kredensial database, JWT Secret Key, dan kredensial Redis dikelola secara aman melalui variabel *Environment* (`.env`) yang diinjeksi via Docker Secret / Kubernetes Secret.
- Repositori GitHub diproteksi dengan *pre-commit hook (git-secrets / gitleaks)* untuk mencegah kredensial ter-commit ke source code.
