<?php

namespace App\Models;

use App\Entities\SubmissionVersionEntity;
use CodeIgniter\Model;

/**
 * SubmissionVersionModel
 *
 * Data Access Layer for submission_versions table.
 */
class SubmissionVersionModel extends Model
{
    protected $table            = 'submission_versions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = SubmissionVersionEntity::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'submission_id',
        'version_number',
        'notes',
        'submitted_at',
    ];

    protected $useTimestamps = false; // Handled explicitly or via DEFAULT CURRENT_TIMESTAMP

    /**
     * Get the latest version record for a submission.
     *
     * @param int $submissionId
     * @return array|null
     */
    public function getLatestVersion(int $submissionId): ?array
    {
        $db = \Config\Database::connect();
        return $db->table('submission_versions')
            ->where('submission_id', $submissionId)
            ->orderBy('version_number', 'DESC')
            ->get()
            ->getRowArray();
    }

    /**
     * Get all versions for a submission ordered by version_number ASC.
     *
     * @param int $submissionId
     * @return array
     */
    public function getVersionsBySubmissionId(int $submissionId): array
    {
        $db = \Config\Database::connect();
        return $db->table('submission_versions')
            ->where('submission_id', $submissionId)
            ->orderBy('version_number', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get the next incremental version number for a submission.
     *
     * @param int $submissionId
     * @return int
     */
    public function getNextVersionNumber(int $submissionId): int
    {
        $db = \Config\Database::connect();
        $row = $db->table('submission_versions')
            ->selectMax('version_number')
            ->where('submission_id', $submissionId)
            ->get()
            ->getRow();

        return $row && $row->version_number ? ((int)$row->version_number + 1) : 1;
    }
}
