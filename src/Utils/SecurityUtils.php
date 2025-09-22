<?php


namespace App\Utils;

class SecurityUtils
{
    public static function sanitizeRequestData(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Nettoie les chaînes de caractères contre XSS
                $sanitized[$key] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}
