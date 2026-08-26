# E-SKLD BACKEND DOCUMENTATION — STEP 11
## COMPREHENSIVE AUDIT LOGGING & EXECUTIVE REPORTING DASHBOARD / EXPORT API

---

## 1. Objective

Step 11 mengimplementasikan lapisan pelaporan eksekutif (*Executive Reporting Dashboard*), penelaahan log audit komprehensif (*Comprehensive Scoped Audit Log Viewing*), dan ekspor data aman (*Streaming CSV/JSON Export API*) di atas fondasi sistem E-SKLD Step 1–10 dengan kepatuhan penuh terhadap prinsip *Zero-Trust Authorization*, isolasi multi-tenant, dan tanpa memodifikasi skema database.

---

## 2. API Endpoints

### A. Audit Logging Endpoints
| Method | Endpoint | Filter | Deskripsi & Otorisasi |
|---|---|:---:|---|
| `GET` | `/api/v1/audit-logs` | `auth` | Daftar log audit terpaginasi dengan filter (`actor_id`, `actor_role`, `action_event`, `resource_entity`, `resource_id`, `institution_id`, `date_from`, `date_to`, `search`). Dibatasi sesuai scope institusi aktor. |
| `GET` | `/api/v1/audit-logs/(:num)` | `auth` | Detail satu rekaman log audit beserta payload lama/baru JSON terurai. Memvalidasi batasan wewenang instansi. |
| `GET` | `/api/v1/audit-logs/export` | `auth` | Mengunduh berkas log audit terfilter dalam format `CSV` (`text/csv`) atau `JSON` (`application/json`). |

### B. Executive Reporting & Dashboard Endpoints
| Method | Endpoint | Filter | Deskripsi & Otorisasi |
|---|---|:---:|---|
| `GET` | `/api/v1/reports/summary` | `auth` | Metrik kartu KPI eksekutif (Total Instansi, Unit Aktif/Inaktif, Formasi Jabatan, Total Usulan, Breakdown Funnel Siklus Usulan). |
| `GET` | `/api/v1/reports/submissions` | `auth` | Rekapitulasi usulan berdasarkan status (`DRAFT`, `SUBMITTED`, `REVISION`, `VERIFIED`, `APPROVED`, `PROMOTED`), tahun pengajuan, dan instansi. |
| `GET` | `/api/v1/reports/institutions` | `auth` | Agregasi kelembagaan per K/L/D (jumlah unit aktif, jabatan aktif, total formasi, dan total usulan). |
| `GET` | `/api/v1/reports/approvals` | `auth` | Rekapitulasi penetapan persetujuan resmi (nomor SK/persetujuan, nama verifikator penyetuju, NIP, tanggal persetujuan). |
| `GET` | `/api/v1/reports/promotions` | `auth` | Rekapitulasi riwayat promosi snapshot usulan ke master data aktif. |
| `GET` | `/api/v1/reports/export` | `auth` | Mengunduh dataset laporan dalam format `CSV` atau `JSON` (`type = submissions / institutions / approvals / promotions`). |

---

## 3. Scoped Authorization Matrix

| Persona / Role | Cakupan Log Audit | Cakupan Laporan Eksekutif | Cakupan Ekspor Data |
|---|---|---|---|
| **`USER`** | Hanya log aktivitas miliknya sendiri (`actor_id = self`) ATAU peristiwa pada berkas usulannya di instansi asal. | Ringkasan usulan, unit, dan formasi instansi asalnya. | Ekspor CSV/JSON terbatas pada data/log miliknya sendiri. |
| **`ADMIN`** | Log peristiwa pada sumber daya di dalam instansi asal + instansi penugasan (`user_scopes`) + instansi delegasi aktif (`access_grants`). | Ringkasan metrik untuk seluruh instansi dalam scope kewenangannya. | Ekspor CSV/JSON untuk seluruh instansi dalam batas scope. |
| **`VERIFIER`** | Log peristiwa pada berkas usulan yang ditugaskan kepada verifikator atau berada dalam batas wilayah telaahnya. | Ringkasan usulan diverifikasi, disetujui, dan direkomendasikan dalam batas scope. | Ekspor CSV/JSON untuk usulan yang ditangani. |
| **`SUPER_ADMIN`** | Log audit global tak terbatas di seluruh instansi dan pengguna (izin `VIEW_AUDIT`). | Dashboard eksekutif nasional penuh (seluruh K/L/D dan formasi nasional). | Ekspor global CSV/JSON di seluruh entitas. |

---

## 4. Security & BOLA/IDOR Protections

1. **Anti-BOLA/IDOR Enforcement**: Parameter query instansi dari klien (`institution_id`) tidak dipercaya secara mentah. Sistem memvalidasi dan mengiriskan (*intersect*) parameter tersebut dengan daftar instansi berwenang aktor melalui `ScopeResolver::getAuthorizedInstitutionIds()`. Permintaan di luar batas kewenangan ditolak dengan HTTP `403 Forbidden`.
2. **Append-Only Integrity**: Tabel `audit_logs` dilindungi di level model [`AuditLogModel`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/app/Models/AuditLogModel.php) dengan pelarangan mutasi (`update()` dan `delete()` memicu `BadMethodCallException`).
3. **Payload Sanitization**: Kolom sensitif seperti password hash tidak pernah dicatat atau diekspos dalam payload log audit.

---

## 5. Streaming Export Strategy

- **Format yang Didukung**: `CSV` berstandar RFC 4180 (`text/csv; charset=UTF-8`) dan format terstruktur `JSON` (`application/json`).
- **Efisiensi Memori**: Ekspor CSV memanfaatkan buffer stream `php://temp` dengan penulisan bertahap (*batched cursor stream*), mencegah lonjakan memori (*out of memory*) pada dataset besar.
- **Tanpa Dependensi Tambahan**: Menggunakan parser native PHP `fputcsv` tanpa ketergantungan pada library pihak ketiga.

---

## 6. Realtime Presence (Future Architecture)

- Sesuai Keputusan Terbuka `OPEN-005`, kehadiran daring (*Online / Offline Realtime Presence*) dicatat sebagai persyaratan masa depan dan **TIDAK** diimplementasikan menggunakan infrastruktur WebSocket, SSE, Socket.IO, atau Redis prematur pada Step 11.

---

## 7. Database Impact

- **Perubahan Skema / DDL**: **0 (NIL)**.
- Seluruh query filtering dan agregasi dioptimalkan menggunakan indeks gabungan eksisting (`idx_audit_investigation`, `idx_audit_actor`, `idx_submissions_queue`).

---

## 8. Test Coverage

- **Suite Uji Baru**: [`tests/unit/AuditReportingTest.php`](file:///c:/Users/Berlin%20Sugiyanto/KemenPANRB/tests/unit/AuditReportingTest.php) (20 test methods mencakup `AUDIT-01..10`, `REPORT-01..07`, `EXPORT-01..04`, dan skenario keamanan).
- **Total Uji Terakumulasi**: **180 tests, 633 assertions, 0 errors, 0 failures (100% PASS)**.
