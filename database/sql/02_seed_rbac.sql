-- =============================================================================
-- E-SKLD KEMENPANRB — SEED DATA RBAC V1
-- Roles, Atomic Permissions, & Role-Permission Mappings
-- Based on: Role-Permission & State Machine Specification V1.1
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. SEED ROLES (4 Core Roles)
-- -----------------------------------------------------------------------------
INSERT INTO `roles` (`role_code`, `role_name`, `description`, `created_at`, `updated_at`)
VALUES
('USER', 'Instansi / Ortala', 'Pengguna instansi penyusun dan pengaju usulan data kelembagaan', NOW(), NOW()),
('ADMIN', 'Admin Gate 1 / Verifikator Awal', 'Pemeriksa administrasi, gatekeeper Gate 1, dan penugas verifikator', NOW(), NOW()),
('VERIFIER', 'Verifikator Gate 2 / Penilai Akhir', 'Penilai substansi kelembagaan dan pemegang otoritas tunggal final approval', NOW(), NOW()),
('SUPER_ADMIN', 'Administrator Sistem Global', 'Pengelola sistem, tata kelola akun, master scope, dan audit nasional', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    `role_name` = VALUES(`role_name`),
    `description` = VALUES(`description`),
    `updated_at` = NOW();

-- -----------------------------------------------------------------------------
-- 2. SEED PERMISSIONS (21 Atomic Permissions)
-- -----------------------------------------------------------------------------
INSERT INTO `permissions` (`permission_code`, `permission_name`, `category`, `description`, `created_at`, `updated_at`)
VALUES
-- Data Management
('VIEW', 'Lihat Data', 'DATA_MANAGEMENT', 'Melihat data kelembagaan dalam batas scope/grant', NOW(), NOW()),
('CREATE', 'Buat Draf', 'DATA_MANAGEMENT', 'Membuat draf usulan kelembagaan baru', NOW(), NOW()),
('EDIT', 'Ubah Draf/Revisi', 'DATA_MANAGEMENT', 'Mengubah isi data draf atau revisi usulan', NOW(), NOW()),
('DELETE_DRAFT', 'Hapus Draf', 'DATA_MANAGEMENT', 'Menghapus berkas usulan yang masih berstatus DRAFT', NOW(), NOW()),
('VIEW_HISTORY', 'Lihat Riwayat Versi', 'DATA_MANAGEMENT', 'Melihat riwayat snapshot versi pengajuan lama', NOW(), NOW()),
('EXPORT', 'Unduh Data/SOTK', 'DATA_MANAGEMENT', 'Mengunduh rekapitulasi data dan bagan struktur SOTK', NOW(), NOW()),

-- Workflow & Verification
('SUBMIT', 'Ajukan Berkas', 'WORKFLOW', 'Mengajukan berkas dari draf atau revisi ke Gate 1', NOW(), NOW()),
('REVIEW', 'Telaah Berkas', 'WORKFLOW', 'Membuka dan menelaah berkas masuk', NOW(), NOW()),
('RETURN_REVISION', 'Kembalikan Revisi', 'WORKFLOW', 'Mengembalikan berkas dengan catatan koreksi', NOW(), NOW()),
('FORWARD_TO_VERIFIER', 'Loloskan ke Gate 2', 'WORKFLOW', 'Meloloskan Gate 1 dan meneruskan berkas ke Gate 2', NOW(), NOW()),
('FORWARD_TO_USER', 'Teruskan Revisi ke User', 'WORKFLOW', 'Meneruskan catatan revisi Gate 2 ke meja User pengusul', NOW(), NOW()),
('ASSIGN_VERIFIER', 'Tugaskan Verifikator', 'WORKFLOW', 'Menugaskan Verifikator spesifik pada berkas Gate 1', NOW(), NOW()),
('REASSIGN_VERIFIER', 'Alihkan Penugasan Verifikator', 'WORKFLOW', 'Mengalihkan/override penugasan Verifikator', NOW(), NOW()),
('VERIFY', 'Validasi Substansi', 'WORKFLOW', 'Melakukan telaah teknis substansi kelembagaan', NOW(), NOW()),
('APPROVE', 'Persetujuan Final', 'WORKFLOW', 'Penetapan sah status APPROVED final', NOW(), NOW()),

-- System Administration & Governance
('MANAGE_USER', 'Kelola Pengguna', 'SYSTEM_ADMIN', 'Mengelola akun user dalam scope kewenangannya', NOW(), NOW()),
('GRANT_ACCESS', 'Terbitkan Access Grant', 'SYSTEM_ADMIN', 'Menerbitkan delegasi izin Access Grant berbatas waktu', NOW(), NOW()),
('REVOKE_ACCESS', 'Cabut Access Grant', 'SYSTEM_ADMIN', 'Mencabut Access Grant sebelum waktu habis', NOW(), NOW()),
('MANAGE_SCOPE', 'Kelola Scope Wilayah', 'SYSTEM_ADMIN', 'Menetapkan penugasan user_scopes Admin dan Verifikator', NOW(), NOW()),
('VIEW_AUDIT', 'Lihat Jejak Audit', 'SYSTEM_ADMIN', 'Membaca log audit sistem dan forensik nasional', NOW(), NOW()),
('MANAGE_MASTER_DATA', 'Kelola Master Data', 'SYSTEM_ADMIN', 'Mengelola data induk K/L/D, jabatan, eselon', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `permission_name` = VALUES(`permission_name`),
    `category` = VALUES(`category`),
    `description` = VALUES(`description`),
    `updated_at` = NOW();

-- -----------------------------------------------------------------------------
-- 3. SEED ROLE_PERMISSIONS MAPPING (Dynamic Code Lookup)
-- -----------------------------------------------------------------------------

-- A. USER ROLE MAPPINGS (7 Permissions)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.role_code = 'USER'
  AND p.permission_code IN (
      'VIEW',
      'CREATE',
      'EDIT',
      'DELETE_DRAFT',
      'SUBMIT',
      'VIEW_HISTORY',
      'EXPORT'
  );

-- B. ADMIN ROLE MAPPINGS (11 Permissions)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.role_code = 'ADMIN'
  AND p.permission_code IN (
      'VIEW',
      'REVIEW',
      'RETURN_REVISION',
      'FORWARD_TO_VERIFIER',
      'FORWARD_TO_USER',
      'ASSIGN_VERIFIER',
      'VIEW_HISTORY',
      'EXPORT',
      'MANAGE_USER',
      'GRANT_ACCESS',
      'REVOKE_ACCESS'
  );

-- C. VERIFIER ROLE MAPPINGS (7 Permissions - Strict Separated Authority)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.role_code = 'VERIFIER'
  AND p.permission_code IN (
      'VIEW',
      'REVIEW',
      'RETURN_REVISION',
      'VERIFY',
      'APPROVE',
      'VIEW_HISTORY',
      'EXPORT'
  );

-- D. SUPER_ADMIN ROLE MAPPINGS (13 Permissions - Global Governance)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.role_code = 'SUPER_ADMIN'
  AND p.permission_code IN (
      'VIEW',
      'FORWARD_TO_VERIFIER',
      'FORWARD_TO_USER',
      'ASSIGN_VERIFIER',
      'REASSIGN_VERIFIER',
      'VIEW_HISTORY',
      'EXPORT',
      'MANAGE_USER',
      'GRANT_ACCESS',
      'REVOKE_ACCESS',
      'MANAGE_SCOPE',
      'VIEW_AUDIT',
      'MANAGE_MASTER_DATA'
  );
