<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * SubmissionVersionEntity
 *
 * Strongly typed representation of an immutable submission version snapshot.
 */
class SubmissionVersionEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['submitted_at', 'created_at'];
    protected $casts   = [
        'id'             => 'integer',
        'submission_id'  => 'integer',
        'version_number' => 'integer',
    ];
}
