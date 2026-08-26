<?php

namespace App\Models;

use App\Entities\OrganizationalUnitEntity;
use CodeIgniter\Model;

/**
 * OrganizationalUnitModel
 *
 * Data Access Layer for organizational_units table.
 */
class OrganizationalUnitModel extends Model
{
    protected $table            = 'organizational_units';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = OrganizationalUnitEntity::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'institution_id',
        'parent_unit_id',
        'unit_code',
        'unit_name',
        'unit_level',
        'order_index',
        'status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get all units for an institution ordered deterministically.
     *
     * @param int  $institutionId
     * @param bool $activeOnly
     * @return array
     */
    public function getByInstitutionId(int $institutionId, bool $activeOnly = true): array
    {
        $builder = $this->where('institution_id', $institutionId);

        if ($activeOnly) {
            $builder->where('status', 'ACTIVE');
        }

        return $builder->orderBy('order_index', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Get a unit with joined institution details and parent name.
     *
     * @param int $unitId
     * @return array|null
     */
    public function getUnitWithDetails(int $unitId): ?array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('organizational_units ou')
            ->select('ou.*, i.name as institution_name, i.institution_code, parent.unit_name as parent_unit_name, parent.unit_code as parent_unit_code')
            ->join('institutions i', 'ou.institution_id = i.id')
            ->join('organizational_units parent', 'ou.parent_unit_id = parent.id', 'left')
            ->where('ou.id', $unitId);

        return $builder->get()->getRowArray();
    }
}
