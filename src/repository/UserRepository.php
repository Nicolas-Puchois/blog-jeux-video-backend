<?php

declare(strict_types=1);

namespace App\repository;

use PDO;
use DateTime;
use Exception;
use App\model\User;
use App\core\Database;

class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnexion();
    }

    public function save(User $user): bool
    {
        // requête préparé obligatoire!!!!
        $stmt = $this->pdo->prepare("INSERT INTO `user`
        (username, email, password_hash, role, email_token, is_verified, created_at)
        VALUES(?,?,?,?,?,?,?);");
        return $stmt->execute([
            $user->getUsername(),
            $user->getEmail(),
            $user->getPassword(),
            json_encode($user->getRoles()),
            $user->getEmailToken(),
            (int)$user->getIsVerified(),
            $user->getCreatedAt()
        ]);
    }


    public function findUserByToken($token): ?User
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM `user` WHERE email_token = ?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$token]);

            $data = $stmt->fetch();
            if (!$data) {
                throw new \Exception("Utilisateur non trouvé");
            }
            // Vérification et nettoyage des données
            $userData = [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password_hash'],
                'email_token' => $data['email_token'],
                'is_verified' => (bool)$data['is_verified']
            ];

            $user = new User($userData);
            $user->setId((int)$data['id_user']);
            $user->setVerifiedAt((new DateTime())->format('Y-m-d H:i:s'));

            // Décodage sécurisé des rôles
            $role = json_decode($data['role'], true);
            if (!is_array($role)) {
                $role = ['ROLE_USER']; // Rôle par défaut si le décodage échoue
            }
            $user->setRoles($role);

            return $user;
        } catch (\PDOException $e) {
            error_log("Erreur PDO : " . $e->getMessage());
            throw new \Exception("Erreur lors de la récupération de l'utilisateur");
        }
    }

    public function findUserByEmail($email): ?User
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM `user` WHERE email = ?');
            $stmt->execute([$email]);

            $data = $stmt->fetch();

            // Si aucun utilisateur n'est trouvé, retourner null
            if (!$data) {
                return null;
            }

            // Vérification et nettoyage des données
            $userData = [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password_hash'],
                'email_token' => $data['email_token'],
                'is_verified' => (bool)$data['is_verified']
            ];

            $user = new User($userData);
            $user->setId((int)$data['id_user']);

            if (isset($data['verified_at'])) {
                $user->setVerifiedAt($data['verified_at']);
            }

            // Décodage sécurisé des rôles
            $roles = json_decode($data['role'], true);
            if (!is_array($roles)) {
                $roles = ['ROLE_USER'];
            }
            $user->setRoles($roles);

            return $user;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la recherche par email : " . $e->getMessage());
            throw new \Exception("Une erreur est survenue lors de la recherche de l'utilisateur");
        }
    }

    public function findUserByUsername($username): ?User
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM `user` WHERE username = ?');
            $stmt->execute([$username]);

            $data = $stmt->fetch();

            // Si aucun utilisateur n'est trouvé, retourner null
            if (!$data) {
                return null;
            }

            // Vérification et nettoyage des données
            $userData = [
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password_hash'],
                'email_token' => $data['email_token'],
                'is_verified' => (bool)$data['is_verified']
            ];

            $user = new User($userData);
            $user->setId((int)$data['id_user']);

            if (isset($data['verified_at'])) {
                $user->setVerifiedAt($data['verified_at']);
            }

            // Décodage sécurisé des rôles
            $roles = json_decode($data['role'], true);
            if (!is_array($roles)) {
                $roles = ['ROLE_USER'];
            }
            $user->setRoles($roles);

            return $user;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la recherche par username : " . $e->getMessage());
            throw new \Exception("Une erreur est survenue lors de la recherche de l'utilisateur");
        }
    }

    public function findUserById(int|string $id): User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `user` WHERE id_user= ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        $user = new User($data);
        $user->setId((int)$data['id_user']);
        $user->setVerifiedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $user->setRoles(json_decode($data['roles'], true));
        return $user;
    }

    public function update(User $user): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE user SET 
            username = ?, 
            email = ?, 
            role = ?, 
            is_verified = ?, 
            email_token = ?,
            verified_at = ?,
            password_hash = ?
            WHERE id_user = ?"
        );

        return $stmt->execute([
            $user->getUsername(),
            $user->getEmail(),
            json_encode($user->getRoles()),
            (int)$user->getIsVerified(),
            $user->getEmailToken(),
            $user->getVerifiedAt(),
            $user->getPassword(),
            $user->getId()
        ]);
    }
}
