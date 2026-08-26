<?php

namespace App\Services\Access;

use App\Entities\UserEntity;
use App\Models\UserScopeModel;
use App\Services\Authorization\AuthorizationService;

/**
 * ApproverResolutionService
 *
 * Determines who possesses authority to review/approve/reject access requests
 * or revoke active access grants.
 */
class ApproverResolutionService
{
    protected UserScopeModel $scopeModel;
    protected AuthorizationService $authzService;

    public function __construct(
        ?UserScopeModel $scopeModel = null,
        ?AuthorizationService $authzService = null
    ) {
        $this->scopeModel   = $scopeModel ?? new UserScopeModel();
        $this->authzService = $authzService ?? new AuthorizationService();
    }

    /**
     * Determine if an actor has authority to review, approve, or reject an access request.
     *
     * @param UserEntity $actor
     * @param array      $accessRequest
     * @return bool
     */
    public function canReviewRequest(UserEntity $actor, array $accessRequest): bool
    {
        // 1. Hard Rule: Requester CANNOT review or approve their own request (Anti-Self-Approval)
        if ((int)$actor->id === (int)$accessRequest['user_id']) {
            return false;
        }

        $roleCode = $this->authzService->getUserRoleCode($actor);

        // 2. SUPER_ADMIN has global approval authority
        if ($roleCode === 'SUPER_ADMIN') {
            return true;
        }

        // 3. ADMIN must have the target institution in their active user_scopes
        if ($roleCode === 'ADMIN') {
            $targetInstitutionId = (int)$accessRequest['target_institution_id'];
            $activeScopes = $this->scopeModel->getActiveInstitutionIds((int)$actor->id);
            return in_array($targetInstitutionId, $activeScopes, true);
        }

        // 4. Other roles (USER, VERIFIER) cannot approve access requests
        return false;
    }

    /**
     * Determine if an actor has authority to revoke an active access grant.
     *
     * @param UserEntity $actor
     * @param array      $accessGrant
     * @return bool
     */
    public function canRevokeGrant(UserEntity $actor, array $accessGrant): bool
    {
        $roleCode = $this->authzService->getUserRoleCode($actor);

        // 1. SUPER_ADMIN has global revocation authority
        if ($roleCode === 'SUPER_ADMIN') {
            return true;
        }

        // 2. ADMIN must have the target institution in their active user_scopes
        if ($roleCode === 'ADMIN') {
            $targetInstitutionId = (int)$accessGrant['target_institution_id'];
            $activeScopes = $this->scopeModel->getActiveInstitutionIds((int)$actor->id);
            return in_array($targetInstitutionId, $activeScopes, true);
        }

        // 3. Other roles cannot revoke grants
        return false;
    }
}
