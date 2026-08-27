"""
Ultra-Crisp Large-Typography Vector Diagram & UI Prototype Generator for SIGMA-K.
Optimized for high-impact visual clarity in Word documents, PDFs, and 16:9 PowerPoint presentations.
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

C_NAVY = "#0B2A4A"
C_BLUE = "#1E40AF"
C_BLUE_LIGHT = "#EFF6FF"
C_BLUE_BORDER = "#3B82F6"
C_GOLD = "#D4AF37"
C_AMBER = "#B45309"
C_AMBER_LIGHT = "#FFFBEB"
C_AMBER_BORDER = "#F59E0B"
C_EMERALD = "#047857"
C_EMERALD_LIGHT = "#ECFDF5"
C_EMERALD_BORDER = "#10B981"
C_PURPLE = "#6B21A8"
C_PURPLE_LIGHT = "#FAF5FF"
C_PURPLE_BORDER = "#A855F7"
C_RED = "#DC2626"
C_RED_LIGHT = "#FEF2F2"
C_RED_BORDER = "#EF4444"
C_SLATE_DARK = "#0F172A"
C_SLATE_MID = "#475569"
C_SLATE_LIGHT = "#F8FAFC"
C_BORDER_DEFAULT = "#CBD5E1"
C_WHITE = "#FFFFFF"

class UltraCanvas:
    def __init__(self, width=1600, height=900, bg="#FFFFFF"):
        self.w = width
        self.h = height
        self.img = Image.new("RGBA", (width, height), bg)
        self.draw = ImageDraw.Draw(self.img)

    def draw_top_title(self, title, subtitle=None):
        f_title = get_font(bold=True, size=32)
        self.draw.text((self.w / 2, 38), title, fill=C_NAVY, font=f_title, anchor="mm")
        
        if subtitle:
            f_sub = get_font(bold=False, size=18)
            self.draw.text((self.w / 2, 72), subtitle, fill=C_SLATE_MID, font=f_sub, anchor="mm")

    def draw_card(self, x, y, w, h, bg=C_WHITE, border=C_BORDER_DEFAULT, border_w=2, radius=10,
                  header_bg=None, header_text=None, header_color=C_WHITE, header_size=21,
                  items=None, item_size=18, item_color=C_SLATE_DARK, line_spacing=30):
        self.draw.rounded_rectangle([x, y, x + w, y + h], radius=radius, fill=bg, outline=border, width=border_w)

        content_top = y + 14

        if header_bg and header_text:
            header_h = 42
            self.draw.rounded_rectangle([x, y, x + w, y + header_h], radius=radius, fill=header_bg)
            self.draw.rectangle([x, y + header_h - radius, x + w, y + header_h], fill=header_bg)
            self.draw.rounded_rectangle([x, y, x + w, y + h], radius=radius, outline=border, width=border_w)
            
            f_head = get_font(bold=True, size=header_size)
            self.draw.text((x + w / 2, y + header_h / 2), header_text, fill=header_color, font=f_head, anchor="mm")
            content_top = y + header_h + 12
        elif header_text:
            f_head = get_font(bold=True, size=header_size)
            self.draw.text((x + w / 2, y + 20), header_text, fill=header_color if header_color != C_WHITE else C_NAVY, font=f_head, anchor="mm")
            content_top = y + 42

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
        print(f"  [SAVED CRISP 16:9] {filename}")

# -------------------------------------------------------------
# 1. SYSTEM ARCHITECTURE
# -------------------------------------------------------------
def render_system_architecture():
    c = UltraCanvas(1600, 900)
    c.draw_top_title("ARSITEKTUR SISTEM E-SKLD / SIGMA-K", 
                     "Integrasi 4-Tier: Frontend Next.js 14, HTTP Gateway, Backend CodeIgniter 4, & MySQL eskld_db")

    # Tier 1
    c.draw_card(40, 95, 1520, 240, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                header_bg=C_NAVY, header_text="TIER 1: PRESENTATION LAYER (Next.js 14 + React 18 + TypeScript)", header_size=20)

    c.draw_card(55, 145, 360, 175, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2,
                header_text="UI Components & Screens", header_size=17, header_color=C_BLUE,
                items=["• Executive Dashboard (/)", "• Org Chart React Flow Canvas", "• Katalog Instansi (/institutions)", "• Submission & Revision UI", "• Verifier Workspace Studio"],
                item_size=15.5, line_spacing=26)

    c.draw_card(430, 145, 360, 175, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2,
                header_text="State & Role Context", header_size=17, header_color=C_BLUE,
                items=["• AuthProvider & RoleContext", "• Read-Only Auth Role Badge", "• Notification Context Provider", "• Dual-Mode Data Dispatcher", "  (NEXT_PUBLIC_DATA_MODE)"],
                item_size=15.5, line_spacing=26)

    c.draw_card(805, 145, 365, 175, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2,
                header_text="Service Facades & DTO", header_size=17, header_color=C_BLUE,
                items=["• AuthService, InstitutionService", "• OrganizationService (Tree)", "• SubmissionService (FSM)", "• AnalyticsService & AuditService", "• TypeScript Strict DTO Schemas"],
                item_size=15.5, line_spacing=26)

    c.draw_card(1185, 145, 360, 175, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2,
                header_text="Domain Mappers", header_size=17, header_color=C_BLUE,
                items=["• DTO (snake_case) -> Domain", "• Flatten Org Hierarchy Tree", "• BigInt to String ID Sanitizer", "• Workflow Status Normalizer", "• AppError Response Normalizer"],
                item_size=15.5, line_spacing=26)

    # Arrow Tier 1 -> Tier 2
    c.draw_arrow(800, 340, 800, 390, color=C_BLUE, width=3, label="HTTPS JSON + Bearer JWT Token", label_bg=C_BLUE_LIGHT, label_color=C_BLUE)

    # Tier 2 Gateway
    c.draw_card(220, 390, 1160, 65, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2, radius=8,
                header_text="HTTP CLIENT & TOKEN PROVIDER GATEWAY", header_size=18, header_color=C_BLUE,
                items=["Native Fetch Client  •  Auto Authorization Header  •  AppError Status Normalizer  •  15s Signal"],
                item_size=15, item_color=C_NAVY, line_spacing=0)

    # Arrow Tier 2 -> Tier 3
    c.draw_arrow(800, 460, 800, 510, color=C_BLUE, width=3, label="REST API Endpoints (/api/v1/*)", label_bg=C_BLUE_LIGHT, label_color=C_BLUE)

    # Tier 3
    c.draw_card(40, 510, 1520, 265, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                header_bg=C_NAVY, header_text="TIER 2: BACKEND & DOMAIN LOGIC (CodeIgniter 4.4.8 + PHP 8.2+)", header_size=20)

    c.draw_card(55, 560, 360, 200, bg=C_WHITE, border=C_AMBER_BORDER, border_w=2,
                header_text="API Controllers & Filters", header_size=17, header_color=C_AMBER,
                items=["• AuthFilter (JWT Verify)", "• AuthController (Login / Me)", "• InstitutionController", "• SubmissionWorkflowController", "• VerifierWorkflowController", "• ReportController (Summary)"],
                item_size=15, line_spacing=26)

    c.draw_card(430, 560, 360, 200, bg=C_WHITE, border=C_AMBER_BORDER, border_w=2,
                header_text="Zero-Trust Authz Engine", header_size=17, header_color=C_AMBER,
                items=["• AuthorizationService (can)", "• ScopeResolver (Multi-Tenant)", "• AccessGrant Engine (Temporary)", "• Anti Self-Approval Security Guard", "• BOLA / IDOR Boundary Defense", "• Read-Only Role Claims Lock"],
                item_size=15, line_spacing=26)

    c.draw_card(805, 560, 365, 200, bg=C_WHITE, border=C_AMBER_BORDER, border_w=2,
                header_text="Core Domain Services", header_size=17, header_color=C_AMBER,
                items=["• SubmissionWorkflowService (FSM)", "• OrgHierarchyService (DFS Tree)", "• RevisionService (v1->v2 Snapshot)", "• ApprovalPromotionService (SK)", "• ExecutiveReportService (Funnel)", "• Separation of Duties Engine"],
                item_size=15, line_spacing=26)

    c.draw_card(1185, 560, 360, 200, bg=C_WHITE, border=C_AMBER_BORDER, border_w=2,
                header_text="Audit & Forensics Engine", header_size=17, header_color=C_AMBER,
                items=["• AuditService (Append-Only)", "• Payload Diffing Engine (JSON)", "• Actor NIP & Role Capture", "• Client IP & User Agent Tracker", "• Security Violation Interceptor", "• Binary CSV / JSON Export Stream"],
                item_size=15, line_spacing=26)

    # Arrow Tier 3 -> Tier 4
    c.draw_arrow(800, 780, 800, 825, color=C_EMERALD, width=3, label="MySQL PDO Connection", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    # Tier 4
    c.draw_card(220, 825, 1160, 60, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=2.5, radius=8,
                header_text="TIER 3: DATA PERSISTENCE - MySQL 8.x (eskld_db - 21 Relational Tables)", header_size=18, header_color=C_EMERALD,
                items=["Integritas Relasi FK  •  Snapshot Table Immutability  •  Zero-Trust User Scopes  •  Append-Only Audit Logs"],
                item_size=15, item_color=C_NAVY, line_spacing=0)

    c.save("system_architecture.png")

# -------------------------------------------------------------
# 2. ROLE & ACCESS MATRIX
# -------------------------------------------------------------
def render_role_access():
    c = UltraCanvas(1600, 900)
    c.draw_top_title("MODEL OTORISASI & MATRIKS HAK AKSES ZERO-TRUST", 
                     "Pemisahan Wewenang (Separation of Duties) dan Batasan Hak Akses per Peran Pengguna")

    roles = [
        ("USER (Operator)", 40, 95, 360, 480, C_BLUE_LIGHT, C_BLUE, C_BLUE, [
            ("• Lingkup: ", "Home Institution"),
            ("• Tugas: ", "Draf Usulan Struktur Organisasi"),
            ("• Drafting: ", "Menyusun unit & jabatan (v1)"),
            ("• Pengajuan: ", "Submit ke Gate 1 Admin"),
            ("• Perbaikan: ", "Memperbaiki berkas revisi (v2)"),
            ("• Batasan: ", "DILARANG telaah & approve"),
            ("• Keamanan: ", "403 Forbidden bila lintas instansi")
        ]),
        ("ADMIN (Penapis)", 425, 95, 360, 480, C_AMBER_LIGHT, C_AMBER, C_AMBER, [
            ("• Lingkup: ", "Scoped / Assigned K/L/D"),
            ("• Tugas: ", "Penapisan Administrasi Gate 1"),
            ("• Screening: ", "Validasi kelengkapan & dasar hukum"),
            ("• Revisi: ", "Mengembalikan revisi formal"),
            ("• Penugasan: ", "Menugaskan Verifikator"),
            ("• Batasan: ", "DILARANG Final Approval"),
            ("• Batas Peran: ", "Hanya penapis formal, bukan penilai")
        ]),
        ("VERIFIER (Verifikator)", 810, 95, 360, 480, C_EMERALD_LIGHT, C_EMERALD, C_EMERALD, [
            ("• Lingkup: ", "Queue Berkas Ditugaskan"),
            ("• Tugas: ", "Telaah Substantif Gate 2"),
            ("• Analisis: ", "Evaluasi beban kerja & formasi"),
            ("• Catatan: ", "Review notes per unit kerja"),
            ("• Wewenang: ", "FINAL APPROVAL SK RESMI"),
            ("• Promosi: ", "Eksekusi migrasi ke master data"),
            ("• Tanggung Jawab: ", "Integritas yuridis & teknis SK")
        ]),
        ("SUPER_ADMIN / SESDEP", 1195, 95, 365, 480, C_PURPLE_LIGHT, C_PURPLE, C_PURPLE, [
            ("• Lingkup: ", "Nasional Seluruh Indonesia"),
            ("• Tugas: ", "Administrasi & Supervisi"),
            ("• Akses: ", "Kelola akun & access grants"),
            ("• Monitoring: ", "Dashboard status seluruh K/L"),
            ("• Forensik: ", "Audit trail & payload diffing"),
            ("• Ekspor: ", "Unduh dataset nasional"),
            ("• Status: ", "SESDEP dinormalisasi ke SUPER_ADMIN")
        ])
    ]

    for title, x, y, w, h, bg, border, hbg, items in roles:
        c.draw_card(x, y, w, h, bg=bg, border=border, border_w=2.5, radius=10,
                    header_bg=hbg, header_text=title, header_size=18,
                    items=items, item_size=16, line_spacing=34)

    # Bottom Principles Card
    c.draw_card(40, 595, 1520, 285, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                header_bg=C_NAVY, header_text="5 PRINSIP UTAMA KEAMANAN ZERO-TRUST & SEPARATION OF DUTIES", header_size=19,
                items=[
                    ("1. Backend Single Source of Truth: ", "Frontend tidak mengambil keputusan otorisasi mandiri; CI4 AuthorizationService menjadi otoritas mutlak."),
                    ("2. Strict Role Locking: ", "Persona switcher dinonaktifkan pada mode API produksi guna mencegah eskalasi hak akses ilegal."),
                    ("3. Separation of Duties: ", "ADMIN berwenang Gate 1 (Penapisan/Penugasan); FINAL APPROVAL dan Promosi mutlak wewenang VERIFIER."),
                    ("4. Anti Self-Approval Guard: ", "Sistem secara kriptografis memblokir pengguna agar tidak dapat menelaah atau menyetujui usulan buatannya sendiri."),
                    ("5. BOLA / IDOR Scoping Guard: ", "Setiap kueri data instansi, struktur unit, dan usulan diverifikasi kepemilikannya terhadap user_scopes & access_grants.")
                ], item_size=15.5, line_spacing=34)

    c.save("role_access_matrix.png")

# -------------------------------------------------------------
# 3. SUBMISSION LIFECYCLE FSM
# -------------------------------------------------------------
def render_submission_lifecycle():
    c = UltraCanvas(1600, 900)
    c.draw_top_title("FINITE STATE MACHINE & SIKLUS HIDUP PENGUSULAN KELEMBAGAAN", 
                     "7 Tahapan Status Usulan Penataan Organisasi dari Drafting hingga Promosi Master Data")

    c.draw_card(40, 100, 340, 185, bg=C_SLATE_LIGHT, border=C_SLATE_MID, border_w=2.5, radius=10,
                header_bg=C_SLATE_MID, header_text="1. DRAFT (Operator)", header_size=18,
                items=["• Operator menyusun draf awal", "• Tambah unit & kuota formasi", "• Disimpan snapshot versi v1"],
                item_size=15.5, line_spacing=28)

    c.draw_arrow(385, 190, 435, 190, color=C_BLUE, width=3, label="submit()", label_bg=C_BLUE_LIGHT, label_color=C_BLUE)

    c.draw_card(435, 100, 340, 185, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2.5, radius=10,
                header_bg=C_BLUE, header_text="2. SUBMITTED_TO_ADMIN", header_size=18,
                items=["• Masuk antrean KemenPANRB", "• Admin memeriksa kelengkapan", "• Pengecekan dasar hukum & naskah"],
                item_size=15.5, line_spacing=28)

    c.draw_arrow(780, 190, 830, 190, color=C_AMBER, width=3, label="accept() & assign()", label_bg=C_AMBER_LIGHT, label_color=C_AMBER)

    c.draw_card(830, 100, 340, 185, bg=C_AMBER_LIGHT, border=C_AMBER, border_w=2.5, radius=10,
                header_bg=C_AMBER, header_text="3. ASSIGNED_TO_VERIFIER", header_size=18,
                items=["• Penugasan Verifikator", "• Telaah beban kerja & eselon", "• Status IN_REVIEW_BY_VERIFIER"],
                item_size=15.5, line_spacing=28)

    c.draw_arrow(1175, 190, 1225, 190, color=C_PURPLE, width=3, label="approveSubstantive()", label_bg=C_PURPLE_LIGHT, label_color=C_PURPLE)

    c.draw_card(1225, 100, 335, 185, bg=C_PURPLE_LIGHT, border=C_PURPLE, border_w=2.5, radius=10,
                header_bg=C_PURPLE, header_text="4. READY_FOR_DECISION", header_size=18,
                items=["• Telaah teknis selesai", "• Catatan telaah terpenuhi", "• Berkas siap pengesahan"],
                item_size=15.5, line_spacing=28)

    # Arrow to Final Approval
    c.draw_arrow(1390, 290, 1390, 380, color=C_EMERALD, width=4, label="finalApprove(VERIFIER)", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    # Bottom Row
    c.draw_card(1225, 380, 335, 185, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=2.5, radius=10,
                header_bg=C_EMERALD, header_text="5. APPROVED (SK Resmi)", header_size=18,
                items=["• SK resmi disahkan Verifikator", "• Nomor & tanggal penetapan SK", "• Rekam jejak approval_records"],
                item_size=15.5, line_spacing=28)

    c.draw_arrow(1225, 470, 1175, 470, color=C_EMERALD, width=3, label="promote()", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    c.draw_card(830, 380, 340, 185, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=2.5, radius=10,
                header_bg=C_EMERALD, header_text="6. PROMOTED (Master Data)", header_size=18,
                items=["• Migrasi otomatis ke master aktif", "• Unit & jabatan live terupdate", "• Usulan ditutup dengan sukses"],
                item_size=15.5, line_spacing=28)

    c.draw_card(40, 380, 735, 185, bg=C_RED_LIGHT, border=C_RED, border_w=2.5, radius=10,
                header_bg=C_RED, header_text="7. REVISION_REQUIRED (Siklus Perbaikan Berkas)", header_size=18,
                items=[
                    ("• Pengembalian Admin: ", "Berkas dikembalikan jika tidak lengkap formal (returnAdmin)."),
                    ("• Pengembalian Verifikator: ", "Berkas dikembalikan jika ada koreksi teknis (returnVerifier)."),
                    ("• Resubmit & Versioning: ", "Operator memperbaiki berkas dan mengirimkan versi baru (v1 -> v2).")
                ], item_size=15.5, line_spacing=30)

    # Revision Arrows
    c.draw_arrow(605, 290, 605, 380, color=C_RED, width=3, label="returnAdmin()", label_bg=C_RED_LIGHT, label_color=C_RED)
    c.draw_arrow(1000, 290, 780, 380, color=C_RED, width=3, label="returnVerifier()", label_bg=C_RED_LIGHT, label_color=C_RED)
    c.draw_arrow(210, 380, 210, 290, color=C_SLATE_MID, width=3, label="resubmit() [v1 -> v2]", label_bg=C_SLATE_LIGHT, label_color=C_SLATE_DARK)

    # Summary Footer Card
    c.draw_card(40, 590, 1520, 290, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                header_bg=C_NAVY, header_text="RINGKASAN ATURAN BISNIS FINITE STATE MACHINE (FSM)", header_size=19,
                items=[
                    ("• Immutabilitas Riwayat: ", "Setiap perpindahan status dicatat permanen dalam tabel audit_logs dan history usulan."),
                    ("• Final Approval Mandate: ", "Pengesahan Surat Keputusan (SK) dan eksekusi promosi data mutlak menjadi wewenang VERIFIER."),
                    ("• Automatic Data Promotion: ", "Tidak ada intervensi manual; sistem memigrasikan perubahan unit dan jabatan dari tabel submission langsung ke tabel aktif."),
                    ("• Resubmission Integrity: ", "Setiap pengajuan ulang setelah revisi secara otomatis menaikkan nomor versi usulan (v1 -> v2 -> v3).")
                ], item_size=15.5, line_spacing=34)

    c.save("submission_lifecycle_fsm.png")

# -------------------------------------------------------------
# 4. DATABASE ERD
# -------------------------------------------------------------
def render_erd():
    c = UltraCanvas(1600, 900)
    c.draw_top_title("ENTITY RELATIONSHIP DIAGRAM (ERD) - eskld_db (21 TABEL)", 
                     "Struktur Data Relasional Terintegrasi: Autentikasi, Master Data, Usulan & Versioning, Verifikasi & Audit")

    c.draw_card(40, 95, 360, 480, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2.5, radius=10,
                header_bg=C_BLUE, header_text="1. AUTH & ACCESS CONTROL", header_size=17,
                items=[
                    ("• users: ", "id (PK), nip, email, pass_hash"),
                    ("• roles: ", "id (PK), code, name"),
                    ("• permissions: ", "id (PK), code, name"),
                    ("• role_permissions: ", "role_id, perm_id"),
                    ("• user_scopes: ", "id (PK), user_id, inst_id"),
                    ("• access_grants: ", "id, user_id, inst_id, type"),
                    ("• access_requests: ", "id, user_id, inst_id, stat")
                ], item_size=15.5, line_spacing=34)

    c.draw_card(425, 95, 360, 480, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=2.5, radius=10,
                header_bg=C_EMERALD, header_text="2. MASTER DATA KELEMBAGAAN", header_size=17,
                items=[
                    ("• institutions: ", "id (PK), code, name, cat"),
                    ("• institution_types: ", "id (PK), code, name"),
                    ("• cabinets: ", "id (PK), name, is_active"),
                    ("• cabinet_institutions: ", "cab_id, inst_id"),
                    ("• organizational_units: ", "id, inst_id, parent_id,"),
                    ("  - unit_code, ", "unit_name, unit_level"),
                    ("• positions: ", "id, unit_id, pos_name, echelon,"),
                    ("  - formation_count, ", "status")
                ], item_size=15.5, line_spacing=34)

    c.draw_card(810, 95, 360, 480, bg=C_AMBER_LIGHT, border=C_AMBER, border_w=2.5, radius=10,
                header_bg=C_AMBER, header_text="3. SUBMISSION & VERSIONING", header_size=17,
                items=[
                    ("• submissions: ", "id (PK), inst_id, author_id,"),
                    ("  - title, ", "submission_year, current_state"),
                    ("• submission_versions: ", "id, sub_id,"),
                    ("  - version_number, ", "notes, created_at"),
                    ("• submission_units: ", "id, version_id, parent_id,"),
                    ("  - unit_code, ", "unit_name, action_type"),
                    ("• submission_positions: ", "id, version_id, unit_id,"),
                    ("  - pos_name, ", "formation_count, action_type")
                ], item_size=15.5, line_spacing=34)

    c.draw_card(1195, 95, 365, 480, bg=C_PURPLE_LIGHT, border=C_PURPLE, border_w=2.5, radius=10,
                header_bg=C_PURPLE, header_text="4. VERIFIKASI & AUDIT", header_size=17,
                items=[
                    ("• verifier_assignments: ", "id, sub_id,"),
                    ("  - verifier_id, ", "assigned_by_admin, status"),
                    ("• verifier_review_notes: ", "id, sub_id,"),
                    ("  - verifier_id, ", "section, note, resolved"),
                    ("• approval_records: ", "id, version_id,"),
                    ("  - approver_id, ", "approval_number, notes"),
                    ("• audit_logs: ", "id (PK), actor_id, event,"),
                    ("  - entity, ", "old_payload, new_payload, ip")
                ], item_size=15.5, line_spacing=34)

    c.draw_card(40, 595, 1520, 285, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                header_bg=C_NAVY, header_text="KONSISTENSI RELASI KUNCI & INTEGRITAS DATA", header_size=19,
                items=[
                    ("• Primary Key & Foreign Key Constraints: ", "Seluruh ID entitas menggunakan tipe integer terindeks dengan relasi constraint CASCADE/RESTRICT."),
                    ("• Adjacency List Tree Model: ", "Tabel organizational_units menerapkan relasi mandiri parent_id dengan pencegahan loop siklus via DFS."),
                    ("• Snapshot Immutability: ", "Tabel submission_versions, submission_units, dan submission_positions mengunci data draf historis secara permanen."),
                    ("• Zero-Trust Scope Binding: ", "Tabel user_scopes dan access_grants mengisolasi hak akses data instansi secara multi-tenant."),
                    ("• Forensik Mutlak: ", "Tabel audit_logs bersifat append-only tanpa izin UPDATE/DELETE guna menjamin keaslian bukti kepatuhan.")
                ], item_size=15.5, line_spacing=34)

    c.save("erd_diagram.png")

# -------------------------------------------------------------
# 5. VERSIONING & DIFF FLOW
# -------------------------------------------------------------
def render_versioning_diff():
    c = UltraCanvas(1600, 900)
    c.draw_top_title("MEKANISME SNAPSHOT VERSIONING & PELACAKAN PERUBAHAN (DIFF)", 
                     "Penguncian Versi Usulan Historis dan Komparasi Visual Perubahan Unit/Jabatan Organisasi")

    c.draw_card(40, 100, 470, 420, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2.5, radius=10,
                header_bg=C_BLUE, header_text="Versi Usulan 1 (v1) - Draf Awal", header_size=18,
                items=[
                    ("• Entitas: ", "submission_versions (v1)"),
                    ("• Snapshot Unit: ", "Daftar unit usulan awal"),
                    ("• Snapshot Jabatan: ", "Daftar formasi jabatan awal"),
                    ("• Pengiriman: ", "Diajukan ke Gate 1 Admin"),
                    ("• Status: ", "SUBMITTED_TO_ADMIN"),
                    ("• Immutabilitas: ", "Data dikunci permanen saat submit")
                ], item_size=16, line_spacing=36)

    c.draw_arrow(515, 310, 565, 310, color=C_RED, width=3, label="returnRevision()", label_bg=C_RED_LIGHT, label_color=C_RED)

    c.draw_card(565, 100, 470, 420, bg=C_RED_LIGHT, border=C_RED, border_w=2.5, radius=10,
                header_bg=C_RED, header_text="Catatan Review & Koreksi Berkas", header_size=18,
                items=[
                    ("• Entitas Catatan: ", "verifier_review_notes"),
                    ("• Asal Catatan: ", "Admin / Verifikator Kelembagaan"),
                    ("• Rincian Koreksi: ", "Koreksi tupoksi, rasio formasi"),
                    ("• Status Berkas: ", "REVISION_REQUIRED"),
                    ("• Akses Operator: ", "Formulir koreksi terbuka"),
                    ("• Status v1: ", "Tersimpan arsip historis permanen")
                ], item_size=16, line_spacing=36)

    c.draw_arrow(1040, 310, 1090, 310, color=C_EMERALD, width=3, label="resubmit() [v2]", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    c.draw_card(1090, 100, 470, 420, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=2.5, radius=10,
                header_bg=C_EMERALD, header_text="Versi Usulan 2 (v2) - Hasil Revisi", header_size=18,
                items=[
                    ("• Entitas Baru: ", "submission_versions (v2)"),
                    ("• Snapshot Baru: ", "Unit & formasi yang diperbaiki"),
                    ("• Otomasi Diff: ", "Mesin membandingkan v2 vs Master"),
                    ("• Status Usulan: ", "RESUBMITTED -> Gate 2"),
                    ("• Pengesahan: ", "Versi v2 siap disahkan SK"),
                    ("• Penomoran SK: ", "SK diterbitkan mengacu pada v2")
                ], item_size=16, line_spacing=36)

    c.draw_card(40, 540, 1520, 340, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                header_bg=C_NAVY, header_text="LOGIKA KOMPARASI DATA PERUBAHAN (DIFF ENGINE & VIEWER)", header_size=19,
                items=[
                    ("• Penambahan Unit (CREATE / HIJAU): ", "Menyoroti unit kerja baru yang belum ada di master data aktif instansi."),
                    ("• Perubahan Formasi (UPDATE / KUNING): ", "Menampilkan pergeseran nomenklatur, level eselon, atau penambahan kuota formasi ASN."),
                    ("• Penghapusan Unit (DELETE / MERAH): ", "Menandai unit struktural yang diusulkan untuk dilebur atau dihapus dari susunan organisasi."),
                    ("• Komparasi Granular per Baris: ", "Verifikator dapat membandingkan data usulan baru berdampingan (Side-by-Side) dengan struktur organisasi aktif."),
                    ("• Efisiensi Telaah SK: ", "Mempercepat proses verifikasi karena Verifikator fokus pada baris perubahan tanpa perlu membaca ulang seluruh SK.")
                ], item_size=15.5, line_spacing=34)

    c.save("versioning_diff_flow.png")

# -------------------------------------------------------------
# 6. SITEMAP & NAVIGATION
# -------------------------------------------------------------
def render_sitemap():
    c = UltraCanvas(1600, 900)
    c.draw_top_title("SITEMAP DAN STRUKTUR NAVIGASI APLIKASI SIGMA-K", 
                     "Pohon Rute Antarmuka 16 Halaman Terintegrasi Berdasarkan Next.js App Router")

    c.draw_card(600, 100, 400, 55, bg=C_NAVY, border=C_NAVY, border_w=2, radius=8,
                header_text="ROOT ENTRY: /login (Layar Masuk)", header_size=18, header_color=C_WHITE)

    c.draw_card(550, 180, 500, 60, bg=C_BLUE, border=C_BLUE, border_w=2, radius=8,
                header_text="APP SHELL: TopBar & Sidebar (Role Context)", header_size=18, header_color=C_WHITE)

    c.draw_arrow(800, 155, 800, 180, color=C_NAVY, width=3)

    routes = [
        ("1. DASHBOARD", 40, 260, 285, 330, C_BLUE_LIGHT, C_BLUE, [
            ("• /: ", "Overview"),
            ("• /analytics: ", "Intelijensi"),
            ("• Postur: ", "Distribusi Eselon"),
            ("• Ekspor: ", "CSV/JSON Center")
        ]),
        ("2. MASTER DATA", 350, 260, 285, 330, C_EMERALD_LIGHT, C_EMERALD, [
            ("• /institutions: ", "Katalog"),
            ("• .../[id]: ", "Profil Instansi"),
            ("• /structure: ", "React Flow"),
            ("• /cabinets: ", "Kabinet"),
            ("• /tupoksi: ", "Tupoksi Unit")
        ]),
        ("3. PENGUSULAN", 660, 260, 285, 330, C_AMBER_LIGHT, C_AMBER, [
            ("• /submissions: ", "Daftar"),
            ("• .../new: ", "Form Baru"),
            ("• .../[id]: ", "Rincian & Diff"),
            ("• .../revision: ", "Perbaikan v2")
        ]),
        ("4. VERIFIKASI", 970, 260, 285, 330, C_PURPLE_LIGHT, C_PURPLE, [
            ("• /verifications: ", "Antrean"),
            ("• Gate 1: ", "Screening Admin"),
            ("• Gate 2: ", "Workspace Verifier"),
            ("• .../[id]: ", "Review & SK")
        ]),
        ("5. AUDIT & NOTIF", 1280, 260, 280, 330, C_SLATE_LIGHT, C_SLATE_MID, [
            ("• /audit-logs: ", "Log Forensik"),
            ("• Diff Modal: ", "Inspeksi JSON"),
            ("• /notifications: ", "Notifikasi"),
            ("• Profil User: ", "Sesi & Hak Akses")
        ])
    ]

    for title, x, y, w, h, bg, border, items in routes:
        c.draw_card(x, y, w, h, bg=bg, border=border, border_w=2.5, radius=10,
                    header_bg=border, header_text=title, header_size=17,
                    items=items, item_size=15.5, line_spacing=32)
        c.draw_arrow(800, 240, x + w/2, y, color=C_SLATE_MID, width=2)

    c.draw_card(40, 610, 1520, 270, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                header_bg=C_NAVY, header_text="INFORMASI IMPLEMENTASI DAN VALIDASI ROUTING", header_size=19,
                items=[
                    ("• Kerangka Kerja Modern: ", "Seluruh rute dikembangkan menggunakan Next.js 14 App Router dengan dukungan Server-Side Rendering & Client Components."),
                    ("• Otorisasi Antarmuka: ", "Visibilitas menu dan tombol aksi dikendalikan secara dinamis oleh RoleContext berdasarkan klaim JWT sesi aktif."),
                    ("• Kompilasi 100% Bersih: ", "Sebanyak 16 rute statis dan dinamis telah terkompilasi sukses tanpa error pada tahap build produksi.")
                ], item_size=15.5, line_spacing=34)

    c.save("sitemap_diagram.png")

# -------------------------------------------------------------
# 7. GATE 1 & GATE 2 FLOWCHARTS
# -------------------------------------------------------------
def render_gate_flowcharts():
    # Gate 1
    c1 = UltraCanvas(1600, 900)
    c1.draw_top_title("ALUR KERJA PENAPISAN ADMINISTRATIF (GATE 1 ADMIN SCREENING)",
                      "Verifikasi Kelengkapan Dokumen, Dasar Hukum, dan Alokasi Penugasan Verifikator")

    c1.draw_card(550, 100, 500, 80, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2.5, radius=8,
                 header_bg=C_BLUE, header_text="Pengajuan Usulan Masuk (SUBMITTED_TO_ADMIN)", header_size=17,
                 items=["Berkas usulan diterima sistem dan masuk antrean Admin KemenPANRB."], item_size=15)

    c1.draw_arrow(800, 180, 800, 240, color=C_NAVY, width=3)

    c1.draw_card(380, 240, 840, 125, bg=C_AMBER_LIGHT, border=C_AMBER, border_w=2.5, radius=10,
                 header_bg=C_AMBER, header_text="Pemeriksaan Kelengkapan Administratif & Dasar Hukum", header_size=18,
                 items=[
                     "• Validasi kelengkapan surat pengantar Menteri/Kepala Lembaga",
                     "• Pemeriksaan lampiran naskah akademik & draf regulasi pembentukan"
                 ], item_size=15.5, line_spacing=28)

    c1.draw_arrow(520, 365, 290, 440, color=C_RED, width=3, label="Berkas Tidak Lengkap / Tidak Sesuai", label_bg=C_RED_LIGHT, label_color=C_RED)
    c1.draw_arrow(1080, 365, 1310, 440, color=C_EMERALD, width=3, label="Berkas Lengkap & Memenuhi Syarat", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    c1.draw_card(40, 440, 560, 220, bg=C_RED_LIGHT, border=C_RED, border_w=2.5, radius=10,
                 header_bg=C_RED, header_text="PENGEMBALIAN REVISI (returnRevision)", header_size=17,
                 items=[
                     ("• Aksi: ", "Admin mengeksekusi returnRevision()"),
                     ("• Status: ", "Transisi status ke REVISION_REQUIRED"),
                     ("• Catatan: ", "Admin menginputkan alasan kekurangan berkas"),
                     ("• Dampak: ", "Operator menerima notifikasi & form perbaikan")
                 ], item_size=15.5, line_spacing=28)

    c1.draw_card(1000, 440, 560, 220, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=2.5, radius=10,
                 header_bg=C_EMERALD, header_text="PENERIMAAN & PENUGASAN (assignVerifier)", header_size=17,
                 items=[
                     ("• Aksi: ", "Admin mengeksekusi accept() & assignVerifier()"),
                     ("• Status: ", "Transisi status ke ASSIGNED_TO_VERIFIER"),
                     ("• Alokasi: ", "Menugaskan Pejabat Verifikator yang kompeten"),
                     ("• Dampak: ", "Berkas masuk ke Ruang Kerja Verifikator")
                 ], item_size=15.5, line_spacing=28)

    c1.draw_card(40, 680, 1520, 200, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                 header_bg=C_NAVY, header_text="BATASAN WEWENANG & PEMISAHAN TUGAS (SEPARATION OF DUTIES)", header_size=19,
                 items=[
                     ("• Larangan Persetujuan Substantif: ", "Admin KemenPANRB secara tegas DILARANG melakukan persetujuan substantif maupun pengesahan SK."),
                     ("• Batasan Peran: ", "Wewenang Admin dibatasi strictly pada verifikasi formalitas dokumen dan manajemen alokasi penugasan kerja Verifikator.")
                 ], item_size=15.5, line_spacing=32)

    c1.save("gate1_admin_flowchart.png")

    # Gate 2
    c2 = UltraCanvas(1600, 900)
    c2.draw_top_title("ALUR KERJA TELAAH SUBSTANTIF & PENGESAHAN SK (GATE 2 VERIFIER)",
                      "Analisis Beban Kerja, Evaluasi Formasi Jabatan ASN, dan Pengesahan Final Surat Keputusan")

    c2.draw_card(550, 100, 500, 80, bg=C_AMBER_LIGHT, border=C_AMBER, border_w=2.5, radius=8,
                 header_bg=C_AMBER, header_text="Menerima Penugasan (ASSIGNED_TO_VERIFIER)", header_size=17,
                 items=["Verifikator membuka berkas usulan di Ruang Kerja Telaah Kelembagaan."], item_size=15)

    c2.draw_arrow(800, 180, 800, 240, color=C_NAVY, width=3)

    c2.draw_card(380, 240, 840, 125, bg=C_PURPLE_LIGHT, border=C_PURPLE, border_w=2.5, radius=10,
                 header_bg=C_PURPLE, header_text="Telaah Substantif Organisasi & Beban Kerja", header_size=18,
                 items=[
                     "• Evaluasi kesesuaian rentang kendali (span of control) & eselonisasi",
                     "• Analisis kuota formasi jabatan ASN & keselarasan tugas pokok fungsi (tupoksi)"
                 ], item_size=15.5, line_spacing=28)

    c2.draw_arrow(520, 365, 290, 440, color=C_RED, width=3, label="Ditemukan Catatan Substantif", label_bg=C_RED_LIGHT, label_color=C_RED)
    c2.draw_arrow(1080, 365, 1310, 440, color=C_EMERALD, width=3, label="Telaah Substantif Disetujui Penuh", label_bg=C_EMERALD_LIGHT, label_color=C_EMERALD)

    c2.draw_card(40, 440, 560, 220, bg=C_RED_LIGHT, border=C_RED, border_w=2.5, radius=10,
                 header_bg=C_RED, header_text="REVISI SUBSTANTIF (returnRevision)", header_size=17,
                 items=[
                     ("• Aksi: ", "Verifikator menginput catatan teknis per unit"),
                     ("• Status: ", "Transisi ke REVISION_REQUIRED_BY_VERIFIER"),
                     ("• Pelacakan: ", "Tersimpan pada tabel verifier_review_notes"),
                     ("• Resubmit: ", "Operator memperbaiki usulan dan mengajukan v2")
                 ], item_size=15.5, line_spacing=28)

    c2.draw_card(1000, 440, 560, 220, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=2.5, radius=10,
                 header_bg=C_EMERALD, header_text="PENGESAHAN SK & PROMOSI MASTER DATA", header_size=17,
                 items=[
                     ("• Wewenang Final: ", "Verifikator mengeksekusi finalApprove()"),
                     ("• Penerbitan SK: ", "Status transisi APPROVED & terbit Nomor SK resmi"),
                     ("• Promosi Otomatis: ", "Eksekusi promote() memigrasikan data ke master"),
                     ("• Status Akhir: ", "Usulan berstatus PROMOTED & data live aktif")
                 ], item_size=15.5, line_spacing=28)

    c2.draw_card(40, 680, 1520, 200, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                 header_bg=C_NAVY, header_text="FINAL APPROVAL AUTHORITY & OTOMASI PROMOSI DATA", header_size=19,
                 items=[
                     ("• Otoritas Pengesahan Akhir: ", "Verifikator memegang hak wewenang tunggal untuk menandatangani Surat Keputusan persetujuan organisasi."),
                     ("• Tanpa Intervensi Manual: ", "Setelah SK disahkan, sistem secara otomatis mengeksekusi mutasi unit dan jabatan ke master data aktif.")
                 ], item_size=15.5, line_spacing=32)

    c2.save("gate2_verifier_flowchart.png")

# -------------------------------------------------------------
# 8. MASTER DATA FLOW & ORG HIERARCHY TREE
# -------------------------------------------------------------
def render_master_data_hierarchy():
    # Master Data Flow
    c1 = UltraCanvas(1600, 900)
    c1.draw_top_title("ALUR KETERHUBUNGAN MASTER DATA KELEMBAGAAN",
                      "Hubungan Relasional Antara Instansi, Komposisi Kabinet, Pohon Unit Kerja, Formasi Jabatan, dan Tupoksi")

    c1.draw_card(40, 100, 470, 430, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2.5, radius=10,
                 header_bg=C_BLUE, header_text="1. Instansi & Kabinet", header_size=18,
                 items=[
                     ("• Master Instansi: ", "Kementerian, LPNK, Pemda"),
                     ("• Klasifikasi Bentuk: ", "KEMENKO, KEMENTERIAN, LPNK"),
                     ("• Komposisi Kabinet: ", "Kabinet Merah Putih"),
                     ("• Koordinasi Kemenko: ", "Instansi di bawah Menko terkait")
                 ], item_size=16, line_spacing=36)

    c1.draw_arrow(515, 315, 565, 315, color=C_BLUE, width=3)

    c1.draw_card(565, 100, 470, 430, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=2.5, radius=10,
                 header_bg=C_EMERALD, header_text="2. Unit Kerja Struktural", header_size=18,
                 items=[
                     ("• Model Data: ", "Adjacency List (parent_id)"),
                     ("• Level Kedudukan: ", "Level 1 s.d. Level 4"),
                     ("• Validasi Anti-Siklus: ", "DFS mencegah referensi melingkar"),
                     ("• Status Unit: ", "Unit berstatus AKTIF / NON-AKTIF")
                 ], item_size=16, line_spacing=36)

    c1.draw_arrow(1040, 315, 1090, 315, color=C_EMERALD, width=3)

    c1.draw_card(1090, 100, 470, 430, bg=C_AMBER_LIGHT, border=C_AMBER, border_w=2.5, radius=10,
                 header_bg=C_AMBER, header_text="3. Formasi Jabatan & Tupoksi", header_size=18,
                 items=[
                     ("• Nomenklatur Jabatan: ", "Nama jabatan struktural"),
                     ("• Jenjang Eselon: ", "Eselon I.a s.d. IV.a"),
                     ("• Kuota Formasi ASN: ", "Jumlah alokasi pegawai aparatur"),
                     ("• Tupoksi Unit: ", "Dasar hukum tugas pokok dan fungsi")
                 ], item_size=16, line_spacing=36)

    c1.draw_card(40, 550, 1520, 330, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                header_bg=C_NAVY, header_text="LOGIKA PENYAJIAN GRAF INTERAKTIF PADA FRONTEND (REACT FLOW CANVAS)", header_size=19,
                items=[
                    ("• Algoritma Flattening: ", "Frontend memanggil GET /api/v1/organizations/tree dan melakukan dekonstruksi payload rekursif menjadi nodes[] dan edges[]."),
                    ("• Tata Letak Otomatis: ", "Posisi X dan Y setiap node dihitung otomatis berdasarkan kedalaman level eselon dan indeks urutan pengelompokan."),
                    ("• Integrasi Drawer Rincian: ", "Klik pada node membuka drawer samping yang memuat rincian jabatan struktural, formasi ASN, dan tupoksi aktif."),
                    ("• Fitur Kanvas Lengkap: ", "Mendukung fitur Zoom In/Out, Panning, MiniMap navigasi, dan pencarian cepat unit kerja.")
                ], item_size=15.5, line_spacing=34)

    c1.save("master_data_flow.png")

    # Org Hierarchy Tree
    c2 = UltraCanvas(1600, 900)
    c2.draw_top_title("BAGAN STRUKTUR POHON ORGANISASI BERJENJANG (ESELON I - IV)",
                      "Model Representasi Relasi Parent-Child Struktur Kementerian/Lembaga Pemerintah")

    # Level 1
    c2.draw_card(600, 100, 400, 85, bg=C_NAVY, border=C_NAVY, border_w=2.5, radius=8,
                 header_text="Menteri / Kepala Lembaga", header_size=18, header_color=C_WHITE,
                 items=["Kedudukan: Level 1 (Pimpinan)  •  Status: AKTIF"], item_size=15, item_color=C_GOLD)

    # Level 2 (2 Branches)
    c2.draw_card(200, 230, 520, 115, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2.5, radius=8,
                 header_bg=C_BLUE, header_text="Sekretariat Kementerian (Eselon I.a)", header_size=17,
                 items=["• Kedudukan: Level 2 (Sekretariat Lembaga)", "• Fungsi: Koordinasi pelaksanaan tugas & administrasi"],
                 item_size=14.5, line_spacing=24)

    c2.draw_card(880, 230, 520, 115, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2.5, radius=8,
                 header_bg=C_BLUE, header_text="Deputi Bidang Kelembagaan (Eselon I.a)", header_size=17,
                 items=["• Kedudukan: Level 2 (Pelaksana Utama)", "• Fungsi: Perumusan kebijakan & penataan kelembagaan"],
                 item_size=14.5, line_spacing=24)

    c2.draw_arrow(700, 185, 460, 230, color=C_NAVY, width=2)
    c2.draw_arrow(900, 185, 1140, 230, color=C_NAVY, width=2)

    # Level 3 (4 Sub-units)
    c2.draw_card(40, 395, 360, 155, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2, radius=8,
                 header_text="Biro SDM & Organisasi", header_size=17, header_color=C_BLUE,
                 items=["• Level 3 (Eselon II.a)", "• Parent: Sekretariat", "• Formasi: 24 Pegawai ASN"],
                 item_size=14.5, line_spacing=26)

    c2.draw_card(425, 395, 360, 155, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2, radius=8,
                 header_text="Biro Perencanaan & Kerjasama", header_size=17, header_color=C_BLUE,
                 items=["• Level 3 (Eselon II.a)", "• Parent: Sekretariat", "• Formasi: 18 Pegawai ASN"],
                 item_size=14.5, line_spacing=26)

    c2.draw_card(810, 395, 360, 155, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2, radius=8,
                 header_text="Asdep Tata Laksana", header_size=17, header_color=C_BLUE,
                 items=["• Level 3 (Eselon II.a)", "• Parent: Deputi Kelembagaan", "• Formasi: 16 Pegawai ASN"],
                 item_size=14.5, line_spacing=26)

    c2.draw_card(1195, 395, 365, 155, bg=C_WHITE, border=C_BLUE_BORDER, border_w=2, radius=8,
                 header_text="Asdep Struktur K/L", header_size=17, header_color=C_BLUE,
                 items=["• Level 3 (Eselon II.a)", "• Parent: Deputi Kelembagaan", "• Formasi: 20 Pegawai ASN"],
                 item_size=14.5, line_spacing=26)

    c2.draw_arrow(350, 345, 220, 395, color=C_BLUE, width=2)
    c2.draw_arrow(570, 345, 605, 395, color=C_BLUE, width=2)
    c2.draw_arrow(1030, 345, 990, 395, color=C_BLUE, width=2)
    c2.draw_arrow(1250, 345, 1375, 395, color=C_BLUE, width=2)

    c2.draw_card(40, 580, 1520, 300, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                 header_bg=C_NAVY, header_text="MODEL DATA ADJACENCY LIST & DFS ANTI-CYCLE VALIDATION", header_size=19,
                 items=[
                     ("• Penyimpanan Efisien: ", "Setiap unit kerja hanya menyimpan pointer ID induk (parent_id) yang merujuk pada tabel yang sama."),
                     ("• Rekursi Aman: ", "Algoritma DFS memvalidasi pohon saat usulan diajukan untuk memastikan tidak ada hubungan melingkar (circular loop).")
                 ], item_size=15.5, line_spacing=34)

    c2.save("org_hierarchy_tree.png")

# -------------------------------------------------------------
# 9. AUTH & SECURITY FLOW
# -------------------------------------------------------------
def render_auth_security():
    c = UltraCanvas(1600, 900)
    c.draw_top_title("ALUR OTENTIKASI JWT & VALIDASI SESI PENGGUNA",
                     "Siklus Autentikasi Kriptografis, Token Provider, dan Otorisasi Zero-Trust Multi-Tenant")

    c.draw_card(40, 100, 470, 440, bg=C_BLUE_LIGHT, border=C_BLUE, border_w=2.5, radius=10,
                header_bg=C_BLUE, header_text="1. Login (POST /auth/login)", header_size=18,
                items=[
                    ("• Input: ", "NIP / Username & Password"),
                    ("• Validasi: ", "Pencocokan kredensial & hash"),
                    ("• Penerbitan: ", "JWT ditandatangani HMAC-SHA256"),
                    ("• Klaim Token: ", "uid, nip, role, home_inst_id"),
                    ("• Keamanan: ", "Rate limiting brute-force"),
                    ("• Balikan: ", "Token JWT & data profil user")
                ], item_size=16, line_spacing=36)

    c.draw_arrow(515, 320, 565, 320, color=C_BLUE, width=3)

    c.draw_card(565, 100, 470, 440, bg=C_EMERALD_LIGHT, border=C_EMERALD, border_w=2.5, radius=10,
                header_bg=C_EMERALD, header_text="2. Token Provider & Injeksi", header_size=18,
                items=[
                    ("• Penyimpanan: ", "Browser Storage via TokenProvider"),
                    ("• Injeksi Header: ", "Authorization: Bearer <token>"),
                    ("• Otomasi Klien: ", "HttpClient menyisipkan otomatis"),
                    ("• Penanganan Sesi: ", "Pembersihan token saat logout"),
                    ("• Error Status: ", "Penanganan 401 terpusat")
                ], item_size=16, line_spacing=36)

    c.draw_arrow(1040, 320, 1090, 320, color=C_EMERALD, width=3)

    c.draw_card(1090, 100, 470, 440, bg=C_PURPLE_LIGHT, border=C_PURPLE, border_w=2.5, radius=10,
                header_bg=C_PURPLE, header_text="3. Profil Sesi (GET /auth/me)", header_size=18,
                items=[
                    ("• AuthFilter: ", "Backend validasi integritas JWT"),
                    ("• ScopeResolver: ", "Ambil daftar wewenang K/L sah"),
                    ("• Inisialisasi: ", "RoleContext memuat permissions"),
                    ("• Isolasi: ", "Role dikunci; switcher mati"),
                    ("• Proteksi IDOR: ", "Blokir 403 jika scope dilanggar")
                ], item_size=16, line_spacing=36)

    c.draw_card(40, 560, 1520, 320, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2.5, radius=10,
                header_bg=C_NAVY, header_text="MEKANISME ISOLASI PERSONA & ZERO-TRUST AUTHORIZATION", header_size=19,
                items=[
                    ("• Mode API Produksi (NEXT_PUBLIC_DATA_MODE=api): ", "Persona switcher pada antarmuka dikunci menjadi Read-Only Badge."),
                    ("• Larangan Manipulasi Klien: ", "Setiap manipulasi role pada browser ditolak mutlak oleh backend karena wewenang diverifikasi dari token JWT."),
                    ("• Proteksi BOLA / IDOR: ", "Pemeriksaan kepemilikan instansi diterapkan pada setiap controller melalui ScopeResolver."),
                    ("• Penolakan Tegas (Anti Mock-Fallback): ", "Jika terjadi kesalahan otorisasi (401/403), UI secara tegas menampilkan layar akses ditolak tanpa mock data.")
                ], item_size=15.5, line_spacing=34)

    c.save("auth_jwt_flow.png")

# -------------------------------------------------------------
# 10. UI PROTOTYPES
# -------------------------------------------------------------
def render_ui_prototypes():
    screens = [
        ("prototype_login.png", "SCR-18: LAYAR MASUK SISTEM (LOGIN)", [
            ("• Header Institusi: ", "Lambang Garuda Pancasila & Identitas Resmi KemenPANRB / SIGMA-K."),
            ("• Card Autentikasi: ", "Form input NIP / Username dan Password dengan validasi format ketat."),
            ("• Dual-Mode Selector: ", "Saklar mode API Live (CodeIgniter 4 eskld_db) vs Mock Simulation."),
            ("• Akun Presets Cepat: ", "Pilihan cepat akun uji (USER Operator, ADMIN Penapis, VERIFIER Verifikator)."),
            ("• Keamanan Sesi: ", "Inisialisasi token JWT Bearer langsung ke Browser Storage saat autentikasi sukses.")
        ]),
        ("prototype_dashboard.png", "SCR-01: DASHBOARD EKSEKUTIF KELEMBAGAAN", [
            ("• 4 Kartu KPI Utama: ", "Total Usulan (Live), Dalam Proses Telaah, Disetujui, dan Promosi Master Data."),
            ("• Sorotan Kabinet: ", "Widget aktif Kabinet Merah Putih & komposisi kementerian koordinator."),
            ("• Antrean Usulan: ", "Tabel status berkas terkini dengan indikator badge warna dinamis."),
            ("• Pengesahan Terkini: ", "Rekam jejak Surat Keputusan (SK) resmi yang baru disahkan oleh Verifikator."),
            ("• Mode Indicator: ", "Badge 'API Live Verified' menandakan integrasi backend aktif penuh.")
        ]),
        ("prototype_institutions.png", "SCR-02: KATALOG MASTER INSTANSI PEMERINTAH", [
            ("• Filter Tabs: ", "Semua K/L/D, Kementerian Koordinator, Kementerian, LPNK, Lembaga Non-Struktural, Pemda."),
            ("• Pencarian Cepat: ", "Pencarian instan berdasarkan Kode Instansi (KL-xxx) atau Nama Lengkap Instansi."),
            ("• Tabel Master: ", "Menampilkan kode instansi, nama resmi, jumlah unit kerja, posisi, dan status aktif."),
            ("• Aksi Terintegrasi: ", "Tombol navigasi langsung ke 'Bagan Struktur Organisasi' dan 'Rincian Profil Instansi'.")
        ]),
        ("prototype_org_structure.png", "SCR-04: BAGAN STRUKTUR ORGANISASI INTERAKTIF", [
            ("• React Flow Canvas: ", "Kanvas graf pohon interaktif dengan kontrol Zoom, Panning, dan MiniMap navigasi."),
            ("• Custom OrgNode: ", "Kartu node menampilkan kode unit, nama pimpinan, level eselon, dan status aktif."),
            ("• Garis Konektor Hierarki: ", "Relasi parent-child otomatis tersusun rapi dari Level 1 (Pimpinan) ke Level 4."),
            ("• Drawer Rincian Samping: ", "Klik pada node membuka drawer samping yang memuat daftar formasi jabatan & tupoksi.")
        ]),
        ("prototype_submission_detail.png", "SCR-09: RINCIAN USULAN & DIFF VIEWER", [
            ("• Workflow Stepper: ", "Visualisasi progress tahapan (Draft -> Penapisan -> Telaah -> Pengesahan SK)."),
            ("• Metadata Usulan: ", "Nomor Tiket (TKT-2026-xxxx), Instansi Pengusul, Tanggal Pengajuan, dan Author NIP."),
            ("• Diff Viewer Engine: ", "Komparasi Before vs After (Unit Baru [HIJAU], Unit Dihapus [MERAH], Update [KUNING])."),
            ("• Tab Riwayat Koreksi: ", "Rekam jejak koreksi catatan telaah dari Admin Gate 1 dan Verifikator Gate 2.")
        ]),
        ("prototype_verifier_workspace.png", "SCR-12: RUANG KERJA TELAAH VERIFIKATOR", [
            ("• Panel Telaah Substantif: ", "Analisis kesesuaian rentang kendali, beban kerja, dan kuota formasi jabatan ASN."),
            ("• Form Catatan Teknis: ", "Input catatan telaah substantif per unit kerja pada verifier_review_notes."),
            ("• Aksi Penolakan / Revisi: ", "Tombol 'Kembalikan untuk Revisi' jika ditemukan ketidaksesuaian substantif."),
            ("• Pengesahan & Promosi: ", "Tombol 'Pengesahan SK Resmi' yang otomatis memicu migrasi data ke master aktif.")
        ]),
        ("prototype_analytics_reporting.png", "SCR-15: INTELIJENSI DATA & POSTUR ASN", [
            ("• 4 Proposed KPIs: ", "Indikator efisiensi struktur organisasi & rasio rentang kendali aparatur negara."),
            ("• Piramida Eselonisasi: ", "Visualisasi distribusi jabatan struktural Eselon I, II, III, dan IV."),
            ("• SLA Kecepatan Layanan: ", "Durasi rata-rata penyelesaian telaah kelembagaan (Target: < 3 hari kerja)."),
            ("• Export Center: ", "Tombol unduh laporan resmi dataset (.CSV / .JSON) langsung dari backend CI4.")
        ]),
        ("prototype_audit_logs.png", "SCR-16: LOG FORENSIK AUDIT TRAIL", [
            ("• Tabel Log Forensik: ", "Timestamp berpresisi tinggi, Nama Aktor, Role, Aksi (CREATE/UPDATE/APPROVE)."),
            ("• Konteks Jaringan: ", "Rekam jejak alamat IP klien dan identitas modul/resource yang dimanipulasi."),
            ("• Modal Inspeksi Payload: ", "Inspeksi mendalam perbandingan snapshot JSON (old_payload vs new_payload)."),
            ("• Integritas Append-Only: ", "Catatan tersimpan permanen dan tidak dapat dimanipulasi oleh siapapun.")
        ])
    ]

    for fname, title, bullet_points in screens:
        c = UltraCanvas(1600, 900)
        c.draw_top_title(f"PROTOTYPE / WIREFRAME: {title}",
                         "Spesifikasi Desain Antarmuka Terverifikasi Berdasarkan Implementasi Frontend Next.js")

        c.draw_card(40, 95, 1520, 780, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=3, radius=12,
                    header_bg=C_NAVY, header_text=f"APLIKASI SIGMA-K - KEMENPANRB  |  {title.split(':')[0]}", header_size=20)

        # TopBar inside Card
        c.draw_card(65, 155, 1470, 60, bg=C_WHITE, border=C_BORDER_DEFAULT, border_w=2, radius=6,
                    header_text="", header_size=16)
        c.draw.text((85, 185), "E-SKLD / SIGMA-K  •  Sistem Pengelolaan Data Kelembagaan", fill=C_BLUE, font=get_font(bold=True, size=18), anchor="lm")
        c.draw.text((1515, 185), "[Sesi Aktif: VERIFIER KELEMBAGAAN - NIP: 198001012005011001]", fill=C_EMERALD, font=get_font(bold=True, size=16), anchor="rm")

        # Inner Content Canvas Area
        c.draw_card(65, 230, 1470, 620, bg=C_WHITE, border=C_BORDER_DEFAULT, border_w=2, radius=8,
                    header_bg=C_BLUE_LIGHT, header_text="RINGKASAN ELEMEN & SPESIFIKASI LAYAR", header_size=18, header_color=C_BLUE,
                    items=bullet_points, item_size=17, line_spacing=46)

        c.save(fname)

def main():
    print("=== STARTING ULTRA 16:9 CRISP VECTOR DIAGRAM RENDERING ===")
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
    print("=== ALL DIAGRAMS RE-RENDERED WITH PERFECT 16:9 PROPORTIONS ===")

if __name__ == '__main__':
    main()
