<?php

namespace App\services;

class RecaptchaService
{
    private string $secretKey;

    public function __construct()
    {
        // À remplacer par votre clé secrète reCAPTCHA
        $this->secretKey = getenv('RECAPTCHA_SECRET_KEY');
    }

    public function verifyToken(string $token): bool
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $this->secretKey,
            'response' => $token
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response);

        return $result->success ?? false;
    }
}
