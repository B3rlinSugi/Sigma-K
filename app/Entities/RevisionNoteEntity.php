<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * RevisionNoteEntity
 *
 * Strongly typed representation of a revision note issued during review.
 */
class RevisionNoteEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at'];
    protected $casts   = [
        'id'              => 'integer',
        'verification_id' => 'integer',
        'version_unit_id' => '?integer',
        'is_resolved'     => 'boolean',
    ];
}
