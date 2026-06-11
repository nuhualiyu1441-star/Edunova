<?php
/**
 * Edunova API - Authentication Handler
 * Handles JWT token generation and validation
 */

class Auth {
    /**
     * Generate JWT Token
     * @param array $data
     * @return string
     */
    public static function generateToken($data) {
        $header = json_encode(['typ' => 'JWT', 'alg' => JWT_ALGORITHM]);
        $payload = json_encode(array_merge($data, ['iat' => time(), 'exp' => time() + JWT_EXPIRY]));

        $header = base64_encode($header);
        $payload = base64_encode($payload);

        $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
        $signature = base64_encode($signature);

        return "$header.$payload.$signature";
    }

    /**
     * Verify JWT Token
     * @param string $token
     * @return array|false
     */
    public static function verifyToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }

        $header = $parts[0];
        $payload = $parts[1];
        $signature = $parts[2];

        // Verify signature
        $expectedSignature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
        if ($signature !== $expectedSignature) {
            return false;
        }

        // Decode and verify expiry
        $decodedPayload = json_decode(base64_decode($payload), true);
        
        if ($decodedPayload['exp'] < time()) {
            return false;
        }

        return $decodedPayload;
    }

    /**
     * Get authorization header
     * @return string|null
     */
    public static function getAuthorizationHeader() {
        $headers = getallheaders();
        
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'authorization') {
                return $value;
            }
        }
        
        return null;
    }

    /**
     * Get token from Authorization header
     * @return string|null
     */
    public static function getToken() {
        $authHeader = self::getAuthorizationHeader();
        
        if ($authHeader && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Check if user is authenticated
     * @return array|false
     */
    public static function authenticate() {
        $token = self::getToken();
        
        if (!$token) {
            return false;
        }
        
        return self::verifyToken($token);
    }

    /**
     * Hash password
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
