<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * OrganizationalUnitEntity
 *
 * Strongly typed representation of an organizational unit in E-SKLD.
 */
class OrganizationalUnitEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'             => 'integer',
        'institution_id' => 'integer',
        'parent_unit_id' => '?integer',
        'unit_level'     => 'integer',
        'order_index'    => 'integer',
    ];

    /**
     * Check if the organizational unit is active.
     */
    public function isActive(): bool
    {
        return strtoupper((string)($this->attributes['status'] ?? '')) === 'ACTIVE';
    }
}
