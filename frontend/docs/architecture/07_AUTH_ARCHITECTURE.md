# 07. AUTHENTICATION ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Security Architect & Lead Full-Stack Engineer  
> **Prinsip Utama:** *Pluggable Authentication Strategy* (Mandiri Lokal saat ini, siap integrasi SSO Nasional di masa depan).

Dokumen ini mendefinisikan arsitektur otentikasi (*Authentication Architecture*) untuk sistem SIGMA-K, mencakup penanganan sesi, keamanan kredensial, dan modul adaptor SSO yang dapat dilepas-pasang.

---

## 1. Diagram Arsitektur Otentikasi Terpadu (Pluggable Strategy)

```
+-----------------------------------------------------------------------------------+
|                           AUTHENTICATION ENTRYPOINT                               |
+-----------------------------------------------------------------------------------+
                                         │
                   ┌─────────────────────┴─────────────────────┐
                   ▼                                           ▼
      [ LOCAL DATABASE AUTH ]                     [ PLUGGABLE SSO ADAPTER ]
      - Username/Email + Password                 - OpenID Connect (OIDC) / OAuth2
      - Bcrypt / Argon2 Hashing                   - KemenPANRB / ASN Digital SSO (TBD)
      - Brute-Force Rate Limiter                  - Claims to Local User Mapping
                   │                                           │
                   └─────────────────────┬─────────────────────┘
                                         ▼
+-----------------------------------------------------------------------------------+
|                        TOKEN & SESSION ISSUANCE ENGINE                            |
|  - Generate Short-lived Access Token (JWT - 15 mins)                              |
|  - Generate Cryptographic Refresh Token (UUID / Opaque Token - 8 hours)           |
|  - Store Refresh Token Session in Redis with TTL                                  |
|  - Set Secure HttpOnly, SameSite=Strict, Secure Cookies                           |
+-----------------------------------------------------------------------------------+
```

---

## 2. Rincian Mekanisme Otentikasi

### A. Local Authentication Strategy (Baseline Implementasi)
1. **Password Security Standard:**
   - Password pengguna di-hash menggunakan algoritma **Bcrypt** dengan *cost factor* 12 (atau **Argon2id**).
   - Kebijakan Kompleksitas Sandi: Minimal 8 karakter, kombinasi huruf besar, huruf kecil, angka, dan simbol khusus (standar keamanan instansi pemerintah).
2. **Mitigasi Serangan Brute-Force:**
   - Endpoint login dilindungi oleh **Redis Sliding Window Rate Limiter** (maksimal 5 kali percobaan gagal per IP/Username dalam 15 menit).
   - Akun dikunci sementara (*temporary lockout*) selama 15 menit jika melampaui batas percobaan.

### B. Dual-Token Architecture (Access & Refresh Token)
- **Access Token (JWT - Stateless):**
  - *Masa Berlaku:* 15 Menit.
  - *Payload Claims:* `sub` (User ID), `username`, `role` (USER/VERIFIKATOR/ADMIN/PIMPINAN), `institutionId` (Scope), `permissions` (Array), `iat`, `exp`.
  - *Penyimpanan:* Memori runtime client (tidak disimpan di LocalStorage untuk mencegah pencurian token via XSS).
- **Refresh Token (Opaque Token - Stateful):**
  - *Masa Berlaku:* 8 Jam (sesuai jam kerja ASN).
  - *Penyimpanan:* Dikirim sebagai `HttpOnly`, `SameSite=Strict`, `Secure` Cookie.
  - *Server-side Session:* Disimpan di Redis dengan key `session:{userId}:{refreshTokenId}`.
- **Refresh Token Rotation:** Setiap kali access token diperbarui via refresh token, token refresh lama di-revoke dan digantikan token refresh baru untuk mencegah *replay attacks*.

### C. Logout & Instant Token Revocation
- Saat pengguna melakukan logout:
  1. Refresh token dihapus dari Redis dan cookie client di-clear.
  2. JTI (*JWT ID*) access token dimasukkan ke dalam daftar hitam (*Redis Token Blacklist*) dengan TTL sisa masa berlaku token, memastikan sesi langsung terputus seketika.

---

## 3. Desain Adaptor SSO yang Dapat Dilepas-Pasang (Pluggable SSO Architecture)

Untuk mengantisipasi integrasi SSO KemenPANRB / ASN Digital Nasional ([REQ-026](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md)), backend mengimplementasikan **Auth Strategy Pattern**:

```typescript
// Blueprint Konseptual Adaptor Otentikasi
export interface IAuthStrategy {
  authenticate(credentialsOrToken: any): Promise<AuthUserResult>;
}

export class LocalAuthStrategy implements IAuthStrategy {
  async authenticate(credentials: LoginDto): Promise<AuthUserResult> {
    // Validasi username dan password bcrypt lokal
  }
}

export class OidcSSOAuthStrategy implements IAuthStrategy {
  async authenticate(idToken: string): Promise<AuthUserResult> {
    // Validasi token klaim OIDC dari IdP KemenPANRB / ASN Digital
    // Melakukan auto-provisioning atau linking akun user lokal
  }
}
```
Dengan arsitektur ini, integrasi SSO dapat diaktifkan kapan saja hanya dengan mengonfigurasi variabel *environment* tanpa merombak arsitektur controller maupun middleware otorisasi.
