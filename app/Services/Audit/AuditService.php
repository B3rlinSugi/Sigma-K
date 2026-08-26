<?php

namespace App\Services\Audit;

use App\Models\AuditLogModel;
use Throwable;

/**
 * AuditService
 *
 * Central service for recording immutable audit trail logs across all system events.
 */
class AuditService
{
    protected AuditLogModel $auditModel;

    public function __construct(?AuditLogModel $auditModel = null)
    {
        $this->auditModel = $auditModel ?? new AuditLogModel();
    }

    /**
     * Record an audit event into audit_logs table.
     *
     * @param array $data
     * @return bool
     */
    public function log(array $data): bool
    {
        try {
            $record = [
                'actor_id'        => $data['actor_id'] ?? null,
                'actor_role'      => $data['actor_role'] ?? null,
                'action_event'    => $data['action_event'] ?? 'UNKNOWN_EVENT',
                'resource_entity' => $data['resource_entity'] ?? 'system',
                'resource_id'     => $data['resource_id'] ?? null,
                'payload_old'     => isset($data['payload_old']) ? (is_string($data['payload_old']) ? $data['payload_old'] : json_encode($data['payload_old'])) : null,
                'payload_new'     => isset($data['payload_new']) ? (is_string($data['payload_new']) ? $data['payload_new'] : json_encode($data['payload_new'])) : null,
                'ip_address'      => $data['ip_address'] ?? service('request')->getIPAddress(),
                'user_agent'      => $data['user_agent'] ?? (string)service('request')->getUserAgent(),
                'reason'          => $data['reason'] ?? null,
                'created_at'      => date('Y-m-d H:i:s'),
            ];

            return (bool)$this->auditModel->insert($record);
        } catch (Throwable $e) {
            log_message('error', '[AuditService] Failed to record audit log: ' . $e->getMessage());
            return false;
        }
    }
}
