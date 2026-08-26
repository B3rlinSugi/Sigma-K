<?php

namespace App\Models;

use App\Entities\UserEntity;
use CodeIgniter\Model;

/**
 * UserModel
 *
 * Data Access Layer for users table in eskld_db.
 */
class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = UserEntity::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'home_institution_id',
        'role_id',
        'username',
        'email',
        'password_hash',
        'full_name',
        'nip',
        'status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Find user by ID, ensuring UserEntity is returned.
     *
     * @param array|int|string|null $id
     * @return UserEntity|array|null
     */
    public function find($id = null)
    {
        $user = parent::find($id);
        if ($user && is_array($user)) {
            return new UserEntity($user);
        }
        return $user;
    }

    /**
     * Find active user with joined role and institution data by username.
     */
    public function findByUsername(string $username): ?UserEntity
    {
        $user = $this->where('username', $username)->first();
        if (!$user) {
            return null;
        }
        if (is_array($user)) {
            return new UserEntity($user);
        }
        return $user;
    }
}
