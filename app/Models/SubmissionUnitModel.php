<?php

namespace App\Models;

use App\Entities\SubmissionUnitEntity;
use CodeIgniter\Model;

/**
 * SubmissionUnitModel
 *
 * Data Access Layer for submission_units table.
 */
class SubmissionUnitModel extends Model
{
    protected $table            = 'submission_units';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = SubmissionUnitEntity::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'version_id',
        'temp_parent_id',
        'source_unit_id',
        'unit_code',
        'unit_name',
        'unit_level',
        'order_index',
        'change_type',
    ];

    protected $useTimestamps = false;

    /**
     * Get all submission units for a specific version.
     *
     * @param int $versionId
     * @return array
     */
    public function getByVersionId(int $versionId): array
    {
        return $this->where('version_id', $versionId)
            ->orderBy('order_index', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Get a submission unit with its submission and institution details.
     *
     * @param int $unitId
     * @return array|null
     */
    public function getUnitWithSubmissionAndInstitution(int $unitId): ?array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('submission_units su')
            ->select('su.*, sv.submission_id, sv.version_number, s.institution_id, s.current_state, s.author_id')
            ->join('submission_versions sv', 'su.version_id = sv.id')
            ->join('submissions s', 'sv.submission_id = s.id')
            ->where('su.id', $unitId);

        return $builder->get()->getRowArray();
    }
}
