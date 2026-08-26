<?php

namespace App\Models;

use App\Entities\SubmissionPositionEntity;
use CodeIgniter\Model;

/**
 * SubmissionPositionModel
 *
 * Data Access Layer for submission_positions table.
 */
class SubmissionPositionModel extends Model
{
    protected $table            = 'submission_positions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = SubmissionPositionEntity::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'version_unit_id',
        'source_position_id',
        'position_name',
        'position_type',
        'echelon',
        'formation_count',
        'change_type',
    ];

    protected $useTimestamps = false;

    /**
     * Get positions by submission unit ID.
     *
     * @param int $versionUnitId
     * @return array
     */
    public function getByUnitId(int $versionUnitId): array
    {
        return $this->where('version_unit_id', $versionUnitId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Get all positions for a specific version ID across all its units.
     *
     * @param int $versionId
     * @return array
     */
    public function getByVersionId(int $versionId): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('submission_positions sp')
            ->select('sp.*, su.unit_code, su.unit_name, su.version_id')
            ->join('submission_units su', 'sp.version_unit_id = su.id')
            ->where('su.version_id', $versionId)
            ->orderBy('sp.id', 'ASC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get a submission position with unit, submission, and institution details.
     *
     * @param int $positionId
     * @return array|null
     */
    public function getPositionWithSubmissionAndInstitution(int $positionId): ?array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('submission_positions sp')
            ->select('sp.*, su.unit_code, su.unit_name, su.version_id, sv.submission_id, sv.version_number, s.institution_id, s.current_state, s.author_id')
            ->join('submission_units su', 'sp.version_unit_id = su.id')
            ->join('submission_versions sv', 'su.version_id = sv.id')
            ->join('submissions s', 'sv.submission_id = s.id')
            ->where('sp.id', $positionId);

        return $builder->get()->getRowArray();
    }
}
