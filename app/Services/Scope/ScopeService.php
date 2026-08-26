<?php

namespace App\Services\Scope;

use App\Entities\UserEntity;
use App\Models\AccessGrantModel;
use App\Models\InstitutionModel;
use App\Models\UserScopeModel;
use App\Services\Authorization\AuthorizationService;

/**
 * ScopeService
 *
 * Provides endpoints for inspecting effective institutional scopes and authority for users.
 */
class ScopeService
{
    protected InstitutionModel $institutionModel;
    protected UserScopeModel $scopeModel;
    protected AccessGrantModel $grantModel;
    protected AuthorizationService $authzService;

    public function __construct(
        ?InstitutionModel $institutionModel = null,
        ?UserScopeModel $scopeModel = null,
        ?AccessGrantModel $grantModel = null,
        ?AuthorizationService $authzService = null
    ) {
        $this->institutionModel = $institutionModel ?? new InstitutionModel();
        $this->scopeModel       = $scopeModel ?? new UserScopeModel();
        $this->grantModel       = $grantModel ?? new AccessGrantModel();
        $this->authzService     = $authzService ?? new AuthorizationService();
    }

    /**
     * Get effective scope breakdown for the current user.
     *
     * @param UserEntity $user
     * @return array
     */
    public function getEffectiveScopes(UserEntity $user): array
    {
        $roleCode = $this->authzService->getUserRoleCode($user);
        $homeInst = $this->institutionModel->find((int)$user->home_institution_id);

        $today = date('Y-m-d');
        $db = \Config\Database::connect();

        // 1. Fetch active assigned user scopes with institution details
        $assignedScopes = $db->table('user_scopes us')
            ->select('us.id as scope_id, us.institution_id, i.name as institution_name, i.institution_code, us.scope_type, us.start_date, us.end_date, us.status')
            ->join('institutions i', 'us.institution_id = i.id')
            ->where('us.user_id', (int)$user->id)
            ->where('us.status', 'ACTIVE')
            ->where('us.start_date <=', $today)
            ->where('us.end_date >=', $today)
            ->get()
            ->getResultArray();

        // 2. Fetch active delegated access grants with permissions
        $activeGrants = $this->grantModel->getActiveGrantsForUser((int)$user->id);

        return [
            'role'             => $roleCode,
            'isGlobalScope'    => ($roleCode === 'SUPER_ADMIN'),
            'homeInstitution'  => $homeInst ? [
                'id'   => (int)$homeInst['id'],
                'code' => $homeInst['institution_code'],
                'name' => $homeInst['name'],
            ] : null,
            'assignedScopes'   => $assignedScopes,
            'activeGrants'     => $activeGrants,
        ];
    }
}
