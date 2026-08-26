<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AccessGrantModel
 *
 * Data Access Layer for access_grants and access_grant_permissions tables.
 */
class AccessGrantModel extends Model
{
    protected $table            = 'access_grants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'request_id',
        'user_id',
        'target_institution_id',
        'start_date',
        'end_date',
        'status',
        'granted_by',
        'grant_reason',
        'revoked_by',
        'revoked_at',
        'revoke_reason',
    ];
    protected $useTimestamps    = true;

    /**
     * Check if an active access grant exists for a user, target institution, and specific permission code.
     */
    public function hasActiveGrantPermission(int $userId, int $targetInstitutionId, string $permissionCode): bool
    {
        $today = date('Y-m-d');
        $db = \Config\Database::connect();

        $builder = $db->table('access_grants ag')
            ->join('access_grant_permissions agp', 'ag.id = agp.grant_id')
            ->join('permissions p', 'agp.permission_id = p.id')
            ->where('ag.user_id', $userId)
            ->where('ag.target_institution_id', $targetInstitutionId)
            ->where('ag.status', 'ACTIVE')
            ->where('ag.start_date <=', $today)
            ->where('ag.end_date >=', $today)
            ->where('p.permission_code', $permissionCode);

        return $builder->countAllResults() > 0;
    }

    /**
     * Get all active grants for a user with target institution and permissions.
     */
    public function getActiveGrantsForUser(int $userId): array
    {
        $today = date('Y-m-d');
        $db = \Config\Database::connect();

        $builder = $db->table('access_grants ag')
            ->select('ag.id, ag.target_institution_id, i.name as target_institution_name, ag.start_date, ag.end_date, GROUP_CONCAT(p.permission_code) as permissions')
            ->join('institutions i', 'ag.target_institution_id = i.id')
            ->join('access_grant_permissions agp', 'ag.id = agp.grant_id')
            ->join('permissions p', 'agp.permission_id = p.id')
            ->where('ag.user_id', $userId)
            ->where('ag.status', 'ACTIVE')
            ->where('ag.start_date <=', $today)
            ->where('ag.end_date >=', $today)
            ->groupBy('ag.id, ag.target_institution_id, i.name, ag.start_date, ag.end_date');

        return $builder->get()->getResultArray();
    }
}
