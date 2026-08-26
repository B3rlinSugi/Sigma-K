# SIGMA-K — COMPONENT INVENTORY

## 1. Direktori Komponen UI Primitives (`src/components/ui/`)

| Nama Komponen | Lokasi File | Deskripsi & Kegunaan |
| :--- | :--- | :--- |
| `Button` | `Button.tsx` | Tombol interaktif dengan 6 varian (`primary`, `secondary`, `outline`, `ghost`, `danger`, `gold`), ukuran (`sm`, `md`, `lg`), dan state loading spinner. |
| `Badge` | `Badge.tsx` | Label status mikro dengan 9 varian warna tematik. |
| `StatusBadge` | `StatusBadge.tsx` | Badge khusus untuk `WorkflowStatusBadge`, `TransitionBadge` (Split/New/Rename), dan `ActiveStatusBadge`. |
| `Card` | `Card.tsx` | Kontainer kartu data dengan sub-komponen `CardHeader`, `CardTitle`, `CardDescription`, `CardContent`, dan `CardFooter`. |
| `Input` & `Select` | `Input.tsx` | Kontrol formulir input teks dan dropdown select dengan label, pesan validasi error, helper text, dan ikon. |
| `Modal` | `Modal.tsx` | Dialog modal pop-up dengan latar belakang backdrop blur, tombol Escape listener, dan footer aksi. |
| `Drawer` | `Drawer.tsx` | Lembar laci samping beranimasi geser untuk panel notifikasi dan detail node pohon organisasi. |
| `Table` | `Table.tsx` | Elemen tabel terstruktur responsif dengan `TableHeader`, `TableBody`, `TableRow`, `TableHead`, dan `TableCell`. |
| `Tabs` | `Tabs.tsx` | Bilah tab horizontal dengan penghitung jumlah data (*count badge*). |
| `Breadcrumb` | `Breadcrumb.tsx` | Jejak navigasi hirarki rute aplikasi. |
| `EmptyState` | `Breadcrumb.tsx` | Tampilan visual elegan ketika hasil pencarian atau antrean data kosong. |
| `Spinner` | `Breadcrumb.tsx` | Indikator animasi loading pemuatan data asinkron. |
| `DiffViewer` | `DiffViewer.tsx` | Komponen penampil komparasi data berdampingan (*before vs after*) untuk kabinet dan usulan tiket. |
| `WorkflowStepper`| `WorkflowStepper.tsx`| Komponen visual 5 langkah siklus hidup usulan dengan penanda status dinamis. |

---

## 2. Direktori Komponen Layout Global (`src/components/layout/`)

| Nama Komponen | Lokasi File | Deskripsi & Kegunaan |
| :--- | :--- | :--- |
| `TopBar` | `TopBar.tsx` | Bilah navigasi atas permanen: Brand SIGMA-K, **Persona Role Switcher**, tombol Notifikasi (badge unread count), dan profil mini. |
| `Sidebar` | `Sidebar.tsx` | Menu navigasi samping dengan filter item menu berbasis hak akses role pengguna aktif. |
| `AppShell` | `AppShell.tsx` | Shell kontainer utama yang membungkus TopBar, Sidebar, Notification Center Drawer, dan area konten utama. |
| `PageHeader` | `PageHeader.tsx` | Header standar halaman yang mencakup judul, subjudul, breadcrumb, badge status, dan tombol aksi utama. |

---

## 3. Direktori Fitur Khusus (`src/components/features/`)

| Nama Komponen | Lokasi File | Deskripsi & Kegunaan |
| :--- | :--- | :--- |
| `OrgNode` | `organization/OrgNode.tsx` | Kustom node React Flow yang menampilkan tingkat eselon, nama unit kerja, pejabat pimpinan, jumlah personel, dan handle konektor atas-bawah. |
| `OrgChartCanvas` | `organization/OrgChartCanvas.tsx` | Kanvas graf visual pohon organisasi interaktif berbasis React Flow, dilengkapi mini-map, background grid, search node filter, dan zoom-pan controls. |
