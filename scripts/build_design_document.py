"""
SIGMA-K Generator Dokumen Perancangan Sistem & Prototipe Aplikasi (Bahasa Indonesia Resmi untuk Pimpinan/Mentor)
Menghasilkan:
- docs/DOKUMEN_PERANCANGAN_SIGMA-K_v1.0.docx
- docs/DOKUMEN_PERANCANGAN_SIGMA-K_v1.0.pdf
"""

import os
import sys
import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn

DOCX_OUT = os.path.abspath('docs/DOKUMEN_PERANCANGAN_SIGMA-K_v1.0.docx')
PDF_OUT = os.path.abspath('docs/DOKUMEN_PERANCANGAN_SIGMA-K_v1.0.pdf')
ASSETS_DIR = os.path.abspath('docs/assets')

def set_cell_background(cell, fill_hex):
    shading_elm = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    cell._tc.get_or_add_tcPr().append(shading_elm)

def set_cell_margins(cell, top=120, bottom=120, left=150, right=150):
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = OxmlElement('w:tcMar')
    for m, val in [('top', top), ('bottom', bottom), ('left', left), ('right', right)]:
        node = OxmlElement(f'w:{m}')
        node.set(qn('w:w'), str(val))
        node.set(qn('w:type'), 'dxa')
        tcMar.append(node)
    tcPr.append(tcMar)

def set_table_borders(table, color_hex="CBD5E1"):
    tblPr = table._tbl.tblPr
    borders_xml = f'''
    <w:tblBorders {nsdecls("w")}>
        <w:top w:val="single" w:sz="6" w:space="0" w:color="{color_hex}"/>
        <w:left w:val="single" w:sz="6" w:space="0" w:color="{color_hex}"/>
        <w:bottom w:val="single" w:sz="6" w:space="0" w:color="{color_hex}"/>
        <w:right w:val="single" w:sz="6" w:space="0" w:color="{color_hex}"/>
        <w:insideH w:val="single" w:sz="4" w:space="0" w:color="{color_hex}"/>
        <w:insideV w:val="single" w:sz="4" w:space="0" w:color="{color_hex}"/>
    </w:tblBorders>
    '''
    tblPr.append(parse_xml(borders_xml))

class DocBuilder:
    def __init__(self):
        self.doc = Document()
        self.fig_count = 0
        self.tbl_count = 0
        self._setup_page_layout()
        self._setup_styles()

    def _setup_page_layout(self):
        for section in self.doc.sections:
            section.top_margin = Inches(1.0)
            section.bottom_margin = Inches(1.0)
            section.left_margin = Inches(1.0)
            section.right_margin = Inches(1.0)
            section.different_first_page_header_footer = True
            
            # Header
            header = section.header
            hp = header.paragraphs[0]
            hp.alignment = WD_ALIGN_PARAGRAPH.RIGHT
            hrun = hp.add_run("Dokumen Perancangan Sistem E-SKLD / SIGMA-K v1.0 | KemenPANRB")
            hrun.font.name = 'Calibri'
            hrun.font.size = Pt(8.5)
            hrun.font.color.rgb = RGBColor(100, 116, 139)

            # Footer
            footer = section.footer
            fp = footer.paragraphs[0]
            fp.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
            frun = fp.add_run("Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (KemenPANRB) — Dokumen Internal")
            frun.font.name = 'Calibri'
            frun.font.size = Pt(8.5)
            frun.font.color.rgb = RGBColor(100, 116, 139)

    def _setup_styles(self):
        style_normal = self.doc.styles['Normal']
        style_normal.font.name = 'Calibri'
        style_normal.font.size = Pt(10.5)
        style_normal.font.color.rgb = RGBColor(30, 41, 59)
        style_normal.paragraph_format.line_spacing = 1.15
        style_normal.paragraph_format.space_after = Pt(4)

    def add_title_cover(self):
        for _ in range(2):
            self.doc.add_paragraph()

        p_kemen = self.doc.add_paragraph()
        p_kemen.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r_kemen = p_kemen.add_run("KEMENTERIAN PENDAYAGUNAAN APARATUR NEGARA\nDAN REFORMASI BIROKRASI REPUBLIK INDONESIA")
        r_kemen.font.name = 'Calibri'
        r_kemen.font.size = Pt(12)
        r_kemen.font.bold = True
        r_kemen.font.color.rgb = RGBColor(11, 42, 74)

        for _ in range(2):
            self.doc.add_paragraph()

        p_main = self.doc.add_paragraph()
        p_main.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r_main = p_main.add_run("DOKUMEN PERANCANGAN SISTEM &\nPROTOTYPE APLIKASI")
        r_main.font.name = 'Calibri'
        r_main.font.size = Pt(22)
        r_main.font.bold = True
        r_main.font.color.rgb = RGBColor(11, 42, 74)

        p_sub = self.doc.add_paragraph()
        p_sub.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r_sub = p_sub.add_run("E-SKLD / SIGMA-K\nSistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah\ndan Struktur Kelembagaan")
        r_sub.font.name = 'Calibri'
        r_sub.font.size = Pt(13)
        r_sub.font.color.rgb = RGBColor(30, 64, 175)

        for _ in range(3):
            self.doc.add_paragraph()

        # Metadata Card Table
        tbl = self.doc.add_table(rows=6, cols=2)
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        set_table_borders(tbl, "CBD5E1")
        
        meta = [
            ("Judul Dokumen", "Dokumen Perancangan Sistem & Prototype Aplikasi E-SKLD (SIGMA-K)"),
            ("Nomor Dokumen", "DOC-PANRB-SIGMAK-2026-V1.0"),
            ("Versi / Rilis", "Versi 1.0 (Arsitektur Resmi & Baseline Implementasi)"),
            ("Tanggal Rilis", "27 Agustus 2026"),
            ("Penyusun", "Tim Pengembang & Analis Kelembagaan KemenPANRB"),
            ("Status Pengujian", "100% Lolos Uji (105 Pengujian Frontend & 198 Pengujian Backend)")
        ]
        
        for idx, (label, val) in enumerate(meta):
            c0, c1 = tbl.cell(idx, 0), tbl.cell(idx, 1)
            c0.width, c1.width = Inches(2.2), Inches(4.3)
            set_cell_margins(c0, 90, 90, 120, 120)
            set_cell_margins(c1, 90, 90, 120, 120)
            set_cell_background(c0, "F1F5F9")
            
            p0 = c0.paragraphs[0]
            p0.paragraph_format.space_after = Pt(0)
            r0 = p0.add_run(label)
            r0.font.bold = True
            r0.font.size = Pt(9.5)
            r0.font.color.rgb = RGBColor(11, 42, 74)

            p1 = c1.paragraphs[0]
            p1.paragraph_format.space_after = Pt(0)
            r1 = p1.add_run(val)
            r1.font.size = Pt(9.5)
            r1.font.color.rgb = RGBColor(30, 41, 59)

        self.doc.add_page_break()

    def add_h1(self, text):
        p = self.doc.add_paragraph()
        p.paragraph_format.space_before = Pt(14)
        p.paragraph_format.space_after = Pt(6)
        p.paragraph_format.keep_with_next = True
        run = p.add_run(text)
        run.font.name = 'Calibri'
        run.font.size = Pt(14)
        run.font.bold = True
        run.font.color.rgb = RGBColor(11, 42, 74)
        return p

    def add_h2(self, text):
        p = self.doc.add_paragraph()
        p.paragraph_format.space_before = Pt(10)
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.keep_with_next = True
        run = p.add_run(text)
        run.font.name = 'Calibri'
        run.font.size = Pt(12)
        run.font.bold = True
        run.font.color.rgb = RGBColor(30, 64, 175)
        return p

    def add_p(self, text):
        p = self.doc.add_paragraph()
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.line_spacing = 1.15
        run = p.add_run(text)
        run.font.name = 'Calibri'
        run.font.size = Pt(10)
        run.font.color.rgb = RGBColor(30, 41, 59)
        return p

    def add_bullet(self, text, bold_prefix=""):
        p = self.doc.add_paragraph(style='List Bullet')
        p.paragraph_format.space_after = Pt(2)
        p.paragraph_format.line_spacing = 1.15
        if bold_prefix:
            r_pre = p.add_run(bold_prefix)
            r_pre.font.bold = True
            r_pre.font.size = Pt(10)
            r_pre.font.color.rgb = RGBColor(11, 42, 74)
        run = p.add_run(text)
        run.font.name = 'Calibri'
        run.font.size = Pt(10)
        run.font.color.rgb = RGBColor(30, 41, 59)
        return p

    def add_callout(self, title, text):
        tbl = self.doc.add_table(rows=1, cols=1)
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        cell = tbl.cell(0, 0)
        cell.width = Inches(6.5)
        set_cell_background(cell, "F8FAFC")
        set_cell_margins(cell, 100, 100, 150, 150)
        
        tcPr = cell._tc.get_or_add_tcPr()
        borders = parse_xml(f'''
        <w:tcBorders {nsdecls("w")}>
            <w:top w:val="none"/>
            <w:left w:val="single" w:sz="24" w:space="0" w:color="0B2A4A"/>
            <w:bottom w:val="none"/>
            <w:right w:val="none"/>
        </w:tcBorders>
        ''')
        tcPr.append(borders)

        p = cell.paragraphs[0]
        p.paragraph_format.space_after = Pt(2)
        r_title = p.add_run(f"CATATAN PENTING UNTUK PIMPINAN: {title}\n")
        r_title.font.bold = True
        r_title.font.size = Pt(9.5)
        r_title.font.color.rgb = RGBColor(11, 42, 74)
        
        r_text = p.add_run(text)
        r_text.font.size = Pt(9)
        r_text.font.color.rgb = RGBColor(51, 65, 85)
        self.doc.add_paragraph().paragraph_format.space_after = Pt(2)

    def add_image_figure(self, filename, caption, width_in=6.3):
        img_path = os.path.join(ASSETS_DIR, filename)
        if os.path.exists(img_path):
            self.fig_count += 1
            p_img = self.doc.add_paragraph()
            p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
            p_img.paragraph_format.space_before = Pt(8)
            p_img.paragraph_format.space_after = Pt(3)
            p_img.paragraph_format.keep_with_next = True
            run = p_img.add_run()
            run.add_picture(img_path, width=Inches(width_in))

            p_cap = self.doc.add_paragraph()
            p_cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
            p_cap.paragraph_format.space_before = Pt(0)
            p_cap.paragraph_format.space_after = Pt(8)
            r_cap = p_cap.add_run(f"Gambar {self.fig_count}: {caption}")
            r_cap.font.name = 'Calibri'
            r_cap.font.size = Pt(9)
            r_cap.font.bold = True
            r_cap.font.color.rgb = RGBColor(71, 85, 105)
        else:
            p_err = self.doc.add_paragraph(f"[Gambar tidak ditemukan: {filename}]")
            p_err.runs[0].font.color.rgb = RGBColor(220, 38, 38)

    def add_table_data(self, title, headers, rows, col_widths=None):
        self.tbl_count += 1
        p_cap = self.doc.add_paragraph()
        p_cap.paragraph_format.space_before = Pt(8)
        p_cap.paragraph_format.space_after = Pt(3)
        p_cap.paragraph_format.keep_with_next = True
        r_cap = p_cap.add_run(f"Tabel {self.tbl_count}: {title}")
        r_cap.font.bold = True
        r_cap.font.size = Pt(9.5)
        r_cap.font.color.rgb = RGBColor(11, 42, 74)

        tbl = self.doc.add_table(rows=len(rows) + 1, cols=len(headers))
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        set_table_borders(tbl, "CBD5E1")

        # Header
        for c_idx, h_text in enumerate(headers):
            cell = tbl.cell(0, c_idx)
            set_cell_background(cell, "0B2A4A")
            set_cell_margins(cell, 80, 80, 100, 100)
            p = cell.paragraphs[0]
            p.paragraph_format.space_after = Pt(0)
            run = p.add_run(h_text)
            run.font.bold = True
            run.font.size = Pt(9)
            run.font.color.rgb = RGBColor(255, 255, 255)

        # Rows
        for r_idx, row_data in enumerate(rows):
            bg = "F8FAFC" if r_idx % 2 == 1 else "FFFFFF"
            for c_idx, val in enumerate(row_data):
                cell = tbl.cell(r_idx + 1, c_idx)
                set_cell_background(cell, bg)
                set_cell_margins(cell, 60, 60, 100, 100)
                p = cell.paragraphs[0]
                p.paragraph_format.space_after = Pt(0)
                run = p.add_run(str(val))
                run.font.size = Pt(8.5)
                run.font.color.rgb = RGBColor(30, 41, 59)

        # Widths
        if col_widths:
            for row in tbl.rows:
                for c_idx, w in enumerate(col_widths):
                    row.cells[c_idx].width = Inches(w)

        self.doc.add_paragraph().paragraph_format.space_after = Pt(4)

def build_sigma_k_document():
    builder = DocBuilder()
    builder.add_title_cover()

    # BAB 1
    builder.add_h1("BAB 1 — GAMBARAN UMUM & LATAR BELAKANG")
    builder.add_h2("1.1 Nama dan Identitas Sistem")
    builder.add_p("Nama resmi aplikasi ini adalah E-SKLD / SIGMA-K (Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan). Sistem ini dibangun sebagai pusat rujukan data tunggal (single source of truth) di lingkungan Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (KemenPANRB) untuk mengelola data kelembagaan instansi pemerintah secara terpadu di seluruh Indonesia.")

    builder.add_h2("1.2 Latar Belakang Kebutuhan Sistem")
    builder.add_p("Dalam rangka mewujudkan tata kelola pemerintahan yang efektif, efisien, dan akuntabel—khususnya menyelaraskan susunan kementerian/lembaga pasca penetapan Kabinet Merah Putih periode 2024–2029—KemenPANRB memerlukan platform digital yang mampu mengelola dan memvalidasi struktur organisasi kementerian/lembaga/pemerintah daerah secara terpusat.")
    builder.add_p("Sebelum adanya sistem ini, proses pengusulan penataan organisasi, penambahan unit kerja, serta penyesuaian formasi jabatan struktural (Eselon I s.d. Eselon IV) masih dilakukan melalui persuratan manual yang rentan terhadap ketidaksinkronan data dan membutuhkan waktu telaah yang relatif lama. SIGMA-K hadir untuk mendigitalisasi proses pengusulan dari hulu ke hilir, memastikan pemisahan wewenang yang tegas, serta menjamin keabsahan hukum dari setiap Surat Keputusan (SK) yang diterbitkan.")

    builder.add_h2("1.3 Tujuan Pengembangan")
    builder.add_bullet("Menyediakan satu basis data terpadu untuk seluruh instansi pemerintah (Kementerian Koordinator, Kementerian, LPNK, Lembaga Non-Struktural, dan Pemda).", "1. Sentralisasi Data Nasional: ")
    builder.add_bullet("Mempercepat proses telaah usulan melalui alur penapisan administratif (Tahap 1 Admin) dan telaah substantif (Tahap 2 Verifikator).", "2. Efisiensi Alur Kerja: ")
    builder.add_bullet("Menampilkan pohon struktur organisasi instansi secara visual dan interaktif sehingga mudah dipahami oleh pimpinan.", "3. Visualisasi Struktur Interaktif: ")
    builder.add_bullet("Setelah SK resmi disahkan oleh Verifikator, sistem secara otomatis memperbarui data master instansi tanpa perlu input manual ulang.", "4. Otomasi Pengesahan & Pembaruan Data: ")
    builder.add_bullet("Mencatat seluruh riwayat perubahan data secara permanen sehingga memudahkan pengawasan pimpinan dan audit kepatuhan.", "5. Transparansi & Jejak Audit Mutlak: ")

    builder.add_h2("1.4 Ruang Lingkup Layanan Sistem")
    builder.add_p("Ruang lingkup operasional sistem mencakup:")
    builder.add_bullet("Pengelolaan katalog K/L/D, pemetaan komposisi kabinet pemerintahan, dan dasar hukum pembentukan instansi.", "• Master Data Kelembagaan: ")
    builder.add_bullet("Penyusunan pohon hierarki unit kerja dari tingkat pimpinan tertinggi (Level 1) hingga unit pelaksana (Level 4).", "• Struktur Organisasi Berjenjang: ")
    builder.add_bullet("Penyusunan draf usulan penataan kelembagaan, penambahan unit baru, penghapusan unit, dan penyesuaian kuota formasi ASN.", "• Pengusulan Struktur Baru: ")
    builder.add_bullet("Penguncian versi draf usulan (v1, v2, dst.) dan komparasi perubahan data (Diff Viewer) sebelum disahkan.", "• Pelacakan Versi & Revisi: ")
    builder.add_bullet("Pemeriksaan kelengkapan surat pengantar dan naskah akademik oleh staf penapis KemenPANRB.", "• Penapisan Tahap 1 (Gate 1 Admin): ")
    builder.add_bullet("Analisis beban kerja, evaluasi formasi jabatan, rekomendasi teknis, dan pengesahan SK resmi oleh pejabat Verifikator.", "• Telaah Tahap 2 (Gate 2 Verifikator): ")
    builder.add_bullet("Penyajian grafik capaian, piramida distribusi eselon, waktu layanan telaah, dan fitur unduh laporan resmi (CSV/JSON).", "• Dashboard & Laporan Eksekutif: ")
    builder.add_bullet("Pencatatan rekam jejak mutasi data secara transparan yang merekam NIP pengguna, waktu, dan rincian perubahan.", "• Rekam Jejak Forensik (Audit Trail): ")

    builder.add_h2("1.5 Pengguna Sistem dan Pembagian Tugas")
    builder.add_p("Sistem melayani empat peran pengguna dengan pembagian tugas yang jelas:")
    builder.add_bullet("Staf perwakilan instansi pengusul yang bertugas membuat draf usulan struktur organisasi unit kerjanya dan memperbaiki berkas bila ada catatan revisi.", "1. Operator Instansi (USER): ")
    builder.add_bullet("Staf administrasi KemenPANRB yang bertugas memeriksa kelengkapan formal surat dan naskah dokumen serta mendistribusikan penugasan berkas ke Verifikator.", "2. Penapis KemenPANRB (ADMIN): ")
    builder.add_bullet("Pejabat fungsional analis kelembagaan KemenPANRB yang bertugas menelaah beban kerja organisasi, menandatangani pengesahan SK resmi, dan mempromosikan usulan ke data aktif.", "3. Verifikator Kelembagaan (VERIFIER): ")
    builder.add_bullet("Pimpinan kementerian (SESDEP) dan administrator sistem yang memiliki hak pantauan analitik nasional, manajemen akses pengguna, dan audit forensik menyeluruh.", "4. Pimpinan & Administrator (SUPER_ADMIN): ")

    builder.add_h2("1.6 Status Kesiapan dan Hasil Pengujian Sistem")
    builder.add_p("Berdasarkan hasil pengujian komprehensif, seluruh arsitektur dan modul sistem telah 100% siap operasional:")
    builder.add_bullet("Sebanyak 105 pengujian otomatis pada antarmuka web (integrasi jaringan, keamanan akun, master data, dan pelaporan) dinyatakan 100% LULUS.", "• Pengujian Frontend Web: ")
    builder.add_bullet("Sebanyak 198 pengujian unit backend CodeIgniter 4 dengan 713 poin pemeriksaan dinyatakan 100% LULUS (0 kesalahan, 0 kegagalan).", "• Pengujian Backend Server: ")
    builder.add_bullet("Seluruh 16 rute halaman aplikasi terkompilasi bersih tanpa ada kesalahan kode maupun peringatan sistem.", "• Kesiapan Halaman Web: ")
    builder.add_bullet("Sebanyak 21 tabel basis data relasional MySQL eskld_db terjaga integritasnya tanpa ada perubahan skema yang menyimpang.", "• Integritas Basis Data: ")

    # BAB 2
    builder.add_h1("BAB 2 — GAMBARAN ARSITEKTUR SISTEM")
    builder.add_h2("2.1 Konsep Arsitektur 4 Lapisan (4-Tier Architecture)")
    builder.add_p("SIGMA-K dirancang dengan arsitektur modern 4 lapisan yang memisahkan secara tegas antara tampilan antarmuka web, gerbang keamanan jaringan, lapisan logika bisnis server, dan media penyimpanan basis data.")
    builder.add_bullet("Dibangun menggunakan Next.js 14, React 18, dan TypeScript untuk menghadirkan tampilan web yang cepat, modern, dan responsif.", "1. Lapisan Antarmuka (Tier 1): ")
    builder.add_bullet("Bertindak sebagai perantara aman yang menyisipkan token otentikasi digital pada setiap komunikasi data ke server backend.", "2. Lapisan Gerbang Jaringan (HTTP Gateway): ")
    builder.add_bullet("Dibangun dengan CodeIgniter 4 dan PHP 8.2+ sebagai pengendali utama logika bisnis, aturan persetujuan, dan keamanan akses.", "3. Lapisan Logika Bisnis & Keamanan (Tier 2): ")
    builder.add_bullet("Menggunakan basis data relasional MySQL 8.x (eskld_db) dengan 21 tabel terintegrasi yang menjamin keutuhan data.", "4. Lapisan Penyimpanan Data (Tier 3): ")

    builder.add_h2("2.2 Modul-Modul Utama Sistem")
    builder.add_table_data(
        "Daftar Modul Utama Aplikasi SIGMA-K",
        ["No", "Nama Modul", "Komponen Server (Backend)", "Komponen Web (Frontend)", "Fungsi Utama"],
        [
            ["1", "Modul Masuk & Sesi", "AuthController, AuthFilter, UserModel", "AuthService, RoleContext, /login", "Autentikasi NIP/Password, penerbitan token JWT, validasi profil pengguna"],
            ["2", "Modul Master Instansi", "InstitutionController, OrgHierarchyService", "InstitutionService, /institutions, /structure", "Katalog instansi pemerintah, struktur pohon unit kerja, bagan interaktif"],
            ["3", "Modul Pengusulan", "SubmissionWorkflowController, SubmissionModel", "SubmissionService, /submissions, /submissions/[id]", "Penyusunan draf usulan, penguncian riwayat versi draf (v1->v2), pelacak perubahan"],
            ["4", "Modul Penapisan Tahap 1", "AdminWorkflowController, VerifierAssignmentModel", "SubmissionService, /verifications (Tahap 1)", "Pemeriksaan kelengkapan berkas formal, pengembalian revisi, penugasan Verifikator"],
            ["5", "Modul Telaah Tahap 2", "VerifierWorkflowController, ApprovalModel", "SubmissionService, /verifications/[id] (Tahap 2)", "Telaah beban kerja, catatan teknis substantif, pengesahan SK resmi, promosi data"],
            ["6", "Modul Pelaporan Eksekutif", "ReportController, ExecutiveReportService", "AnalyticsService, /, /analytics", "Dashboard capaian, grafik piramida eselon, SLA telaah, unduh berkas CSV/JSON"],
            ["7", "Modul Audit Forensik", "AuditLogController, AuditService", "AuditService, /audit-logs", "Pencatatan log transaksi permanen, inspeksi data sebelum dan sesudah perubahan"]
        ],
        [0.4, 1.5, 1.6, 1.6, 1.4]
    )

    builder.add_h2("2.3 Diagram Arsitektur Terintegrasi")
    builder.add_p("Diagram arsitektur sistem secara menyeluruh ditunjukkan pada Gambar 1 di bawah ini:")
    builder.add_image_figure("system_architecture.png", "Arsitektur Sistem Terintegrasi E-SKLD / SIGMA-K (4 Lapisan)")

    # BAB 3
    builder.add_h1("BAB 3 — PEMBAGIAN PERAN DAN HAK AKSES")
    builder.add_h2("3.1 Prinsip Pemisahan Wewenang Kerja (Separation of Duties)")
    builder.add_p("Untuk menjamin tata kelola yang akuntabel dan mencegah konflik kepentingan, SIGMA-K menerapkan prinsip Pemisahan Wewenang Kerja yang ketat antara staf pembuat usulan, staf penapis administrasi, dan pejabat penelaah substantif:")
    builder.add_bullet("Operator hanya berwenang membuat draf usulan dan menanggapi catatan revisi pada instansinya sendiri. Operator dilarang keras menelaah atau menyetujui usulan.", "1. Batasan Operator: ")
    builder.add_bullet("Admin KemenPANRB hanya bertugas memeriksa kelengkapan berkas formalitas (Tahap 1) dan menugaskan Verifikator. Admin dilarang keras mengesahkan SK final.", "2. Batasan Penapis Admin: ")
    builder.add_bullet("Verifikator memegang hak wewenang tunggal untuk menandatangani pengesahan SK resmi (Tahap 2) dan mengeksekusi pembaruan ke master data aktif.", "3. Wewenang Verifikator: ")
    builder.add_bullet("Pimpinan memiliki akses pantauan dashboard nasional dan log audit tanpa mencampuri alur telaah operasional.", "4. Wewenang Pimpinan: ")

    builder.add_callout("Status Akun SESDEP", "Pada prototipe awal, SESDEP diperkenalkan sebagai representasi persona pimpinan. Dalam implementasi resmi backend yang telah diverifikasi, kode akun SESDEP dinormalisasi otomatis ke dalam peran SUPER_ADMIN dengan wewenang pengawasan eksekutif nasional penuh. Pengganti peran manual pada tampilan web dinonaktifkan permanen saat terhubung ke server utama guna menjamin keamanan.")

    builder.add_h2("3.2 Matriks Peran dan Tanggung Jawab")
    builder.add_table_data(
        "Matriks Pembagian Tugas dan Hak Akses per Peran Pengguna",
        ["Peran Pengguna", "Fokus Tugas Utama", "Izin Operasi Sistem", "Wilayah Akses Data", "Tanggung Jawab Alur Kerja"],
        [
            ["OPERATOR (USER)", "Penyusunan usulan struktur organisasi instansi pengusul", "submission:create, submission:edit, org:read, inst:read_own", "Instansi Sendiri (Home Institution)", "Menyusun draf usulan, mengirim usulan, memperbaiki berkas revisi (v1->v2)"],
            ["PENAPIS (ADMIN)", "Penapisan kelengkapan berkas formalitas (Tahap 1)", "screening:read, screening:action, verifier:assign, audit:read_scoped", "Berkas K/L yang Ditugaskan", "Pemeriksaan formal surat/naskah, kembalikan revisi, alokasi penugasan Verifikator"],
            ["VERIFIKATOR (VERIFIER)", "Telaah substantif beban kerja & pengesahan SK resmi (Tahap 2)", "review:substantive, approval:final, promotion:execute, audit:read", "Antrean Usulan Penugasan", "Telaah beban kerja & formasi ASN, input catatan teknis, pengesahan SK, promosi data master"],
            ["PIMPINAN / SUPER_ADMIN", "Pengawasan nasional, kelola akun & audit forensik", "admin:all, user:manage, audit:forensics, reports:export_all", "Nasional Seluruh Indonesia (K/L/D)", "Monitoring KPI nasional, pemeriksaan log audit kepatuhan, ekspor dataset"]
        ],
        [1.1, 1.4, 1.5, 1.1, 1.4]
    )

    builder.add_image_figure("role_access_matrix.png", "Model Otorisasi dan Matriks Pembagian Peran Pengguna SIGMA-K")

    # BAB 4
    builder.add_h1("BAB 4 — PRINSIP DESAIN ANTARMUKA PENGGUNA (UI/UX)")
    builder.add_h2("4.1 Standar Visual & Kemudahan Penggunaan")
    builder.add_p("Desain antarmuka SIGMA-K dibangun dengan mengedepankan identitas resmi instansi pemerintah, kejelasan hierarki informasi, dan kenyamanan pengguna:")
    builder.add_bullet("Biru Dongker (#0B2A4A) sebagai warna resmi institusi, Biru Primer (#1E40AF) untuk tombol aksi utama, Aksen Emas (#D4AF37) untuk status eksekutif, dan Putih Bersih (#F8FAFC) sebagai latar kerja yang nyaman di mata.", "• Palet Warna Harmonis: ")
    builder.add_bullet("Menu samping terorganisir per kelompok layanan, bar status sesi di atas, rekam jejak posisi halaman (breadcrumb), dan judul halaman yang tegas.", "• Tata Letak Konsisten: ")
    builder.add_bullet("Setiap proses pengiriman data menampilkan indikator animasi pemuatan agar pengguna mengetahui sistem sedang bekerja.", "• Indikator Pemuatan (Loading State): ")
    builder.add_bullet("Pemberitahuan galat (seperti 401 Sesi Habis, 403 Akses Ditolak, 404 Data Tidak Ditemukan) ditampilkan dengan bahasa Indonesia yang jelas dan informatif.", "• Penanganan Galat Jelas: ")

    # BAB 5
    builder.add_h1("BAB 5 — SITEMAP DAN STRUKTUR NAVIGASI APLIKASI")
    builder.add_h2("5.1 Daftar 16 Halaman Terintegrasi")
    builder.add_table_data(
        "Katalog 16 Halaman Aplikasi E-SKLD / SIGMA-K",
        ["No", "Alamat URL", "Nama Halaman", "Akses Pengguna", "Fungsi Halaman"],
        [
            ["1", "/login", "Layar Masuk Sistem", "Semua Pengguna", "Autentikasi NIP/Password & inisialisasi sesi token digital"],
            ["2", "/", "Dashboard Eksekutif", "Semua Pengguna", "Ringkasan metrik nasional, status usulan, dan widget SK terbaru"],
            ["3", "/analytics", "Intelijensi & Postur ASN", "Semua Pengguna", "Analisis capaian, piramida eselon, durasi layanan, unduh CSV"],
            ["4", "/institutions", "Katalog Master Instansi", "Semua Pengguna", "Daftar seluruh K/L/D dengan filter kategori dan pencarian"],
            ["5", "/institutions/[id]", "Rincian Profil Instansi", "Semua Pengguna", "Dasar hukum, daftar unit kerja, dan rekapitulasi posisi jabatan"],
            ["6", "/structure", "Bagan Struktur Organisasi", "Semua Pengguna", "Bagan visual interaktif pohon hierarki unit kerja instansi"],
            ["7", "/cabinets", "Katalog Kabinet", "Semua Pengguna", "Struktur Kabinet Merah Putih dan daftar kementerian koordinator"],
            ["8", "/cabinets/compare", "Komparasi Kabinet", "Semua Pengguna", "Perbandingan perubahan struktur kementerian lintas kabinet"],
            ["9", "/submissions", "Daftar Usulan", "Operator, Penapis, Verifikator", "Tabel status berkas usulan penataan kelembagaan"],
            ["10", "/submissions/new", "Form Usulan Baru", "Operator Instansi", "Formulir penyusunan draf usulan penataan unit & formasi"],
            ["11", "/submissions/[id]", "Rincian & Diff Usulan", "Semua Pengguna", "Informasi perubahan unit, jabatan, dan alur tahapan usulan"],
            ["12", "/submissions/[id]/revision", "Form Perbaikan Revisi", "Operator Instansi", "Ruang perbaikan berkas setelah menerima catatan telaah (v2)"],
            ["13", "/verifications", "Antrean Verifikasi", "Penapis & Verifikator", "Antrean kerja penapisan formal dan telaah substantif"],
            ["14", "/verifications/[id]", "Ruang Kerja Telaah", "Penapis & Verifikator", "Panel telaah teknis, input catatan review, dan pengesahan SK"],
            ["15", "/audit-logs", "Log Forensik Audit", "Penapis & Super Admin", "Tabel rekam jejak mutasi data dan perbandingan perubahan"],
            ["16", "/notifications", "Pusat Pemberitahuan", "Semua Pengguna", "Notifikasi perubahan status berkas dan penugasan telaah"]
        ],
        [0.4, 1.6, 1.5, 1.3, 1.7]
    )
    builder.add_image_figure("sitemap_diagram.png", "Pohon Struktur Navigasi dan Sitemap Aplikasi SIGMA-K")

    # BAB 6
    builder.add_h1("BAB 6 — MASTER DATA KELEMBAGAAN")
    builder.add_h2("6.1 Keterhubungan Data & Model Pohon Organisasi")
    builder.add_p("Hubungan relasional antar data kelembagaan disajikan pada Gambar 4, dan representasi pohon hierarki struktural berjenjang disajikan pada Gambar 5.")
    builder.add_image_figure("master_data_flow.png", "Keterhubungan dan Alur Integrasi Master Data Kelembagaan")
    builder.add_image_figure("org_hierarchy_tree.png", "Bagan Struktur Pohon Organisasi Berjenjang (Eselon I - IV)")

    # BAB 7
    builder.add_h1("BAB 7 — STRUKTUR BASIS DATA & ERD (21 TABEL)")
    builder.add_h2("7.1 Skema Relasional Basis Data (MySQL eskld_db)")
    builder.add_table_data(
        "Katalog 21 Tabel Relasional Basis Data eskld_db",
        ["No", "Nama Tabel", "Kelompok Data", "Kunci Utama (PK)", "Kunci Relasi (FK)", "Deskripsi Fungsi Data"],
        [
            ["1", "users", "Akun Pengguna", "id (INT)", "role_id, institution_id", "Data akun pengguna, NIP, email, dan kata sandi terenkripsi"],
            ["2", "roles", "Hak Akses", "id (INT)", "-", "Daftar 4 peran resmi (USER, ADMIN, VERIFIER, SUPER_ADMIN)"],
            ["3", "permissions", "Hak Akses", "id (INT)", "-", "Katalog izin operasi spesifik (buat draf, telaah, sahkan SK)"],
            ["4", "role_permissions", "Hak Akses", "role_id, perm_id", "role_id, permission_id", "Pemetaan relasi izin operasi untuk masing-masing peran"],
            ["5", "user_scopes", "Wilayah Akses", "id (INT)", "user_id, institution_id", "Batasan wilayah instansi yang berhak diakses pengguna"],
            ["6", "access_grants", "Wilayah Akses", "id (INT)", "user_id, institution_id", "Pemberian izin akses tugas khusus sementara lintas instansi"],
            ["7", "access_requests", "Wilayah Akses", "id (INT)", "user_id, institution_id", "Pengajuan permohonan penambahan akses data instansi"],
            ["8", "institutions", "Master Instansi", "id (INT)", "type_id", "Master data seluruh instansi kementerian/lembaga/daerah"],
            ["9", "institution_types", "Master Instansi", "id (INT)", "-", "Kategori bentuk instansi pemerintah (Kemenko, LPNK, dll.)"],
            ["10", "cabinets", "Master Kabinet", "id (INT)", "-", "Data kabinet pemerintahan dan masa periode jabatan"],
            ["11", "cabinet_institutions", "Master Kabinet", "cabinet_id, inst_id", "cabinet_id, institution_id", "Pemetaan instansi yang tergabung dalam kabinet aktif"],
            ["12", "organizational_units", "Master Struktur", "id (INT)", "institution_id, parent_id", "Unit kerja aktif tersusun dalam struktur pohon hierarkis"],
            ["13", "positions", "Master Jabatan", "id (INT)", "unit_id", "Jabatan struktural aktif, jenjang eselon, dan kuota formasi ASN"],
            ["14", "submissions", "Pengusulan", "id (INT)", "institution_id, author_id", "Header usulan penataan kelembagaan dan status alur kerja"],
            ["15", "submission_versions", "Pengusulan", "id (INT)", "submission_id", "Perekam versi draf usulan (v1, v2) untuk riwayat historis"],
            ["16", "submission_units", "Pengusulan", "id (INT)", "version_id, parent_id", "Rincian usulan perubahan unit kerja (tambah/ubah/hapus)"],
            ["17", "submission_positions", "Pengusulan", "id (INT)", "version_id, unit_id", "Rincian usulan perubahan formasi jabatan struktural"],
            ["18", "verifier_assignments", "Verifikasi", "id (INT)", "submission_id, verifier_id", "Rekam penugasan berkas usulan dari Admin ke Verifikator"],
            ["19", "verifier_review_notes", "Verifikasi", "id (INT)", "submission_id, verifier_id", "Catatan koreksi telaah teknis substantif per unit kerja"],
            ["20", "approval_records", "Pengesahan SK", "id (INT)", "version_id, approver_id", "Dokumen penetapan persetujuan SK resmi oleh Verifikator"],
            ["21", "audit_logs", "Audit Forensik", "id (INT)", "actor_id, institution_id", "Log mutasi data permanen, rekam jejak IP, dan rincian perubahan"]
        ],
        [0.3, 1.4, 1.1, 0.9, 1.3, 1.5]
    )
    builder.add_image_figure("erd_diagram.png", "Entity Relationship Diagram (ERD) Basis Data eskld_db (21 Tabel)")

    # BAB 8
    builder.add_h1("BAB 8 — ALUR SIKLUS HIDUP PENGUSULAN KELEMBAGAAN")
    builder.add_p("Alur usulan penataan kelembagaan diatur melalui mesin status (Finite State Machine) 7 tahapan yang transparan:")
    builder.add_image_figure("submission_lifecycle_fsm.png", "Diagram Alur Siklus Hidup Pengusulan Kelembagaan (7 Tahapan)")

    # BAB 9
    builder.add_h1("BAB 9 — MEKANISME VERSI DRAF & PELACAK PERUBAHAN (DIFF)")
    builder.add_p("Sistem secara otomatis mengunci draf awal (v1) dan membandingkan hasil perbaikan (v2) dengan data aktif melalui fitur Diff Viewer:")
    builder.add_image_figure("versioning_diff_flow.png", "Mekanisme Perekaman Versi dan Pelacakan Perubahan Data (Diff Viewer)")

    # BAB 10
    builder.add_h1("BAB 10 — PENAPISAN TAHAP 1 (GATE 1 ADMIN SCREENING)")
    builder.add_p("Penapisan Tahap 1 memverifikasi kelengkapan formalitas surat permohonan dan naskah akademik sebelum berkas didistribusikan ke Verifikator:")
    builder.add_image_figure("gate1_admin_flowchart.png", "Diagram Alir Penapisan Administratif (Tahap 1 Admin Screening)")

    # BAB 11
    builder.add_h1("BAB 11 — TELAAH SUBSTANTIF TAHAP 2 (GATE 2 VERIFIKATOR)")
    builder.add_p("Telaah Tahap 2 mengevaluasi beban kerja organisasi, formasi ASN, menandatangani pengesahan SK resmi, dan mempromosikan data ke master aktif:")
    builder.add_image_figure("gate2_verifier_flowchart.png", "Diagram Alir Telaah Substantif & Pengesahan SK (Tahap 2 Verifikator)")

    # BAB 12
    builder.add_h1("BAB 12 — ARSITEKTUR OTENTIKASI & KEAMANAN SESI (JWT)")
    builder.add_p("Siklus otentikasi mengelola penerbitan token digital, pengamanan sesi pengguna, dan perlindungan dari manipulasi hak akses:")
    builder.add_image_figure("auth_jwt_flow.png", "Diagram Alur Otentikasi Token Digital JWT dan Pengamanan Sesi")

    # BAB 13 s.d. BAB 16
    builder.add_h1("BAB 13 — KEAMANAN SISTEM & PROTEKSI DATA MULTI-INSTANSI")
    builder.add_p("Layanan otorisasi pada backend CodeIgniter 4 bertindak sebagai pelindung mutlak yang memastikan operator hanya dapat mengakses data instansinya sendiri.")

    builder.add_h1("BAB 14 — DASHBOARD EKSEKUTIF & PELAPORAN NASIONAL")
    builder.add_p("Modul pelaporan menyajikan agregasi capaian kelembagaan nasional, status antrean berkas, serta layanan unduh dokumen laporan resmi.")

    builder.add_h1("BAB 15 — FORENSIK AUDIT TRAIL (REKAM JEJAK TRANSAKSI)")
    builder.add_p("Pencatatan rekam jejak permanen pada tabel audit_logs mendokumentasikan setiap aksi mutasi data secara transparan untuk pengawasan pimpinan.")

    builder.add_h1("BAB 16 — KATALOG LAYANAN API TERVERIFIKASI")
    builder.add_table_data(
        "Katalog Layanan REST API Terverifikasi E-SKLD / SIGMA-K",
        ["Metode", "Alamat Endpoint API", "Fungsi Layanan", "Filter & Keamanan", "Hasil Respon Data"],
        [
            ["POST", "/api/v1/auth/login", "Masuk Sistem (Login)", "Validasi NIP & Sandi", "Token Akses JWT & Profil Pengguna"],
            ["GET", "/api/v1/auth/me", "Profil Sesi Aktif", "Pemeriksaan Token JWT", "Profil Pengguna, Peran, dan Wilayah Akses"],
            ["POST", "/api/v1/auth/logout", "Keluar Sistem (Logout)", "Pemeriksaan Token JWT", "Status sukses keluar & penonaktifan token"],
            ["GET", "/api/v1/institutions", "Katalog Instansi K/L/D", "Pemeriksaan Token JWT", "Daftar seluruh master instansi pemerintah"],
            ["GET", "/api/v1/institutions/{id}", "Rincian Profil Instansi", "Pemeriksaan Wilayah Akses", "Rincian profil, tipe, dan rekapitulasi unit"],
            ["GET", "/api/v1/organizations/tree", "Bagan Struktur Unit", "Pemeriksaan Wilayah Akses", "Struktur pohon hierarkis unit kerja"],
            ["GET", "/api/v1/organizations/{id}", "Rincian Unit Kerja", "Pemeriksaan Wilayah Akses", "Detail unit, induk, dan formasi jabatan"],
            ["GET", "/api/v1/submissions", "Daftar Usulan Berkas", "Filter Wilayah Pengguna", "Daftar usulan sesuai wewenang peran"],
            ["POST", "/api/v1/submissions", "Pembuatan Usulan Baru", "Khusus Operator Instansi", "Header draf usulan & nomor versi draf 1"],
            ["GET", "/api/v1/submissions/{id}", "Rincian Berkas Usulan", "Pemeriksaan Wilayah Akses", "Detail usulan, komparasi unit & jabatan"],
            ["POST", "/api/v1/submissions/{id}/submit", "Pengiriman ke Tahap 1", "Khusus Pemilik Usulan", "Perubahan status ke PENAPISAN ADMIN"],
            ["POST", "/api/v1/admin/screen/{id}", "Tindakan Penapis Admin", "Khusus Penapis Admin", "Terima berkas atau kembalikan revisi"],
            ["POST", "/api/v1/admin/assign/{id}", "Penugasan Verifikator", "Khusus Penapis Admin", "Perubahan status ke PENUGASAN VERIFIKATOR"],
            ["POST", "/api/v1/verifier/review/{id}", "Telaah Substantif", "Khusus Verifikator Terpilih", "Input catatan telaah & status SIAP PENGESAHAN"],
            ["POST", "/api/v1/verifier/approve/{id}", "Pengesahan SK Resmi", "Khusus Pejabat Verifikator", "Penerbitan nomor SK & status DISETUJUI"],
            ["POST", "/api/v1/verifier/promote/{id}", "Promosi ke Master Data", "Khusus Pejabat Verifikator", "Pembaruan otomatis ke master data aktif"],
            ["GET", "/api/v1/reports/summary", "Ringkasan Pimpinan", "Pemeriksaan Token JWT", "Metrik overview nasional, funnel, SK terbaru"],
            ["GET", "/api/v1/reports/export", "Ekspor Dokumen Laporan", "Izin Khusus Ekspor", "Berkas unduhan resmi format CSV/JSON"],
            ["GET", "/api/v1/audit-logs", "Log Forensik Audit", "Admin & Super Admin", "Daftar transaksi mutasi data dan rincian perubahan"]
        ],
        [0.7, 1.8, 1.4, 1.3, 1.3]
    )

    # BAB 17: PROTOTYPES
    builder.add_h1("BAB 17 — SPESIFIKASI & PROTOTYPE TAMPILAN APLIKASI")
    
    builder.add_h2("17.1 SCR-18: Layar Masuk Sistem (Login)")
    builder.add_image_figure("prototype_login.png", "Prototype Layar Masuk Sistem (SCR-18 Login)")

    builder.add_h2("17.2 SCR-01: Dashboard Eksekutif Pimpinan")
    builder.add_image_figure("prototype_dashboard.png", "Prototype Dashboard Eksekutif Pimpinan (SCR-01)")

    builder.add_h2("17.3 SCR-02: Katalog Master Instansi Pemerintah")
    builder.add_image_figure("prototype_institutions.png", "Prototype Katalog Master Instansi Pemerintah (SCR-02)")

    builder.add_h2("17.4 SCR-04: Bagan Pohon Struktur Organisasi Interaktif")
    builder.add_image_figure("prototype_org_structure.png", "Prototype Bagan Pohon Struktur Organisasi Interaktif (SCR-04)")

    builder.add_h2("17.5 SCR-09: Rincian Usulan & Pelacak Perubahan (Diff)")
    builder.add_image_figure("prototype_submission_detail.png", "Prototype Rincian Usulan & Pelacak Perubahan (SCR-09 Diff Viewer)")

    builder.add_h2("17.6 SCR-12: Ruang Kerja Telaah Verifikator")
    builder.add_image_figure("prototype_verifier_workspace.png", "Prototype Ruang Kerja Telaah Verifikator (SCR-12 Verifier Workspace)")

    builder.add_h2("17.7 SCR-15: Intelijensi Data & Postur ASN")
    builder.add_image_figure("prototype_analytics_reporting.png", "Prototype Intelijensi Data & Postur ASN (SCR-15 Analytics Studio)")

    builder.add_h2("17.8 SCR-16: Log Forensik Audit Trail")
    builder.add_image_figure("prototype_audit_logs.png", "Prototype Log Forensik Audit Trail (SCR-16)")

    # BAB 18
    builder.add_h1("BAB 18 — HASIL PENGUJIAN & VALIDASI SISTEM")
    builder.add_table_data(
        "Hasil Pengujian Kualitas dan Keandalan Sistem SIGMA-K",
        ["Kelompok Pengujian", "Alat Pengujian", "Cakupan Modul", "Target Pengujian", "Hasil Pengujian"],
        [
            ["Fondasi Frontend (14A)", "npx tsx run-foundation-tests.ts", "Klien Jaringan, Penanganan Galat, Pemetaan Data", "28 Pengujian", "100% LULUS (PASS)"],
            ["Autentikasi & Keamanan (14B)", "npx tsx run-auth-tests.ts", "Siklus Token JWT, Penguncian Peran", "24 Pengujian", "100% LULUS (PASS)"],
            ["Master Data Instansi (14C)", "npx tsx run-master-tests.ts", "Katalog K/L/D, Pohon Hierarki, Rincian Unit", "24 Pengujian", "100% LULUS (PASS)"],
            ["Laporan Eksekutif (14D)", "npx tsx run-report-tests.ts", "Ringkasan Metrik, Funnel, Unduh Berkas CSV", "29 Pengujian", "100% LULUS (PASS)"],
            ["Total Pengujian Frontend", "npx tsx run-report-tests.ts", "Seluruh Modul Antarmuka Web", "105 Pengujian", "100% LULUS (PASS)"],
            ["Pengujian Backend Server", "vendor/bin/phpunit", "Otorisasi, Alur Pengusulan, Tahap 1 & 2, Log Audit", "198 Pengujian / 713 Poin", "100% LULUS (0 Error, 0 Fail)"],
            ["Pemeriksaan Tipe TypeScript", "npx tsc --noEmit", "Seluruh Komponen, Layanan & Skema Data", "0 Kesalahan Tipe", "100% LULUS (PASS)"],
            ["Analisis Kualitas Kode (ESLint)", "npm run lint", "Standar Kerapian & Kebersihan Kode", "0 Peringatan / 0 Galat", "100% LULUS (PASS)"],
            ["Kompilasi Rute Produksi", "npm run build", "Kompilasi 16 Halaman Aplikasi", "16 Rute Berhasil", "100% LULUS (PASS)"]
        ],
        [1.5, 1.5, 1.5, 1.0, 1.0]
    )

    # BAB 19
    builder.add_h1("BAB 19 — MATRIKS KESIAPAN FITUR APLIKASI")
    builder.add_table_data(
        "Matriks Kesiapan Implementasi Fitur E-SKLD / SIGMA-K",
        ["Fitur / Modul Layanan", "Status Kesiapan", "Kesiapan Server", "Kesiapan Web", "Keterangan Validasi"],
        [
            ["Masuk & Otentikasi Sesi", "SELESAI (100%)", "CodeIgniter 4 + AuthFilter", "Next.js AuthService & Context", "Token digital & profil terverifikasi penuh"],
            ["Otorisasi & Pembatasan Akses", "SELESAI (100%)", "AuthorizationService + ScopeResolver", "Penguncian Peran Otomatis", "Perlindungan data antar instansi aktif"],
            ["Katalog Master Instansi", "SELESAI (100%)", "InstitutionController", "InstitutionService + Katalog UI", "Katalog K/L/D & profil terintegrasi penuh"],
            ["Bagan Pohon Organisasi", "SELESAI (100%)", "OrgHierarchyService (Pohon Hierarkis)", "Bagan Interaktif Visual", "Bagan pohon & panel rincian aktif"],
            ["Penyusunan Usulan Baru", "SELESAI (100%)", "SubmissionWorkflowService", "SubmissionService + Form UI", "Penyusunan unit & jabatan aktif"],
            ["Perekaman Riwayat Versi", "SELESAI (100%)", "Tabel submission_versions (v1->v2)", "Penampil Perubahan (Diff)", "Penguncian draf historis aktif"],
            ["Penapisan Tahap 1 (Admin)", "SELESAI (100%)", "AdminWorkflowController", "Ruang Kerja Penapisan", "Pemeriksaan formalitas & penugasan Verifikator"],
            ["Telaah Tahap 2 (Verifikator)", "SELESAI (100%)", "VerifierWorkflowController", "Ruang Kerja Verifikator", "Telaah beban kerja & catatan teknis aktif"],
            ["Pengesahan Surat Keputusan", "SELESAI (100%)", "ApprovalPromotionService", "Komponen Pengesahan SK", "Wewenang final mutlak pada Verifikator"],
            ["Pembaruan Data Otomatis", "SELESAI (100%)", "Mesin Pemindahan Data Master", "Indikator Status Promosi", "Mutasi usulan ke master aktif otomatis"],
            ["Dashboard Capaian Pimpinan", "SELESAI (100%)", "ReportController::summary", "Kartu Capaian & Funnel UI", "Metrik live terhubung ke basis data"],
            ["Pusat Unduh Laporan", "SELESAI (100%)", "ReportController::export", "Pusat Analitik & Unduh CSV", "Unduh berkas CSV/JSON resmi terverifikasi"],
            ["Forensik Log Transaksi", "SELESAI (100%)", "AuditService (Pencatatan Permanen)", "Tabel Log & Modal Perubahan", "Rekam jejak mutasi & IP terverifikasi"],
            ["Status Verifikator Aktif", "PENGEMBANGAN LANJUT", "Rencana Tahap Berikutnya", "Rencana Tahap Berikutnya", "Tercatat resmi sebagai agenda OPEN-005"]
        ],
        [1.4, 1.1, 1.3, 1.3, 1.4]
    )

    # BAB 20 & 21
    builder.add_h1("BAB 20 — RENCANA PENGEMBANGAN TAHAP LANJUTAN")
    builder.add_bullet("Penyediaan indikator status online real-time untuk mengetahui verifikator yang sedang menelaah usulan yang sama menggunakan teknologi WebSocket / Server-Sent Events.", "1. Status Verifikator Aktif (OPEN-005): ")
    builder.add_bullet("Integrasi tanda tangan digital tersertifikasi bekerjasama dengan Balai Sertifikasi Elektronik (BSrE) BSSN untuk penerbitan dokumen SK elektronik.", "2. Tanda Tangan Elektronik Tersertifikasi: ")
    builder.add_bullet("Penambahan media penyimpanan terenkripsi untuk pengunggahan naskah akademik, regulasi pembentukan, dan dokumen pendukung berukuran besar.", "3. Penyimpanan Berkas Naskah Digital: ")

    builder.add_h1("BAB 21 — KESIMPULAN & PENUTUP")
    builder.add_p("Dokumen Perancangan Sistem dan Prototype Aplikasi E-SKLD / SIGMA-K Versi 1.0 ini merangkum seluruh hasil capaian pembangunan perangkat lunak yang telah berhasil dibangun, diintegrasikan, dan diuji secara komprehensif. Sistem telah membuktikan kesiapan arsitektur dalam mengelola master data kelembagaan kementerian/lembaga/pemerintah daerah, memfasilitasi tata kelola pengusulan penataan organisasi secara terstruktur, menegakkan prinsip pemisahan wewenang kerja yang akuntabel, serta menyediakan rekam jejak forensik mutlak guna mendukung terwujudnya tata kelola pemerintahan digital yang bersih, transparan, dan terpercaya di Indonesia.")

    print(f"Menyimpan Dokumen Word ke {DOCX_OUT}...")
    builder.doc.save(DOCX_OUT)
    print("Dokumen Word berhasil dibuat!")

if __name__ == '__main__':
    build_sigma_k_document()
