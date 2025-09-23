<?php

namespace App\middleware;

class CSRFMiddleware
{
    public static function verifyToken(): bool
    {
        error_log("Vérification CSRF...");

        // Utiliser l'opérateur null coalescing pour éviter les erreurs
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        $csrfSecret = $_ENV['CSRF_SECRET'] ?? getenv('CSRF_SECRET'); // Essayer les deux méthodes

        error_log("Token CSRF reçu: " . ($csrfToken ?? 'non défini'));
        error_log("Token CSRF attendu: " . ($csrfSecret ?? 'non défini'));
        error_log("Toutes les variables d'env: " . print_r($_ENV, true));

        if (!$csrfToken || !$csrfSecret) {
            error_log("Token CSRF ou Secret manquant");
            return false;
        }

        return hash_equals($csrfToken, $csrfSecret);
    }
}
