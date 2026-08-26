<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * VerificationRecordEntity
 *
 * Strongly typed representation of a Gate 1 / Gate 2 verification log record.
 */
class VerificationRecordEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['verified_at'];
    protected $casts   = [
        'id'          => 'integer',
        'version_id'  => 'integer',
        'reviewer_id' => 'integer',
    ];
}
