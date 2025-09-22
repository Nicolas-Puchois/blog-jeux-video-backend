<?php

namespace App\middleware;

class CSRFMiddleware
{
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verifyToken(string $token = null): bool
    {
        // Récupérer la clé secrète depuis .env
        $csrfSecret = getenv('CSRF_SECRET');

        // Récupérer le token depuis les headers
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        // Vérifier que le token correspond
        return $headerToken === $csrfSecret;
    }
}
