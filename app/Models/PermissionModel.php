<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PermissionModel
 *
 * Data Access Layer for permissions table.
 */
class PermissionModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['permission_code', 'permission_name', 'category', 'description'];
    protected $useTimestamps    = true;

    /**
     * Get all permission codes for a specific role ID.
     *
     * @param int $roleId
     * @return array List of permission_code strings
     */
    public function getPermissionsByRoleId(int $roleId): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('role_permissions rp')
            ->select('p.permission_code')
            ->join('permissions p', 'rp.permission_id = p.id')
            ->where('rp.role_id', $roleId);

        $results = $builder->get()->getResultArray();
        return array_column($results, 'permission_code');
    }
}
