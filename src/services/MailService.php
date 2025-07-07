<?php

declare(strict_types=1);

namespace App\services;

class MailService
{
    private static function initMailSettings(): void
    {
        // Configuration SMTP pour Mailpit
        ini_set('SMTP', 'localhost');
        ini_set('smtp_port', '1025');
    }

    public static function sendEmailVerification(string $email, string $token): void
    {
        self::initMailSettings();

        $link = "http://localhost:3001/validateEmail?token=" . $token;
        $subject = "Verify Your Email Address";
        $message = "
        <html>
        <head>
            <title>Email Verification</title>
        </head>
        <body style='font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px;'>
            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05);'>
                <tr>
                    <td style='text-align: center;'>
                        <h2 style='color: #333;'>Bienvenue sur InfoDotGame</h2>
                    </td>
                </tr>
                <tr>
                    <td style='padding: 20px 0; color: #555; font-size: 16px;'>
                        <p>Cher Utilisateur,</p>
                        <p>Merci pour votre inscription. Afin de confirmer votre inscription, merci de valider votre email en cliquand sur le boutton ci-dessous</p>
                        <p style='text-align: center; margin: 30px 0;'>
                            <a href='{$link}' style='background-color: #007bff; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Vérifier Email</a>
                        </p>
                        <p>Si vous n'avez pas créer le compte, veuillez ne pas tenir compte de ce message</p>
                        <p>Merci,<br>L'auteur de InfoDotGame</p>
                    </td>
                </tr>
                <tr>
                    <td style='text-align: center; font-size: 12px; color: #999; padding-top: 20px;'>
                        © " . date('Y') . " InfoDotGame. All rights reserved.
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        // Correct headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: InfoDotGame <noreply@infodotgame.com>\r\n";

        // Send the email
        mail($email, $subject, $message, $headers);
    }

    /**
     * Envoie un lien de réinitialisation du mot de passe
     * @param string $email
     * @param string $token
     */
    public static function sendPasswordResetEmail(string $email, string $token): void
    {
        self::initMailSettings();

        $link = "http://localhost:3001/reset-password?token=" . urlencode($token);

        $subject = "Réinitialisation de votre mot de passe InfoDotGame";
        $message = "Vous avez demandé une réinitialisation de mot de passe. Cliquez ici pour le réinitialiser : $link\n\n";
        $message .= "Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=UTF-8\r\n";
        $headers .= "From: InfoDotGame <noreply@infodotgame.com>\r\n";

        mail($email, $subject, $message, $headers);
    }
}
