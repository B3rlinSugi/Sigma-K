<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AccessRequestModel
 *
 * Data Access Layer for access_requests and access_request_permissions tables.
 */
class AccessRequestModel extends Model
{
    protected $table            = 'access_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id',
        'target_institution_id',
        'reason',
        'requested_start_date',
        'requested_end_date',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];
    protected $useTimestamps    = true;

    /**
     * Get access request details with joined user, target institution, reviewer, and requested permissions.
     */
    public function getRequestWithDetails(int $requestId): ?array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('access_requests ar')
            ->select('ar.*, u.username as requester_username, u.full_name as requester_name, i.name as target_institution_name, i.institution_code as target_institution_code, rev.username as reviewer_username')
            ->join('users u', 'ar.user_id = u.id')
            ->join('institutions i', 'ar.target_institution_id = i.id')
            ->join('users rev', 'ar.reviewed_by = rev.id', 'left')
            ->where('ar.id', $requestId);

        $request = $builder->get()->getRowArray();
        if (!$request) {
            return null;
        }

        // Get requested permissions
        $permBuilder = $db->table('access_request_permissions arp')
            ->select('p.id, p.permission_code, p.permission_name')
            ->join('permissions p', 'arp.permission_id = p.id')
            ->where('arp.request_id', $requestId);

        $request['requested_permissions'] = $permBuilder->get()->getResultArray();
        $request['permission_codes'] = array_column($request['requested_permissions'], 'permission_code');

        return $request;
    }
}
