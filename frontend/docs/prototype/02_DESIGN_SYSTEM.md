# SIGMA-K — DESIGN SYSTEM SPECIFICATION

## 1. Design Philosophy
Desain antarmuka SIGMA-K mengadopsi standar aplikasi perusahaan pemerintahan Republik Indonesia modern (*Indonesian Enterprise e-Gov Design System*):
- **Institutional & Trustworthy:** Menggunakan palet resmi Biru KemenPANRB dan Emas Garuda.
- **Data-Dense yet Legible:** Tata letak tabular dan kartu informasi dirancang ringkas dengan perpaduan tipografi terstruktur agar pimpinan dapat memindai informasi strategis secara cepat.
- **Accessible (WCAG 2.1 AA):** Rasio kontras teks terhadap latar belakang dipertahankan di atas 4.5:1 untuk menjamin keterbacaan optimal.

---

## 2. Color Palette & Design Tokens

### Primary Brand Colors (KemenPANRB Navy)
| Token | Hex Code | Deskripsi Penggunaan |
| :--- | :--- | :--- |
| `primary-50` | `#f0f7ff` | Latar belakang badge terpilih, sel hover tabel |
| `primary-100`| `#e0effe` | Indikator aktif tab navigasi |
| `primary-800`| `#1e3a8a` | Border aktif tombol utama, hover link |
| `primary-900`| `#0B2A4A` | **Warna Utama Brand SIGMA-K** (TopBar, Button Primary, Header Banner) |
| `primary-950`| `#06182c` | Latar belakang gelap container eksekutif |

### Accent Colors (Garuda Gold)
| Token | Hex Code | Deskripsi Penggunaan |
| :--- | :--- | :--- |
| `gold-300` | `#F3E5AB` | Teks aksen pada latar belakang gelap TopBar |
| `gold-400` | `#E6CA65` | Icon aksen dan ring sorot seleksi node React Flow |
| `gold-500` | `#D4AF37` | **Warna Aksen Garuda** (Tombol Komparasi Kabinet, Badge Proposed KPI) |
| `gold-600` | `#B89628` | Hover state tombol aksen gold |

### Semantic State Colors
- **Success / Validated:** `#10b981` (Emerald-500) & `#ecfdf5` (Emerald-50)
- **Warning / In Review / Split:** `#f59e0b` (Amber-500) & `#fffbeb` (Amber-50)
- **Danger / Rejected / Dissolved:** `#ef4444` (Red-500) & `#fef2f2` (Red-50)
- **Info / Merge / Submitted:** `#0ea5e9` (Sky-500) & `#f0f9ff` (Sky-50)

---

## 3. Typography Hierarchy
- **Body & UI Elements:** Font `Inter`, sans-serif (Google Fonts) — dirancang untuk kejelasan data dan teks teknis.
- **Headings & Executive Numbers:** Font `Outfit`, sans-serif (Google Fonts) — memberikan sentuhan modern dan berwibawa pada judul halaman dan kartu metrik eksekutif.

---

## 4. Reusable Primitives Inventory
1. `Button` (Primary, Secondary, Outline, Ghost, Danger, Gold dengan built-in spinner)
2. `Badge` & `StatusBadge` (Workflow Status, Cabinet Active Status, Transition Type)
3. `Card`, `CardHeader`, `CardTitle`, `CardDescription`, `CardContent`, `CardFooter`
4. `Input` & `Select` (Form controls dengan label, asteris wajib, pesan error, helper text, dan ikon)
5. `Modal` (Dialog konfirmasi aksi telaah dan pengesahan)
6. `Drawer` (Lembar samping detail notifikasi dan detail node organisasi)
7. `Table` (Tabular responsif dengan pagination placeholder dan hover highlight)
8. `Tabs` (Tab navigasi berpenghitung data / count badge)
9. `DiffViewer` (Komponen visual side-by-side sebelum vs sesudah perubahan data)
10. `WorkflowStepper` (Indikator progres tahapan siklus hidup pengajuan)
