# 18. DEVOPS, CI/CD & DEPLOYMENT ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** DevSecOps Architect & Senior Infrastructure Engineer  
> **Prinsip:** *Deployment-Agnostic & Zero-Cloud-Lock-in* (Siap jalan di PDN, VM On-Premise, atau Cloud KemenPANRB).

Dokumen ini mendefinisikan arsitektur otomatisasi pengiriman (*CI/CD Pipeline*), standarisasi kontainerisasi (*Docker*), dan manajemen konfigurasi lingkungan deployment SIGMA-K.

---

## 1. Topologi Deployment Kontainer (Docker Compose Target)

```
                                  [ INTERNET / INTRANET KemenPANRB ]
                                                  │
                                                  │ HTTPS :443 / WSS
                                                  ▼
                        +---------------------------------------------------+
                        |             NGINX REVERSE PROXY CONTAINER         |
                        | - SSL Termination (Let's Encrypt / KemenPANRB SSL)|
                        | - Gzip / Brotli Compression, Security Headers     |
                        | - Rate Limiting & Proxy Forwarding                |
                        +-------------------------┬-------------------------+
                                                  │
                                 ┌────────────────┴────────────────┐
                                 │ HTTP :3000                      │ HTTP/WSS :4000
                                 ▼                                 ▼
+---------------------------------------------------+   +---------------------------------------------------+
|            FRONTEND CONTAINER (Next.js)           |   |             BACKEND CONTAINER (NestJS)            |
| - Node 20 Alpine Multi-Stage Build                |   | - Node 20 Alpine Multi-Stage Build                |
| - Non-Root User (`nextjs:nodejs`)                 |   | - Non-Root User (`nestjs:nodejs`)                 |
| - Standalone Output Mode (Ultra Lightweight)      |   | - Production Clustering / PM2                     |
+---------------------------------------------------+   +-------------------------┬-------------------------+
                                                                                  │
                                                         ┌────────────────────────┼────────────────────────┐
                                                         │ :5432                  │ :6379                  │ :9000
                                                         ▼                        ▼                        ▼
                                                +------------------+     +------------------+     +------------------+
                                                | POSTGRESQL 16 DB |     |   REDIS 7 CACHE  |     |   MinIO STORAGE  |
                                                | Named Volume:    |     | Named Volume:    |     | Named Volume:    |
                                                | `pg_data`        |     | `redis_data`     |     | `minio_data`     |
                                                +------------------+     +------------------+     +------------------+
```

---

## 2. Pipa Otomatisasi CI/CD (GitHub Actions Workflow)

```
[ PUSH / PULL REQUEST KE GITHUB ]
               │
               ▼
+-----------------------------------------------------------------------------------+
| STAGE 1: CODE QUALITY & SECURITY LINT (GitHub Actions Runner)                     |
| - TypeScript Strict Typecheck (`npm run type-check`)                              |
| - ESLint & Prettier Code Standards (`npm run lint`)                               |
| - Security Scan Dependensi (`npm audit` & Snyk / Gitleaks)                        |
+-----------------------------------------------------------------------------------+
               │ (Pass)
               ▼
+-----------------------------------------------------------------------------------+
| STAGE 2: AUTOMATED TESTING (Unit & Integration Tests)                            |
| - Eksekusi Unit Tests via Vitest / Jest (`npm run test`)                          |
| - Eksekusi Integration Tests terhadap isolated Test Database (`npm run test:e2e`) |
+-----------------------------------------------------------------------------------+
               │ (Pass on `main` / `staging` branch)
               ▼
+-----------------------------------------------------------------------------------+
| STAGE 3: CONTAINER BUILD & REGISTRY PUSH                                          |
| - Build Multi-stage Docker Image untuk Frontend & Backend                         |
| - Push Image ke GitHub Packages / Private Container Registry                      |
+-----------------------------------------------------------------------------------+
               │ (Automated / Manual Trigger)
               ▼
+-----------------------------------------------------------------------------------+
| STAGE 4: DEPLOYMENT EXECUTION (Target Server KemenPANRB / Staging VM)             |
| - SSH Deployment Script / Self-Hosted Runner                                      |
| - Pull Docker Images Terbaru & Jalankan Zero-Downtime Rolling Update              |
| - Jalankan Database Migration Scripts terisolasi                                  |
| - Eksekusi Health Check Verification (`/api/v1/health/readiness`)                 |
+-----------------------------------------------------------------------------------+
```

---

## 3. Strategi Pencadangan & Pemulihan (Backup & Disaster Recovery)
1. **Automated Scheduled Backup:** Script otomatis menjalankan dump database harian (`pg_dump -Fc sigma_k_db`) yang dienkripsi dan disimpan di lokasi terisolasi (off-site storage).
2. **Rollback Mechanism:**
   - Image Docker ditandai dengan Semantic Version dan Git Commit SHA unik (misal: `sigma-backend:v1.2.0-sha123`).
   - Jika rilis baru mengalami kegagalan fungsi, rollback dapat dilakukan dalam waktu $< 2$ menit dengan mengembalikan tag image sebelumnya via Docker Compose (`docker compose up -d`).
