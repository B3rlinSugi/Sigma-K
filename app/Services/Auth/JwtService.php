<?php

namespace App\Services\Auth;

use App\Entities\UserEntity;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;

/**
 * JwtService
 *
 * Handles encoding, decoding, and cryptographic validation of JWT Access Tokens.
 */
class JwtService
{
    protected string $secret;
    protected string $algorithm;
    protected int $expiry;
    protected string $issuer;

    public function __construct()
    {
        $this->secret    = env('jwt.secret', 'default_insecure_secret_key_change_in_production_123');
        $this->algorithm = env('jwt.algorithm', 'HS256');
        $this->expiry    = (int)env('jwt.accessTokenExpiry', 3600);
        $this->issuer    = env('jwt.issuer', 'eskld-kemenpanrb');
    }

    /**
     * Generate a signed JWT Access Token for an authenticated user.
     *
     * @param UserEntity $user
     * @param string     $roleCode
     * @return string Signed JWT token string
     */
    public function generateAccessToken(UserEntity $user, string $roleCode): string
    {
        $now = time();
        $payload = [
            'iss'  => $this->issuer,
            'sub'  => (int)$user->id,
            'iat'  => $now,
            'exp'  => $now + $this->expiry,
            'jti'  => bin2hex(random_bytes(16)),
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Decode and validate a JWT token.
     *
     * @param string $token
     * @return stdClass Decoded token payload
     * @throws Exception If signature is invalid, token is expired, or malformed
     */
    public function validateToken(string $token): stdClass
    {
        return JWT::decode($token, new Key($this->secret, $this->algorithm));
    }

    /**
     * Get token expiry in seconds.
     */
    public function getExpirySeconds(): int
    {
        return $this->expiry;
    }
}
