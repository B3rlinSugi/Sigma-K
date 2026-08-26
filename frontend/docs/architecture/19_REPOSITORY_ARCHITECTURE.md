# 19. REPOSITORY & WORKSPACE ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Software Architect & Lead Full-Stack Engineer  

Dokumen ini mengevaluasi dan merancang struktur repositori kode sumber (*Repository Architecture*) untuk mendukung kolaborasi efisien antara Full-Stack Engineer, Data Analyst, dan Pembimbing selama periode magang.

---

## 1. Evaluasi Komparatif: Monorepo vs Multi-Repo

| Kriteria Evaluasi | Opsi A: Monorepo Terpadu (Turborepo / Workspaces) | Opsi B: Multi-Repo Terpisah (`sigma-web`, `sigma-api`, `sigma-data`) |
|---|---|---|
| **Kesesuaian Tim Kecil (Berlin + Ikhsan)** | **Sangat Tinggi (1 Repositori Terpusat).** | Rendah (Konteks terpecah di 3 repositori berbeda). |
| **Penyelarasan Tipe Data (Type Sharing)** | **Sangat Sempurna (Shared `@sigma/types` antara Frontend & Backend).** | Rumit (Harus publish package npm privat atau copy-paste DTO). |
| **Atomic Commit & Refactoring** | **Dapat mengubah API contract dan UI dalam 1 Pull Request atomik.** | Memerlukan koordinasi sinkronisasi merge lintas repositori. |
| **Kemudahan Onboarding & Dev Setup** | **1 Command Setup (`npm install && docker compose up`).** | Harus clone 3 repo terpisah dan setup env masing-masing. |
| **Dokumentasi & Handover SESDEP** | **Seluruh dokumentasi (`docs/`) terpadu di satu tempat.** | Dokumentasi tersebar dan berisiko tidak sinkron. |

---

## 2. Rekomendasi Resmi: Monorepo Terpadu (Turborepo / Workspaces)

### Struktur Repositori Resmi SIGMA-K:

```
SIGMA-K/                          # Root Repository Workspace
├── apps/                         # Executable Applications
│   ├── web/                      # Frontend Application (Next.js 14+ / React)
│   │   ├── src/
│   │   ├── public/
│   │   ├── package.json
│   │   └── next.config.mjs
│   └── api/                      # Backend Core Application (NestJS 10+)
│       ├── src/
│       ├── test/
│       ├── package.json
│       └── nest-cli.json
├── packages/                     # Shared Internal Packages
│   ├── types/                    # Shared TypeScript DTOs, Enums, Interfaces (@sigma/types)
│   │   ├── src/
│   │   └── package.json
│   ├── database/                 # Prisma Schema & Database Client (@sigma/database)
│   │   ├── prisma/schema.prisma
│   │   └── package.json
│   └── eslint-config/            # Shared Linting & Prettier Rules
├── docs/                         # Centralized Project Documentation
│   ├── discovery/                # Phase 0 Discovery Artifacts (10 docs)
│   ├── requirements/             # Phase 1 Requirement Specs (16 docs)
│   └── architecture/             # Phase 2 Technical Blueprints (26 docs + ADRs)
├── infrastructure/               # DevOps & Container Configs
│   ├── docker/                   # Dockerfiles for Web & API
│   ├── nginx/                    # Reverse Proxy Nginx Config
│   └── docker-compose.yml        # Multi-Container Compose Setup
├── package.json                  # Root Monorepo Workspace Config
├── turbo.json                    # Turborepo Build Pipeline & Caching
└── README.md                     # Project Master Readme & Quickstart
```

---

## 3. Strategi Percabangan Git (Branching & Versioning Strategy)

Mengadopsi **Trunk-Based Development with Short-Lived Feature Branches**:
1. **`main` (Production Branch):** Selalu dalam kondisi stabil dan siap deploy/presentasi.
2. **`staging` (Integration Branch):** Tempat integrasi fitur sebelum masuk ke presentasi SESDEP.
3. **`feature/xxx` (Feature Branch):** Branch kerja sementara (misal: `feature/cabinet-membership`, `feature/org-chart-canvas`, `feature/analytics-posture`).
4. **Conventional Commits Standard:** Seluruh commit wajib mematuhi standar format:
   - `feat(cabinets): implement historical comparison logic`
   - `fix(org-tree): prevent circular dependency on re-parenting`
   - `docs(architecture): add repository architecture blueprint`
