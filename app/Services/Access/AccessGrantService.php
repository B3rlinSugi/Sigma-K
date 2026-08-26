<?php

namespace App\Services\Access;

use App\Entities\UserEntity;
use App\Models\AccessGrantModel;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AuthorizationService;
use Exception;
use Throwable;

/**
 * AccessGrantService
 *
 * Handles inspection, listing, and revocation of active delegated access grants.
 */
class AccessGrantService
{
    protected AccessGrantModel $grantModel;
    protected ApproverResolutionService $approverService;
    protected AuthorizationService $authzService;
    protected AuditService $auditService;

    public function __construct(
        ?AccessGrantModel $grantModel = null,
        ?ApproverResolutionService $approverService = null,
        ?AuthorizationService $authzService = null,
        ?AuditService $auditService = null
    ) {
        $this->grantModel      = $grantModel ?? new AccessGrantModel();
        $this->approverService = $approverService ?? new ApproverResolutionService();
        $this->authzService    = $authzService ?? new AuthorizationService();
        $this->auditService    = $auditService ?? new AuditService();
    }

    /**
     * List access grants belonging to the current user.
     *
     * @param UserEntity $user
     * @return array
     */
    public function listMyGrants(UserEntity $user): array
    {
        return $this->grantModel->getActiveGrantsForUser((int)$user->id);
    }

    /**
     * Revoke an active access grant before its expiration date.
     *
     * @param UserEntity $actor
     * @param int        $grantId
     * @param string     $revokeReason
     * @return array
     * @throws Exception
     */
    public function revokeGrant(UserEntity $actor, int $grantId, string $revokeReason): array
    {
        $grant = $this->grantModel->find($grantId);
        if (!$grant) {
            throw new Exception('Access grant not found.');
        }

        if ($grant['status'] !== 'ACTIVE') {
            throw new Exception("Access grant cannot be revoked because current status is '{$grant['status']}'.");
        }

        // 1. Authorization: Verify actor has authority to revoke this grant
        if (!$this->approverService->canRevokeGrant($actor, $grant)) {
            throw new Exception('FORBIDDEN');
        }

        $revokeReason = trim($revokeReason);
        if (empty($revokeReason)) {
            throw new Exception('Revocation reason is required.');
        }

        // 2. Execute Atomic Database Transaction
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $this->grantModel->update($grantId, [
                'status'        => 'REVOKED',
                'revoked_by'    => (int)$actor->id,
                'revoked_at'    => date('Y-m-d H:i:s'),
                'revoke_reason' => $revokeReason,
            ]);

            $actorRole = $this->authzService->getUserRoleCode($actor);
            $this->auditService->log([
                'actor_id'        => $actor->id,
                'actor_role'      => $actorRole,
                'action_event'    => 'REVOKE_ACCESS',
                'resource_entity' => 'access_grants',
                'resource_id'     => $grantId,
                'payload_new'     => json_encode([
                    'status'        => 'REVOKED',
                    'revoked_by'    => (int)$actor->id,
                    'revoke_reason' => $revokeReason,
                ]),
            ]);

            $db->transCommit();
            return [
                'grantId' => $grantId,
                'status'  => 'REVOKED',
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
