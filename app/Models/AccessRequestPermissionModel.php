<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AccessRequestPermissionModel
 *
 * Junction table model between access_requests and permissions.
 */
class AccessRequestPermissionModel extends Model
{
    protected $table            = 'access_request_permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['request_id', 'permission_id'];
    protected $useTimestamps    = false;
}
