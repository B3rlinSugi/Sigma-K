<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AccessGrantPermissionModel
 *
 * Junction table model between access_grants and permissions.
 */
class AccessGrantPermissionModel extends Model
{
    protected $table            = 'access_grant_permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['grant_id', 'permission_id'];
    protected $useTimestamps    = false;
}
