<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class TokenBlacklistService
{
    private const CACHE_PREFIX = 'jwt_blacklist_';

    /**
     * Blacklist a token by its unique JTI (JWT ID).
     *
     * @param string $jti The JWT ID.
     * @param int $expirationTimestamp The exact UNIX timestamp when the token expires.
     */
    public static function blacklist(string $jti, int $expirationTimestamp): void
    {
        $ttl = max(0, $expirationTimestamp - time());

        if ($ttl > 0) {
            // Store the JTI in the cache until its natural expiration.
            // Using put with a TTL in seconds.
            Cache::put(self::CACHE_PREFIX . $jti, true, $ttl);
        }
    }

    /**
     * Check if a token JTI is blacklisted.
     *
     * @param string $jti The JWT ID.
     * @return bool True if blacklisted, false otherwise.
     */
    public static function isBlacklisted(string $jti): bool
    {
        return Cache::has(self::CACHE_PREFIX . $jti);
    }
}
