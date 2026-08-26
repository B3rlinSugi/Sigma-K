# 17. OBSERVABILITY & MONITORING ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** DevSecOps Architect & Senior Site Reliability Engineer  

Dokumen ini mendefinisikan arsitektur pemantauan sistem (*Observability & Monitoring Architecture*), memisahkan secara tegas antara **Technical Logs** (untuk keandalan sistem) dan **Business Audit Trail** (untuk kepatuhan tata kelola hukum).

---

## 1. Pemisahan Tegas: Technical Log vs Business Audit Trail

```
+-----------------------------------------------------------------------------------+
|                        OBSERVABILITY DUAL-PIPELINE                                |
+-----------------------------------------------------------------------------------+
| 1. TECHNICAL LOGGING PIPELINE (For Developers & SysAdmins):                       |
|    - Structured JSON Logs (Pino / Winston) ke stdout / rotating log file.         |
|    - Level: DEBUG, INFO, WARN, ERROR, FATAL.                                      |
|    - Tujuan: Root cause analysis, crash diagnostics, performance profiling.       |
|    - Sifat: Ephemeral / Retensi 30-90 hari.                                       |
|                                                                                   |
| 2. BUSINESS AUDIT TRAIL (For SESDEP, Auditors, & Compliance):                     |
|    - Immutable Table `audit_logs` di PostgreSQL.                                  |
|    - Payload: User ID, Action Type, Entity ID, Snapshot Old/New Values JSONB.     |
|    - Tujuan: Forensik akuntabilitas perubahan data kelembagaan kementerian.       |
|    - Sifat: Permanent / Retensi Abadi (Append-Only).                              |
+-----------------------------------------------------------------------------------+
```

---

## 2. Standar Technical Logging (Structured JSON Logging)

Setiap request HTTP yang masuk secara otomatis diinjeksi **Correlation ID (X-Request-ID)** yang diteruskan ke seluruh log tracing:

```json
{
  "timestamp": "2026-08-25T20:30:15.123Z",
  "level": "INFO",
  "traceId": "req-88a1-44bc-91e2",
  "context": "WorkflowService",
  "message": "Submission ticket submitted successfully",
  "metadata": {
    "ticketId": "tkt-8812",
    "institutionId": "inst-pangan-01",
    "userId": "usr-8812",
    "durationMs": 42
  }
}
```

---

## 3. Health Checks & Probes (Kesiapan Kontainer)

Backend menyediakan endpoint diagnostik standar untuk container orchestrator (Docker / Kubernetes):
1. **Liveness Probe (`GET /api/v1/health/liveness`):** Memastikan proses server Node.js aktif dan merespon request (status `200 OK`).
2. **Readiness Probe (`GET /api/v1/health/readiness`):** Memeriksa konektivitas terhadap database PostgreSQL dan cache Redis. Jika salah satu dependensi mati, probe mengembalikan status `503 Service Unavailable` sehingga traffic tidak diarahkan ke instance yang bermasalah.

---

## 4. Metrik Kinerja Aplikasi (Metrics Baseline)
- Pelacakan metrik dasar (*Prometheus-compatible endpoint `/metrics`*):
  - Request throughput per second (RPS).
  - Distribusi latensi HTTP (P50, P95, P99).
  - Jumlah koneksi aktif WebSocket realtime.
  - Ukuran antrean job background di Redis.
