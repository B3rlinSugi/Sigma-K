<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * ApprovalRecordEntity
 *
 * Domain Entity representing the final internal system approval record.
 */
class ApprovalRecordEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['approved_at'];
    protected $casts   = [
        'id'          => 'integer',
        'version_id'  => 'integer',
        'approver_id' => 'integer',
    ];
}
