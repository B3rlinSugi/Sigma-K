<?php

namespace App\Models;

use App\Entities\VerifierAssignmentEntity;
use CodeIgniter\Model;

/**
 * VerifierAssignmentModel
 *
 * Data Access Layer for verifier_assignments table.
 */
class VerifierAssignmentModel extends Model
{
    protected $table            = 'verifier_assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = VerifierAssignmentEntity::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'submission_id',
        'verifier_id',
        'assigned_by',
        'status',
        'assignment_notes',
        'assigned_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get active assignment for a submission.
     *
     * @param int $submissionId
     * @return array|null
     */
    public function getActiveAssignment(int $submissionId): ?array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('verifier_assignments va')
            ->select('va.*, u.username as verifier_username, u.full_name as verifier_name, a.username as assigner_username')
            ->join('users u', 'va.verifier_id = u.id')
            ->join('users a', 'va.assigned_by = a.id')
            ->where('va.submission_id', $submissionId)
            ->where('va.status', 'ASSIGNED')
            ->orderBy('va.id', 'DESC');

        return $builder->get()->getRowArray();
    }

    /**
     * Get paginated assigned submissions for a specific verifier.
     *
     * @param int $verifierId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAssignedSubmissionsForVerifier(int $verifierId, int $page = 1, int $perPage = 20): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('verifier_assignments va')
            ->select('s.*, i.name as institution_name, i.institution_code, u.username as author_username, u.full_name as author_name, va.id as assignment_id, va.assigned_at, va.assignment_notes')
            ->join('submissions s', 'va.submission_id = s.id')
            ->join('institutions i', 's.institution_id = i.id')
            ->join('users u', 's.author_id = u.id')
            ->where('va.verifier_id', $verifierId)
            ->where('va.status', 'ASSIGNED')
            ->whereIn('s.current_state', ['ASSIGNED_TO_VERIFIER', 'IN_REVIEW_BY_VERIFIER', 'VERIFIER_REVIEW', 'RESUBMITTED']);

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();

        $offset = ($page - 1) * $perPage;
        $items = $builder->orderBy('va.id', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return [
            'items' => $items,
            'meta'  => [
                'page'       => $page,
                'perPage'    => $perPage,
                'total'      => $total,
                'totalPages' => $perPage > 0 ? (int)ceil($total / $perPage) : 1,
            ],
        ];
    }
}
