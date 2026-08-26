<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserScopeModel
 *
 * Data Access Layer for user_scopes table.
 */
class UserScopeModel extends Model
{
    protected $table            = 'user_scopes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id',
        'institution_id',
        'scope_type',
        'start_date',
        'end_date',
        'status',
        'assigned_by',
        'revoked_by',
        'revoked_at',
        'revoke_reason',
    ];
    protected $useTimestamps    = true;

    /**
     * Get all active institution IDs for a specific user ID.
     */
    public function getActiveInstitutionIds(int $userId): array
    {
        $today = date('Y-m-d');
        $results = $this->where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->where('start_date <=', $today)
            ->where('end_date >=', $today)
            ->findAll();

        return array_map('intval', array_column($results, 'institution_id'));
    }
}
