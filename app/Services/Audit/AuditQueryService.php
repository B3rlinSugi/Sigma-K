<?php

namespace App\Services\Audit;

use App\Entities\UserEntity;
use App\Models\AuditLogModel;
use App\Services\Authorization\AuthorizationService;
use App\Services\Authorization\ScopeResolver;
use Exception;

/**
 * AuditQueryService
 *
 * Query and reporting service for viewing and exporting immutable audit logs
 * with strict role-based multi-tenant isolation.
 */
class AuditQueryService
{
    protected AuditLogModel $auditModel;
    protected AuthorizationService $authzService;
    protected ScopeResolver $scopeResolver;

    public function __construct(
        ?AuditLogModel $auditModel = null,
        ?AuthorizationService $authzService = null,
        ?ScopeResolver $scopeResolver = null
    ) {
        $this->auditModel    = $auditModel ?? new AuditLogModel();
        $this->authzService  = $authzService ?? new AuthorizationService();
        $this->scopeResolver = $scopeResolver ?? new ScopeResolver();
    }

    /**
     * Get paginated audit logs scoped to the authenticated user's permissions.
     *
     * @param UserEntity $actor
     * @param array      $params
     * @return array
     * @throws Exception
     */
    public function getLogs(UserEntity $actor, array $params = []): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        $authorizedInstIds = $this->scopeResolver->getAuthorizedInstitutionIds($actor, $roleCode);

        // Security check: If explicit institution_id filter is requested by non-SuperAdmin
        if (!empty($params['institution_id']) && $authorizedInstIds !== null) {
            $reqInstId = (int)$params['institution_id'];
            if (!in_array($reqInstId, $authorizedInstIds, true)) {
                throw new Exception('FORBIDDEN');
            }
        }

        $page    = max(1, (int)($params['page'] ?? 1));
        $perPage = min(100, max(1, (int)($params['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        $db = \Config\Database::connect();

        // 1. Build Count Query
        $countBuilder = $this->buildQuery($db, $actor, $roleCode, $authorizedInstIds, $params);
        $totalRecords = $countBuilder->countAllResults(false);

        // 2. Build Data Query
        $dataBuilder = $this->buildQuery($db, $actor, $roleCode, $authorizedInstIds, $params);
        $dataBuilder->select('al.id, al.actor_id, al.actor_role, al.action_event, al.resource_entity, al.resource_id, al.ip_address, al.user_agent, al.reason, al.created_at, u.username as actor_username, u.full_name as actor_name, u.home_institution_id as actor_institution_id')
            ->orderBy('al.id', 'DESC')
            ->limit($perPage, $offset);

        $results = $dataBuilder->get()->getResultArray();

        // Sanitize output
        foreach ($results as &$row) {
            $row['id']          = (int)$row['id'];
            $row['actor_id']    = $row['actor_id'] !== null ? (int)$row['actor_id'] : null;
            $row['resource_id'] = $row['resource_id'] !== null ? (int)$row['resource_id'] : null;
        }
        unset($row);

        return [
            'data'       => $results,
            'pagination' => [
                'total'       => $totalRecords,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => $perPage > 0 ? (int)ceil($totalRecords / $perPage) : 1,
            ],
        ];
    }

    /**
     * Get a single audit log entry by ID with authorization check.
     *
     * @param UserEntity $actor
     * @param int        $id
     * @return array
     * @throws Exception
     */
    public function getLogById(UserEntity $actor, int $id): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        $authorizedInstIds = $this->scopeResolver->getAuthorizedInstitutionIds($actor, $roleCode);

        $db = \Config\Database::connect();
        $builder = $db->table('audit_logs al')
            ->select('al.*, u.username as actor_username, u.full_name as actor_name, u.home_institution_id as actor_institution_id')
            ->join('users u', 'al.actor_id = u.id', 'left')
            ->where('al.id', $id);

        $log = $builder->get()->getRowArray();
        if (!$log) {
            throw new Exception('NOT_FOUND');
        }

        // Authorization check on single log
        if (!$this->canViewLog($actor, $roleCode, $authorizedInstIds, $log)) {
            throw new Exception('FORBIDDEN');
        }

        // Decode JSON payloads if valid
        $log['id']          = (int)$log['id'];
        $log['actor_id']    = $log['actor_id'] !== null ? (int)$log['actor_id'] : null;
        $log['resource_id'] = $log['resource_id'] !== null ? (int)$log['resource_id'] : null;

        if (!empty($log['payload_old']) && is_string($log['payload_old'])) {
            $decodedOld = json_decode($log['payload_old'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $log['payload_old'] = $decodedOld;
            }
        }

        if (!empty($log['payload_new']) && is_string($log['payload_new'])) {
            $decodedNew = json_decode($log['payload_new'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $log['payload_new'] = $decodedNew;
            }
        }

        return $log;
    }

    /**
     * Export audit logs as an array formatted for CSV or JSON.
     *
     * @param UserEntity $actor
     * @param array      $params
     * @param int        $maxLimit
     * @return array
     * @throws Exception
     */
    public function exportLogs(UserEntity $actor, array $params = [], int $maxLimit = 1000): array
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);
        $authorizedInstIds = $this->scopeResolver->getAuthorizedInstitutionIds($actor, $roleCode);

        $db = \Config\Database::connect();
        $builder = $this->buildQuery($db, $actor, $roleCode, $authorizedInstIds, $params);
        $builder->select('al.id, al.created_at, al.actor_id, u.username as actor_username, u.full_name as actor_name, al.actor_role, al.action_event, al.resource_entity, al.resource_id, al.ip_address, al.reason')
            ->orderBy('al.id', 'DESC')
            ->limit($maxLimit);

        return $builder->get()->getResultArray();
    }

    /**
     * Internal query builder applying role-based scoping and query filters.
     */
    protected function buildQuery($db, UserEntity $actor, string $roleCode, ?array $authorizedInstIds, array $params)
    {
        $builder = $db->table('audit_logs al')
            ->join('users u', 'al.actor_id = u.id', 'left');

        // Scoped access restriction
        if ($roleCode === 'USER') {
            // User can only see events where they were the actor OR events on resources in their home institution
            $homeId = (int)$actor->home_institution_id;
            $builder->groupStart()
                ->where('al.actor_id', (int)$actor->id)
                ->orGroupStart()
                    ->where('u.home_institution_id', $homeId)
                    ->whereIn('al.resource_entity', ['submissions', 'access_requests'])
                ->groupEnd()
            ->groupEnd();
        } elseif (in_array($roleCode, ['ADMIN', 'VERIFIER'], true)) {
            // Admin & Verifier can see logs within their authorized institution scope or actions they performed
            if (!empty($authorizedInstIds)) {
                $builder->groupStart()
                    ->where('al.actor_id', (int)$actor->id)
                    ->orWhereIn('u.home_institution_id', $authorizedInstIds)
                ->groupEnd();
            } else {
                $builder->where('al.actor_id', (int)$actor->id);
            }
        }
        // SUPER_ADMIN has global access (no mandatory scope filter)

        // Apply Optional Query Filters
        if (!empty($params['actor_id'])) {
            $builder->where('al.actor_id', (int)$params['actor_id']);
        }
        if (!empty($params['actor_role'])) {
            $builder->where('al.actor_role', trim((string)$params['actor_role']));
        }
        if (!empty($params['action_event'])) {
            $builder->where('al.action_event', trim((string)$params['action_event']));
        }
        if (!empty($params['resource_entity'])) {
            $builder->where('al.resource_entity', trim((string)$params['resource_entity']));
        }
        if (!empty($params['resource_id'])) {
            $builder->where('al.resource_id', (int)$params['resource_id']);
        }
        if (!empty($params['institution_id'])) {
            $builder->where('u.home_institution_id', (int)$params['institution_id']);
        }
        if (!empty($params['date_from'])) {
            $builder->where('al.created_at >=', trim((string)$params['date_from']) . ' 00:00:00');
        }
        if (!empty($params['date_to'])) {
            $builder->where('al.created_at <=', trim((string)$params['date_to']) . ' 23:59:59');
        }
        if (!empty($params['search'])) {
            $search = trim((string)$params['search']);
            $builder->groupStart()
                ->like('al.action_event', $search)
                ->orLike('al.resource_entity', $search)
                ->orLike('al.reason', $search)
                ->orLike('u.username', $search)
                ->orLike('u.full_name', $search)
            ->groupEnd();
        }

        return $builder;
    }

    /**
     * Check if a user can view a specific audit log entry.
     */
    protected function canViewLog(UserEntity $actor, string $roleCode, ?array $authorizedInstIds, array $log): bool
    {
        if ($roleCode === 'SUPER_ADMIN') {
            return true;
        }

        if ((int)$log['actor_id'] === (int)$actor->id) {
            return true;
        }

        $actorInstId = !empty($log['actor_institution_id']) ? (int)$log['actor_institution_id'] : null;

        if ($roleCode === 'USER') {
            return ($actorInstId !== null && $actorInstId === (int)$actor->home_institution_id);
        }

        if (in_array($roleCode, ['ADMIN', 'VERIFIER'], true) && $authorizedInstIds !== null) {
            return ($actorInstId !== null && in_array($actorInstId, $authorizedInstIds, true));
        }

        return false;
    }
}
