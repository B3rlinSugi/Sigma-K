<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * RoleModel
 *
 * Data Access Layer for roles table.
 */
class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['role_code', 'role_name', 'description'];
    protected $useTimestamps    = true;
}
