"""
Visual SQL Database Schema & Relational ERD Diagram Generator for eskld_db (21 Tables).
Draws individual database table schema cards with PK, FK, column types, and crisp directional connector arrows.
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

def get_font(bold=False, size=14):
    path = F_SEGOE_BOLD if bold else F_SEGOE_REG
    if not os.path.exists(path):
        path = F_CALIBRI_BOLD if bold else F_CALIBRI_REG
    return ImageFont.truetype(path, size)

# Colors
C_NAVY = "#0B2A4A"
C_BLUE = "#1E40AF"
C_BLUE_LIGHT = "#EFF6FF"
C_GOLD = "#D4AF37"
C_AMBER = "#B45309"
C_AMBER_LIGHT = "#FFFBEB"
C_EMERALD = "#047857"
C_EMERALD_LIGHT = "#ECFDF5"
C_PURPLE = "#6B21A8"
C_PURPLE_LIGHT = "#FAF5FF"
C_RED = "#DC2626"
C_SLATE_DARK = "#0F172A"
C_SLATE_MID = "#475569"
C_SLATE_LIGHT = "#F8FAFC"
C_BORDER_DEFAULT = "#CBD5E1"
C_WHITE = "#FFFFFF"

class VisualSqlErdCanvas:
    def __init__(self, width=1800, height=1100, bg="#FFFFFF"):
        self.w = width
        self.h = height
        self.img = Image.new("RGBA", (width, height), bg)
        self.draw = ImageDraw.Draw(self.img)

    def draw_top_title(self, title, subtitle=None):
        f_title = get_font(bold=True, size=28)
        self.draw.text((self.w / 2, 32), title, fill=C_NAVY, font=f_title, anchor="mm")
        if subtitle:
            f_sub = get_font(bold=False, size=16)
            self.draw.text((self.w / 2, 62), subtitle, fill=C_SLATE_MID, font=f_sub, anchor="mm")

    def draw_card(self, x, y, w, h, bg=C_WHITE, border=C_BORDER_DEFAULT, border_w=2, radius=8,
                  header_bg=None, header_text=None, header_color=C_WHITE, header_size=16,
                  items=None, item_size=13.5, item_color=C_SLATE_DARK, line_spacing=26):
        self.draw.rounded_rectangle([x, y, x + w, y + h], radius=radius, fill=bg, outline=border, width=int(border_w))
        content_top = y + 12

        if header_bg and header_text:
            header_h = 36
            self.draw.rounded_rectangle([x, y, x + w, y + header_h], radius=radius, fill=header_bg)
            self.draw.rectangle([x, y + header_h - radius, x + w, y + header_h], fill=header_bg)
            self.draw.rounded_rectangle([x, y, x + w, y + h], radius=radius, outline=border, width=int(border_w))
            f_head = get_font(bold=True, size=header_size)
            self.draw.text((x + w / 2, y + header_h / 2), header_text, fill=header_color, font=f_head, anchor="mm")
            content_top = y + header_h + 12
        elif header_text:
            f_head = get_font(bold=True, size=header_size)
            self.draw.text((x + w / 2, y + 18), header_text, fill=header_color if header_color != C_WHITE else C_NAVY, font=f_head, anchor="mm")
            content_top = y + 38

        if items:
            f_item = get_font(bold=False, size=item_size)
            f_item_bold = get_font(bold=True, size=item_size)
            cur_y = content_top
            for it in items:
                if isinstance(it, tuple):
                    prefix, rest = it
                    self.draw.text((x + 12, cur_y), prefix, fill=C_NAVY, font=f_item_bold)
                    bbox = self.draw.textbbox((x + 12, cur_y), prefix, font=f_item_bold)
                    p_w = bbox[2] - bbox[0]
                    self.draw.text((x + 12 + p_w, cur_y), rest, fill=item_color, font=f_item)
                else:
                    self.draw.text((x + 12, cur_y), it, fill=item_color, font=f_item)
                cur_y += line_spacing

    def draw_table_card(self, x, y, w, table_name, columns, header_bg=C_NAVY, category_tag=None):
        row_h = 24
        header_h = 32
        h = header_h + len(columns) * row_h + 8

        self.draw.rounded_rectangle([x, y, x + w, y + h], radius=6, fill=C_WHITE, outline=C_BORDER_DEFAULT, width=2)
        self.draw.rounded_rectangle([x, y, x + w, y + header_h], radius=6, fill=header_bg)
        self.draw.rectangle([x, y + header_h - 6, x + w, y + header_h], fill=header_bg)
        self.draw.rounded_rectangle([x, y, x + w, y + h], radius=6, outline=header_bg, width=2)

        f_head = get_font(bold=True, size=15)
        self.draw.text((x + 10, y + header_h / 2), table_name, fill=C_WHITE, font=f_head, anchor="lm")

        if category_tag:
            f_tag = get_font(bold=True, size=11)
            t_bbox = self.draw.textbbox((0, 0), category_tag, font=f_tag)
            tw = t_bbox[2] - t_bbox[0] + 10
            th = t_bbox[3] - t_bbox[1] + 6
            tx = x + w - tw - 8
            ty = y + (header_h - th) / 2
            self.draw.rounded_rectangle([tx, ty, tx + tw, ty + th], radius=4, fill="#FFFFFF33")
            self.draw.text((tx + tw/2, ty + th/2), category_tag, fill=C_WHITE, font=f_tag, anchor="mm")

        cur_y = y + header_h + 4
        f_col = get_font(bold=False, size=13)
        f_col_bold = get_font(bold=True, size=13)
        f_badge = get_font(bold=True, size=10)

        for key_type, col_name, data_type in columns:
            if key_type == 'PK':
                self.draw.rounded_rectangle([x + 8, cur_y + 3, x + 30, cur_y + 19], radius=3, fill=C_GOLD)
                self.draw.text((x + 19, cur_y + 11), "PK", fill=C_NAVY, font=f_badge, anchor="mm")
            elif key_type == 'FK':
                self.draw.rounded_rectangle([x + 8, cur_y + 3, x + 30, cur_y + 19], radius=3, fill=C_BLUE_LIGHT, outline=C_BLUE, width=1)
                self.draw.text((x + 19, cur_y + 11), "FK", fill=C_BLUE, font=f_badge, anchor="mm")
            elif key_type == 'PFK':
                self.draw.rounded_rectangle([x + 8, cur_y + 3, x + 30, cur_y + 19], radius=3, fill=C_AMBER_LIGHT, outline=C_AMBER, width=1)
                self.draw.text((x + 19, cur_y + 11), "PFK", fill=C_AMBER, font=f_badge, anchor="mm")

            is_pk = 'PK' in key_type
            col_font = f_col_bold if is_pk else f_col
            col_color = C_NAVY if is_pk else C_SLATE_DARK
            self.draw.text((x + 36, cur_y + 11), col_name, fill=col_color, font=col_font, anchor="lm")
            self.draw.text((x + w - 10, cur_y + 11), data_type, fill=C_SLATE_MID, font=f_col, anchor="rm")
            cur_y += row_h

        return (x, y, w, h)

    def draw_connector(self, p1, p2, color=C_BLUE, width=2, style='ortho'):
        x1, y1 = p1
        x2, y2 = p2

        if style == 'ortho':
            mid_x = (x1 + x2) / 2
            points = [(x1, y1), (mid_x, y1), (mid_x, y2), (x2, y2)]
            for i in range(len(points) - 1):
                self.draw.line([points[i], points[i+1]], fill=color, width=width)
        else:
            self.draw.line([(x1, y1), (x2, y2)], fill=color, width=width)

        import math
        angle = math.atan2(0, x2 - (x1 + x2)/2) if style == 'ortho' else math.atan2(y2 - y1, x2 - x1)
        arrow_len = 8
        arrow_angle = math.pi / 5
        pa = (x2 - arrow_len * math.cos(angle - arrow_angle), y2 - arrow_len * math.sin(angle - arrow_angle))
        pb = (x2 - arrow_len * math.cos(angle + arrow_angle), y2 - arrow_len * math.sin(angle + arrow_angle))
        self.draw.polygon([(x2, y2), pa, pb], fill=color)

    def save(self, filename):
        out_path = os.path.join(ASSETS_DIR, filename)
        rgb_img = Image.new("RGB", self.img.size, (255, 255, 255))
        rgb_img.paste(self.img, mask=self.img.split()[3])
        rgb_img.save(out_path, "PNG", dpi=(300, 300))
        print(f"  [SAVED VISUAL SQL ERD] {filename}")

def render_visual_sql_erd():
    c = VisualSqlErdCanvas(1800, 1100)
    c.draw_top_title("SKEMA RELASIONAL BASIS DATA (VISUAL SQL ERD) - eskld_db (21 TABEL)",
                     "Bagan Relasi Kunci Antar Tabel: Autentikasi & Hak Akses, Master Data Kelembagaan, Pengusulan & Versi, Serta Verifikasi & Audit")

    # KOLOM 1: AKUN & HAK AKSES PENGGUNA (X = 50)
    c.draw_table_card(50, 90, 300, "roles", [
        ("PK", "id", "INT (PK)"),
        ("", "code", "VARCHAR(50)"),
        ("", "name", "VARCHAR(100)"),
        ("", "description", "TEXT")
    ], header_bg=C_BLUE, category_tag="Akses")

    c.draw_table_card(50, 240, 300, "users", [
        ("PK", "id", "INT (PK)"),
        ("FK", "role_id", "INT (FK)"),
        ("FK", "institution_id", "INT (FK)"),
        ("", "nip", "VARCHAR(18)"),
        ("", "email", "VARCHAR(100)"),
        ("", "password_hash", "VARCHAR(255)"),
        ("", "is_active", "BOOLEAN")
    ], header_bg=C_BLUE, category_tag="Akun")

    c.draw_table_card(50, 480, 300, "user_scopes", [
        ("PK", "id", "INT (PK)"),
        ("FK", "user_id", "INT (FK)"),
        ("FK", "institution_id", "INT (FK)")
    ], header_bg=C_BLUE, category_tag="Wilayah")

    c.draw_table_card(50, 620, 300, "access_grants", [
        ("PK", "id", "INT (PK)"),
        ("FK", "user_id", "INT (FK)"),
        ("FK", "institution_id", "INT (FK)"),
        ("", "grant_type", "VARCHAR(50)"),
        ("", "expires_at", "DATETIME")
    ], header_bg=C_BLUE, category_tag="Izin Khusus")

    c.draw_table_card(50, 810, 300, "audit_logs", [
        ("PK", "id", "INT (PK)"),
        ("FK", "actor_id", "INT (FK)"),
        ("FK", "institution_id", "INT (FK)"),
        ("", "action_event", "VARCHAR(100)"),
        ("", "resource_entity", "VARCHAR(100)"),
        ("", "old_payload", "JSON"),
        ("", "new_payload", "JSON"),
        ("", "ip_address", "VARCHAR(45)"),
        ("", "created_at", "DATETIME")
    ], header_bg=C_SLATE_DARK, category_tag="Audit Trail")

    # KOLOM 2: MASTER DATA KELEMBAGAAN & KABINET (X = 450)
    c.draw_table_card(450, 90, 320, "institution_types", [
        ("PK", "id", "INT (PK)"),
        ("", "code", "VARCHAR(50)"),
        ("", "name", "VARCHAR(100)")
    ], header_bg=C_EMERALD, category_tag="Master")

    c.draw_table_card(450, 220, 320, "institutions", [
        ("PK", "id", "INT (PK)"),
        ("FK", "type_id", "INT (FK)"),
        ("", "code", "VARCHAR(50)"),
        ("", "name", "VARCHAR(255)"),
        ("", "category", "VARCHAR(50)"),
        ("", "status", "VARCHAR(20)")
    ], header_bg=C_EMERALD, category_tag="Master K/L/D")

    c.draw_table_card(450, 430, 320, "cabinets", [
        ("PK", "id", "INT (PK)"),
        ("", "name", "VARCHAR(100)"),
        ("", "president_name", "VARCHAR(100)"),
        ("", "is_active", "BOOLEAN")
    ], header_bg=C_EMERALD, category_tag="Kabinet")

    c.draw_table_card(450, 580, 320, "cabinet_institutions", [
        ("PFK", "cabinet_id", "INT (FK)"),
        ("PFK", "institution_id", "INT (FK)")
    ], header_bg=C_EMERALD, category_tag="Relasi Kabinet")

    c.draw_table_card(450, 710, 320, "organizational_units", [
        ("PK", "id", "INT (PK)"),
        ("FK", "institution_id", "INT (FK)"),
        ("FK", "parent_id", "INT (FK / Self)"),
        ("", "unit_code", "VARCHAR(50)"),
        ("", "unit_name", "VARCHAR(255)"),
        ("", "unit_level", "TINYINT (1-4)"),
        ("", "status", "VARCHAR(20)")
    ], header_bg=C_EMERALD, category_tag="Pohon Struktur")

    c.draw_table_card(450, 950, 320, "positions", [
        ("PK", "id", "INT (PK)"),
        ("FK", "unit_id", "INT (FK)"),
        ("", "position_name", "VARCHAR(255)"),
        ("", "echelon", "VARCHAR(10)"),
        ("", "formation_count", "INT"),
        ("", "status", "VARCHAR(20)")
    ], header_bg=C_EMERALD, category_tag="Formasi ASN")

    # KOLOM 3: PENGUSULAN & SNAPSHOT VERSIONING (X = 900)
    c.draw_table_card(900, 90, 380, "submissions", [
        ("PK", "id", "INT (PK)"),
        ("FK", "institution_id", "INT (FK)"),
        ("FK", "author_id", "INT (FK)"),
        ("", "title", "VARCHAR(255)"),
        ("", "submission_year", "YEAR"),
        ("", "current_state", "VARCHAR(50)"),
        ("", "created_at", "DATETIME")
    ], header_bg=C_AMBER, category_tag="Usulan Baru")

    c.draw_table_card(900, 330, 380, "submission_versions", [
        ("PK", "id", "INT (PK)"),
        ("FK", "submission_id", "INT (FK)"),
        ("", "version_number", "INT (v1, v2)"),
        ("", "notes", "TEXT"),
        ("", "created_at", "DATETIME")
    ], header_bg=C_AMBER, category_tag="Snapshot Versi")

    c.draw_table_card(900, 520, 380, "submission_units", [
        ("PK", "id", "INT (PK)"),
        ("FK", "version_id", "INT (FK)"),
        ("FK", "parent_id", "INT (FK)"),
        ("", "unit_code", "VARCHAR(50)"),
        ("", "unit_name", "VARCHAR(255)"),
        ("", "action_type", "VARCHAR(20) [ADD/MOD/DEL]")
    ], header_bg=C_AMBER, category_tag="Usulan Unit")

    c.draw_table_card(900, 730, 380, "submission_positions", [
        ("PK", "id", "INT (PK)"),
        ("FK", "version_id", "INT (FK)"),
        ("FK", "unit_id", "INT (FK)"),
        ("", "position_name", "VARCHAR(255)"),
        ("", "formation_count", "INT"),
        ("", "action_type", "VARCHAR(20)")
    ], header_bg=C_AMBER, category_tag="Usulan Formasi")

    # KOLOM 4: VERIFIKASI, TELAAH & PENGESAHAN SK (X = 1380)
    c.draw_table_card(1380, 90, 370, "verifier_assignments", [
        ("PK", "id", "INT (PK)"),
        ("FK", "submission_id", "INT (FK)"),
        ("FK", "verifier_id", "INT (FK)"),
        ("FK", "assigned_by_admin_id", "INT (FK)"),
        ("", "status", "VARCHAR(50)"),
        ("", "assigned_at", "DATETIME")
    ], header_bg=C_PURPLE, category_tag="Penugasan")

    c.draw_table_card(1380, 330, 370, "verifier_review_notes", [
        ("PK", "id", "INT (PK)"),
        ("FK", "submission_id", "INT (FK)"),
        ("FK", "verifier_id", "INT (FK)"),
        ("", "section", "VARCHAR(100)"),
        ("", "note_text", "TEXT"),
        ("", "is_resolved", "BOOLEAN"),
        ("", "created_at", "DATETIME")
    ], header_bg=C_PURPLE, category_tag="Catatan Telaah")

    c.draw_table_card(1380, 560, 370, "approval_records", [
        ("PK", "id", "INT (PK)"),
        ("FK", "version_id", "INT (FK)"),
        ("FK", "approver_id", "INT (FK)"),
        ("", "approval_number", "VARCHAR(100) [No. SK]"),
        ("", "approval_notes", "TEXT"),
        ("", "approved_at", "DATETIME")
    ], header_bg=C_PURPLE, category_tag="Pengesahan SK")

    # Legend / Info Card di Kanan Bawah
    c.draw_card(1380, 780, 370, 270, bg=C_SLATE_LIGHT, border=C_NAVY, border_w=2, radius=8,
                header_bg=C_NAVY, header_text="PETUNJUK RELASI RELASIONAL (SQL ERD)", header_size=16,
                items=[
                    ("• PK (Primary Key): ", "Kunci utama pengenal unik baris."),
                    ("• FK (Foreign Key): ", "Penunjuk relasi kunci ke tabel induk."),
                    ("• Garis Panah Relasi: ", "Menghubungkan FK anak ke PK induk."),
                    ("• 1 to Many (1:N): ", "Satu instansi memiliki banyak unit kerja."),
                    ("• Snapshot Immutability: ", "Versi usulan mengunci data draf historis."),
                    ("• Promosi Data Otomatis: ", "SK disahkan -> Data pindah ke master aktif.")
                ], item_size=13.5, line_spacing=26)

    # RELATIONAL CONNECTOR ARROWS (MENGHUBUNGKAN FK KE PK)
    # 1. users.role_id -> roles.id
    c.draw_connector((50 + 300, 240 + 50), (50 + 300, 90 + 50), color=C_BLUE, width=2)

    # 2. users.institution_id -> institutions.id
    c.draw_connector((50 + 300, 240 + 74), (450, 220 + 40), color=C_BLUE, width=2)

    # 3. institutions.type_id -> institution_types.id
    c.draw_connector((450 + 320, 220 + 64), (450 + 320, 90 + 50), color=C_EMERALD, width=2)

    # 4. cabinet_institutions.cabinet_id -> cabinets.id
    c.draw_connector((450 + 320, 580 + 40), (450 + 320, 430 + 50), color=C_EMERALD, width=2)

    # 5. cabinet_institutions.institution_id -> institutions.id
    c.draw_connector((450, 580 + 64), (450, 220 + 90), color=C_EMERALD, width=2)

    # 6. organizational_units.institution_id -> institutions.id
    c.draw_connector((450, 710 + 64), (450, 220 + 114), color=C_EMERALD, width=2)

    # 7. positions.unit_id -> organizational_units.id
    c.draw_connector((450 + 320, 950 + 50), (450 + 320, 710 + 50), color=C_EMERALD, width=2)

    # 8. submissions.institution_id -> institutions.id
    c.draw_connector((900, 90 + 64), (450 + 320, 220 + 50), color=C_AMBER, width=2)

    # 9. submission_versions.submission_id -> submissions.id
    c.draw_connector((900 + 190, 330), (900 + 190, 90 + 200), color=C_AMBER, width=2)

    # 10. submission_units.version_id -> submission_versions.id
    c.draw_connector((900 + 190, 520), (900 + 190, 330 + 150), color=C_AMBER, width=2)

    # 11. submission_positions.version_id -> submission_versions.id
    c.draw_connector((900 + 380, 730 + 50), (900 + 380, 330 + 80), color=C_AMBER, width=2)

    # 12. verifier_assignments.submission_id -> submissions.id
    c.draw_connector((1380, 90 + 64), (900 + 380, 90 + 50), color=C_PURPLE, width=2)

    # 13. verifier_review_notes.submission_id -> submissions.id
    c.draw_connector((1380, 330 + 64), (900 + 380, 90 + 100), color=C_PURPLE, width=2)

    # 14. approval_records.version_id -> submission_versions.id
    c.draw_connector((1380, 560 + 64), (900 + 380, 330 + 50), color=C_PURPLE, width=2)

    # 15. audit_logs.actor_id -> users.id
    c.draw_connector((50 + 300, 810 + 50), (50 + 300, 240 + 120), color=C_SLATE_DARK, width=2)

    c.save("erd_diagram.png")

if __name__ == '__main__':
    render_visual_sql_erd()
