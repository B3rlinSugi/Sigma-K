"""
Generator Diagram Vektor & Prototipe Antarmuka Berbahasa Indonesia untuk SIGMA-K.
Dirancang khusus untuk konsumsi Pimpinan dan Mentor:
- Bahasa Indonesia yang lugas, baku, komunikatif, dan mudah dipahami.
- Format 16:9 widescreen (1600x900) dengan tipografi tegas dan keterbacaan tinggi.
- 0 Text Overlap, 0 Text Collision, tata letak seimbang dan rapi.
"""

import os
from PIL import Image, ImageDraw, ImageFont

ASSETS_DIR = os.path.abspath('docs/assets')
os.makedirs(ASSETS_DIR, exist_ok=True)

FONT_DIR = "C:/Windows/Fonts"
F_SEGOE_REG = os.path.join(FONT_DIR, "segoeui.ttf")
F_SEGOE_BOLD = os.path.join(FONT_DIR, "segoeuib.ttf")
F_CALIBRI_REG = os.path.join(FONT_DIR, "calibri.ttf")
F_CALIBRI_BOLD = os.path.join(FONT_DIR, "calibrib.ttf")

def get_font(bold=False, size=24):
    path = F_SEGOE_BOLD if bold else F_SEGOE_REG
    if not os.path.exists(path):
        path = F_CALIBRI_BOLD if bold else F_CALIBRI_REG
    return ImageFont.truetype(path, size)

# Palet Warna Resmi & Harmonis
C_NAVY = "#0B2A4A"          # Biru Dongker Resmi (KemenPANRB)
C_BLUE = "#1E40AF"          # Biru Primer
C_BLUE_LIGHT = "#EFF6FF"    # Latar Biru Lembut
C_BLUE_BORDER = "#3B82F6"
C_GOLD = "#D4AF37"          # Aksen Emas
C_AMBER = "#B45309"         # Oranye / Penapis
C_AMBER_LIGHT = "#FFFBEB"
C_AMBER_BORDER = "#F59E0B"
C_EMERALD = "#047857"       # Hijau / Verifikator & Sukses
C_EMERALD_LIGHT = "#ECFDF5"
C_EMERALD_BORDER = "#10B981"
C_PURPLE = "#6B21A8"        # Ungu / Super Admin & Keputusan
C_PURPLE_LIGHT = "#FAF5FF"
C_PURPLE_BORDER = "#A855F7"
C_RED = "#DC2626"           # Merah / Revisi & Peringatan
C_RED_LIGHT = "#FEF2F2"
C_RED_BORDER = "#EF4444"
C_SLATE_DARK = "#0F172A"    # Teks Utama
C_SLATE_MID = "#475569"     # Teks Sekunder
C_SLATE_LIGHT = "#F8FAFC"   # Latar Kartu
C_BORDER_DEFAULT = "#CBD5E1"
C_WHITE = "#FFFFFF"

class ExecutiveCanvas:
    def __init__(self, width=1600, height=900, bg="#FFFFFF"):
        self.w = width
        self.h = height
        self.img = Image.new("RGBA", (width, height), bg)
        self.draw = ImageDraw.Draw(self.img)

    def draw_top_title(self, title, subtitle=None):
        f_title = get_font(bold=True, size=30)
        self.draw.text((self.w / 2, 38), title, fill=C_NAVY, font=f_title, anchor="mm")
        
        if subtitle:
            f_sub = get_font(bold=False, size=17)
            self.draw.text((self.w / 2, 70), subtitle, fill=C_SLATE_MID, font=f_sub, anchor="mm")

    def draw_card(self, x, y, w, h, bg=C_WHITE, border=C_BORDER_DEFAULT, border_w=2, radius=10,
                  header_bg=None, header_text=None, header_color=C_WHITE, header_size=20,
                  items=None, item_size=16, item_color=C_SLATE_DARK, line_spacing=30):
        # Background card
        self.draw.rounded_rectangle([x, y, x + w, y + h], radius=radius, fill=bg, outline=border, width=int(border_w))

        content_top = y + 14

        # With distinct header banner
        if header_bg and header_text:
            header_h = 42
            self.draw.rounded_rectangle([x, y, x + w, y + header_h], radius=radius, fill=header_bg)
            self.draw.rectangle([x, y + header_h - radius, x + w, y + header_h], fill=header_bg)
            self.draw.rounded_rectangle([x, y, x + w, y + h], radius=radius, outline=border, width=int(border_w))
            
            f_head = get_font(bold=True, size=header_size)
            self.draw.text((x + w / 2, y + header_h / 2), header_text, fill=header_color, font=f_head, anchor="mm")
            content_top = y + header_h + 14
        elif header_text:
            f_head = get_font(bold=True, size=header_size)
            if not items:
                # Pill banner single text
                self.draw.text((x + w / 2, y + h / 2), header_text, fill=header_color, font=f_head, anchor="mm")
            else:
                self.draw.text((x + w / 2, y + 22), header_text, fill=header_color if header_color != C_WHITE else C_NAVY, font=f_head, anchor="mm")
                content_top = y + 44

        if items:
            f_item = get_font(bold=False, size=item_size)
            f_item_bold = get_font(bold=True, size=item_size)
            cur_y = content_top
            for it in items:
                if isinstance(it, tuple):
                    prefix, rest = it
                    self.draw.text((x + 14, cur_y), prefix, fill=C_NAVY, font=f_item_bold)
                    bbox = self.draw.textbbox((x + 14, cur_y), prefix, font=f_item_bold)
                    p_w = bbox[2] - bbox[0]
                    self.draw.text((x + 14 + p_w, cur_y), rest, fill=item_color, font=f_item)
                else:
                    self.draw.text((x + 14, cur_y), it, fill=item_color, font=f_item)
                cur_y += line_spacing

    def draw_arrow(self, x1, y1, x2, y2, color=C_BLUE, width=3, label=None, label_bg=C_BLUE_LIGHT, label_color=C_BLUE):
        self.draw.line([(x1, y1), (x2, y2)], fill=color, width=width)
        
        import math
        angle = math.atan2(y2 - y1, x2 - x1)
        arrow_len = 14
        arrow_angle = math.pi / 6
        
        p1 = (x2 - arrow_len * math.cos(angle - arrow_angle), y2 - arrow_len * math.sin(angle - arrow_angle))
        p2 = (x2 - arrow_len * math.cos(angle + arrow_angle), y2 - arrow_len * math.sin(angle + arrow_angle))
        self.draw.polygon([(x2, y2), p1, p2], fill=color)

        if label:
            mx = (x1 + x2) / 2
            my = (y1 + y2) / 2
            f_lbl = get_font(bold=True, size=15)
            bbox = self.draw.textbbox((0, 0), label, font=f_lbl)
            lw = bbox[2] - bbox[0] + 18
            lh = bbox[3] - bbox[1] + 10
            self.draw.rounded_rectangle([mx - lw/2, my - lh/2, mx + lw/2, my + lh/2], radius=6, fill=label_bg, outline=color, width=1)
            self.draw.text((mx, my), label, fill=label_color, font=f_lbl, anchor="mm")

    def save(self, filename):
        out_path = os.path.join(ASSETS_DIR, filename)
        rgb_img = Image.new("RGB", self.img.size, (255, 255, 255))
        rgb_img.paste(self.img, mask=self.img.split()[3])
        rgb_img.save(out_path, "PNG", dpi=(300, 300))
        print(f"  [BERHASIL DISIMPAN] {filename}")

# -------------------------------------------------------------
# 1. ARSITEKTUR SISTEM
# -------------------------------------------------------------
def render_system_architecture():
    c = ExecutiveCanvas(1600, 900)
    c.draw_top_title("ARSITEKTUR SISTEM E-SKLD / SIGMA-K", 
                     "Integrasi 4 Lapisan: Antarmuka Web (Next.js), Gerbang Jaringan, Logika Bisnis (CodeIgniter 4) & Basis Data MySQL")

    # Lapisan 1
    c.draw_card(40, 95, 1520, 240, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                header_bg=C_NAVY, header_text="LAPISAN 1: ANTARMUKA PENGGUNA (Next.js 14 + React 18 + TypeScript)", header_size=19)

    c.draw_card(55, 145, 360, 175, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2,
                header_text="Halaman & Komponen UI", header_size=17, header_color=C_BLUE,
                items=[
                    "• Dashboard Eksekutif Pimpinan",
                    "• Bagan Organisasi Interaktif",
                    "• Katalog Instansi Pemerintah",
                    "• Formulir Usulan & Revisi",
                    "• Ruang Kerja Verifikator"
                ], item_size=15.5, line_spacing=26)

    c.draw_card(430, 145, 360, 175, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2,
                header_text="Manajemen Sesi & Peran", header_size=17, header_color=C_BLUE,
                items=[
                    "• Penyedia Otentikasi (Auth)",
                    "• Pengunci Hak Akses Pengguna",
                    "• Pusat Pemberitahuan / Notif",
                    "• Pengatur Aliran Data Sesi",
                    "• Isolasi Peran Anti-Bypass"
                ], item_size=15.5, line_spacing=26)

    c.draw_card(805, 145, 365, 175, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2,
                header_text="Layanan Integrasi Bisnis", header_size=17, header_color=C_BLUE,
                items=[
                    "• Layanan Akun & Instansi",
                    "• Layanan Struktur Organisasi",
                    "• Layanan Alur Pengusulan",
                    "• Layanan Analitik & Ekspor",
                    "• Skema Data TypeScript Ketat"
                ], item_size=15.5, line_spacing=26)

    c.draw_card(1185, 145, 360, 175, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2,
                header_text="Penerjemah Struktur Data", header_size=17, header_color=C_BLUE,
                items=[
                    "• Konversi Data API ke UI",
                    "• Penyusun Pohon Hierarki",
                    "• Pembersih Format ID & Angka",
                    "• Penyelaras Status Usulan",
                    "• Penanganan Pesan Galat/Error"
                ], item_size=15.5, line_spacing=26)

    # Panah Lapisan 1 -> Gerbang
    c.draw_arrow(800, 340, 800, 390, color=C_BLUE, width=3, label="Protokol Aman HTTPS + Token Keamanan JWT", label_bg=C_BLUE_LIGHT, label_color=C_BLUE)

    # Gerbang Jaringan
    c.draw_card(220, 390, 1160, 65, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2, radius=8,
                header_text="GERBANG JARINGAN & PENYEDIA TOKEN OTENTIKASI (HTTP GATEWAY)", header_size=17.5, header_color=C_BLUE,
                items=["Klien Jaringan Otomatis  •  Penyisipan Token Resmi  •  Penanganan Kode Akses (401, 403, 404, 500)  •  Batas Waktu Respon 15 Detik"],
                item_size=15, item_color=C_NAVY, line_spacing=0)

    # Panah Gerbang -> Lapisan 2
    c.draw_arrow(800, 460, 800, 510, color=C_BLUE, width=3, label="Panggilan Layanan REST API Terverifikasi", label_bg=C_BLUE_LIGHT, label_color=C_BLUE)

    # Lapisan 2 Backend
    c.draw_card(40, 510, 1520, 265, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                header_bg=C_NAVY, header_text="LAPISAN 2: LOGIKA BISNIS & KEAMANAN SISTEM (CodeIgniter 4.4.8 + PHP 8.2+)", header_size=19)

    c.draw_card(55, 560, 360, 200, bg=C_WHITE, border=C_AMBER_BORDER, border_w=2,
                header_text="Pengendali API & Filter", header_size=17, header_color=C_AMBER,
                items=[
                    "• Filter Validasi Token JWT",
                    "• Pengendali Akun & Sesi",
                    "• Pengendali Master Instansi",
                    "• Pengendali Usulan Organisasi",
                    "• Pengendali Kerja Verifikator",
                    "• Pengendali Laporan Eksekutif"
                ], item_size=15, line_spacing=26)

    c.draw_card(430, 560, 360, 200, bg=C_WHITE, border=C_AMBER_BORDER, border_w=2,
                header_text="Keamanan & Otorisasi", header_size=17, header_color=C_AMBER,
                items=[
                    "• Pemeriksa Izin Akses (can)",
                    "• Pembatas Wilayah Instansi",
                    "• Izin Akses Tugas Khusus",
                    "• Cegah Persetujuan Diri Sendiri",
                    "• Proteksi Data Antar Instansi",
                    "• Penguncian Hak Akses Resmi"
                ], item_size=15, line_spacing=26)

    c.draw_card(805, 560, 365, 200, bg=C_WHITE, border=C_AMBER_BORDER, border_w=2,
                header_text="Layanan Bisnis Utama", header_size=17, header_color=C_AMBER,
                items=[
                    "• Mesin Alur Usulan (FSM)",
                    "• Penyusun Struktur Pohon Organisasi",
                    "• Perekam Versi Draf (v1->v2)",
                    "• Mesin Pengesahan SK Resmi",
                    "• Agregasi Laporan Nasional",
                    "• Pemisahan Wewenang Kerja"
                ], item_size=15, line_spacing=26)

    c.draw_card(1185, 560, 360, 200, bg=C_WHITE, border=C_AMBER_BORDER, border_w=2,
                header_text="Perekam Jejak Audit", header_size=17, header_color=C_AMBER,
                items=[
                    "• Pencatatan Mutasi Data",
                    "• Pelacak Perubahan Data (Diff)",
                    "• Perekam NIP & Peran Pengguna",
                    "• Perekam Alamat IP & Browser",
                    "• Pencegah Pelanggaran Akses",
                    "• Ekspor Berkas Laporan CSV"
                ], item_size=15, line_spacing=26)

    # Panah Lapisan 2 -> Basis Data
    c.draw_arrow(800, 780, 800, 825, color=C_EMERALD, width=3, label="Koneksi Aman Relasional MySQL", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    # Lapisan 3 Basis Data
    c.draw_card(220, 825, 1160, 60, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=3, radius=8,
                header_text="LAPISAN 3: PENYIMPANAN DATA UTAMA - MySQL 8.x (eskld_db - 21 Tabel Relasional)", header_size=17.5, header_color=C_EMERALD,
                items=["Integritas Relasi Kunci  •  Immutabilitas Snapshot Versi  •  Isolasi Data Instansi  •  Log Audit Permanen Anti-Ubah"],
                item_size=15, item_color=C_NAVY, line_spacing=0)

    c.save("system_architecture.png")

# -------------------------------------------------------------
# 2. MATRIKS PERAN & HAK AKSES
# -------------------------------------------------------------
def render_role_access():
    c = ExecutiveCanvas(1600, 900)
    c.draw_top_title("PEMBAGIAN PERAN & MATRIKS HAK AKSES PENGGUNA", 
                     "Prinsip Pemisahan Wewenang Kerja (Separation of Duties) dan Batasan Hak Akses per Peran")

    roles = [
        ("OPERATOR (Instansi Pengusul)", 40, 95, 360, 480, C_BLUE_LIGHT, C_BLUE, C_BLUE, [
            ("• Lingkup Kerja: ", "Instansi Sendiri"),
            ("• Tugas Pokok: ", "Menyusun usulan struktur baru"),
            ("• Pembuatan Draf: ", "Input draf unit kerja & formasi (v1)"),
            ("• Pengajuan: ", "Mengirim berkas ke KemenPANRB"),
            ("• Perbaikan: ", "Memperbaiki berkas jika ada revisi (v2)"),
            ("• Batasan Tegas: ", "TIDAK BISA menelaah / menyetujui"),
            ("• Keamanan: ", "Ditolak sistem bila akses K/L lain")
        ]),
        ("PENAPIS (Admin KemenPANRB)", 425, 95, 360, 480, C_AMBER_LIGHT, C_AMBER, C_AMBER, [
            ("• Lingkup Kerja: ", "Berkas K/L yang Ditugaskan"),
            ("• Tugas Pokok: ", "Penapisan Administratif (Tahap 1)"),
            ("• Pemeriksaan: ", "Validasi kelengkapan berkas & hukum"),
            ("• Pengembalian: ", "Kembalikan berkas bila belum lengkap"),
            ("• Penugasan: ", "Menugaskan Verifikator yang sesuai"),
            ("• Batasan Tegas: ", "TIDAK BISA mengesahkan SK final"),
            ("• Batas Peran: ", "Hanya verifikasi formal, bukan substansi")
        ]),
        ("VERIFIKATOR (Kelembagaan)", 810, 95, 360, 480, C_EMERALD_LIGHT, C_EMERALD, C_EMERALD, [
            ("• Lingkup Kerja: ", "Antrean Usulan Penugasan"),
            ("• Tugas Pokok: ", "Telaah Substantif (Tahap 2)"),
            ("• Analisis: ", "Evaluasi beban kerja & formasi ASN"),
            ("• Catatan Telaah: ", "Memberikan catatan telaah per unit"),
            ("• Wewenang Mutlak: ", "PENGESAHAN SURAT KEPUTUSAN (SK)"),
            ("• Promosi Data: ", "Migrasi usulan ke data master aktif"),
            ("• Tanggung Jawab: ", "Keabsahan yuridis & teknis SK")
        ]),
        ("PIMPINAN & SUPER ADMIN", 1195, 95, 365, 480, C_PURPLE_LIGHT, C_PURPLE, C_PURPLE, [
            ("• Lingkup Kerja: ", "Nasional (Seluruh K/L/D)"),
            ("• Tugas Pokok: ", "Supervisi, Monitoring & Pengawasan"),
            ("• Kelola Akses: ", "Kelola akun pengguna & penugasan"),
            ("• Pantauan: ", "Dashboard analitik capaian nasional"),
            ("• Forensik Audit: ", "Pemeriksaan riwayat log & perubahan"),
            ("• Ekspor Laporan: ", "Unduh data kelembagaan nasional"),
            ("• Status SESDEP: ", "Hak akses setara SUPER_ADMIN")
        ])
    ]

    for title, x, y, w, h, bg, border, hbg, items in roles:
        c.draw_card(x, y, w, h, bg=bg, border=border, border_w=3, radius=10,
                    header_bg=hbg, header_text=title, header_size=17,
                    items=items, item_size=15.5, line_spacing=34)

    c.draw_card(40, 595, 1520, 285, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                header_bg=C_NAVY, header_text="5 PRINSIP UTAMA TATA KELOLA & KEAMANAN AKSES SISTEM", header_size=18.5,
                items=[
                    ("1. Otoritas Backend Terpusat: ", "Keputusan hak akses ditentukan 100% oleh server backend, bukan oleh browser pengguna."),
                    ("2. Penguncian Peran Ketat: ", "Pengguna tidak dapat mengubah peran sendiri di aplikasi untuk mencegah penyalahgunaan wewenang."),
                    ("3. Pemisahan Tugas (Separation of Duties): ", "Admin hanya bertugas penapisan administrasi; hak pengesahan SK mutlak milik Verifikator."),
                    ("4. Larangan Persetujuan Sendiri: ", "Sistem secara otomatis memblokir pengguna agar tidak dapat memeriksa atau menyetujui usulan buatannya sendiri."),
                    ("5. Batasan Wilayah Instansi: ", "Operator hanya dapat melihat data instansinya sendiri dan diblokir jika mencoba mengakses instansi lain.")
                ], item_size=15.5, line_spacing=34)

    c.save("role_access_matrix.png")

# -------------------------------------------------------------
# 3. SIKLUS HIDUP PENGUSULAN (FSM)
# -------------------------------------------------------------
def render_submission_lifecycle():
    c = ExecutiveCanvas(1600, 900)
    c.draw_top_title("ALUR TAHAPAN SIKLUS HIDUP USULAN KELEMBAGAAN", 
                     "7 Tahapan Status Usulan Penataan Organisasi: Mulai dari Penyusunan Draf hingga Pengesahan & Promosi Data")

    c.draw_card(40, 100, 340, 185, bg=C_SLATE_LIGHT, border=C_SLATE_MID, border_w=3, radius=10,
                header_bg=C_SLATE_MID, header_text="1. DRAF AWAL (Operator)", header_size=17.5,
                items=["• Operator menyusun usulan struktur", "• Input unit kerja & kebutuhan formasi", "• Disimpan sebagai draf versi 1 (v1)"],
                item_size=15.5, line_spacing=28)

    c.draw_arrow(385, 190, 435, 190, color=C_BLUE, width=3, label="Kirim Usulan", label_bg=C_BLUE_LIGHT, label_color=C_BLUE)

    c.draw_card(435, 100, 340, 185, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=3, radius=10,
                header_bg=C_BLUE, header_text="2. PENAPISAN (Admin PANRB)", header_size=17.5,
                items=["• Berkas masuk ke antrean KemenPANRB", "• Pemeriksaan kelengkapan administrasi", "• Validasi surat pengantar & naskah"],
                item_size=15.5, line_spacing=28)

    c.draw_arrow(780, 190, 830, 190, color=C_AMBER, width=3, label="Terima & Tugaskan", label_bg=C_AMBER_LIGHT, label_color=C_AMBER)

    c.draw_card(830, 100, 340, 185, bg=C_AMBER_LIGHT, border=C_AMBER, border_w=3, radius=10,
                header_bg=C_AMBER, header_text="3. TELAAH (Verifikator)", header_size=17.5,
                items=["• Berkas ditugaskan ke Verifikator", "• Evaluasi beban kerja & eselonisasi", "• Pemeriksaan keselarasan formasi ASN"],
                item_size=15.5, line_spacing=28)

    c.draw_arrow(1175, 190, 1225, 190, color=C_PURPLE, width=3, label="Setujui Telaah", label_bg=C_PURPLE_LIGHT, label_color=C_PURPLE)

    c.draw_card(1225, 100, 335, 185, bg=C_PURPLE_LIGHT, border=C_PURPLE, border_w=3, radius=10,
                header_bg=C_PURPLE, header_text="4. SIAP PENGESAHAN", header_size=17.5,
                items=["• Hasil telaah substantif tuntas", "• Catatan perbaikan telah terpenuhi", "• Berkas siap untuk disahkan SK"],
                item_size=15.5, line_spacing=28)

    c.draw_arrow(1390, 290, 1390, 380, color=C_EMERALD, width=4, label="Sahkan SK Resmi", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    c.draw_card(1225, 380, 335, 185, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=3, radius=10,
                header_bg=C_EMERALD, header_text="5. DISETUJUI (SK Terbit)", header_size=17.5,
                items=["• SK resmi disahkan oleh Verifikator", "• Penetapan nomor & tanggal SK resmi", "• Bukti pengesahan tercatat permanen"],
                item_size=15.5, line_spacing=28)

    c.draw_arrow(1225, 470, 1175, 470, color=C_EMERALD, width=3, label="Promosikan Data", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    c.draw_card(830, 380, 340, 185, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=3, radius=10,
                header_bg=C_EMERALD, header_text="6. DATA AKTIF (Promosi Selesai)", header_size=17.5,
                items=["• Data otomatis masuk master instansi", "• Struktur unit & formasi live terupdate", "• Proses pengusulan selesai sukses"],
                item_size=15.5, line_spacing=28)

    c.draw_card(40, 380, 735, 185, bg=C_RED_LIGHT, border=C_RED, border_w=3, radius=10,
                header_bg=C_RED, header_text="7. PERBAIKAN BERKAS (Siklus Revisi Dokumen)", header_size=17.5,
                items=[
                    ("• Pengembalian oleh Admin: ", "Berkas dikembalikan jika dokumen belum lengkap."),
                    ("• Pengembalian oleh Verifikator: ", "Berkas dikembalikan jika ada koreksi teknis substantif."),
                    ("• Pengajuan Ulang (Resubmit): ", "Operator memperbaiki berkas lalu mengajukan versi baru (v1 -> v2).")
                ], item_size=15.5, line_spacing=30)

    c.draw_arrow(605, 290, 605, 380, color=C_RED, width=3, label="Revisi Admin", label_bg=C_RED_LIGHT, label_color=C_RED)
    c.draw_arrow(1000, 290, 780, 380, color=C_RED, width=3, label="Revisi Verifikator", label_bg=C_RED_LIGHT, label_color=C_RED)
    c.draw_arrow(210, 380, 210, 290, color=C_SLATE_MID, width=3, label="Ajukan Ulang (v2)", label_bg=C_SLATE_LIGHT, label_color=C_SLATE_DARK)

    c.draw_card(40, 590, 1520, 290, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                header_bg=C_NAVY, header_text="RINGKASAN ATURAN KERJA ALUR PENGUSULAN", header_size=18.5,
                items=[
                    ("• Rekam Jejak Permanen: ", "Setiap perpindahan tahapan dan status dicatat otomatis tanpa bisa dihapus untuk keperluan audit."),
                    ("• Wewenang Pengesahan Akhir: ", "Penerbitan Surat Keputusan (SK) dan promosi ke master data mutlak wewenang VERIFIER."),
                    ("• Otomasi Pembaruan Data: ", "Setelah SK disahkan, sistem otomatis memindahkan unit dan jabatan baru ke data aktif instansi."),
                    ("• Pelacakan Riwayat Versi: ", "Setiap perbaikan berkas tersimpan dalam nomor versi baru (v1, v2, v3) agar perubahan mudah dibandingkan.")
                ], item_size=15.5, line_spacing=34)

    c.save("submission_lifecycle_fsm.png")

# -------------------------------------------------------------
# 4. DATABASE ERD
# -------------------------------------------------------------
def render_erd():
    c = ExecutiveCanvas(1600, 900)
    c.draw_top_title("STRUKTUR DATABASE & ENTITY RELATIONSHIP DIAGRAM (ERD)", 
                     "Struktur 21 Tabel Relasional eskld_db: Akun & Hak Akses, Master Data Kelembagaan, Usulan & Versioning, Serta Verifikasi & Audit")

    c.draw_card(40, 95, 360, 480, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=3, radius=10,
                header_bg=C_BLUE, header_text="1. AKUN & HAK AKSES PENGGUNA", header_size=16.5,
                items=[
                    ("• users: ", "Data akun, NIP, email, password"),
                    ("• roles: ", "Daftar 4 peran resmi sistem"),
                    ("• permissions: ", "Katalog hak akses terperinci"),
                    ("• role_permissions: ", "Pemetaan hak akses per peran"),
                    ("• user_scopes: ", "Batasan instansi per pengguna"),
                    ("• access_grants: ", "Izin akses tugas khusus sementara"),
                    ("• access_requests: ", "Permohonan akses instansi baru")
                ], item_size=15, line_spacing=34)

    c.draw_card(425, 95, 360, 480, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=3, radius=10,
                header_bg=C_EMERALD, header_text="2. MASTER DATA KELEMBAGAAN", header_size=16.5,
                items=[
                    ("• institutions: ", "Master instansi K/L/Pemda"),
                    ("• institution_types: ", "Kategori bentuk instansi"),
                    ("• cabinets: ", "Data kabinet & periode presiden"),
                    ("• cabinet_institutions: ", "Pemetaan K/L per kabinet"),
                    ("• organizational_units: ", "Unit kerja aktif (Pohon Adjacency)"),
                    ("  - level, nama, ", "kode unit, status aktif"),
                    ("• positions: ", "Jabatan struktural & formasi ASN")
                ], item_size=15, line_spacing=34)

    c.draw_card(810, 95, 360, 480, bg=C_AMBER_LIGHT, border=C_AMBER, border_w=3, radius=10,
                header_bg=C_AMBER, header_text="3. USULAN & VERSI DRAF", header_size=16.5,
                items=[
                    ("• submissions: ", "Header usulan & status alur"),
                    ("  - instansi pengusul, ", "tahun, pembuat"),
                    ("• submission_versions: ", "Riwayat versi draf (v1, v2, dll.)"),
                    ("• submission_units: ", "Rincian usulan unit baru/ubah/hapus"),
                    ("  - kode unit, ", "nama unit, jenis tindakan"),
                    ("• submission_positions: ", "Rincian usulan formasi jabatan"),
                    ("  - nama jabatan, ", "eselon, jumlah formasi")
                ], item_size=15, line_spacing=34)

    c.draw_card(1195, 95, 365, 480, bg=C_PURPLE_LIGHT, border=C_PURPLE, border_w=3, radius=10,
                header_bg=C_PURPLE, header_text="4. PENUGASAN, TELAAH & AUDIT", header_size=16.5,
                items=[
                    ("• verifier_assignments: ", "Alokasi berkas ke Verifikator"),
                    ("• verifier_review_notes: ", "Catatan telaah teknis substantif"),
                    ("  - unit target, ", "catatan koreksi, status selesai"),
                    ("• approval_records: ", "Dokumen pengesahan SK resmi"),
                    ("  - nomor SK, ", "tanggal pengesahan, pejabat"),
                    ("• audit_logs: ", "Log rekam jejak mutasi data (permanen)"),
                    ("  - data sebelum/sesudah, ", "IP, NIP pembuat")
                ], item_size=15, line_spacing=34)

    c.draw_card(40, 595, 1520, 285, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                header_bg=C_NAVY, header_text="PENJELASAN INTEGRITAS DATA & HUBUNGAN RELASIONAL", header_size=18.5,
                items=[
                    ("• Relasi Kunci Induk-Anak: ", "Seluruh data terhubung dengan kunci relasi (Foreign Key) yang menjamin tidak ada data yatim/rusak."),
                    ("• Struktur Pohon Anti-Melingkar: ", "Unit kerja terstruktur dari Level 1 (Pimpinan) ke Level 4 dengan algoritma pencegah hubungan melingkar."),
                    ("• Keamanan Versi Usulan: ", "Draf usulan yang telah diajukan dikunci permanen agar memiliki bukti historis yang tidak dapat diubah."),
                    ("• Isolasi Data Multi-Instansi: ", "Tabel batasan wilayah memastikan operator hanya dapat mengelola data instansinya sendiri secara aman."),
                    ("• Forensik Audit Lengkap: ", "Tabel log audit mencatat setiap perubahan data secara transparan untuk pengawasan pimpinan.")
                ], item_size=15.5, line_spacing=34)

    c.save("erd_diagram.png")

# -------------------------------------------------------------
# 5. PELACAKAN PERUBAHAN & VERSIONING (DIFF)
# -------------------------------------------------------------
def render_versioning_diff():
    c = ExecutiveCanvas(1600, 900)
    c.draw_top_title("MEKANISME PEREKAMAN VERSI & PELACAKAN PERUBAHAN (DIFF)", 
                     "Penguncian Draf Historis dan Komparasi Visual Perubahan Struktur Unit Kerja / Formasi Jabatan")

    c.draw_card(40, 100, 470, 420, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=3, radius=10,
                header_bg=C_BLUE, header_text="Versi Usulan 1 (v1) - Draf Awal", header_size=17.5,
                items=[
                    ("• Nomor Versi: ", "Versi Draf 1 (v1)"),
                    ("• Isi Usulan: ", "Rancangan unit kerja & formasi awal"),
                    ("• Pengajuan: ", "Dikirim ke Penapis KemenPANRB"),
                    ("• Status Berkas: ", "DALAM PENAPISAN ADMINISTRASI"),
                    ("• Penguncian Data: ", "Data dikunci permanen saat dikirim"),
                    ("• Bukti Historis: ", "Tersimpan sebagai arsip awal usulan")
                ], item_size=15.5, line_spacing=36)

    c.draw_arrow(515, 310, 565, 310, color=C_RED, width=3, label="Kembalikan Revisi", label_bg=C_RED_LIGHT, label_color=C_RED)

    c.draw_card(565, 100, 470, 420, bg=C_RED_LIGHT, border=C_RED, border_w=3, radius=10,
                header_bg=C_RED, header_text="Catatan Telaah & Koreksi Berkas", header_size=17.5,
                items=[
                    ("• Asal Catatan: ", "Admin Penapis / Verifikator Kelembagaan"),
                    ("• Poin Koreksi: ", "Koreksi tupoksi, rasio beban kerja ASN"),
                    ("• Status Berkas: ", "PERLU PERBAIKAN (REVISION_REQUIRED)"),
                    ("• Akses Operator: ", "Formulir koreksi terbuka untuk diperbaiki"),
                    ("• Status Versi 1: ", "Tersimpan rapi dan tidak akan hilang"),
                    ("• Waktu Respons: ", "Target perbaikan sesuai tenggat instansi")
                ], item_size=15.5, line_spacing=36)

    c.draw_arrow(1040, 310, 1090, 310, color=C_EMERALD, width=3, label="Ajukan Ulang (v2)", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    c.draw_card(1090, 100, 470, 420, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=3, radius=10,
                header_bg=C_EMERALD, header_text="Versi Usulan 2 (v2) - Hasil Perbaikan", header_size=17.5,
                items=[
                    ("• Nomor Versi Baru: ", "Versi Draf 2 (v2)"),
                    ("• Isi Perbaikan: ", "Unit & formasi yang telah disesuaikan"),
                    ("• Pelacak Perubahan: ", "Sistem membandingkan v2 vs data aktif"),
                    ("• Status Berkas: ", "DALAM TELAAH AKHIR VERIFIKATOR"),
                    ("• Kesiapan SK: ", "Versi v2 siap disahkan menjadi SK resmi"),
                    ("• Dasar Hukum SK: ", "Nomor SK diterbitkan mengacu versi v2")
                ], item_size=15.5, line_spacing=36)

    c.draw_card(40, 540, 1520, 340, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                header_bg=C_NAVY, header_text="PENJELASAN FITUR KOMPARASI PERUBAHAN DATA (DIFF VIEWER)", header_size=18.5,
                items=[
                    ("• Penambahan Unit (Warna HIJAU): ", "Menyoroti pembentukan unit kerja baru yang belum ada pada struktur aktif instansi."),
                    ("• Perubahan Formasi (Warna KUNING): ", "Menyoroti pergeseran nama unit, jenjang eselon, atau penambahan/pengurangan kuota formasi ASN."),
                    ("• Penghapusan Unit (Warna MERAH): ", "Menandai unit struktural yang diusulkan untuk dihapus atau digabungkan ke unit lain."),
                    ("• Tampilan Berdampingan (Side-by-Side): ", "Pimpinan dan Verifikator dapat membandingkan struktur lama dan usulan baru secara langsung."),
                    ("• Efisiensi Pengambilan Keputusan: ", "Mempercepat telaah karena pimpinan langsung fokus pada poin perubahan tanpa harus membaca berkas tebal.")
                ], item_size=15.5, line_spacing=34)

    c.save("versioning_diff_flow.png")

# -------------------------------------------------------------
# 6. SITEMAP & NAVIGASI APLIKASI
# -------------------------------------------------------------
def render_sitemap():
    c = ExecutiveCanvas(1600, 900)
    c.draw_top_title("SITEMAP DAN STRUKTUR NAVIGASI APLIKASI SIGMA-K", 
                     "Peta 16 Halaman Terintegrasi: Pengelompokan Berdasarkan Kebutuhan Layanan dan Hak Akses Pengguna")

    # Pintu Masuk
    c.draw_card(550, 95, 500, 60, bg=C_NAVY, border=C_NAVY, border_w=2, radius=8,
                header_text="PINTU MASUK: /login (Layar Masuk Sistem)", header_size=18, header_color=C_WHITE)

    # Kerangka Aplikasi
    c.draw_card(500, 175, 600, 65, bg=C_BLUE, border=C_BLUE, border_w=2, radius=8,
                header_text="KERANGKA APLIKASI (Menu Samping & Status Sesi Aktif)", header_size=18, header_color=C_WHITE)

    c.draw_arrow(800, 155, 800, 175, color=C_NAVY, width=3)

    routes = [
        ("1. DASHBOARD", 40, 260, 285, 330, C_BLUE_LIGHT, C_BLUE, [
            ("• /: ", "Ringkasan Pimpinan"),
            ("• /analytics: ", "Analitik Data"),
            ("• Postur ASN: ", "Distribusi Eselon"),
            ("• Ekspor: ", "Unduh CSV/JSON")
        ]),
        ("2. MASTER DATA", 350, 260, 285, 330, C_EMERALD_LIGHT, C_EMERALD, [
            ("• /institutions: ", "Katalog K/L/D"),
            ("• .../[id]: ", "Profil Instansi"),
            ("• /structure: ", "Bagan Pohon"),
            ("• /cabinets: ", "Kabinet"),
            ("• /tupoksi: ", "Tupoksi Unit")
        ]),
        ("3. PENGUSULAN", 660, 260, 285, 330, C_AMBER_LIGHT, C_AMBER, [
            ("• /submissions: ", "Daftar Usulan"),
            ("• .../new: ", "Form Usulan Baru"),
            ("• .../[id]: ", "Rincian & Diff"),
            ("• .../revision: ", "Perbaikan v2")
        ]),
        ("4. VERIFIKASI", 970, 260, 285, 330, C_PURPLE_LIGHT, C_PURPLE, [
            ("• /verifications: ", "Antrean Berkas"),
            ("• Tahap 1: ", "Penapis Admin"),
            ("• Tahap 2: ", "Verifikator"),
            ("• .../[id]: ", "Telaah & SK")
        ]),
        ("5. AUDIT & NOTIF", 1280, 260, 280, 330, C_SLATE_LIGHT, C_SLATE_MID, [
            ("• /audit-logs: ", "Log Forensik"),
            ("• Modal Diff: ", "Inspeksi Data"),
            ("• /notifications: ", "Pemberitahuan"),
            ("• Profil Akun: ", "Sesi Pengguna")
        ])
    ]

    for title, x, y, w, h, bg, border, items in routes:
        c.draw_card(x, y, w, h, bg=bg, border=border, border_w=3, radius=10,
                    header_bg=border, header_text=title, header_size=16.5,
                    items=items, item_size=15.5, line_spacing=32)
        c.draw_arrow(800, 240, x + w/2, y, color=C_SLATE_MID, width=2)

    c.draw_card(40, 610, 1520, 270, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                header_bg=C_NAVY, header_text="PENJELASAN PENGATURAN NAVIGASI & KEMUDAHAN PENGGUNA", header_size=18.5,
                items=[
                    ("• Struktur Teratur & Terpadu: ", "Seluruh halaman dikelompokkan secara logis sehingga pengguna mudah menemukan menu yang dibutuhkan."),
                    ("• Penyesuaian Menu Otomatis: ", "Menu yang tampil pada layar disesuaikan otomatis dengan hak akses masing-masing pengguna."),
                    ("• Kesiapan Sistem 100%: ", "Sebanyak 16 rute halaman telah siap operasional dan teruji bebas kesalahan kompilasi.")
                ], item_size=15.5, line_spacing=34)

    c.save("sitemap_diagram.png")

# -------------------------------------------------------------
# 7. DIAGRAM ALIR PENAPISAN (GATE 1) & TELAAH (GATE 2)
# -------------------------------------------------------------
def render_gate_flowcharts():
    # Gate 1
    c1 = ExecutiveCanvas(1600, 900)
    c1.draw_top_title("ALUR PENAPISAN ADMINISTRATIF (GATE 1 ADMIN PANRB)",
                      "Pemeriksaan Kelengkapan Surat Permohonan, Dasar Hukum, dan Alokasi Penugasan Verifikator")

    c1.draw_card(550, 100, 500, 80, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2, radius=8,
                 header_bg=C_BLUE, header_text="Usulan Baru Masuk dari Instansi Pengusul", header_size=17,
                 items=["Berkas usulan masuk ke antrean kerja Penapis KemenPANRB."], item_size=15)

    c1.draw_arrow(800, 180, 800, 240, color=C_NAVY, width=3)

    c1.draw_card(380, 240, 840, 125, bg=C_AMBER_LIGHT, border=C_AMBER, border_w=3, radius=10,
                 header_bg=C_AMBER, header_text="Pemeriksaan Kelengkapan Formal Dokumen & Dasar Hukum", header_size=17.5,
                 items=[
                     "• Validasi kelengkapan surat pengantar Menteri / Kepala Lembaga",
                     "• Pemeriksaan lampiran naskah akademik & draf regulasi pembentukan"
                 ], item_size=15.5, line_spacing=28)

    c1.draw_arrow(520, 365, 290, 440, color=C_RED, width=3, label="Berkas Tidak Lengkap / Tidak Sesuai", label_bg=C_RED_LIGHT, label_color=C_RED)
    c1.draw_arrow(1080, 365, 1310, 440, color=C_EMERALD, width=3, label="Berkas Lengkap & Memenuhi Syarat", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    c1.draw_card(40, 440, 560, 220, bg=C_RED_LIGHT, border=C_RED, border_w=3, radius=10,
                 header_bg=C_RED, header_text="PENGEMBALIAN REVISI ADMINISTRATIF", header_size=17,
                 items=[
                     ("• Tindakan: ", "Admin mengklik tombol kembalikan berkas"),
                     ("• Status Berkas: ", "Berubah menjadi PERLU REVISI"),
                     ("• Catatan: ", "Admin mencantumkan dokumen yang kurang"),
                     ("• Notifikasi: ", "Operator menerima pemberitahuan perbaikan")
                 ], item_size=15.5, line_spacing=28)

    c1.draw_card(1000, 440, 560, 220, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=3, radius=10,
                 header_bg=C_EMERALD, header_text="PENERIMAAN & PENUGASAN VERIFIKATOR", header_size=17,
                 items=[
                     ("• Tindakan: ", "Admin menerima berkas & memilih Verifikator"),
                     ("• Status Berkas: ", "Berubah menjadi DITUGASKAN KE VERIFIKATOR"),
                     ("• Alokasi: ", "Menugaskan Pejabat Analis yang berkompeten"),
                     ("• Dampak: ", "Berkas masuk ke Ruang Kerja Verifikator")
                 ], item_size=15.5, line_spacing=28)

    c1.draw_card(40, 680, 1520, 200, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                 header_bg=C_NAVY, header_text="BATASAN WEWENANG PENAPIS ADMIN (SEPARATION OF DUTIES)", header_size=18.5,
                 items=[
                     ("• Fokus Tugas Penapis: ", "Admin KemenPANRB bertugas memverifikasi kelengkapan formalitas surat dan naskah dokumen."),
                     ("• Batasan Tegas: ", "Admin DILARANG melakukan persetujuan substantif maupun menandatangani pengesahan SK resmi.")
                 ], item_size=15.5, line_spacing=32)

    c1.save("gate1_admin_flowchart.png")

    # Gate 2
    c2 = ExecutiveCanvas(1600, 900)
    c2.draw_top_title("ALUR TELAAH SUBSTANTIF & PENGESAHAN SK (GATE 2 VERIFIKATOR)",
                      "Evaluasi Beban Kerja Organisasi, Analisis Formasi Jabatan ASN, dan Pengesahan Surat Keputusan (SK)")

    c2.draw_card(550, 100, 500, 80, bg=C_AMBER_LIGHT, border=C_AMBER, border_w=2, radius=8,
                 header_bg=C_AMBER, header_text="Menerima Penugasan Telaah Kelembagaan", header_size=17,
                 items=["Verifikator membuka berkas usulan di Ruang Kerja Telaah."], item_size=15)

    c2.draw_arrow(800, 180, 800, 240, color=C_NAVY, width=3)

    c2.draw_card(380, 240, 840, 125, bg=C_PURPLE_LIGHT, border=C_PURPLE, border_w=3, radius=10,
                 header_bg=C_PURPLE, header_text="Telaah Substantif Kelembagaan & Analisis Beban Kerja", header_size=17.5,
                 items=[
                     "• Evaluasi rentang kendali struktur (span of control) & eselonisasi",
                     "• Analisis kuota formasi jabatan ASN & keselarasan tugas pokok dan fungsi"
                 ], item_size=15.5, line_spacing=28)

    c2.draw_arrow(520, 365, 290, 440, color=C_RED, width=3, label="Ditemukan Catatan Substantif", label_bg=C_RED_LIGHT, label_color=C_RED)
    c2.draw_arrow(1080, 365, 1310, 440, color=C_EMERALD, width=3, label="Telaah Substantif Disetujui Penuh", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    c2.draw_card(40, 440, 560, 220, bg=C_RED_LIGHT, border=C_RED, border_w=3, radius=10,
                 header_bg=C_RED, header_text="PENGEMBALIAN REVISI SUBSTANTIF", header_size=17,
                 items=[
                     ("• Tindakan: ", "Verifikator mengisi catatan telaah teknis"),
                     ("• Status Berkas: ", "Berubah menjadi REVISI DARI VERIFIKATOR"),
                     ("• Pelacakan: ", "Tersimpan rinci pada catatan review per unit"),
                     ("• Perbaikan: ", "Operator memperbaiki usulan lalu kirim versi 2")
                 ], item_size=15.5, line_spacing=28)

    c2.draw_card(1000, 440, 560, 220, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=3, radius=10,
                 header_bg=C_EMERALD, header_text="PENGESAHAN SK RESMI & PROMOSI DATA", header_size=17,
                 items=[
                     ("• Wewenang Pengesahan: ", "Verifikator mengklik tombol Sahkan SK"),
                     ("• Penerbitan SK: ", "Status DISETUJUI & terbit nomor SK resmi"),
                     ("• Promosi Otomatis: ", "Data unit & jabatan masuk ke data master"),
                     ("• Hasil Akhir: ", "Usulan selesai sukses & struktur aktif live")
                 ], item_size=15.5, line_spacing=28)

    c2.draw_card(40, 680, 1520, 200, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                 header_bg=C_NAVY, header_text="WEWENANG MUTLAK PENGESAHAN SK & OTOMASI DATA", header_size=18.5,
                 items=[
                     ("• Otoritas Pengesahan Akhir: ", "Verifikator memegang hak wewenang tunggal untuk menandatangani persetujuan Surat Keputusan."),
                     ("• Pembaruan Otomatis: ", "Setelah SK disahkan, sistem otomatis memutasi unit dan jabatan ke data master aktif tanpa intervensi manual.")
                 ], item_size=15.5, line_spacing=32)

    c2.save("gate2_verifier_flowchart.png")

# -------------------------------------------------------------
# 8. MASTER DATA FLOW & POHON ORGANISASI
# -------------------------------------------------------------
def render_master_data_hierarchy():
    # Master Data Flow
    c1 = ExecutiveCanvas(1600, 900)
    c1.draw_top_title("KETERHUBUNGAN MASTER DATA KELEMBAGAAN",
                      "Hubungan Antara Data Instansi, Komposisi Kabinet, Pohon Unit Kerja, Formasi Jabatan, dan Tupoksi")

    c1.draw_card(40, 100, 470, 430, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=3, radius=10,
                 header_bg=C_BLUE, header_text="1. Instansi & Kabinet Pemerintahan", header_size=17.5,
                 items=[
                     ("• Master Instansi: ", "Kementerian, LPNK, Lembaga & Pemda"),
                     ("• Kategori Bentuk: ", "Kemenko, Kementerian, LPNK, dll."),
                     ("• Komposisi Kabinet: ", "Pemetaan ke Kabinet Merah Putih"),
                     ("• Koordinasi Kemenko: ", "Instansi teknis di bawah Menko terkait")
                 ], item_size=15.5, line_spacing=36)

    c1.draw_arrow(515, 315, 565, 315, color=C_BLUE, width=3)

    c1.draw_card(565, 100, 470, 430, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=3, radius=10,
                 header_bg=C_EMERALD, header_text="2. Unit Kerja Struktural (Pohon Berjenjang)", header_size=17.5,
                 items=[
                     ("• Struktur Data: ", "Hubungan Induk-Anak (Parent-Child)"),
                     ("• Tingkat Kedudukan: ", "Level 1 (Pimpinan) s.d. Level 4 (Pelaksana)"),
                     ("• Validasi Keamanan: ", "Mencegah relasi melingkar / rusak"),
                     ("• Status Keaktifan: ", "Unit berstatus AKTIF atau NON-AKTIF")
                 ], item_size=15.5, line_spacing=36)

    c1.draw_arrow(1040, 315, 1090, 315, color=C_EMERALD, width=3)

    c1.draw_card(1090, 100, 470, 430, bg=C_AMBER_LIGHT, border=C_AMBER, border_w=3, radius=10,
                 header_bg=C_AMBER, header_text="3. Formasi Jabatan & Tupoksi Unit", header_size=17.5,
                 items=[
                     ("• Nama Jabatan: ", "Nama jabatan struktural resmi"),
                     ("• Jenjang Eselon: ", "Eselon I.a, I.b, II.a, II.b, III.a, IV.a"),
                     ("• Kuota Formasi ASN: ", "Jumlah pegawai aparatur yang disetujui"),
                     ("• Tupoksi Unit: ", "Tugas pokok dan fungsi operasional")
                 ], item_size=15.5, line_spacing=36)

    c1.draw_card(40, 550, 1520, 330, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                header_bg=C_NAVY, header_text="PENYAJIAN BAGAN POHON INTERAKTIF PADA APLIKASI", header_size=18.5,
                items=[
                    ("• Visualisasi Bagan Interaktif: ", "Aplikasi otomatis menyusun struktur organisasi menjadi diagram pohon visual yang mudah dipahami."),
                    ("• Penataan Otomatis: ", "Posisi kotak unit dihitung rapi berdasarkan tingkatan eselon dan divisi kerja."),
                    ("• Panel Rincian Cepat: ", "Klik pada kotak unit akan langsung menampilkan formasi jabatan pegawai dan tugas pokok fungsi (tupoksi)."),
                    ("• Navigasi Mudah: ", "Mendukung fitur perbesar/perkecil (Zoom), geser kanvas (Pan), dan pencarian cepat unit kerja.")
                ], item_size=15.5, line_spacing=34)

    c1.save("master_data_flow.png")

    # Pohon Organisasi Berjenjang
    c2 = ExecutiveCanvas(1600, 900)
    c2.draw_top_title("BAGAN POHON STRUKTUR ORGANISASI BERJENJANG (ESELON I - IV)",
                      "Contoh Representasi Hubungan Induk-Anak Struktur Kementerian/Lembaga Pemerintah")

    # Level 1
    c2.draw_card(600, 100, 400, 85, bg=C_NAVY, border=C_NAVY, border_w=2, radius=8,
                 header_text="Menteri / Kepala Lembaga", header_size=18, header_color=C_WHITE,
                 items=["Kedudukan: Level 1 (Pimpinan Lembaga)  •  Status: AKTIF"], item_size=15, item_color=C_GOLD)

    # Level 2 (2 Cabang)
    c2.draw_card(200, 230, 520, 115, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2, radius=8,
                 header_bg=C_BLUE, header_text="Sekretariat Kementerian (Eselon I.a)", header_size=17,
                 items=["• Kedudukan: Level 2 (Sekretariat Lembaga)", "• Fungsi: Koordinasi pelaksanaan tugas, pembinaan & administrasi"],
                 item_size=14.5, line_spacing=24)

    c2.draw_card(880, 230, 520, 115, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2, radius=8,
                 header_bg=C_BLUE, header_text="Deputi Bidang Kelembagaan (Eselon I.a)", header_size=17,
                 items=["• Kedudukan: Level 2 (Pelaksana Utama)", "• Fungsi: Perumusan kebijakan & penataan kelembagaan nasional"],
                 item_size=14.5, line_spacing=24)

    c2.draw_arrow(700, 185, 460, 230, color=C_NAVY, width=2)
    c2.draw_arrow(900, 185, 1140, 230, color=C_NAVY, width=2)

    # Level 3 (4 Unit)
    c2.draw_card(40, 395, 360, 155, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2, radius=8,
                 header_text="Biro SDM & Organisasi", header_size=17, header_color=C_BLUE,
                 items=["• Level 3 (Eselon II.a)", "• Induk: Sekretariat", "• Formasi: 24 Pegawai ASN"],
                 item_size=14.5, line_spacing=26)

    c2.draw_card(425, 395, 360, 155, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2, radius=8,
                 header_text="Biro Perencanaan & Kerjasama", header_size=17, header_color=C_BLUE,
                 items=["• Level 3 (Eselon II.a)", "• Induk: Sekretariat", "• Formasi: 18 Pegawai ASN"],
                 item_size=14.5, line_spacing=26)

    c2.draw_card(810, 395, 360, 155, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2, radius=8,
                 header_text="Asdep Tata Laksana", header_size=17, header_color=C_BLUE,
                 items=["• Level 3 (Eselon II.a)", "• Induk: Deputi Kelembagaan", "• Formasi: 16 Pegawai ASN"],
                 item_size=14.5, line_spacing=26)

    c2.draw_card(1195, 395, 365, 155, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2, radius=8,
                 header_text="Asdep Struktur K/L", header_size=17, header_color=C_BLUE,
                 items=["• Level 3 (Eselon II.a)", "• Induk: Deputi Kelembagaan", "• Formasi: 20 Pegawai ASN"],
                 item_size=14.5, line_spacing=26)

    c2.draw_arrow(350, 345, 220, 395, color=C_BLUE, width=2)
    c2.draw_arrow(570, 345, 605, 395, color=C_BLUE, width=2)
    c2.draw_arrow(1030, 345, 990, 395, color=C_BLUE, width=2)
    c2.draw_arrow(1250, 345, 1375, 395, color=C_BLUE, width=2)

    c2.draw_card(40, 580, 1520, 300, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                 header_bg=C_NAVY, header_text="MODEL PENYIMPANAN DATA EFISIEN & AMAN", header_size=18.5,
                 items=[
                     ("• Penyimpanan Ringkas: ", "Setiap unit kerja hanya menyimpan penunjuk ID induknya sehingga basis data tetap ringan dan cepat diakses."),
                     ("• Validasi Pohon Otomatis: ", "Saat usulan diajukan, sistem otomatis memverifikasi bahwa struktur pohon tidak memiliki kesalahan relasi.")
                 ], item_size=15.5, line_spacing=34)

    c2.save("org_hierarchy_tree.png")

# -------------------------------------------------------------
# 9. OTENTIKASI & KEAMANAN SESI (JWT)
# -------------------------------------------------------------
def render_auth_security():
    c = ExecutiveCanvas(1600, 900)
    c.draw_top_title("ALUR MASUK SISTEM (LOGIN) & KEAMANAN SESI PENGGUNA",
                     "Siklus Autentikasi Pengguna Menggunakan Token Kriptografis (JWT) dan Penegakan Hak Akses Aman")

    c.draw_card(40, 100, 470, 440, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=3, radius=10,
                header_bg=C_BLUE, header_text="1. Masuk Sistem (Login)", header_size=17.5,
                items=[
                    ("• Masukan: ", "NIP / Nama Pengguna & Kata Sandi"),
                    ("• Validasi Server: ", "Pencocokan akun & kata sandi aman"),
                    ("• Penerbitan Token: ", "Menerbitkan Token Digital JWT Resmi"),
                    ("• Isi Token: ", "ID Pengguna, NIP, Peran, Instansi Asal"),
                    ("• Keamanan: ", "Perlindungan dari upaya tebak sandi"),
                    ("• Respon: ", "Token aktif & data profil pengguna")
                ], item_size=15.5, line_spacing=36)

    c.draw_arrow(515, 320, 565, 320, color=C_BLUE, width=3)

    c.draw_card(565, 100, 470, 440, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=3, radius=10,
                header_bg=C_EMERALD, header_text="2. Penyimpanan & Penyisipan Token", header_size=17.5,
                items=[
                    ("• Penyimpanan: ", "Tersimpan aman di memori browser"),
                    ("• Penyisipan Otomatis: ", "Token otomatis disertakan di setiap request"),
                    ("• Klien Jaringan: ", "Aplikasi menjaga sesi tetap terhubung"),
                    ("• Keluar Sistem: ", "Token langsung dihapus saat pengguna keluar"),
                    ("• Penanganan Sesi: ", "Pengalihan aman jika sesi habis waktu")
                ], item_size=15.5, line_spacing=36)

    c.draw_arrow(1040, 320, 1090, 320, color=C_EMERALD, width=3)

    c.draw_card(1090, 100, 470, 440, bg=C_PURPLE_LIGHT, border=C_PURPLE, border_w=3, radius=10,
                header_bg=C_PURPLE, header_text="3. Validasi Profil & Batasan Akses", header_size=17.5,
                items=[
                    ("• Pemeriksaan Token: ", "Server memeriksa keaslian token digital"),
                    ("• Penetapan Wilayah: ", "Server menentukan daftar K/L yang sah diakses"),
                    ("• Pengaturan Menu: ", "Tampilan antarmuka disesuaikan dengan peran"),
                    ("• Penguncian Peran: ", "Peran terkunci; tidak bisa diubah sendiri"),
                    ("• Blokir Otomatis: ", "Akses langsung ditolak jika mencoba buka K/L lain")
                ], item_size=15.5, line_spacing=36)

    c.draw_card(40, 560, 1520, 320, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=10,
                header_bg=C_NAVY, header_text="JAMINAN KEAMANAN DATA TINGKAT TINGGI", header_size=18.5,
                items=[
                    ("• Mode Produksi Resmi: ", "Pengganti peran manual di browser dinonaktifkan permanen saat terhubung ke server utama."),
                    ("• Tolak Manipulasi Klien: ", "Segala upaya manipulasi peran pada browser akan langsung ditolak mentah-mentah oleh server backend."),
                    ("• Perlindungan Data Instansi: ", "Server memverifikasi kepemilikan instansi pada setiap proses untuk mencegah kebocoran data antar K/L/D."),
                    ("• Keamanan Tanpa Kompromi: ", "Jika hak akses tidak sah, sistem langsung menampilkan pesan 'Akses Ditolak' secara tegas dan aman.")
                ], item_size=15.5, line_spacing=34)

    c.save("auth_jwt_flow.png")

# -------------------------------------------------------------
# 10. PROTOTIPE TAMPILAN APLIKASI
# -------------------------------------------------------------
def render_ui_prototypes():
    screens = [
        ("prototype_login.png", "SCR-18: LAYAR MASUK SISTEM (LOGIN)", [
            ("• Identitas Resmi Pemerintah: ", "Lambang Garuda Pancasila dan identitas resmi KemenPANRB / SIGMA-K."),
            ("• Formulir Masuk Aman: ", "Kolom isian NIP / Nama Pengguna dan Kata Sandi dengan validasi ketat."),
            ("• Pilihan Mode Operasional: ", "Dukungan mode API Live (Server Nyata) dan mode Simulasi Draf."),
            ("• Kemudahan Masuk Cepat: ", "Pilihan cepat akun percontohan (Operator Instansi, Penapis, Verifikator)."),
            ("• Pengamanan Sesi: ", "Penerbitan token digital resmi langsung setelah pengguna berhasil masuk.")
        ]),
        ("prototype_dashboard.png", "SCR-01: DASHBOARD EKSEKUTIF PIMPINAN", [
            ("• 4 Indikator Capaian Utama (KPI): ", "Total Usulan Masuk, Usulan Dalam Telaah, Usulan Disetujui, dan Promosi Selesai."),
            ("• Sorotan Kabinet Aktif: ", "Informasi Kabinet Merah Putih dan daftar kementerian koordinator terkait."),
            ("• Tabel Antrean Usulan: ", "Daftar usulan organisasi terkini lengkap dengan label warna status yang jelas."),
            ("• Pengesahan SK Terkini: ", "Daftar Surat Keputusan (SK) resmi yang baru saja disahkan oleh Verifikator."),
            ("• Indikator Status Sistem: ", "Label hijau 'Server Aktif Terhubung' memastikan sistem berjalan prima.")
        ]),
        ("prototype_institutions.png", "SCR-02: KATALOG MASTER INSTANSI PEMERINTAH", [
            ("• Tab Penyaring Instansi: ", "Semua K/L/D, Kementerian Koordinator, Kementerian, LPNK, Lembaga, dan Pemda."),
            ("• Pencarian Cepat: ", "Pencarian instan berdasarkan Kode Instansi (misal: KL-001) atau Nama Instansi."),
            ("• Tabel Master Lengkap: ", "Menampilkan kode resmi, nama instansi, jumlah unit kerja, posisi, dan status keaktifan."),
            ("• Navigasi Cepat: ", "Tombol langsung menuju 'Bagan Pohon Organisasi' dan 'Rincian Profil Instansi'.")
        ]),
        ("prototype_org_structure.png", "SCR-04: BAGAN POHON STRUKTUR ORGANISASI", [
            ("• Kanvas Interaktif: ", "Bagan struktur organisasi visual dengan kontrol perbesar, perkecil, dan peta mini."),
            ("• Kotak Unit Kerja: ", "Menampilkan kode unit, nama pimpinan, tingkat eselon, dan status aktif."),
            ("• Garis Hubungan Berjenjang: ", "Garis hubungan induk-anak tersusun rapi dari Pimpinan (Level 1) ke Pelaksana."),
            ("• Panel Rincian Samping: ", "Klik pada kotak unit membuka panel rincian formasi jabatan pegawai dan tupoksi.")
        ]),
        ("prototype_submission_detail.png", "SCR-09: RINCIAN USULAN & PELACAK PERUBAHAN (DIFF)", [
            ("• Indikator Tahapan Usulan: ", "Visualisasi progres alur (Draf -> Penapisan -> Telaah -> Pengesahan SK)."),
            ("• Informasi Usulan: ", "Nomor Berkas Usulan, Instansi Pengusul, Tanggal Pengajuan, dan NIP Pembuat."),
            ("• Pelacak Perubahan (Diff): ", "Unit Baru [Warna Hijau], Unit Dihapus [Warna Merah], Perubahan Formasi [Warna Kuning]."),
            ("• Riwayat Catatan Koreksi: ", "Rekam jejak catatan telaah dari Penapis Admin maupun Verifikator.")
        ]),
        ("prototype_verifier_workspace.png", "SCR-12: RUANG KERJA TELAAH VERIFIKATOR", [
            ("• Panel Telaah Substantif: ", "Analisis kesesuaian rentang kendali, beban kerja organisasi, dan formasi ASN."),
            ("• Isian Catatan Teknis: ", "Kolom input catatan telaah substantif per unit kerja untuk instansi pengusul."),
            ("• Tombol Kembalikan Revisi: ", "Tombol untuk mengembalikan berkas jika ditemukan kekurangan teknis."),
            ("• Pengesahan SK & Promosi: ", "Tombol pengesahan SK resmi yang langsung memperbarui data master aktif.")
        ]),
        ("prototype_analytics_reporting.png", "SCR-15: INTELIJENSI DATA & POSTUR ASN", [
            ("• Metrik Efisiensi Organisasi: ", "Indikator rasio rentang kendali dan beban kerja aparatur sipil negara."),
            ("• Piramida Eselonisasi: ", "Grafik distribusi jabatan struktural Eselon I, II, III, dan IV seluruh instansi."),
            ("• Kecepatan Layanan Telaah: ", "Rata-rata durasi penyelesaian telaah kelembagaan (Target: < 3 hari kerja)."),
            ("• Pusat Ekspor Dokumen: ", "Tombol unduh berkas laporan resmi (.CSV / .JSON) langsung dari server.")
        ]),
        ("prototype_audit_logs.png", "SCR-16: LOG FORENSIK AUDIT TRAIL", [
            ("• Tabel Riwayat Mutasi Data: ", "Waktu transaksi presisi tinggi, Nama Pengguna, Peran, dan Tindakan yang dilakukan."),
            ("• Informasi Jaringan Pengguna: ", "Rekam jejak alamat IP dan aplikasi peramban (browser) yang digunakan."),
            ("• Penampil Perubahan Data: ", "Kotak perbandingan data sebelum vs sesudah perubahan secara transparan."),
            ("• Jaminan Keamanan Mutlak: ", "Catatan tersimpan permanen dan tidak dapat dihapus oleh pihak manapun.")
        ])
    ]

    for fname, title, bullet_points in screens:
        c = ExecutiveCanvas(1600, 900)
        c.draw_top_title(f"PROTOTIPE / TAMPILAN: {title}",
                         "Spesifikasi Antarmuka Terverifikasi yang Telah Siap Operasional pada Aplikasi SIGMA-K")

        c.draw_card(40, 95, 1520, 780, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=12,
                    header_bg=C_NAVY, header_text=f"APLIKASI SIGMA-K - KEMENPANRB  |  {title.split(':')[0]}", header_size=19)

        # TopBar Sesi di Dalam
        c.draw_card(65, 155, 1470, 60, bg=C_WHITE, border=C_BORDER_DEFAULT, border_w=2, radius=6,
                    header_text="", header_size=16)
        c.draw.text((85, 185), "E-SKLD / SIGMA-K  •  Sistem Pengelolaan Data Kelembagaan", fill=C_BLUE, font=get_font(bold=True, size=18), anchor="lm")
        c.draw.text((1515, 185), "[Sesi Pengguna: VERIFIKATOR KELEMBAGAAN - NIP: 198001012005011001]", fill=C_EMERALD, font=get_font(bold=True, size=16), anchor="rm")

        # Konten Utama
        c.draw_card(65, 230, 1470, 620, bg=C_WHITE, border=C_BORDER_DEFAULT, border_w=2, radius=8,
                    header_bg=C_BLUE_LIGHT, header_text="PENJELASAN ELEMEN & FUNGSI PADA LAYAR", header_size=18, header_color=C_BLUE,
                    items=bullet_points, item_size=16.5, line_spacing=46)

        c.save(fname)

def main():
    print("=== MEMULAI GENERASI DIAGRAM VEKTOR BAHASA INDONESIA RESMI ===")
    render_system_architecture()
    render_role_access()
    render_submission_lifecycle()
    render_erd()
    render_versioning_diff()
    render_sitemap()
    render_gate_flowcharts()
    render_master_data_hierarchy()
    render_auth_security()
    render_ui_prototypes()
    print("=== SELURUH DIAGRAM BAHASA INDONESIA BERHASIL DIRENDER ===")

if __name__ == '__main__':
    main()
