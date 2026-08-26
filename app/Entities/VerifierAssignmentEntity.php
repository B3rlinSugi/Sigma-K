<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * VerifierAssignmentEntity
 *
 * Strongly typed representation of a Verifier assignment record.
 */
class VerifierAssignmentEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['assigned_at'];
    protected $casts   = [
        'id'            => 'integer',
        'submission_id' => 'integer',
        'verifier_id'   => 'integer',
        'assigned_by'   => 'integer',
    ];
}
