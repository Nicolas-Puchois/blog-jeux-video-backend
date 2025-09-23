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

    public static function verifyToken(): bool
    {
        error_log("Vérification CSRF...");
        error_log("Headers reçus: " . print_r(getallheaders(), true));

        // Récupérer le token CSRF de l'en-tête
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        error_log("Token CSRF reçu: " . $csrfToken);
        error_log("Token CSRF attendu: " . getenv('CSRF_SECRET'));

        if (!$csrfToken) {
            error_log("Pas de token CSRF trouvé dans la requête");
            return false;
        }

        return $csrfToken === getenv('CSRF_SECRET');
    }
}
