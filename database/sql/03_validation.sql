-- =============================================================================
-- E-SKLD KEMENPANRB — DATABASE SCHEMA & RBAC VALIDATION SCRIPT
-- Verification & Inspection Queries for MySQL 8.x
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. VALIDASI JUMLAH & DAFTAR TABEL (Harus Tepat 21 Tabel)
-- -----------------------------------------------------------------------------
SELECT 
    table_name AS `Table Name`,
    engine AS `Engine`,
    table_rows AS `Approx Rows`,
    table_collation AS `Collation`
FROM information_schema.tables
WHERE table_schema = DATABASE()
ORDER BY table_name ASC;

SELECT 
    COUNT(*) AS `Total Tables Count`,
    CASE WHEN COUNT(*) = 21 THEN 'VALID [21/21 Tables]' ELSE 'INVALID [Count Mismatch]' END AS `Status`
FROM information_schema.tables
WHERE table_schema = DATABASE();

-- -----------------------------------------------------------------------------
-- 2. VALIDASI FOREIGN KEY CONSTRAINTS (Integritas Relasional)
-- -----------------------------------------------------------------------------
SELECT 
    constraint_name AS `FK Constraint Name`,
    table_name AS `Child Table`,
    column_name AS `Child Column`,
    referenced_table_name AS `Parent Table`,
    referenced_column_name AS `Parent Column`
FROM information_schema.key_column_usage
WHERE table_schema = DATABASE()
  AND referenced_table_name IS NOT NULL
ORDER BY table_name, constraint_name;

SELECT 
    COUNT(*) AS `Total Foreign Keys Count`
FROM information_schema.key_column_usage
WHERE table_schema = DATABASE()
  AND referenced_table_name IS NOT NULL;

-- -----------------------------------------------------------------------------
-- 3. VALIDASI UNIQUE CONSTRAINTS (Anti-Duplikasi Data Bisnis)
-- -----------------------------------------------------------------------------
SELECT 
    table_name AS `Table Name`,
    constraint_name AS `Unique Constraint Name`
FROM information_schema.table_constraints
WHERE table_schema = DATABASE()
  AND constraint_type = 'UNIQUE'
ORDER BY table_name, constraint_name;

SELECT 
    COUNT(*) AS `Total Unique Constraints Count`
FROM information_schema.table_constraints
WHERE table_schema = DATABASE()
  AND constraint_type = 'UNIQUE';

-- -----------------------------------------------------------------------------
-- 4. VALIDASI INDEXES (Indeks Performa & Query Optimization)
-- -----------------------------------------------------------------------------
SELECT 
    table_name AS `Table Name`,
    index_name AS `Index Name`,
    GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ', ') AS `Indexed Columns`,
    non_unique AS `Non-Unique Flag`
FROM information_schema.statistics
WHERE table_schema = DATABASE()
GROUP BY table_name, index_name, non_unique
ORDER BY table_name, index_name;

-- -----------------------------------------------------------------------------
-- 5. VALIDASI SEED ROLES (Harus Tepat 4 Peran)
-- -----------------------------------------------------------------------------
SELECT 
    id,
    role_code,
    role_name,
    description
FROM `roles`
ORDER BY id ASC;

-- -----------------------------------------------------------------------------
-- 6. VALIDASI SEED PERMISSIONS (Harus Tepat 21 Izin Atomik)
-- -----------------------------------------------------------------------------
SELECT 
    category,
    COUNT(*) AS `Permission Count`,
    GROUP_CONCAT(permission_code ORDER BY permission_code SEPARATOR ', ') AS `Permissions`
FROM `permissions`
GROUP BY category
ORDER BY category ASC;

SELECT 
    COUNT(*) AS `Total Permissions Count`,
    CASE WHEN COUNT(*) = 21 THEN 'VALID [21/21 Permissions]' ELSE 'INVALID' END AS `Status`
FROM `permissions`;

-- -----------------------------------------------------------------------------
-- 7. VALIDASI ROLE-PERMISSION MAPPINGS (Matriks Peran x Hak Akses)
-- -----------------------------------------------------------------------------
SELECT 
    r.role_code AS `Role Code`,
    r.role_name AS `Role Name`,
    COUNT(rp.permission_id) AS `Total Assigned Permissions`,
    GROUP_CONCAT(p.permission_code ORDER BY p.permission_code SEPARATOR ', ') AS `Assigned Permissions List`
FROM `roles` r
LEFT JOIN `role_permissions` rp ON r.id = rp.role_id
LEFT JOIN `permissions` p ON rp.permission_id = p.id
GROUP BY r.id, r.role_code, r.role_name
ORDER BY r.id ASC;

-- -----------------------------------------------------------------------------
-- 8. INTEGRITY SANITY CHECKS (Separation of Duties & Anti-Privilege Escalation)
-- -----------------------------------------------------------------------------

-- Sanity Check 1: Pastikan hanya VERIFIER yang memiliki hak APPROVE
SELECT 
    r.role_code, 
    p.permission_code,
    CASE WHEN r.role_code = 'VERIFIER' THEN 'VALID (Exclusive Approver)' ELSE 'VIOLATION! Non-Verifier has APPROVE' END AS `SoD Check`
FROM `role_permissions` rp
JOIN `roles` r ON rp.role_id = r.id
JOIN `permissions` p ON rp.permission_id = p.id
WHERE p.permission_code = 'APPROVE';

-- Sanity Check 2: Pastikan hanya VERIFIER yang memiliki hak VERIFY
SELECT 
    r.role_code, 
    p.permission_code,
    CASE WHEN r.role_code = 'VERIFIER' THEN 'VALID (Exclusive Verifier)' ELSE 'VIOLATION! Non-Verifier has VERIFY' END AS `SoD Check`
FROM `role_permissions` rp
JOIN `roles` r ON rp.role_id = r.id
JOIN `permissions` p ON rp.permission_id = p.id
WHERE p.permission_code = 'VERIFY';

-- Sanity Check 3: Pastikan ADMIN tidak memiliki hak APPROVE atau VERIFY
SELECT 
    COUNT(*) AS `Admin Illegal Permissions Count`,
    CASE WHEN COUNT(*) = 0 THEN 'VALID (Admin Gate 1 Strictly Separated)' ELSE 'VIOLATION! Admin has illegal permissions' END AS `Status`
FROM `role_permissions` rp
JOIN `roles` r ON rp.role_id = r.id
JOIN `permissions` p ON rp.permission_id = p.id
WHERE r.role_code = 'ADMIN' 
  AND p.permission_code IN ('APPROVE', 'VERIFY', 'EDIT', 'CREATE');
