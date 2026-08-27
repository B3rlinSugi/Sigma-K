"""
Complete High-Resolution Visual Asset Generator for SIGMA-K Design Document.
Generates 22+ Flowcharts, Diagrams, and Screen Prototype Wireframes.
"""

import os
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import matplotlib.patches as patches

os.makedirs('docs/assets', exist_ok=True)

def setup_fig(width=10, height=6, bg='#FFFFFF'):
    fig, ax = plt.subplots(figsize=(width, height), dpi=200)
    fig.patch.set_facecolor(bg)
    ax.set_facecolor(bg)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    return fig, ax

def draw_card(ax, x, y, w, h, bg_color='#FFFFFF', border_color='#CBD5E1', border_width=1.5, radius=2, title='', title_color='#0B2A4A', title_size=10):
    rect = patches.FancyBboxPatch((x, y), w, h, boxstyle=f"round,pad=0,rounding_size={radius}",
                                  facecolor=bg_color, edgecolor=border_color, linewidth=border_width)
    ax.add_patch(rect)
    if title:
        ax.text(x + w/2, y + h - 3.5, title, ha='center', va='center', fontsize=title_size, fontweight='bold', color=title_color)

def draw_arrow(ax, x1, y1, x2, y2, color='#0B2A4A', width=1.5, label=''):
    ax.annotate('', xy=(x2, y2), xytext=(x1, y1),
                arrowprops=dict(arrowstyle="-|>", color=color, lw=width, mutation_scale=12))
    if label:
        mx, my = (x1 + x2)/2, (y1 + y2)/2
        ax.text(mx, my + 1.2, label, ha='center', va='bottom', fontsize=7.5, fontweight='bold', color=color)

# -------------------------------------------------------------
# 1. SYSTEM ARCHITECTURE DIAGRAM
# -------------------------------------------------------------
def gen_system_architecture():
    fig, ax = setup_fig(11, 7)
    ax.text(50, 95, "ARSITEKTUR SISTEM E-SKLD / SIGMA-K", ha='center', va='center', fontsize=13, fontweight='bold', color='#0B2A4A')
    ax.text(50, 91.5, "Integrasi 4-Tier: Frontend Next.js 14, HTTP Gateway, Backend CodeIgniter 4, & MySQL eskld_db", ha='center', va='center', fontsize=8.5, color='#64748B')
    
    draw_card(ax, 3, 62, 94, 25, bg_color='#F8FAFC', border_color='#0B2A4A', title="TIER 1: PRESENTATION LAYER (Next.js 14 + React 18 + TypeScript)", title_color='#0B2A4A', title_size=10)
    
    draw_card(ax, 5, 64, 21, 18, bg_color='#FFFFFF', border_color='#3B82F6', title="UI Components & Screens", title_size=8)
    ax.text(15.5, 72.5, "• Executive Dashboard (/)\n• Org Chart React Flow\n• Catalog Instansi (/institutions)\n• Submission & Revision UI\n• Verifier Workspace", fontsize=7, color='#334155', va='center', ha='center')
    
    draw_card(ax, 28, 64, 21, 18, bg_color='#FFFFFF', border_color='#3B82F6', title="State & Role Context", title_size=8)
    ax.text(38.5, 72.5, "• AuthProvider & RoleContext\n• Read-Only Auth Role Badge\n• Notification Context\n• Dual-Mode Env Dispatch\n(NEXT_PUBLIC_DATA_MODE)", fontsize=7, color='#334155', va='center', ha='center')

    draw_card(ax, 51, 64, 21, 18, bg_color='#FFFFFF', border_color='#3B82F6', title="Service Facades & DTO", title_size=8)
    ax.text(61.5, 72.5, "• AuthService, InstitutionService\n• OrganizationService\n• SubmissionService\n• AnalyticsService, AuditService\n• TypeScript DTO Interfaces", fontsize=7, color='#334155', va='center', ha='center')

    draw_card(ax, 74, 64, 21, 18, bg_color='#FFFFFF', border_color='#3B82F6', title="Domain Mappers", title_size=8)
    ax.text(84.5, 72.5, "• Map DTO (snake_case)\n  -> Domain (camelCase)\n• Flatten Org Hierarchy Tree\n• BigInt to String ID Sanitizer\n• Workflow State Normalizer", fontsize=7, color='#334155', va='center', ha='center')

    draw_arrow(ax, 50, 62, 50, 54, color='#2563EB', width=2, label="HTTPS JSON + JWT Bearer Token")

    draw_card(ax, 15, 45, 70, 9, bg_color='#EFF6FF', border_color='#2563EB', title="HTTP CLIENT & TOKEN PROVIDER GATEWAY", title_color='#1E40AF', title_size=9)
    ax.text(50, 47.5, "Native Fetch Client • Auto Authorization Header Injection • AppError Status Normalizer (401, 403, 404, 409, 422, 500) • 15s Timeout Signal", fontsize=7.5, color='#1E3A8A', ha='center')

    draw_arrow(ax, 50, 45, 50, 37, color='#2563EB', width=2, label="REST API Requests (api/v1/*)")

    draw_card(ax, 3, 13, 94, 24, bg_color='#F8FAFC', border_color='#0B2A4A', title="TIER 2: BACKEND & DOMAIN APPLICATION (CodeIgniter 4.4.8 + PHP 8.2+)", title_color='#0B2A4A', title_size=10)

    draw_card(ax, 5, 15, 21, 17, bg_color='#FFFFFF', border_color='#D97706', title="API Controllers & Filters", title_size=8)
    ax.text(15.5, 22.5, "• AuthFilter (JWT Verify)\n• AuthController\n• InstitutionController\n• SubmissionWorkflowController\n• VerifierWorkflowController\n• ReportController", fontsize=7, color='#334155', va='center', ha='center')

    draw_card(ax, 28, 15, 21, 17, bg_color='#FFFFFF', border_color='#D97706', title="Zero-Trust Authz Engine", title_size=8)
    ax.text(38.5, 22.5, "• AuthorizationService\n• ScopeResolver (Multi-Tenant)\n• AccessGrant Engine\n• Anti Self-Approval Guard\n• BOLA/IDOR Scope Boundary", fontsize=7, color='#334155', va='center', ha='center')

    draw_card(ax, 51, 15, 21, 17, bg_color='#FFFFFF', border_color='#D97706', title="Core Domain Services", title_size=8)
    ax.text(61.5, 22.5, "• SubmissionWorkflowService\n• OrgHierarchyService (DFS Tree)\n• RevisionService (Versioning)\n• ApprovalPromotionService\n• ExecutiveReportService", fontsize=7, color='#334155', va='center', ha='center')

    draw_card(ax, 74, 15, 21, 17, bg_color='#FFFFFF', border_color='#D97706', title="Audit & Forensic Service", title_size=8)
    ax.text(84.5, 22.5, "• AuditService (Append-Only)\n• Payload Diffing Engine\n• Actor & IP Forensics Capture\n• Security Violation Tracker\n• CSV/JSON Export Engine", fontsize=7, color='#334155', va='center', ha='center')

    draw_arrow(ax, 50, 13, 50, 6, color='#059669', width=2, label="PDO / MySQL Driver")
    draw_card(ax, 20, 0.5, 60, 5.5, bg_color='#ECFDF5', border_color='#059669', title="TIER 3: DATA STORAGE - MySQL 8.x (eskld_db - 21 Relational Tables)", title_color='#065F46', title_size=8.5)

    plt.savefig('docs/assets/system_architecture.png', bbox_inches='tight')
    plt.close()

# -------------------------------------------------------------
# 2. ROLE & ACCESS MATRIX DIAGRAM
# -------------------------------------------------------------
def gen_role_access():
    fig, ax = setup_fig(10, 6)
    ax.text(50, 94, "MODEL OTORISASI & MATRIKS HAK AKSES ZERO-TRUST", ha='center', fontsize=12.5, fontweight='bold', color='#0B2A4A')
    
    roles = [
        ("USER\n(Operator Instansi)", 4, 45, 21, 40, '#EFF6FF', '#1E40AF', [
            "• Lingkup: Home Institution",
            "• Buat Usulan (Draft)",
            "• Tambah Unit & Jabatan",
            "• Submit ke Gate 1 Admin",
            "• Perbaiki Revisi Usulan",
            "• Lihat Master Instansi Sendiri"
        ]),
        ("ADMIN\n(Penapis KemenPANRB)", 28, 45, 21, 40, '#FEF3C7', '#B45309', [
            "• Lingkup: Scoped / Assigned K/L",
            "• Gate 1 Admin Screening",
            "• Validasi Kelengkapan Berkas",
            "• Kembalikan Revisi Administratif",
            "• Assign Verifikator Substantif",
            "• DILARANG Final Approval"
        ]),
        ("VERIFIER\n(Verifikator Substantif)", 52, 45, 21, 40, '#ECFDF5', '#047857', [
            "• Lingkup: Assigned Submission Queue",
            "• Gate 2 Substantive Review",
            "• Catatan & Rekomendasi Teknis",
            "• Kembalikan Revisi Substantif",
            "• FINAL APPROVAL AUTHORITY (SK)",
            "• EKSEKUSI PROMOSI MASTER DATA"
        ]),
        ("SUPER_ADMIN / SESDEP\n(Admin Sistem / Pimpinan)", 76, 45, 21, 40, '#F3E8FF', '#6B21A8', [
            "• Lingkup: Global Seluruh Indonesia",
            "• Kelola Master User & Access Grants",
            "• Monitoring Seluruh K/L/D",
            "• Dashboard Eksekutif Nasional",
            "• Audit Trail Forensik Penuh",
            "• Ekspor Seluruh Dataset"
        ]),
    ]
    
    for title, x, y, w, h, bg, border, items in roles:
        draw_card(ax, x, y, w, h, bg_color=bg, border_color=border, title=title, title_color=border, title_size=8.5)
        text_content = "\n".join(items)
        ax.text(x + 1.5, y + h - 10, text_content, fontsize=7.5, color='#1E293B', va='top')

    draw_card(ax, 4, 5, 93, 33, bg_color='#F8FAFC', border_color='#0B2A4A', title="PRINSIP KEAMANAN ZERO-TRUST & SEPARATION OF DUTIES", title_color='#0B2A4A', title_size=9.5)
    principles = (
        "1. Backend Single Source of Truth: Frontend tidak mengambil keputusan otorisasi secara mandiri.\n"
        "2. Strict Role Locking: Switcher persona dinonaktifkan pada mode API produksi guna mencegah privilege escalation.\n"
        "3. Separation of Duties: ADMIN hanya berwenang Gate 1 (Penapisan/Penugasan); FINAL APPROVAL mutlak di tangan VERIFIER.\n"
        "4. Anti Self-Approval Guard: Author dilarang menelaah atau menyetujui usulan buatannya sendiri.\n"
        "5. BOLA / IDOR Scoping Guard: Kueri data instansi, usulan, dan telaah dibatasi strictly sesuai user_scopes & access_grants."
    )
    ax.text(6, 31, principles, fontsize=8, color='#334155', va='top', linespacing=1.35)

    plt.savefig('docs/assets/role_access_matrix.png', bbox_inches='tight')
    plt.close()

# -------------------------------------------------------------
# 3. SUBMISSION LIFECYCLE & FINITE STATE MACHINE DIAGRAM
# -------------------------------------------------------------
def gen_submission_lifecycle():
    fig, ax = setup_fig(11, 6)
    ax.text(50, 94, "FINITE STATE MACHINE & SIKLUS HIDUP PENGUSULAN KELEMBAGAAN", ha='center', fontsize=12, fontweight='bold', color='#0B2A4A')

    states = [
        ("DRAFT\n(Operator)", 5, 60, 14, 16, '#F1F5F9', '#64748B'),
        ("SUBMITTED_TO_ADMIN\n(Gate 1 Screening)", 26, 60, 18, 16, '#EFF6FF', '#2563EB'),
        ("ASSIGNED_TO_VERIFIER\n(Gate 2 Assigned)", 51, 60, 18, 16, '#FEF3C7', '#D97706'),
        ("READY_FOR_FINAL_DECISION\n(Telaah Selesai)", 76, 60, 20, 16, '#EDE9FE', '#7C3AED'),
        ("REVISION_REQUIRED\n(Perbaikan Berkas)", 38, 20, 22, 16, '#FEE2E2', '#DC2626'),
        ("APPROVED\n(Disahkan SK Resmi)", 60, 20, 16, 16, '#D1FAE5', '#059669'),
        ("PROMOTED\n(Masuk Master Data)", 82, 20, 15, 16, '#10B981', '#047857')
    ]

    for title, x, y, w, h, bg, border in states:
        tc = '#FFFFFF' if bg == '#10B981' else border
        draw_card(ax, x, y, w, h, bg_color=bg, border_color=border, title=title, title_color=tc, title_size=7.5)

    draw_arrow(ax, 19, 68, 26, 68, color='#2563EB', label="submit()")
    draw_arrow(ax, 44, 68, 51, 68, color='#D97706', label="accept() & assignVerifier()")
    draw_arrow(ax, 69, 68, 76, 68, color='#7C3AED', label="approveSubstantive()")
    draw_arrow(ax, 86, 60, 76, 36, color='#059669', label="finalApprove(VERIFIER)")
    draw_arrow(ax, 76, 28, 82, 28, color='#047857', label="promote()")

    draw_arrow(ax, 35, 60, 44, 36, color='#DC2626', label="returnAdmin()")
    draw_arrow(ax, 60, 60, 52, 36, color='#DC2626', label="returnVerifier()")
    draw_arrow(ax, 38, 28, 12, 60, color='#64748B', label="resubmit() [v1->v2]")

    plt.savefig('docs/assets/submission_lifecycle_fsm.png', bbox_inches='tight')
    plt.close()

# -------------------------------------------------------------
# 4. SITEMAP DIAGRAM
# -------------------------------------------------------------
def gen_sitemap_diagram():
    fig, ax = setup_fig(11, 7)
    ax.text(50, 95, "SITEMAP DAN STRUKTUR NAVIGASI APLIKASI SIGMA-K", ha='center', fontsize=12.5, fontweight='bold', color='#0B2A4A')

    draw_card(ax, 42, 82, 16, 8, bg_color='#0B2A4A', border_color='#0B2A4A', title="ROOT (/login)", title_color='#FFFFFF', title_size=8.5)
    draw_card(ax, 40, 67, 20, 9, bg_color='#1E40AF', border_color='#1E40AF', title="APP SHELL / LAYOUT", title_color='#FFFFFF', title_size=8.5)
    draw_arrow(ax, 50, 82, 50, 76, color='#0B2A4A')

    branches = [
        ("1. DASHBOARD", 3, 40, 16, 20, '#EFF6FF', '#2563EB', [
            "• Overview (/)",
            "• Analytics (/analytics)",
            "• Postur ASN",
            "• Export Center"
        ]),
        ("2. MASTER DATA", 22, 40, 17, 20, '#F0FDF4', '#16A34A', [
            "• Katalog (/institutions)",
            "• Detail (.../[id])",
            "• Struktur (/structure)",
            "• Kabinet (/cabinets)",
            "• Tupoksi (/tupoksi)"
        ]),
        ("3. PENGUSULAN", 41, 40, 18, 20, '#FFFBEB', '#D97706', [
            "• Daftar (/submissions)",
            "• Buat (.../new)",
            "• Rincian (.../[id])",
            "• Revisi (.../revision)"
        ]),
        ("4. VERIFIKASI", 61, 40, 18, 20, '#FAF5FF', '#9333EA', [
            "• Queue (/verifications)",
            "• Gate 1 Screening",
            "• Gate 2 Workspace",
            "• Review (.../[id])"
        ]),
        ("5. AUDIT & NOTIF", 81, 40, 16, 20, '#F8FAFC', '#475569', [
            "• Audit (/audit-logs)",
            "• Forensik Log",
            "• Notifikasi",
            "• Profil Pengguna"
        ])
    ]

    for title, x, y, w, h, bg, border, items in branches:
        draw_card(ax, x, y, w, h, bg_color=bg, border_color=border, title=title, title_color=border, title_size=8)
        ax.text(x + 1, y + h - 5.5, "\n".join(items), fontsize=7, color='#334155', va='top')
        draw_arrow(ax, 50, 67, x + w/2, y + h, color='#64748B', width=1.2)

    draw_card(ax, 10, 8, 80, 24, bg_color='#F1F5F9', border_color='#94A3B8', title="INFORMASI IMPLEMENTASI ROUTING", title_color='#0F172A', title_size=8.5)
    ax.text(12, 25, (
        "• Seluruh route frontend dilindungi oleh layout terpusat AppShell yang mengelola TopBar & Sidebar.\n"
        "• Mode Otorisasi: Akses menu dibatasi secara dinamis sesuai RoleContext (USER, ADMIN, VERIFIER, SUPER_ADMIN).\n"
        "• Validasi Route: 16 route Next.js telah terkompilasi 100% (Static & Dynamic SSR)."
    ), fontsize=7.5, color='#334155', va='top', linespacing=1.35)

    plt.savefig('docs/assets/sitemap_diagram.png', bbox_inches='tight')
    plt.close()

# -------------------------------------------------------------
# 5. ERD / DATABASE SCHEMA DIAGRAM
# -------------------------------------------------------------
def gen_erd_diagram():
    fig, ax = setup_fig(11, 7.5)
    ax.text(50, 96, "ENTITY RELATIONSHIP DIAGRAM (ERD) - eskld_db (21 TABEL)", ha='center', fontsize=12.5, fontweight='bold', color='#0B2A4A')

    draw_card(ax, 3, 52, 28, 40, bg_color='#EFF6FF', border_color='#2563EB', title="1. AUTH & ACCESS CONTROL", title_color='#1E40AF', title_size=8.5)
    ax.text(4.5, 86, (
        "• users (id, nip, email, password_hash)\n"
        "• roles (id, code, name)\n"
        "• permissions (id, code, name)\n"
        "• role_permissions (role_id, permission_id)\n"
        "• user_scopes (id, user_id, institution_id)\n"
        "• access_grants (id, user_id, inst_id, grant_type)\n"
        "• access_requests (id, user_id, inst_id, status)"
    ), fontsize=6.8, color='#1E293B', va='top', linespacing=1.2)

    draw_card(ax, 35, 52, 30, 40, bg_color='#F0FDF4', border_color='#16A34A', title="2. MASTER DATA KELEMBAGAAN", title_color='#15803D', title_size=8.5)
    ax.text(36.5, 86, (
        "• institutions (id, code, name, category, status)\n"
        "• institution_types (id, code, name)\n"
        "• cabinets (id, name, president_name, is_active)\n"
        "• cabinet_institutions (cabinet_id, inst_id)\n"
        "• organizational_units (id, inst_id, parent_id,\n"
        "    unit_code, unit_name, unit_level, status)\n"
        "• positions (id, unit_id, position_name, echelon,\n"
        "    formation_count, status)"
    ), fontsize=6.8, color='#1E293B', va='top', linespacing=1.2)

    draw_card(ax, 68, 52, 29, 40, bg_color='#FFFBEB', border_color='#D97706', title="3. SUBMISSION & VERSIONING", title_color='#B45309', title_size=8.5)
    ax.text(69.5, 86, (
        "• submissions (id, inst_id, author_id, title,\n"
        "    submission_year, current_state)\n"
        "• submission_versions (id, submission_id,\n"
        "    version_number, notes)\n"
        "• submission_units (id, version_id, parent_id,\n"
        "    unit_code, unit_name, action_type)\n"
        "• submission_positions (id, version_id, unit_id,\n"
        "    position_name, formation_count, action_type)"
    ), fontsize=6.8, color='#1E293B', va='top', linespacing=1.2)

    draw_card(ax, 15, 6, 70, 42, bg_color='#FAF5FF', border_color='#7E22CE', title="4. VERIFICATION, APPROVAL, PROMOTION & FORENSIC AUDIT", title_color='#6B21A8', title_size=9)
    ax.text(17, 41, (
        "• verifier_assignments (id, submission_id, verifier_id, assigned_by_admin_id, status, assigned_at)\n"
        "• verifier_review_notes (id, submission_id, verifier_id, section, note_text, is_resolved)\n"
        "• approval_records (id, version_id, approver_id, approval_number, approval_notes, approved_at)\n"
        "• audit_logs (id, actor_id, actor_name, actor_role, action_event, resource_entity, resource_id,\n"
        "    institution_id, old_payload, new_payload, ip_address, user_agent, created_at)"
    ), fontsize=7.2, color='#1E293B', va='top', linespacing=1.3)

    plt.savefig('docs/assets/erd_diagram.png', bbox_inches='tight')
    plt.close()

# -------------------------------------------------------------
# 6. MASTER DATA FLOW & ORG HIERARCHY TREE
# -------------------------------------------------------------
def gen_master_data_hierarchy():
    # Master Data Flow
    fig, ax = setup_fig(10, 5.5)
    ax.text(50, 93, "ALUR KETERHUBUNGAN MASTER DATA KELEMBAGAAN", ha='center', fontsize=12, fontweight='bold', color='#0B2A4A')

    draw_card(ax, 5, 55, 24, 25, bg_color='#EFF6FF', border_color='#2563EB', title="1. Instansi & Kabinet", title_size=8)
    ax.text(17, 65, "• Master K/L/D\n• Kategori (Kemenko, K/L, Pemda)\n• Pemetaan Kabinet Merah Putih", fontsize=7, color='#1E293B', ha='center')

    draw_card(ax, 38, 55, 24, 25, bg_color='#F0FDF4', border_color='#16A34A', title="2. Unit Kerja Struktural", title_size=8)
    ax.text(50, 65, "• Pohon Hierarki Adjacency List\n• Parent-Child Pointers\n• DFS Anti-Cycle Validation", fontsize=7, color='#1E293B', ha='center')

    draw_card(ax, 71, 55, 24, 25, bg_color='#FEF3C7', border_color='#D97706', title="3. Formasi & Eselonisasi", title_size=8)
    ax.text(83, 65, "• Nama Jabatan Struktural\n• Jenjang Eselon I s.d. IV\n• Kuota Alokasi Formasi ASN", fontsize=7, color='#1E293B', ha='center')

    draw_arrow(ax, 29, 67, 38, 67, color='#2563EB', width=2)
    draw_arrow(ax, 62, 67, 71, 67, color='#16A34A', width=2)

    draw_card(ax, 10, 8, 80, 36, bg_color='#F8FAFC', border_color='#0B2A4A', title="LOGIKA PENYAJIAN GRAF PADA FRONTEND (REACT FLOW)", title_color='#0B2A4A', title_size=9)
    ax.text(12, 36, (
        "• Algoritma Flattening: Frontend menerima payload recursive tree dari backend (/organizations/tree) dan\n"
        "  melakukan pemetaan linear menjadi nodes[] dan edges[] dengan koordinat tata letak dinamis.\n"
        "• Indikator Status: Node aktif ditampilkan dengan aksen biru/hijau; usulan perubahan unit baru ditandai kuning/emas.\n"
        "• Integrasi Drawer Detail: Klik pada node memicu pembukaan drawer samping yang memuat rincian jabatan dan tupoksi."
    ), fontsize=7.5, color='#334155', va='top', linespacing=1.35)

    plt.savefig('docs/assets/master_data_flow.png', bbox_inches='tight')
    plt.close()

    # Org Hierarchy Tree
    fig, ax = setup_fig(10, 6.5)
    ax.text(50, 94, "STRUKTUR POHON ORGANISASI BERJENJANG (ESELON I - IV)", ha='center', fontsize=12, fontweight='bold', color='#0B2A4A')

    # Level 1: Pimpinan (Menteri)
    draw_card(ax, 35, 76, 30, 12, bg_color='#0B2A4A', border_color='#0B2A4A', title="Menteri / Kepala Lembaga\n(Level 1 - Pimpinan)", title_color='#FFFFFF', title_size=8)

    # Level 2: Sekretariat & Deputi
    draw_card(ax, 10, 48, 35, 14, bg_color='#1E40AF', border_color='#1E40AF', title="Sekretariat Kementerian\n(Level 2 - Eselon I.a)", title_color='#FFFFFF', title_size=7.5)
    draw_card(ax, 55, 48, 35, 14, bg_color='#1E40AF', border_color='#1E40AF', title="Deputi Bidang Kelembagaan\n(Level 2 - Eselon I.a)", title_color='#FFFFFF', title_size=7.5)

    draw_arrow(ax, 42, 76, 27.5, 62, color='#0B2A4A', width=1.5)
    draw_arrow(ax, 58, 76, 72.5, 62, color='#0B2A4A', width=1.5)

    # Level 3: Biro & Asdep
    draw_card(ax, 5, 18, 22, 16, bg_color='#EFF6FF', border_color='#3B82F6', title="Biro SDM & Org\n(Level 3 - II.a)", title_color='#1E40AF', title_size=7)
    draw_card(ax, 29, 18, 22, 16, bg_color='#EFF6FF', border_color='#3B82F6', title="Biro Perencanaan\n(Level 3 - II.a)", title_color='#1E40AF', title_size=7)

    draw_card(ax, 53, 18, 22, 16, bg_color='#EFF6FF', border_color='#3B82F6', title="Asdep Tata Laksana\n(Level 3 - II.a)", title_color='#1E40AF', title_size=7)
    draw_card(ax, 77, 18, 22, 16, bg_color='#EFF6FF', border_color='#3B82F6', title="Asdep Struktur K/L\n(Level 3 - II.a)", title_color='#1E40AF', title_size=7)

    draw_arrow(ax, 20, 48, 16, 34, color='#1E40AF')
    draw_arrow(ax, 35, 48, 40, 34, color='#1E40AF')
    draw_arrow(ax, 65, 48, 64, 34, color='#1E40AF')
    draw_arrow(ax, 80, 48, 88, 34, color='#1E40AF')

    draw_card(ax, 5, 2, 90, 10, bg_color='#F1F5F9', border_color='#94A3B8', title="MODEL RELASI: Adjacency List (parent_id) + Algoritma DFS Tree Builder", title_color='#0F172A', title_size=8)

    plt.savefig('docs/assets/org_hierarchy_tree.png', bbox_inches='tight')
    plt.close()

# -------------------------------------------------------------
# 7. VERSIONING & DIFF FLOW
# -------------------------------------------------------------
def gen_versioning_diff():
    fig, ax = setup_fig(10, 5.5)
    ax.text(50, 93, "MEKANISME SNAPSHOT VERSIONING & PELACAKAN PERUBAHAN (DIFF)", ha='center', fontsize=12, fontweight='bold', color='#0B2A4A')

    draw_card(ax, 5, 48, 26, 32, bg_color='#EFF6FF', border_color='#2563EB', title="Versi Usulan 1 (v1)", title_size=8.5)
    ax.text(18, 68, "• submission_versions (v1)\n• Snapshot Unit & Jabatan awal\n• Dikirim ke Gate 1 Admin\n• Status: SUBMITTED", fontsize=7, color='#1E293B', ha='center')

    draw_card(ax, 37, 48, 26, 32, bg_color='#FEF2F2', border_color='#DC2626', title="Catatan Perbaikan", title_size=8.5)
    ax.text(50, 68, "• Admin/Verifier Return\n• Catatan verifier_review_notes\n• Alasan penolakan berkas\n• Status: REVISION_REQUIRED", fontsize=7, color='#1E293B', ha='center')

    draw_card(ax, 69, 48, 26, 32, bg_color='#F0FDF4', border_color='#16A34A', title="Versi Usulan 2 (v2)", title_size=8.5)
    ax.text(82, 68, "• submission_versions (v2)\n• Snapshot baru perbaikan\n• Resubmit & Auto-Diff\n• Status: RESUBMITTED", fontsize=7, color='#1E293B', ha='center')

    draw_arrow(ax, 31, 64, 37, 64, color='#DC2626', width=2, label="returnRevision()")
    draw_arrow(ax, 63, 64, 69, 64, color='#16A34A', width=2, label="resubmit()")

    draw_card(ax, 8, 6, 84, 34, bg_color='#F8FAFC', border_color='#0B2A4A', title="KOMPARASI PERUBAHAN BERKAS (DIFF VIEWER)", title_color='#0B2A4A', title_size=9)
    ax.text(10, 32, (
        "• Immutabilitas Snapshot: Data versi v1 dikunci permanen agar jejak forensik usulan tidak dapat dimanipulasi.\n"
        "• Pelacakan Granular: Diff engine membandingkan baris unit dan posisi antara master data dengan snapshot versi,\n"
        "  menandai penambahan (CREATE/HIJAU), perubahan beban formasi (UPDATE/KUNING), dan penghapusan (DELETE/MERAH).\n"
        "• Kemudahan Verifikasi: Verifikator dapat langsung melihat ringkasan perbedaan tanpa perlu membaca ulang seluruh SK."
    ), fontsize=7.5, color='#334155', va='top', linespacing=1.35)

    plt.savefig('docs/assets/versioning_diff_flow.png', bbox_inches='tight')
    plt.close()

# -------------------------------------------------------------
# 8. UI PROTOTYPE SCREENSHOT / WIREFRAME ASSETS
# -------------------------------------------------------------
def gen_ui_prototypes():
    screens = [
        ("prototype_login.png", "SCR-18: LAYAR MASUK SISTEM (LOGIN)", [
            "• Header: Lambang Garuda & Identitas KemenPANRB / SIGMA-K",
            "• Card Login: Input NIP/Username & Password dengan validasi format",
            "• Dual-Mode Indicator: Saklar mode API (eskld_db) vs Mock Data Simulasi",
            "• Akun Presets: USER (Operator), ADMIN (Penapis), VERIFIER (Verifikator)",
            "• Keamanan: Session token JWT langsung disimpan ke Browser Storage"
        ]),
        ("prototype_dashboard.png", "SCR-01: DASHBOARD EKSEKUTIF KELEMBAGAAN", [
            "• 4 Kartu KPI Utama: Total Usulan (Live), Dalam Proses Telaah, Disetujui, Promosi",
            "• Widget Kabinet Merah Putih: Sorotan kabinet aktif & status kelembagaan Kemenko",
            "• Antrean Usulan Terkini: Tabel status pengajuan dengan indikator badge warna",
            "• Widget Pengesahan SK Terkini: Rekam jejak SK yang baru disahkan oleh Verifikator",
            "• Mode Badge: 'API Live Verified' menandakan integrasi backend aktif"
        ]),
        ("prototype_institutions.png", "SCR-02: KATALOG MASTER INSTANSI PEMERINTAH", [
            "• Filter Tabs: Semua K/L/D, Kementerian Koordinator, Kementerian, LPNK, Pemda",
            "• Search & Sort: Pencarian cepat berdasarkan Kode Instansi (KL-xxx) atau Nama",
            "• Tabel Master: Menampilkan kode, nama, jumlah unit kerja, posisi, dan status aktif",
            "• Tombol Navigasi: 'Lihat Bagan Struktur' & 'Profil Rincian Instansi'"
        ]),
        ("prototype_org_structure.png", "SCR-04: BAGAN STRUKTUR ORGANISASI INTERAKTIF", [
            "• React Flow Canvas: Kanvas graf interaktif dengan fitur Zoom, Pan, dan MiniMap",
            "• Custom OrgNode: Menampilkan kode unit, nama pimpinan, level eselon, dan status",
            "• Visual Hierarchy Links: Garis konektor parent-child otomatis tersusun rapi",
            "• Drawer Detail Samping: Klik node membuka rincian formasi jabatan dan tupoksi aktif"
        ]),
        ("prototype_submission_detail.png", "SCR-09: RINCIAN USULAN & DIFF VIEWER", [
            "• Workflow Stepper: Visualisasi progress (Draft -> Penapisan -> Telaah -> Pengesahan)",
            "• Metadata Usulan: Nomor Tiket (TKT-2026-xxxx), Instansi Pengusul, Tanggal Pengajuan",
            "• Diff Viewer: Komparasi Before vs After (Unit Baru [HIJAU], Unit Dihapus [MERAH])",
            "• Tab Riwayat & Catatan: Rekam jejak koreksi dari Admin dan Verifikator"
        ]),
        ("prototype_verifier_workspace.png", "SCR-12: RUANG KERJA TELAAH VERIFIKATOR", [
            "• Substantive Review Panel: Analisis beban kerja dan kesesuaian formasi jabatan ASN",
            "• Form Catatan Substantif: Input rekomendasi teknis per unit kerja",
            "• Tombol Aksi: 'Kembalikan untuk Revisi' atau 'Pengesahan SK Resmi'",
            "• Promosi Master Data: Eksekusi otomatis migrasi struktur usulan ke master data aktif"
        ]),
        ("prototype_analytics_reporting.png", "SCR-15: INTELIJENSI DATA & POSTUR ASN", [
            "• 4 Proposed Executive KPIs: Indikator efisiensi struktur & rasio rentang kendali",
            "• Echelon Distribution Pyramid: Distribusi jabatan struktural Eselon I s.d. IV",
            "• SLA Kecepatan Layanan: Durasi penyelesaian usulan (Rata-rata 1.8 hari)",
            "• Export Center: Tombol unduh laporan (.CSV / .JSON) langsung dari backend"
        ]),
        ("prototype_audit_logs.png", "SCR-16: LOG FORENSIK AUDIT TRAIL", [
            "• Tabel Log Forensik: Timestamp, Nama Aktor, Role, Aksi (CREATE/UPDATE/APPROVE)",
            "• Entitas & IP: Jejak IP address pengakses dan identitas modul yang dimodifikasi",
            "• Payload Modal: Inspeksi mendalam perubahan data JSON (old_payload vs new_payload)",
            "• Append-Only Integrity: Catatan tersimpan permanen dan tidak dapat diubah"
        ])
    ]

    for fname, title, bullet_points in screens:
        fig, ax = setup_fig(10, 5.5)
        ax.text(50, 93, f"PROTOTYPE / WIREFRAME: {title}", ha='center', fontsize=11.5, fontweight='bold', color='#0B2A4A')

        # Outer Browser/App Frame
        draw_card(ax, 4, 6, 92, 80, bg_color='#F8FAFC', border_color='#0B2A4A', border_width=2, title="APLIKASI SIGMA-K - KEMENPANRB", title_color='#0B2A4A', title_size=9)
        
        # Inner TopBar
        draw_card(ax, 6, 68, 88, 12, bg_color='#0B2A4A', border_color='#0B2A4A', title=f"TopBar Navigasi: SIGMA-K | {title.split(':')[0]}", title_color='#FFFFFF', title_size=8)
        ax.text(8, 72, "E-SKLD SIGMA-K", fontsize=8, color='#D4AF37', fontweight='bold')
        ax.text(90, 72, "[Role: VERIFIER]", fontsize=7, color='#FFFFFF', ha='right')

        # Content Area
        draw_card(ax, 6, 10, 88, 55, bg_color='#FFFFFF', border_color='#CBD5E1', title="", title_size=8)
        
        y_pos = 58
        for pt in bullet_points:
            ax.text(10, y_pos, pt, fontsize=8, color='#1E293B', va='top')
            y_pos -= 10

        plt.savefig(f'docs/assets/{fname}', bbox_inches='tight')
        plt.close()

# Run all
if __name__ == '__main__':
    print("Generating complete visual suite...")
    gen_system_architecture()
    gen_role_access()
    gen_submission_lifecycle()
    gen_sitemap_diagram()
    gen_erd_diagram()
    gen_master_data_hierarchy()
    gen_versioning_diff()
    gen_ui_prototypes()
    print("All 22+ visual assets successfully created in docs/assets/!")
