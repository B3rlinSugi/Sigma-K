<?php

namespace App\Models;

use App\Entities\VerificationRecordEntity;
use CodeIgniter\Model;

/**
 * VerificationRecordModel
 *
 * Data Access Layer for verification_records table.
 */
class VerificationRecordModel extends Model
{
    protected $table            = 'verification_records';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = VerificationRecordEntity::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'version_id',
        'reviewer_id',
        'gate_level',
        'verification_result',
        'general_notes',
        'verified_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get verification records for a specific version.
     *
     * @param int $versionId
     * @return array
     */
    public function getByVersionId(int $versionId): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('verification_records vr')
            ->select('vr.*, u.username as reviewer_username, u.full_name as reviewer_name')
            ->join('users u', 'vr.reviewer_id = u.id')
            ->where('vr.version_id', $versionId)
            ->orderBy('vr.id', 'ASC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get the latest Gate 2 verification record for a version.
     *
     * @param int $versionId
     * @return array|null
     */
    public function getLatestGate2Record(int $versionId): ?array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('verification_records vr')
            ->select('vr.*, u.username as reviewer_username, u.full_name as reviewer_name')
            ->join('users u', 'vr.reviewer_id = u.id')
            ->where('vr.version_id', $versionId)
            ->where('vr.gate_level', 'GATE_2')
            ->orderBy('vr.id', 'DESC');

        return $builder->get()->getRowArray();
    }
}
