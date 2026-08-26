<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * UserEntity
 *
 * Strongly typed representation of a user in E-SKLD.
 */
class UserEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'                  => 'integer',
        'home_institution_id' => 'integer',
        'role_id'             => 'integer',
    ];

    /**
     * Check if user account is currently active.
     */
    public function isActive(): bool
    {
        return strtoupper((string)$this->attributes['status']) === 'ACTIVE';
    }

    /**
     * Return safe user array for API output (excluding password_hash).
     */
    public function toSafeArray(): array
    {
        $data = $this->toArray();
        unset($data['password_hash']);
        return $data;
    }
}
