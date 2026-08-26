<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * SubmissionPositionEntity
 *
 * Strongly typed representation of a proposed position change in a submission version.
 */
class SubmissionPositionEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = [];
    protected $casts   = [
        'id'                 => 'integer',
        'version_unit_id'    => 'integer',
        'source_position_id' => '?integer',
        'formation_count'    => 'integer',
    ];
}
