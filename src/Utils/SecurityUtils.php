<?php

namespace App\Utils;

class SecurityUtils
{
    /**
     * Nettoie les données contre les attaques XSS
     * @param mixed $data Les données à nettoyer
     * @return mixed Les données nettoyées
     */
    public static function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $data;
    }

    /**
     * Nettoie un tableau de données
     * @param array $data
     * @return array
     */
    public static function sanitizeRequestData(array $data): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return self::sanitizeRequestData($value);
            }
            return is_string($value) ? self::sanitize($value) : $value;
        }, $data);
    }
}
