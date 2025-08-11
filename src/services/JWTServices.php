<?php

declare(strict_types=1);

namespace App\services;

use Exception;

class JWTServices
{
    public static ?string $key = null;

    public static function initKey(): void
    {
        if (self::$key == null) {
            self::$key = $_ENV["JWT_SECRET_KEY"] ?? '';
            if (empty(self::$key)) {
                throw new Exception("Clé secrète non définie dans la configuration (JWT_SECRET_KEY)");
            }
        }
    }

    public static function generate(array $payload): string
    {
        self::initKey();
        $headers = [
            'type' => "JWT",
            'alg' => 'HS256'
        ];
        // payload avec expiration 24H
        $payload['exp'] = time() + (24 * 60 * 60);

        $base64Header = self::base64url_encode(json_encode($headers));
        $base64Payload = self::base64url_encode(json_encode($payload));

        // création de la signature 
        $signature = hash_hmac("sha256", $base64Header . '.' . $base64Payload, self::$key, true);
        $base64Signature = self::base64url_encode($signature);

        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }

    private static function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64url_decode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function verify(string $token): ?array
    {
        try {
            self::initKey();

            // Découpage du token
            $tokenParts = explode('.', $token);
            if (count($tokenParts) != 3) {
                throw new Exception('Format de token invalide');
            }

            // Décodage du payload
            $payload = json_decode(self::base64url_decode($tokenParts[1]), true);

            // Vérification de l'expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new Exception('Token expiré');
            }

            // Vérification de la signature
            $signature = self::base64url_decode($tokenParts[2]);
            $expectedSignature = hash_hmac(
                "sha256",
                $tokenParts[0] . '.' . $tokenParts[1],
                self::$key,
                true
            );

            if (!hash_equals($signature, $expectedSignature)) {
                throw new Exception('Signature invalide');
            }

            return $payload;
        } catch (\Exception $e) {
            error_log("Erreur de vérification du token : " . $e->getMessage());
            return null;
        }
    }
}
