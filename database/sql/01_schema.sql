-- =============================================================================
-- E-SKLD KEMENPANRB — PHYSICAL DATABASE SCHEMA V1
-- Target Database: MySQL 8.x
-- Storage Engine : InnoDB
-- Charset        : utf8mb4
-- Collation      : utf8mb4_unicode_ci
-- Timezone       : UTC (Strict Standard)
-- Total Tables   : 21 Tables
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. ROLES (RBAC Master Reference)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_code` VARCHAR(50) NOT NULL,
    `role_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UK_roles_role_code` (`role_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. PERMISSIONS (RBAC Atomic Permissions)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `permission_code` VARCHAR(50) NOT NULL,
    `permission_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UK_permissions_code` (`permission_code`),
    KEY `idx_permissions_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. ROLE_PERMISSIONS (RBAC Junction)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UK_role_permission` (`role_id`, `permission_id`),
    KEY `idx_rp_role` (`role_id`),
    KEY `idx_rp_permission` (`permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. INSTITUTIONS (Governance Master Data K/L/D)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `institutions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `institution_code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `short_name` VARCHAR(100) NULL DEFAULT NULL,
    `category` VARCHAR(50) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UK_institutions_code` (`institution_code`),
    KEY `idx_institutions_name` (`name`),
    KEY `idx_institutions_cat` (`category`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. USERS (Identity & Authentication)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `home_institution_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `username` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(200) NOT NULL,
    `nip` VARCHAR(50) NULL DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UK_users_username` (`username`),
    UNIQUE KEY `UK_users_email` (`email`),
    UNIQUE KEY `UK_users_nip` (`nip`),
    KEY `idx_users_login` (`username`, `status`),
    KEY `idx_users_email` (`email`, `status`),
    KEY `idx_users_home_inst` (`home_institution_id`, `status`),
    KEY `idx_users_role` (`role_id`),
    CONSTRAINT `fk_users_home_institution` FOREIGN KEY (`home_institution_id`) REFERENCES `institutions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. USER_SCOPES (Multi-Institution Regional & Cluster Scope)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_scopes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `institution_id` BIGINT UNSIGNED NOT NULL,
    `scope_type` VARCHAR(50) NOT NULL DEFAULT 'ADMIN_SCOPE',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    `assigned_by` BIGINT UNSIGNED NOT NULL,
    `revoked_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `revoked_at` DATETIME NULL DEFAULT NULL,
    `revoke_reason` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UK_user_inst_scope_active` (`user_id`, `institution_id`, `start_date`),
    KEY `idx_user_scopes_eval` (`user_id`, `institution_id`, `status`, `start_date`, `end_date`),
    KEY `idx_user_scopes_inst` (`institution_id`),
    KEY `idx_user_scopes_assigned_by` (`assigned_by`),
    KEY `idx_user_scopes_revoked_by` (`revoked_by`),
    CONSTRAINT `fk_user_scopes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_scopes_institution` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_scopes_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_user_scopes_revoked_by` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk_user_scopes_date` CHECK (`end_date` >= `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. ACCESS_REQUESTS (Cross-Institution Access Application)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `access_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `target_institution_id` BIGINT UNSIGNED NOT NULL,
    `reason` TEXT NOT NULL,
    `requested_start_date` DATE NOT NULL,
    `requested_end_date` DATE NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    `reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `reviewed_at` DATETIME NULL DEFAULT NULL,
    `review_notes` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_access_req_status` (`user_id`, `target_institution_id`, `status`),
    KEY `idx_access_req_target` (`target_institution_id`),
    KEY `idx_access_req_reviewed_by` (`reviewed_by`),
    CONSTRAINT `fk_access_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_access_requests_target` FOREIGN KEY (`target_institution_id`) REFERENCES `institutions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_access_requests_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk_access_requests_date` CHECK (`requested_end_date` >= `requested_start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. ACCESS_REQUEST_PERMISSIONS (Atomic Permissions for Request)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `access_request_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UK_req_perm` (`request_id`, `permission_id`),
    KEY `idx_arp_request` (`request_id`),
    KEY `idx_arp_permission` (`permission_id`),
    CONSTRAINT `fk_arp_request` FOREIGN KEY (`request_id`) REFERENCES `access_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_arp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. ACCESS_GRANTS (Time-Bound Delegated Access Grant)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `access_grants` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `target_institution_id` BIGINT UNSIGNED NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    `granted_by` BIGINT UNSIGNED NOT NULL,
    `grant_reason` TEXT NOT NULL,
    `revoked_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `revoked_at` DATETIME NULL DEFAULT NULL,
    `revoke_reason` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_active_grants_eval` (`user_id`, `target_institution_id`, `status`, `start_date`, `end_date`),
    KEY `idx_access_grants_target` (`target_institution_id`),
    KEY `idx_access_grants_request` (`request_id`),
    KEY `idx_access_grants_granted_by` (`granted_by`),
    KEY `idx_access_grants_revoked_by` (`revoked_by`),
    CONSTRAINT `fk_access_grants_request` FOREIGN KEY (`request_id`) REFERENCES `access_requests` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_access_grants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_access_grants_target` FOREIGN KEY (`target_institution_id`) REFERENCES `institutions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_access_grants_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_access_grants_revoked_by` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk_access_grants_date` CHECK (`end_date` >= `start_date`),
    CONSTRAINT `chk_access_grants_no_self_grant` CHECK (`granted_by` != `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. ACCESS_GRANT_PERMISSIONS (Atomic Permissions for Grant)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `access_grant_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `grant_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UK_grant_perm` (`grant_id`, `permission_id`),
    KEY `idx_agp_grant` (`grant_id`),
    KEY `idx_agp_permission` (`permission_id`),
    CONSTRAINT `fk_agp_grant` FOREIGN KEY (`grant_id`) REFERENCES `access_grants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_agp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. ORGANIZATIONAL_UNITS (Active Master Tree Hierarchy)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `organizational_units` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `institution_id` BIGINT UNSIGNED NOT NULL,
    `parent_unit_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `unit_code` VARCHAR(50) NOT NULL,
    `unit_name` VARCHAR(255) NOT NULL,
    `unit_level` INT NOT NULL DEFAULT 1,
    `order_index` INT NOT NULL DEFAULT 0,
    `status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_org_units_tree` (`institution_id`, `parent_unit_id`, `order_index`),
    KEY `idx_org_units_parent` (`parent_unit_id`),
    CONSTRAINT `fk_org_units_institution` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_org_units_parent` FOREIGN KEY (`parent_unit_id`) REFERENCES `organizational_units` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 12. POSITIONS (Active Master Position Formations)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `positions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `unit_id` BIGINT UNSIGNED NOT NULL,
    `position_name` VARCHAR(255) NOT NULL,
    `position_type` VARCHAR(50) NOT NULL,
    `echelon` VARCHAR(20) NULL DEFAULT NULL,
    `formation_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_positions_unit` (`unit_id`, `status`),
    CONSTRAINT `fk_positions_unit` FOREIGN KEY (`unit_id`) REFERENCES `organizational_units` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 13. SUBMISSIONS (Proposal Header Workspace)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `submissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `institution_id` BIGINT UNSIGNED NOT NULL,
    `author_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `submission_year` SMALLINT UNSIGNED NOT NULL,
    `current_state` VARCHAR(50) NOT NULL DEFAULT 'DRAFT',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_submissions_queue` (`institution_id`, `current_state`),
    KEY `idx_submissions_author` (`author_id`),
    CONSTRAINT `fk_submissions_institution` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_submissions_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 14. SUBMISSION_VERSIONS (Immutable Snapshot Header)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `submission_versions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `submission_id` BIGINT UNSIGNED NOT NULL,
    `version_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `notes` TEXT NULL DEFAULT NULL,
    `submitted_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UK_sub_version` (`submission_id`, `version_number`),
    KEY `idx_sub_versions_lookup` (`submission_id`, `version_number`),
    CONSTRAINT `fk_sub_versions_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 15. SUBMISSION_UNITS (Normalized Relational Snapshot Units)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `submission_units` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `version_id` BIGINT UNSIGNED NOT NULL,
    `temp_parent_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `source_unit_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `unit_code` VARCHAR(50) NOT NULL,
    `unit_name` VARCHAR(255) NOT NULL,
    `unit_level` INT NOT NULL DEFAULT 1,
    `order_index` INT NOT NULL DEFAULT 0,
    `change_type` VARCHAR(20) NOT NULL DEFAULT 'UNCHANGED',
    PRIMARY KEY (`id`),
    KEY `idx_snapshot_units_tree` (`version_id`, `temp_parent_id`, `order_index`),
    KEY `idx_snapshot_units_parent` (`temp_parent_id`),
    KEY `idx_snapshot_units_source` (`source_unit_id`),
    CONSTRAINT `fk_sub_units_version` FOREIGN KEY (`version_id`) REFERENCES `submission_versions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sub_units_temp_parent` FOREIGN KEY (`temp_parent_id`) REFERENCES `submission_units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sub_units_source` FOREIGN KEY (`source_unit_id`) REFERENCES `organizational_units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 16. SUBMISSION_POSITIONS (Normalized Relational Snapshot Positions)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `submission_positions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `version_unit_id` BIGINT UNSIGNED NOT NULL,
    `source_position_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `position_name` VARCHAR(255) NOT NULL,
    `position_type` VARCHAR(50) NOT NULL,
    `echelon` VARCHAR(20) NULL DEFAULT NULL,
    `formation_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `change_type` VARCHAR(20) NOT NULL DEFAULT 'UNCHANGED',
    PRIMARY KEY (`id`),
    KEY `idx_snapshot_pos_unit` (`version_unit_id`),
    KEY `idx_snapshot_pos_source` (`source_position_id`),
    CONSTRAINT `fk_sub_pos_unit` FOREIGN KEY (`version_unit_id`) REFERENCES `submission_units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sub_pos_source` FOREIGN KEY (`source_position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 17. VERIFIER_ASSIGNMENTS (Gate 2 Assignment Queue)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `verifier_assignments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `submission_id` BIGINT UNSIGNED NOT NULL,
    `verifier_id` BIGINT UNSIGNED NOT NULL,
    `assigned_by` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'ASSIGNED',
    `assignment_notes` TEXT NULL DEFAULT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_verifier_workload` (`verifier_id`, `status`),
    KEY `idx_sub_assignments` (`submission_id`, `status`),
    KEY `idx_verifier_assigned_by` (`assigned_by`),
    CONSTRAINT `fk_vassign_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_vassign_verifier` FOREIGN KEY (`verifier_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_vassign_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 18. VERIFICATION_RECORDS (Gate 1 & Gate 2 Historical Log)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `verification_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `version_id` BIGINT UNSIGNED NOT NULL,
    `reviewer_id` BIGINT UNSIGNED NOT NULL,
    `gate_level` VARCHAR(20) NOT NULL,
    `verification_result` VARCHAR(30) NOT NULL,
    `general_notes` TEXT NULL DEFAULT NULL,
    `verified_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_verification_history` (`version_id`, `gate_level`, `verified_at`),
    KEY `idx_verification_reviewer` (`reviewer_id`),
    CONSTRAINT `fk_verif_records_version` FOREIGN KEY (`version_id`) REFERENCES `submission_versions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_verif_records_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 19. REVISION_NOTES (Granular Issue Feedback Log)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `revision_notes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `verification_id` BIGINT UNSIGNED NOT NULL,
    `version_unit_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `issue_description` TEXT NOT NULL,
    `is_resolved` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rev_notes_unit` (`verification_id`, `version_unit_id`),
    KEY `idx_rev_notes_vunit` (`version_unit_id`),
    CONSTRAINT `fk_rev_notes_verification` FOREIGN KEY (`verification_id`) REFERENCES `verification_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rev_notes_unit` FOREIGN KEY (`version_unit_id`) REFERENCES `submission_units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 20. APPROVAL_RECORDS (Authoritative Final Approval Evidence)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `approval_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `version_id` BIGINT UNSIGNED NOT NULL,
    `approver_id` BIGINT UNSIGNED NOT NULL,
    `approval_number` VARCHAR(100) NULL DEFAULT NULL,
    `approval_notes` TEXT NULL DEFAULT NULL,
    `approved_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `UK_approval_version` (`version_id`),
    UNIQUE KEY `UK_approval_number` (`approval_number`),
    KEY `idx_approval_approver` (`approver_id`, `approved_at`),
    CONSTRAINT `fk_approval_version` FOREIGN KEY (`version_id`) REFERENCES `submission_versions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_approval_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 21. AUDIT_LOGS (Immutable Append-Only Forensics Trail)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `actor_role` VARCHAR(50) NULL DEFAULT NULL,
    `action_event` VARCHAR(100) NOT NULL,
    `resource_entity` VARCHAR(100) NOT NULL,
    `resource_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `payload_old` JSON NULL DEFAULT NULL,
    `payload_new` JSON NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` VARCHAR(255) NULL DEFAULT NULL,
    `reason` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_investigation` (`resource_entity`, `resource_id`, `created_at`),
    KEY `idx_audit_actor` (`actor_id`, `action_event`, `created_at`),
    CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
