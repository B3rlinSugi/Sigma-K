<?php

namespace App\Models;

use App\Entities\ApprovalRecordEntity;
use CodeIgniter\Model;

/**
 * ApprovalRecordModel
 *
 * Data Access Layer for approval_records table.
 */
class ApprovalRecordModel extends Model
{
    protected $table            = 'approval_records';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = ApprovalRecordEntity::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'version_id',
        'approver_id',
        'approval_number',
        'approval_notes',
        'approved_at',
    ];

    protected $useTimestamps = false; // Handled via CURRENT_TIMESTAMP default or explicitly

    /**
     * Get approval record with joined approver and version info.
     *
     * @param int $submissionId
     * @return array|null
     */
    public function getBySubmissionId(int $submissionId): ?array
    {
        $db = \Config\Database::connect();
        return $db->table('approval_records ar')
            ->select('ar.*, u.username as approver_username, u.full_name as approver_name, sv.version_number, sv.submission_id')
            ->join('users u', 'ar.approver_id = u.id')
            ->join('submission_versions sv', 'ar.version_id = sv.id')
            ->where('sv.submission_id', $submissionId)
            ->orderBy('ar.id', 'DESC')
            ->get()
            ->getRowArray();
    }

    /**
     * Get approval record for a specific submission version.
     *
     * @param int $versionId
     * @return array|null
     */
    public function getByVersionId(int $versionId): ?array
    {
        $db = \Config\Database::connect();
        return $db->table('approval_records ar')
            ->select('ar.*, u.username as approver_username, u.full_name as approver_name, sv.version_number')
            ->join('users u', 'ar.approver_id = u.id')
            ->join('submission_versions sv', 'ar.version_id = sv.id')
            ->where('ar.version_id', $versionId)
            ->get()
            ->getRowArray();
    }
}
