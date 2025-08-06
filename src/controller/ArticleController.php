<?php

declare(strict_types=1);

namespace App\controller;

use Exception;

use App\core\attributes\Route;
use App\model\Article;
use App\repository\ArticleRepository;
use App\services\JWTServices;
use DateTime;

class ArticleController
{
    private ArticleRepository $articleRepository;

    public function __construct()
    {
        $this->articleRepository = new ArticleRepository;
    }

    #[Route('/api/articles/id/{id}', 'DELETE')]
    public function deleteById(int $id): void
    {
        try {
            // Vérification de l'authentification
            if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Token non fourni']);
                return;
            }

            $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
            $payload = JWTServices::verify($token);

            $article = $this->articleRepository->findById($id);
            if (!$article) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Article non trouvé']);
                return;
            }

            // Vérifier si l'utilisateur est l'auteur ou un admin
            if ($article->getUserId() !== $payload->id && !$payload->isAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Non autorisé']);
                return;
            }

            if ($this->articleRepository->delete($id)) {
                http_response_code(200);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
        }
    }

    #[Route('/api/articles/id/{id}', 'GET')]
    public function getById(int|string $id): void
    {
        try {
            // Assurer que l'ID est un entier
            $articleId = is_string($id) ? (int) $id : $id;

            $article = $this->articleRepository->findById($articleId);

            if (!$article) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Article non trouvé'
                ]);
                return;
            }

            // Vérifier si l'utilisateur est connecté pour les droits d'édition
            $isAuthor = false;
            $isAdmin = false;

            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
                try {
                    $payload = JWTServices::verify($token);
                    $isAuthor = $article->getUserId() === $payload->id;
                    $isAdmin = isset($payload->isAdmin) ? $payload->isAdmin : false;
                } catch (\Exception $e) {
                    // Si le token est invalide, on continue sans droits spéciaux
                }
            }

            $articleData = $article->toArray();
            $articleData['is_author'] = $isAuthor;
            $articleData['is_admin'] = $isAdmin;

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $articleData
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/api/articles/{slug}', 'GET')]
    public function getBySlug(string $slug): void
    {
        try {
            $article = $this->articleRepository->findBySlug($slug);

            if (!$article) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Article non trouvé']);
                return;
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $article
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
        }
    }

    #[Route('/api/articles', 'POST')]
    public function create(): void
    {
        // Activer la capture de la sortie

        try {
            // Debug des données reçues
            error_log("=== Début de la requête de création d'article ===");

            // Récupération des données JSON
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);

            error_log("Données JSON reçues : " . print_r($data, true));


            // Vérification de l'authentification
            if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Token non fourni']);
                return;
            }

            // Récupération du token
            $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);

            try {
                // Décodage du token pour récupérer l'ID de l'utilisateur
                $payload = JWTServices::verify($token);
                if (!isset($payload['id'])) {
                    throw new \Exception('ID utilisateur non trouvé dans le token');
                }
                $userId = $payload['id'];
            } catch (\Exception $e) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Token invalide']);
                return;
            }

            // Validation des données requises
            if (empty($data['title']) || empty($data['content'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Titre et contenu requis']);
                return;
            }

            // Création de l'article
            $article = new Article([
                'title' => $data['title'],
                'content' => $data['content'],
                'introduction' => $data['introduction'] ?? '',
                'cover_image' => null,
                'id_user' => $userId,
                'published_at' => (new DateTime())->format('Y-m-d H:i:s'),
                'created_at' => (new DateTime())->format('Y-m-d H:i:s')
            ]);

            // Génération automatique du slug
            $article->generateSlug();

            // Gestion des tags
            if (!empty($data['tags'])) {
                $tags = json_decode($data['tags'], true);
                if (is_array($tags)) {
                    $article->setTags($tags);
                }
                error_log('Tags reçus : ' . $data['tags']);
                error_log('Tags décodés : ' . print_r($tags, true));
            }

            // Sauvegarde de l'article
            $articleId = $this->articleRepository->create($article);

            if ($articleId === null) {
                throw new \Exception('Erreur lors de la création de l\'article');
            }

            // Nettoyage de toute sortie précédente


            // Retour de la réponse
            header('Content-Type: application/json');
            http_response_code(201);

            $response = [
                'success' => true,
                'message' => 'Article créé avec succès',
                'articleId' => $articleId
            ];

            error_log("Réponse envoyée : " . json_encode($response));
            echo json_encode($response);
        } catch (\Exception $e) {
            // Nettoyage de toute sortie précédente


            // Log détaillé de l'erreur
            error_log("Erreur dans ArticleController::create : " . $e->getMessage());
            error_log("Stack trace : " . $e->getTraceAsString());

            header('Content-Type: application/json');
            http_response_code(500);

            $error = [
                'success' => false,
                'error' => 'Une erreur est survenue lors de la création de l\'article',
                'details' => $e->getMessage()
            ];

            error_log("Réponse d'erreur envoyée : " . json_encode($error));
            echo json_encode($error);
        }
    }



    #[Route('/api/articles/{id}/image', 'POST')]
    public function uploadImage(int $id): void
    {
        try {
            error_log("=== Début de l'upload d'image pour l'article $id ===");
            error_log('Fichiers reçus : ' . print_r($_FILES, true));
            // Vérification de l'authentification
            if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Token non fourni']);
                return;
            }

            // Vérification du fichier
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Image non fournie ou invalide']);
                return;
            }

            // Vérification du type MIME
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Format d\'image non supporté']);
                return;
            }

            // Création du dossier uploads s'il n'existe pas
            $uploadDir = __DIR__ . '/../../public/uploads/articles';
            if (!is_dir($uploadDir)) {
                error_log("Création du dossier d'upload : $uploadDir");
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new \Exception("Impossible de créer le dossier d'upload");
                }
                chmod($uploadDir, 0777);
            }

            // Vérification des permissions du dossier
            if (!is_writable($uploadDir)) {
                error_log("Le dossier d'upload n'est pas accessible en écriture : $uploadDir");
                throw new \Exception("Le dossier d'upload n'est pas accessible en écriture");
            }

            // Vérification de la taille du fichier (max 5MB)
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            if ($_FILES['image']['size'] > $maxFileSize) {
                throw new \Exception('La taille du fichier ne doit pas dépasser 5MB');
            }

            // Génération d'un nom de fichier unique
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('article_' . $id . '_') . '.' . $extension;
            $filepath = $uploadDir . '/' . $filename;

            // Déplacement du fichier
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                throw new \Exception('Erreur lors du déplacement du fichier');
            }

            // Mise à jour du chemin de l'image dans la base de données
            $article = $this->articleRepository->findById($id);
            error_log("Article récupéré : " . print_r($article, true));

            if (!$article) {
                throw new \Exception('Article non trouvé');
            }

            // Suppression de l'ancienne image si elle existe
            $oldImage = $article->getCoverImage();
            if ($oldImage) {
                $oldImagePath = __DIR__ . '/../../public' . $oldImage;
                error_log("Tentative de suppression de l'ancienne image : $oldImagePath");
                if (file_exists($oldImagePath) && is_file($oldImagePath)) {
                    unlink($oldImagePath);
                    error_log("Ancienne image supprimée avec succès");
                }
            }

            $imagePath = '/uploads/articles/' . $filename;
            error_log("Chemin de l'image à sauvegarder : " . $imagePath);

            $article->setCover_image($imagePath);
            error_log("Article avant update : " . print_r($article, true));

            $success = $this->articleRepository->update($article);
            error_log("Résultat de la mise à jour : " . ($success ? "succès" : "échec"));

            if (!$success) {
                throw new \Exception('Erreur lors de la mise à jour de l\'article');
            }

            // Réponse
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Image uploadée avec succès',
                'path' => '/uploads/articles/' . $filename
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans ArticleController::uploadImage : " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de l\'upload de l\'image',
                'details' => $e->getMessage()
            ]);
        }
    }

    #[Route('/api/articles', 'GET')]
    public function getAllArticles(): void
    {
        try {
            // Récupération des paramètres de pagination et filtres
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;

            // Préparation des filtres
            $filters = [];

            if (!empty($_GET['date'])) {
                $filters['date'] = $_GET['date'];
            }

            if (!empty($_GET['author'])) {
                $filters['author'] = $_GET['author'];
            }

            if (!empty($_GET['tags'])) {
                $filters['tags'] = $_GET['tags'];
            }

            // Récupération des articles
            $result = $this->articleRepository->findAll($page, $limit, $filters);

            // Transformation des articles en format JSON
            $articles = array_map(function ($article) {
                return [
                    'id' => $article->getId(),
                    'title' => $article->getTitle(),
                    'slug' => $article->getSlug(),
                    'content' => $article->getContent(),
                    'introduction' => $article->getIntroduction(),
                    'cover_image' => $article->getCoverImage(),
                    'published_at' => $article->getPublishedAt(),
                    'created_at' => $article->getCreatedAt(),
                    'tags' => $article->getTags()
                ];
            }, $result['articles']);

            // Envoi de la réponse
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $result['total']
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans ArticleController::getAllArticles : " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la récupération des articles',
                'details' => $e->getMessage()
            ]);
        }
    }

    #[Route('/api/articles/{id}', 'PUT')]
    public function update(int $id): void
    {
        try {
            // Vérification de l'authentification
            if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Token non fourni']);
                return;
            }

            // Récupération du token
            $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
            $payload = JWTServices::verify($token);

            // Récupérer l'article existant
            $article = $this->articleRepository->findById($id);
            if (!$article) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Article non trouvé']);
                return;
            }

            // Vérifier si l'utilisateur est l'auteur ou un admin
            if ($article->getUserId() !== $payload['id'] && !isset($payload['isAdmin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Non autorisé']);
                return;
            }

            // Récupération des données
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Données invalides']);
                return;
            }

            // Mise à jour des champs
            if (isset($data['title'])) {
                $article->setTitle($data['title']);
                // Regénérer le slug si le titre change
                $article->generateSlug();
            }
            if (isset($data['introduction'])) {
                $article->setIntroduction($data['introduction']);
            }
            if (isset($data['content'])) {
                $article->setContent($data['content']);
            }
            if (isset($data['tags'])) {
                $tags = is_string($data['tags']) ? json_decode($data['tags'], true) : $data['tags'];
                $article->setTags($tags);
            }

            // Mise à jour de l'article
            $success = $this->articleRepository->update($article);
            if (!$success) {
                throw new \Exception('Erreur lors de la mise à jour de l\'article');
            }

            // Réponse
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Article mis à jour avec succès',
                'data' => $article->toArray()
            ]);
        } catch (\Exception $e) {
            error_log("Erreur lors de la mise à jour de l'article : " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la mise à jour de l\'article',
                'details' => $e->getMessage()
            ]);
        }
    }
}
