<?php

namespace App\Models;

use App\Entities\PositionEntity;
use CodeIgniter\Model;

/**
 * PositionModel
 *
 * Data Access Layer for positions table.
 */
class PositionModel extends Model
{
    protected $table            = 'positions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = PositionEntity::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'unit_id',
        'position_name',
        'position_type',
        'echelon',
        'formation_count',
        'status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get positions by organizational unit ID.
     *
     * @param int  $unitId
     * @param bool $activeOnly
     * @return array
     */
    public function getByUnitId(int $unitId, bool $activeOnly = true): array
    {
        $builder = $this->where('unit_id', $unitId);

        if ($activeOnly) {
            $builder->where('status', 'ACTIVE');
        }

        return $builder->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Get position details with joined unit and institution info.
     *
     * @param int $positionId
     * @return array|null
     */
    public function getPositionWithUnitAndInstitution(int $positionId): ?array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('positions p')
            ->select('p.*, ou.unit_name, ou.unit_code, ou.unit_level, ou.institution_id, i.name as institution_name, i.institution_code')
            ->join('organizational_units ou', 'p.unit_id = ou.id')
            ->join('institutions i', 'ou.institution_id = i.id')
            ->where('p.id', $positionId);

        return $builder->get()->getRowArray();
    }
}
