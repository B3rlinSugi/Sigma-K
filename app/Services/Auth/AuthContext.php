<?php

namespace App\Services\Auth;

use App\Entities\UserEntity;

/**
 * AuthContext
 *
 * Thread-safe request context storing the currently authenticated user entity.
 */
class AuthContext
{
    protected static ?UserEntity $user = null;

    public static function setUser(?UserEntity $user): void
    {
        self::$user = $user;
    }

    public static function getUser(): ?UserEntity
    {
        return self::$user;
    }

    public static function getUserId(): ?int
    {
        return self::$user ? (int)self::$user->id : null;
    }

    public static function clear(): void
    {
        self::$user = null;
    }
}
