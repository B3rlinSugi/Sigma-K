"""
Script Generator Presentasi Eksekutif (PPTX) E-SKLD / SIGMA-K Berbahasa Indonesia.
Dirancang khusus untuk presentasi kepada Pimpinan dan Mentor:
- Bahasa Indonesia yang lugas, terstruktur, dan mudah dicerna.
- Format 16:9 widescreen dengan tata letak simetris dan elegan.
Output: docs/PRESENTASI_PERANCANGAN_SIGMA-K_v1.0.pptx
"""

import os
import pptx
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.enum.text import PP_ALIGN
from pptx.dml.color import RGBColor
from pptx.enum.shapes import MSO_SHAPE

PPTX_OUT = os.path.abspath('docs/PRESENTASI_PERANCANGAN_SIGMA-K_v1.0.pptx')
ASSETS_DIR = os.path.abspath('docs/assets')

# Palet Warna Resmi
C_NAVY = RGBColor(11, 42, 74)       # #0B2A4A (Biru Dongker KemenPANRB)
C_BLUE = RGBColor(30, 64, 175)     # #1E40AF (Biru Primer)
C_GOLD = RGBColor(212, 175, 55)    # #D4AF37 (Aksen Emas)
C_SLATE = RGBColor(30, 41, 59)     # #1E293B (Teks Utama)
C_MUTED = RGBColor(100, 116, 139)  # #64748B (Teks Sekunder)
C_WHITE = RGBColor(255, 255, 255)
C_BG_LIGHT = RGBColor(248, 250, 252)

def add_header(slide, title_text, category="E-SKLD / SIGMA-K — KEMENPANRB"):
    top_bar = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0), Inches(0), Inches(13.333), Inches(1.1))
    top_bar.fill.solid()
    top_bar.fill.fore_color.rgb = C_NAVY
    top_bar.line.color.rgb = C_NAVY

    gold_line = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0), Inches(1.1), Inches(13.333), Inches(0.06))
    gold_line.fill.solid()
    gold_line.fill.fore_color.rgb = C_GOLD
    gold_line.line.color.rgb = C_GOLD

    tx_cat = slide.shapes.add_textbox(Inches(0.8), Inches(0.12), Inches(11.7), Inches(0.3))
    p_cat = tx_cat.text_frame.paragraphs[0]
    p_cat.text = category.upper()
    p_cat.font.size = Pt(9.5)
    p_cat.font.bold = True
    p_cat.font.color.rgb = C_GOLD

    tx_title = slide.shapes.add_textbox(Inches(0.8), Inches(0.35), Inches(11.7), Inches(0.6))
    p_title = tx_title.text_frame.paragraphs[0]
    p_title.text = title_text
    p_title.font.size = Pt(20)
    p_title.font.bold = True
    p_title.font.color.rgb = C_WHITE

def add_footer(slide):
    tx_foot = slide.shapes.add_textbox(Inches(0.8), Inches(7.1), Inches(11.7), Inches(0.3))
    p_foot = tx_foot.text_frame.paragraphs[0]
    p_foot.text = "Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (KemenPANRB) — Dokumen Perancangan Sistem v1.0"
    p_foot.font.size = Pt(8.5)
    p_foot.font.color.rgb = C_MUTED

def build_presentation():
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # =========================================================
    # SLIDE 1: COVER
    # =========================================================
    s1 = prs.slides.add_slide(blank_layout)
    bg1 = s1.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0), Inches(0), Inches(13.333), Inches(7.5))
    bg1.fill.solid()
    bg1.fill.fore_color.rgb = C_NAVY
    bg1.line.color.rgb = C_NAVY

    gb = s1.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.2), Inches(0.15), Inches(4.8))
    gb.fill.solid()
    gb.fill.fore_color.rgb = C_GOLD
    gb.line.color.rgb = C_GOLD

    tx1 = s1.shapes.add_textbox(Inches(1.2), Inches(1.2), Inches(11.0), Inches(4.8))
    tf1 = tx1.text_frame
    tf1.word_wrap = True

    p0 = tf1.paragraphs[0]
    p0.text = "KEMENTERIAN PENDAYAGUNAAN APARATUR NEGARA DAN REFORMASI BIROKRASI"
    p0.font.size = Pt(13)
    p0.font.bold = True
    p0.font.color.rgb = C_GOLD

    p1 = tf1.add_paragraph()
    p1.text = "DOKUMEN PERANCANGAN SISTEM &\nPROTOTYPE APLIKASI E-SKLD (SIGMA-K)"
    p1.font.size = Pt(28)
    p1.font.bold = True
    p1.font.color.rgb = C_WHITE
    p1.space_before = Pt(14)

    p2 = tf1.add_paragraph()
    p2.text = "Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan"
    p2.font.size = Pt(14)
    p2.font.color.rgb = RGBColor(191, 219, 254)
    p2.space_before = Pt(10)

    p3 = tf1.add_paragraph()
    p3.text = "Versi 1.0  |  Status: 100% Siap Operasional & Lolos Uji  |  27 Agustus 2026"
    p3.font.size = Pt(11)
    p3.font.color.rgb = C_MUTED
    p3.space_before = Pt(30)

    # =========================================================
    # SLIDE 2: EXECUTIVE SUMMARY & OBJECTIVES
    # =========================================================
    s2 = prs.slides.add_slide(blank_layout)
    add_header(s2, "1. Gambaran Umum & Urgensi Pengembangan SIGMA-K")
    add_footer(s2)

    tx2 = s2.shapes.add_textbox(Inches(0.8), Inches(1.5), Inches(5.8), Inches(5.3))
    tf2 = tx2.text_frame
    tf2.word_wrap = True
    
    p = tf2.paragraphs[0]
    p.text = "Latar Belakang & Urgensi Sistem:"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_NAVY

    bullets = [
        "Penyelarasan struktur organisasi K/L/D pasca penetapan Kabinet Merah Putih.",
        "Kebutuhan pusat rujukan data tunggal (single source of truth) untuk master data instansi, unit kerja, dan formasi jabatan ASN.",
        "Digitalisasi dan standardisasi alur pengusulan penataan kelembagaan yang transparan.",
        "Penerapan pemisahan wewenang kerja (Separation of Duties): Penapisan Formalitas (Tahap 1 Admin) dan Telaah Substantif (Tahap 2 Verifikator).",
        "Wewenang mutlak pengesahan Surat Keputusan (SK) resmi berada pada Pejabat Verifikator."
    ]
    for b in bullets:
        bp = tf2.add_paragraph()
        bp.text = "• " + b
        bp.font.size = Pt(11)
        bp.font.color.rgb = C_SLATE
        bp.space_before = Pt(6)

    card = s2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(7.0), Inches(1.5), Inches(5.5), Inches(5.3))
    card.fill.solid()
    card.fill.fore_color.rgb = C_BG_LIGHT
    card.line.color.rgb = RGBColor(203, 213, 225)

    tx_card = s2.shapes.add_textbox(Inches(7.2), Inches(1.7), Inches(5.1), Inches(4.9))
    tfc = tx_card.text_frame
    tfc.word_wrap = True
    pc = tfc.paragraphs[0]
    pc.text = "Hasil Uji & Validasi Sistem (100% LULUS):"
    pc.font.size = Pt(13)
    pc.font.bold = True
    pc.font.color.rgb = C_BLUE

    metrics = [
        ("105 Pengujian Frontend Lulus", "Integrasi jaringan, keamanan akun, master data & laporan."),
        ("198 Pengujian Backend Lulus", "713 poin pemeriksaan, 0 kesalahan, 0 kegagalan."),
        ("0 Peringatan / Kesalahan Kode", "Pemeriksaan tipe data dan standar kode 100% bersih."),
        ("16 Halaman Siap Operasional", "Seluruh rute halaman aplikasi terkompilasi sukses."),
        ("21 Tabel Basis Data Relasional", "Integritas skema data MySQL eskld_db terjaga mutlak.")
    ]
    for m_title, m_desc in metrics:
        p_m = tfc.add_paragraph()
        p_m.text = f"✔ {m_title}: {m_desc}"
        p_m.font.size = Pt(10)
        p_m.font.color.rgb = C_SLATE
        p_m.space_before = Pt(8)

    # Helper untuk gambar tunggal terpusat (16:9)
    def add_single_diagram(slide_obj, title, filename):
        add_header(slide_obj, title)
        add_footer(slide_obj)
        img_path = os.path.join(ASSETS_DIR, filename)
        if os.path.exists(img_path):
            slide_obj.shapes.add_picture(img_path, Inches(1.666), Inches(1.35), width=Inches(10.0), height=Inches(5.625))

    # Helper untuk gambar ganda berdampingan (16:9)
    def add_dual_diagrams(slide_obj, title, file1, file2):
        add_header(slide_obj, title)
        add_footer(slide_obj)
        p1 = os.path.join(ASSETS_DIR, file1)
        p2 = os.path.join(ASSETS_DIR, file2)
        if os.path.exists(p1) and os.path.exists(p2):
            slide_obj.shapes.add_picture(p1, Inches(0.7), Inches(1.45), width=Inches(5.75), height=Inches(5.35))
            slide_obj.shapes.add_picture(p2, Inches(6.883), Inches(1.45), width=Inches(5.75), height=Inches(5.35))

    # SLIDE 3: ARSITEKTUR SISTEM
    s3 = prs.slides.add_slide(blank_layout)
    add_single_diagram(s3, "2. Arsitektur Sistem 4 Lapisan Terintegrasi", 'system_architecture.png')

    # SLIDE 4: PEMBAGIAN PERAN & HAK AKSES
    s4 = prs.slides.add_slide(blank_layout)
    add_single_diagram(s4, "3. Pembagian Peran Pengguna & Matriks Hak Akses", 'role_access_matrix.png')

    # SLIDE 5: SITEMAP & NAVIGASI
    s5 = prs.slides.add_slide(blank_layout)
    add_single_diagram(s5, "4. Struktur Navigasi & Sitemap Aplikasi (16 Halaman)", 'sitemap_diagram.png')

    # SLIDE 6: STRUKTUR BASIS DATA (ERD)
    s6 = prs.slides.add_slide(blank_layout)
    add_single_diagram(s6, "5. Struktur Basis Data & Entity Relationship Diagram (21 Tabel)", 'erd_diagram.png')

    # SLIDE 7: POHON HIERARKI ORGANISASI
    s7 = prs.slides.add_slide(blank_layout)
    add_single_diagram(s7, "6. Model Hierarki Organisasi & Bagan Interaktif", 'org_hierarchy_tree.png')

    # SLIDE 8: SIKLUS HIDUP USULAN (FSM)
    s8 = prs.slides.add_slide(blank_layout)
    add_single_diagram(s8, "7. Alur Tahapan Siklus Hidup Pengusulan Kelembagaan", 'submission_lifecycle_fsm.png')

    # SLIDE 9: ALUR PENAPISAN (GATE 1) & TELAAH (GATE 2)
    s9 = prs.slides.add_slide(blank_layout)
    add_dual_diagrams(s9, "8. Alur Penapisan Formalitas (Tahap 1) & Telaah Substantif (Tahap 2)", 'gate1_admin_flowchart.png', 'gate2_verifier_flowchart.png')

    # SLIDE 10: VERSIONING & PELACAK PERUBAHAN
    s10 = prs.slides.add_slide(blank_layout)
    add_single_diagram(s10, "9. Perekaman Riwayat Versi Draf & Pelacak Perubahan (Diff)", 'versioning_diff_flow.png')

    # SLIDE 11: PROTOTIPE: DASHBOARD & KATALOG INSTANSI
    s11 = prs.slides.add_slide(blank_layout)
    add_dual_diagrams(s11, "10. Prototipe Antarmuka: Dashboard Pimpinan & Katalog Master Instansi", 'prototype_dashboard.png', 'prototype_institutions.png')

    # SLIDE 12: PROTOTIPE: BAGAN STRUKTUR & DIFF USULAN
    s12 = prs.slides.add_slide(blank_layout)
    add_dual_diagrams(s12, "11. Prototipe Antarmuka: Bagan Struktur Organisasi & Rincian Diff Usulan", 'prototype_org_structure.png', 'prototype_submission_detail.png')

    # SLIDE 13: PROTOTIPE: RUANG KERJA VERIFIKATOR & ANALITIK
    s13 = prs.slides.add_slide(blank_layout)
    add_dual_diagrams(s13, "12. Prototipe Antarmuka: Ruang Kerja Telaah Verifikator & Analitik ASN", 'prototype_verifier_workspace.png', 'prototype_analytics_reporting.png')

    # SLIDE 14: KESIMPULAN & PENUTUP
    s14 = prs.slides.add_slide(blank_layout)
    bg14 = s14.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0), Inches(0), Inches(13.333), Inches(7.5))
    bg14.fill.solid()
    bg14.fill.fore_color.rgb = C_NAVY
    bg14.line.color.rgb = C_NAVY

    tx14 = s14.shapes.add_textbox(Inches(1.2), Inches(1.5), Inches(11.0), Inches(4.5))
    tf14 = tx14.text_frame
    tf14.word_wrap = True

    p = tf14.paragraphs[0]
    p.text = "KESIMPULAN & KESIAPAN IMPLEMENTASI SISTEM"
    p.font.size = Pt(24)
    p.font.bold = True
    p.font.color.rgb = C_GOLD

    recap = [
        "Arsitektur teruji dan siap produksi (CodeIgniter 4 + MySQL + Next.js 14).",
        "Pemisahan wewenang kerja yang akuntabel (Tahap 1 Penapis Admin & Tahap 2 Pengesahan SK Verifikator).",
        "Keamanan sistem tingkat tinggi dengan pembatasan hak akses instansi yang ketat.",
        "Otomasi pembaruan master data pasca pengesahan SK resmi menjamin konsistensi data nasional.",
        "Seluruh dokumen teknis resmi (Word, PDF, dan Slide Presentasi) telah siap diserahkan kepada pimpinan."
    ]
    for r in recap:
        pr = tf14.add_paragraph()
        pr.text = "✔  " + r
        pr.font.size = Pt(13)
        pr.font.color.rgb = C_WHITE
        pr.space_before = Pt(12)

    print(f"Menyimpan Presentasi ke {PPTX_OUT}...")
    prs.save(PPTX_OUT)
    print("Presentasi berhasil dibuat!")

if __name__ == '__main__':
    build_presentation()
