<?php

namespace App\Services\Authorization;

use App\Entities\UserEntity;
use App\Models\AccessGrantModel;
use App\Models\UserScopeModel;

/**
 * ScopeResolver
 *
 * Implements the 4-tier institutional authority resolution algorithm:
 * 1. Global Scope (SUPER_ADMIN)
 * 2. Home Institution Scope (USER)
 * 3. Assigned User Scopes (ADMIN / VERIFIER)
 * 4. Time-Bound Delegated Access Grants
 */
class ScopeResolver
{
    protected UserScopeModel $scopeModel;
    protected AccessGrantModel $grantModel;

    public function __construct(?UserScopeModel $scopeModel = null, ?AccessGrantModel $grantModel = null)
    {
        $this->scopeModel = $scopeModel ?? new UserScopeModel();
        $this->grantModel = $grantModel ?? new AccessGrantModel();
    }

    /**
     * Resolve whether a user has institutional scope authority for a specific action on a target institution.
     *
     * @param UserEntity  $user
     * @param int|null    $targetInstitutionId
     * @param string      $action Permission code (e.g. 'VIEW', 'EDIT')
     * @param string      $roleCode User's role code ('USER', 'ADMIN', 'VERIFIER', 'SUPER_ADMIN')
     * @return bool True if user possesses valid scope, false otherwise
     */
    public function resolveScope(UserEntity $user, ?int $targetInstitutionId, string $action, string $roleCode): bool
    {
        // 1. If no specific target institution is queried (e.g. global lists / system administration)
        if ($targetInstitutionId === null) {
            return true;
        }

        // 2. Tier 1: SUPER_ADMIN has global administrative scope
        if ($roleCode === 'SUPER_ADMIN') {
            return true;
        }

        // 3. Tier 2: Home Institution Scope (Applicable to USER and all roles within their home base)
        if ((int)$user->home_institution_id === $targetInstitutionId) {
            return true;
        }

        // 4. Tier 3: Assigned User Scopes for ADMIN and VERIFIER (Regional / Cluster assigned scope)
        if (in_array($roleCode, ['ADMIN', 'VERIFIER'], true)) {
            $activeScopeIds = $this->scopeModel->getActiveInstitutionIds((int)$user->id);
            if (in_array($targetInstitutionId, $activeScopeIds, true)) {
                return true;
            }
        }

        // 5. Tier 4: Time-Bound Delegated Access Grants (Atomic permission check)
        return $this->grantModel->hasActiveGrantPermission((int)$user->id, $targetInstitutionId, $action);
    }

    /**
     * Get list of all authorized institution IDs for a user.
     * Returns null if user is SUPER_ADMIN (unrestricted global scope).
     *
     * @param UserEntity $user
     * @param string     $roleCode
     * @return array<int>|null
     */
    public function getAuthorizedInstitutionIds(UserEntity $user, string $roleCode): ?array
    {
        if ($roleCode === 'SUPER_ADMIN') {
            return null; // Null indicates global unrestricted access
        }

        $ids = [];
        if (!empty($user->home_institution_id)) {
            $ids[] = (int)$user->home_institution_id;
        }

        if (in_array($roleCode, ['ADMIN', 'VERIFIER'], true)) {
            $assignedScopeIds = $this->scopeModel->getActiveInstitutionIds((int)$user->id);
            $ids = array_merge($ids, $assignedScopeIds);

            $grants = $this->grantModel->getActiveGrantsForUser((int)$user->id);
            foreach ($grants as $g) {
                if (!empty($g['target_institution_id'])) {
                    $ids[] = (int)$g['target_institution_id'];
                }
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }
}

