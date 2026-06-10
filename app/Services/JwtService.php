<?php

namespace App\Services;

class JwtService
{
    /**
     * Encode payload to JWT string.
     */
    public static function encode(array $payload, int $expirySeconds = 86400): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        
        // Add default claims
        $payload['iat'] = time();
        $payload['exp'] = time() + $expirySeconds;
        $payload['iss'] = env('APP_URL', 'http://localhost');
        $payload['jti'] = \Illuminate\Support\Str::uuid()->toString();

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Decode JWT string and return payload or null if invalid/expired.
     */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$base64UrlHeader, $base64UrlPayload, $base64UrlSignature] = $parts;

        // Verify signature
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        if (!hash_equals(self::base64UrlEncode($expectedSignature), $base64UrlSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);
        if (!$payload) {
            return null;
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        // Check blacklist
        if (isset($payload['jti']) && TokenBlacklistService::isBlacklisted($payload['jti'])) {
            return null;
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private static function getSecret(): string
    {
        $secret = env('JWT_SECRET', env('APP_KEY'));
        if (!$secret) {
            throw new \RuntimeException('JWT_SECRET or APP_KEY must be set in the environment.');
        }
        return $secret;
    }
}
