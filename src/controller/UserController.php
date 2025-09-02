<?php

declare(strict_types=1);

namespace App\controller;

use DateTime;
use Exception;
use App\model\User;
use App\services\MailService;
use App\core\attributes\Route;
use App\repository\UserRepository;
use App\services\JWTServices;
use App\Utils\SecurityUtils;

class UserController
{

    private UserRepository $userRepository;
    public function __construct()
    {
        $this->userRepository = new UserRepository;
    }

    public function validateUniqueUserData(array $data, ?User $currentUser = null): void
    {
        error_log("Validation data: " . json_encode($data));
        error_log("Current user: " . ($currentUser ? $currentUser->getUsername() . " / " . $currentUser->getEmail() : "null"));

        $usernameExists = false;
        $emailExists = false;

        // Check username only if it's provided and different from current user's username
        if (!empty($data['username'])) {
            error_log("Checking username: " . $data['username']);
            if ($currentUser === null || $data['username'] !== $currentUser->getUsername()) {
                error_log("Username is different from current, checking database...");
                $existingUser = $this->userRepository->findUserByUsername($data['username']);
                $usernameExists = $existingUser ? true : false;
                error_log("Username exists: " . ($usernameExists ? "yes" : "no"));
            } else {
                error_log("Username same as current user, skipping check");
            }
        }

        // Same for email...
        if (!empty($data['email'])) {
            error_log("Checking email: " . $data['email']);
            if ($currentUser === null || $data['email'] !== $currentUser->getEmail()) {
                error_log("Email is different from current, checking database...");
                $existingUser = $this->userRepository->findUserByEmail($data['email']);
                $emailExists = $existingUser ? true : false;
                error_log("Email exists: " . ($emailExists ? "yes" : "no"));
            } else {
                error_log("Email same as current user, skipping check");
            }
        }
    }



    #[Route('/api/register', 'POST')]
    public function register()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) throw new Exception('JSON invalide');

            // Nettoyer les données entrantes contre XSS
            $data = SecurityUtils::sanitizeRequestData($data);

            $emailToken = bin2hex(random_bytes(32));

            $userData = [
                "username" => $data['username'] ?? '',
                "email" => $data['email'] ?? '',
                "password" => password_hash($data["password"], PASSWORD_BCRYPT),
                "avatar" => $data['avatar'] ?? "avatar_par_defaut.png",
                "email_token"  => $emailToken
            ];

            // création user
            $user = new User($userData);
            $this->validateUniqueUserData($data, $user);

            $user->setCreatedAt((new DateTime())->format('Y-m-d H:i:s'));
            $saved = $this->userRepository->save($user);

            if (!$saved) throw new Exception('erreur lors de la sauvegarde');

            if (!$user->getEmailToken()) throw new Exception('Erreur lors de l génération du token de vérification');

            MailService::sendEmailVerification($user->getEmail(), $user->getEmailToken());

            echo json_encode([
                'success' => true,
                'message' => 'Inscription réussie ! Veuillez vérifier vos email.' . json_encode($data)
            ]);
        } catch (\Exception $e) {
            error_log('Erreur inscription: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }


    #[Route('/api/login', 'POST')]
    public function login()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Données invalides');
            }

            // Nettoyer les données entrantes contre XSS
            $data = SecurityUtils::sanitizeRequestData($data);

            if (empty($data['email']) || empty($data['password'])) {
                throw new Exception('Email et mot de passe requis');
            }

            $user = $this->userRepository->findUserByEmail($data['email']);
            if (!$user) {
                throw new Exception('Identifiants incorrects');
            }

            if (!password_verify($data['password'], $user->getPassword())) {
                throw new Exception('Identifiants incorrects');
            }

            if (!$user->getIsVerified()) {
                throw new Exception('Veuillez vérifier votre email avant de vous connecter');
            }

            $jwtService = new JWTServices();
            $token = $jwtService->generate([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRoles()
            ]);

            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'role' => $user->getRoles()
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }


    #[Route('/api/valider-email', 'GET')]
    public function verifyEmail()
    {
        try {
            $token = $_GET['token'] ?? null;

            if (!$token) throw new Exception('Token manquant!');

            $user = $this->userRepository->findUserByToken($token);

            if (!$user) throw new Exception('Utilisateur introuvable');

            $user->setEmailToken(null);
            $user->setIsVerified(true);

            $updated = $this->userRepository->update($user);


            if (!$updated) throw new Exception('erreur lors de la mise a jour');
            echo json_encode([
                'success' => true,
                'message' => 'Email vérifié avec succès!'
            ]);
        } catch (\Exception $e) {
            error_log('Erreur inscription: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }
}
