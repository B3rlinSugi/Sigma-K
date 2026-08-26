<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * InstitutionModel
 *
 * Data Access Layer for institutions table.
 */
class InstitutionModel extends Model
{
    protected $table            = 'institutions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['institution_code', 'name', 'short_name', 'category', 'status'];
    protected $useTimestamps    = true;
}
