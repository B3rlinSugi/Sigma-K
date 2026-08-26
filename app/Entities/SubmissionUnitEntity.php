<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * SubmissionUnitEntity
 *
 * Strongly typed representation of a proposed organizational unit change in a submission version.
 */
class SubmissionUnitEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = [];
    protected $casts   = [
        'id'             => 'integer',
        'version_id'     => 'integer',
        'temp_parent_id' => '?integer',
        'source_unit_id' => '?integer',
        'unit_level'     => 'integer',
        'order_index'    => 'integer',
    ];
}
