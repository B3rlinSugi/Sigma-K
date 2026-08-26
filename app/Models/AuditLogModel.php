<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AuditLogModel
 *
 * Append-only Data Access Layer for audit_logs table.
 */
class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'actor_id',
        'actor_role',
        'action_event',
        'resource_entity',
        'resource_id',
        'payload_old',
        'payload_new',
        'ip_address',
        'user_agent',
        'reason',
        'created_at',
    ];
    protected $useTimestamps    = false; // Handled explicitly or via DEFAULT CURRENT_TIMESTAMP

    /**
     * Prevent application-level update on audit_logs.
     */
    public function update($id = null, $data = null): bool
    {
        throw new \BadMethodCallException('Audit log is strictly append-only and cannot be updated.');
    }

    /**
     * Prevent application-level delete on audit_logs.
     */
    public function delete($id = null, bool $purge = false): bool
    {
        throw new \BadMethodCallException('Audit log is strictly append-only and cannot be deleted.');
    }
}
