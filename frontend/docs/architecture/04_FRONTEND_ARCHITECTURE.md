# 04. FRONTEND ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Principal Frontend Engineer & Solutions Architect  
> **Stack Terpilih:** Next.js 14+ (App Router) + TypeScript + Tailwind CSS + TanStack Query + Zustand + React Flow  

Dokumen ini mendefinisikan arsitektur antarmuka pengguna (*Frontend Architecture*) untuk aplikasi web SIGMA-K yang dirancang untuk menghasilkan performa tinggi, modularitas, dan tampilan *Executive Grade* untuk pimpinan/SESDEP.

---

## 1. Pola Struktur Arsitektur Frontend (Feature-Driven Architecture)

Aplikasi frontend diorganisasikan menggunakan pola **Feature-Driven Architecture** di dalam direktori `src/` Next.js App Router:

```
src/
├── app/                          # Next.js App Router (Routing & Layouts)
│   ├── (auth)/                   # Route Group: Login, Forgot Password
│   │   ├── login/page.tsx
│   │   └── layout.tsx
│   ├── (dashboard)/              # Route Group: Authenticated Core Workspace
│   │   ├── layout.tsx            # Executive Shell (Navbar, Sidebar, Notification Center)
│   │   ├── page.tsx              # Executive Overview Dashboard (PAGE-002)
│   │   ├── institutions/         # Institution Catalog & Detail (PAGE-003, PAGE-004)
│   │   ├── cabinets/             # Cabinet Master & Membership (PAGE-005, PAGE-006, PAGE-007)
│   │   ├── org-chart/            # Interactive Tree Visualizer (PAGE-008)
│   │   ├── tupoksi/              # Duty & Function Governance (PAGE-009)
│   │   ├── submissions/          # Draft & Submission Workspace (PAGE-010)
│   │   ├── verifications/        # Verifier Queue & Diff Viewer (PAGE-011)
│   │   ├── approvals/            # Admin Final Approval (PAGE-012)
│   │   ├── audit-trail/          # Audit Trail Viewer (PAGE-014)
│   │   └── analytics/            # ASN Posture & Executive Intelligence (PAGE-015)
│   └── api/                      # Next.js BFF / Proxy Handlers (if needed)
├── components/                   # Reusable UI Component Library
│   ├── ui/                       # Base Primitives (Buttons, Dialogs, Inputs, Tooltips - Radix UI)
│   ├── layout/                   # Navbar, Sidebar, Breadcrumbs, UserDropdown
│   ├── feedback/                 # RealtimeToast, EmptyState, LoadingSkeleton
│   └── visualization/            # OrgChartCanvas, DiffViewer, MetricCard, CabinetComparison
├── features/                     # Domain Feature Modules (State, Hooks, API Clients)
│   ├── auth/                     # useAuth, authStore, tokenStorage
│   ├── institutions/             # useInstitutions, institutionApi, InstitutionForm
│   ├── cabinets/                 # useCabinets, cabinetMembershipApi, LineageDiagram
│   ├── org-structure/            # useOrgTree, orgTreeUtils (Cycle Check), NodeCard
│   ├── workflow/                 # useSubmissions, useVerifications, DiffComponent
│   ├── realtime/                 # useSocket, notificationStore, RealtimeToastContainer
│   └── analytics/                # useAnalytics, PostureChart, EchelonBarGraph
├── hooks/                        # Global Custom Hooks (useDebounce, useMediaQuery, useTheme)
├── lib/                          # Shared Utilities, API Client (Axios instance), Date Formatter
├── styles/                       # Global CSS, Tailwind Design Tokens, Fonts
└── types/                        # TypeScript Type Definitions & API DTO Contracts
```

---

## 2. Strategi State Management & Data Fetching

Frontend SIGMA-K membagi state management secara tegas menjadi 3 kategori:

```
+-----------------------------------------------------------------------------------+
|                        FRONTEND STATE MANAGEMENT STRATEGY                         |
+-----------------------------------------------------------------------------------+
| 1. SERVER CACHE STATE (TanStack Query v5):                                        |
|    - Master Data Instansi, Kabinet, Unit Kerja, Tiket Verifikasi, Feed Notifikasi.|
|    - Otomatis: Caching, Deduping, Background Invalidation on Realtime Event.      |
|                                                                                   |
| 2. GLOBAL CLIENT STATE (Zustand Stores):                                          |
|    - Auth Session State (Token, Current User Profile, Role, Scoped Institution).   |
|    - Notification Store (Unread Badge Count, Realtime Toast Queue).               |
|    - UI Theme & Preferences (Dark/Light mode, Sidebar Collapsed).                |
|                                                                                   |
| 3. LOCAL / COMPONENT STATE (React useState / useReducer / React Hook Form):      |
|    - Form Wizard Draf, Org Chart Canvas Zoom/Pan Matrix, Modal Open/Close.        |
+-----------------------------------------------------------------------------------+
```

---

## 3. Komponen Khusus & Visualisasi Kompleks

### A. Interactive Org-Chart Canvas (`features/org-structure`)
- **Teknologi Visualisasi:** **React Flow (v11+) / D3 Hierarchy Tree Engine**.
- **Fitur Kunci:**
  - *Infinite Canvas with Zoom & Pan:* Menavigasi pohon organisasi kementerian berukuran besar dengan mulus.
  - *Collapsible Sub-trees:* Pengguna dapat melipat atau membuka ranting unit eselon di bawahnya untuk menjaga kerapian layar.
  - *Client-side Cycle Guard:* Saat operator melakukan *drag-and-drop* atau mengubah unit atasan (*re-parenting*), modul menjalankan algoritma deteksi siklus (*cycle check*) di peramban sebelum mengirim mutasi ke backend.
  - *Virtual DOM Optimization:* Hanya me-render node unit kerja yang berada di dalam *viewport* kanvas untuk menghemat memori client.

### B. Side-by-Side Diff Viewer (`features/workflow`)
- **Fungsi:** Komponen peninjauan perubahan data untuk Verifikator (PAGE-011).
- **Mekanisme Visual:**
  - Menampilkan panel kiri (*Current Live Data*) dan panel kanan (*Proposed Draft Data*).
  - Penandaan warna semantik: Merah (*Removed Unit/Tupoksi*), Hijau (*Newly Added Unit*), Kuning (*Modified Attributes / Re-parented Unit*).
  - Terintegrasi dengan preview dokumen dasar hukum PDF (menggunakan PDF.js viewer terenkapsulasi).

### C. Realtime Event Listener & Notification Hub (`features/realtime`)
- **Hook `useRealtimeNotifications`:**
  - Menginisialisasi koneksi WSS Socket.io saat user login.
  - Berlangganan ke kanal global (`events:global`) dan kanal privat instansi (`institution:{id}`).
  - Saat menerima event `DATA_MUTATED` atau `TICKET_UPDATED`:
    1. Memunculkan floating Toast notifikasi seketika di peramban.
    2. Menambah angka counter lonceng notifikasi di navbar (+1).
    3. Memicu invalidasi cache TanStack Query (`queryClient.invalidateQueries(...)`) sehingga tabel dan kartu metrik dashboard ter-update otomatis tanpa me-refresh halaman!

---

## 4. Standar Desain UI & Theme Tokens (Tailwind CSS)

Antarmuka mengadopsi standar *Executive Corporate Modern* dengan palet warna resmi yang dapat beradaptasi dengan mode Terang dan Gelap:
- **Primary Brand (KemenPANRB Identity):** Royal Navy Blue (`hsl(222, 47%, 18%)` $\rightarrow$ `#152238`) & Gold Accent (`hsl(43, 86%, 54%)`).
- **Surface & Glassmorphism:** Dark/Light translucent cards (`backdrop-blur-md bg-white/80 dark:bg-slate-900/80 border border-slate-200/60 dark:border-slate-800/60`).
- **Semantic Colors:**
  - Success / Active: Emerald Green (`#10b981`)
  - Warning / Revision: Amber Gold (`#f59e0b`)
  - Danger / Rejected: Crimson Red (`#ef4444`)
  - Verified / In Review: Cobalt Blue (`#3b82f6`)
- **Typography:** Font Google Inter (`font-sans`) untuk teks antarmuka dan Outfit (`font-heading`) untuk judul eksekutif.

---

## 5. Optimasi Kinerja & Aksesibilitas
1. **Code Splitting & Dynamic Imports:** Komponen berat seperti Org Chart Canvas (`ReactFlow`) dan PDF Viewer diimpor secara dinamis (`next/dynamic` dengan `ssr: false`) agar bundle awal halaman dashboard tetap sangat ringan ($< 150$ KB).
2. **Optimistic UI Updates:** Pengubahan draf langsung tercermin pada antarmuka seketika sebelum respon server tiba untuk pengalaman pengguna yang sangat responsif.
3. **Accessibility (a11y):** Seluruh elemen interaktif menggunakan atribut WAI-ARIA bawaan dari Radix UI, mendukung navigasi keyboard penuh, dan rasio kontras warna WCAG AA.
