<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * SubmissionEntity
 *
 * Strongly typed representation of an institutional kelembagaan submission.
 */
class SubmissionEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'              => 'integer',
        'institution_id'  => 'integer',
        'author_id'       => 'integer',
        'submission_year' => 'integer',
    ];

    /**
     * Check if the submission is currently in DRAFT state.
     */
    public function isDraft(): bool
    {
        return strtoupper((string)($this->attributes['current_state'] ?? '')) === 'DRAFT';
    }

    /**
     * Check if the submission is locked from user editing (SUBMITTED_TO_ADMIN or later).
     */
    public function isLocked(): bool
    {
        return !$this->isDraft();
    }
}
