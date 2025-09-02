<?php

namespace App\controller;

use App\core\attributes\Route;
use App\services\RecaptchaService;
use App\Utils\SecurityUtils;

class RecaptchaController
{
    private RecaptchaService $recaptchaService;

    public function __construct()
    {
        $this->recaptchaService = new RecaptchaService();
    }

    #[Route("/verify-recaptcha", "POST")]
    public function verifyRecaptcha(): array
    {
        // Récupérer le token depuis le corps de la requête
        $data = json_decode(file_get_contents('php://input'), true);
        // Nettoyer les données entrantes contre XSS
        $data = SecurityUtils::sanitizeRequestData($data);
        $token = $data['token'] ?? '';

        if (empty($token)) {
            http_response_code(400);
            return ['success' => false, 'error' => 'Token manquant'];
        }

        // Vérifier le token avec le service
        $isValid = $this->recaptchaService->verifyToken($token);

        if (!$isValid) {
            http_response_code(400);
            return ['success' => false, 'error' => 'Validation reCAPTCHA échouée'];
        }

        return ['success' => true];
    }
}
