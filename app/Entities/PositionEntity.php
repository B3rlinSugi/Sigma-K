<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * PositionEntity
 *
 * Strongly typed representation of a position (jabatan) in E-SKLD.
 */
class PositionEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'              => 'integer',
        'unit_id'         => 'integer',
        'formation_count' => 'integer',
    ];

    /**
     * Check if the position is active.
     */
    public function isActive(): bool
    {
        return strtoupper((string)($this->attributes['status'] ?? '')) === 'ACTIVE';
    }
}
