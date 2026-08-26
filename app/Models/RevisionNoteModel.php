<?php

namespace App\Models;

use App\Entities\RevisionNoteEntity;
use CodeIgniter\Model;

/**
 * RevisionNoteModel
 *
 * Data Access Layer for revision_notes table.
 */
class RevisionNoteModel extends Model
{
    protected $table            = 'revision_notes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = RevisionNoteEntity::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'verification_id',
        'version_unit_id',
        'issue_description',
        'is_resolved',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get revision notes for a specific verification record.
     *
     * @param int $verificationId
     * @return array
     */
    public function getByVerificationId(int $verificationId): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('revision_notes rn')
            ->select('rn.*, su.unit_code, su.unit_name')
            ->join('submission_units su', 'rn.version_unit_id = su.id', 'left')
            ->where('rn.verification_id', $verificationId)
            ->orderBy('rn.id', 'ASC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get all revision notes for all versions/verifications of a submission.
     *
     * @param int $submissionId
     * @return array
     */
    public function getBySubmissionId(int $submissionId): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('revision_notes rn')
            ->select('rn.*, vr.gate_level, vr.verification_result, vr.verified_at, u.username as reviewer_username, u.full_name as reviewer_name, su.unit_code, su.unit_name, sv.version_number')
            ->join('verification_records vr', 'rn.verification_id = vr.id')
            ->join('submission_versions sv', 'vr.version_id = sv.id')
            ->join('users u', 'vr.reviewer_id = u.id')
            ->join('submission_units su', 'rn.version_unit_id = su.id', 'left')
            ->where('sv.submission_id', $submissionId)
            ->orderBy('rn.id', 'ASC');

        return $builder->get()->getResultArray();
    }
}
