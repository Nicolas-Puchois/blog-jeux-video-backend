<?php

declare(strict_types=1);

namespace App\controller;

use Exception;
use App\core\attributes\Route;
use App\model\Article;
use App\repository\ArticleRepository;
use App\services\JWTServices;
use App\Utils\SecurityUtils;
use App\middleware\CSRFMiddleware;
use DateTime;

class ArticleController
{
    private ArticleRepository $articleRepository;


    public function __construct()
    {
        $this->articleRepository = new ArticleRepository();
    }

    #[Route('/api/article-delete/{id}', 'DELETE')]
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

            if (!$payload) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Token invalide']);
                return;
            }

            $article = $this->articleRepository->findById($id);
            if (!$article) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Article non trouvé']);
                return;
            }

            // Vérifier si l'utilisateur est l'auteur ou un admin
            $isAdmin = is_array($payload['role']) ? in_array('admin', $payload['role']) : $payload['role'] === 'admin';

            if ($article->getUserId() !== (int)$payload['id'] && !$isAdmin) {
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
            $article = $this->articleRepository->findById($id);

            if (!$article) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Article non trouvé'
                ]);
                return;
            }

            $articleData = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'content' => $article->getContent(),
                'introduction' => $article->getIntroduction(),
                'cover_image' => $article->getCoverImage(),
                'published_at' => $article->getPublishedAt(),
                'created_at' => $article->getCreatedAt(),
                'user_id' => $article->getUserId(),
                'tags' => $article->getTags()
            ];

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $articleData
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans getById: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la récupération de l\'article'
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
        try {
            // Ajouter la vérification CSRF en premier
            if (!CSRFMiddleware::verifyToken()) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Token CSRF invalide'
                ]);
                return;
            }

            // Vérifier et décoder le token JWT
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (empty($authHeader)) {
                throw new Exception('Token JWT manquant');
            }

            // Extraire le token du header "Bearer <token>"
            $token = str_replace('Bearer ', '', $authHeader);
            $payload = JWTServices::verify($token);
            if (!$payload || !isset($payload['id'])) {
                throw new Exception('Token JWT invalide ou expiré');
            }

            // Récupérer les données de l'article
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Données invalides');
            }

            // Créer l'article avec l'ID utilisateur du token
            $article = new Article();
            $article->setTitle($data['title']);
            $article->setContent($data['content']);
            $article->setUserId((int)$payload['id']);
            $article->setPublished_at((new DateTime())->format('Y-m-d H:i:s'));

            // Ajout de l'introduction
            if (isset($data['introduction'])) {
                $article->setIntroduction($data['introduction']);
            }

            if (isset($data['tags'])) {
                $article->setTags($data['tags']);
            }

            // Générer le slug avant la création
            $baseSlug = strtolower($article->getTitle());
            $baseSlug = iconv('UTF-8', 'ASCII//TRANSLIT', $baseSlug);
            $baseSlug = preg_replace('/[^a-z0-9]+/', '-', $baseSlug);
            $baseSlug = trim($baseSlug, '-');
            $timestamp = substr((string)time(), -4);
            $article->setSlug($baseSlug . '-' . $timestamp);

            // Sauvegarde de l'article
            $articleId = $this->articleRepository->create($article);

            if ($articleId === null) {
                throw new \Exception('Erreur lors de la création de l\'article');
            }

            // Retour de la réponse
            http_response_code(201);
            $response = [
                'success' => true,
                'message' => 'Article créé avec succès',
                'data' => [
                    'id_article' => $articleId,
                    'title' => $article->getTitle(),
                    'content' => $article->getContent(),
                    'introduction' => $article->getIntroduction(),
                    'tags' => $article->getTags()
                ]
            ];

            echo json_encode($response);
        } catch (\Exception $e) {
            error_log("Erreur dans ArticleController::create : " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);

            $error = [
                'success' => false,
                'error' => 'Une erreur est survenue lors de la création de l\'article',
                'details' => $e->getMessage()
            ];

            echo json_encode($error);
        }
    }

    #[Route('/api/articles/{id}/image', 'POST')]
    public function uploadImage(int $id): void
    {
        try {
            // Ajouter la vérification CSRF
            if (!CSRFMiddleware::verifyToken()) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Token CSRF invalide'
                ]);
                return;
            }

            // Vérifier si un fichier a été envoyé
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = match ($_FILES['image']['error'] ?? -1) {
                    UPLOAD_ERR_INI_SIZE => "L'image est trop volumineuse (max: " . ini_get('upload_max_filesize') . ")",
                    UPLOAD_ERR_FORM_SIZE => "L'image dépasse la taille maximale autorisée",
                    UPLOAD_ERR_NO_FILE => "Aucune image n'a été envoyée",
                    default => "Erreur lors de l'upload de l'image"
                };
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $errorMessage]);
                return;
            }

            // Vérifier le type MIME
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
            if (!in_array($mimeType, $allowedTypes)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF'
                ]);
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
            $maxFileSize = 10 * 1024 * 1024; // 5MB
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
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

            $result = $this->articleRepository->findAll($page, $limit);

            // Plus besoin d'échapper les caractères ici
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

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => [
                    'articles' => $articles,
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
    public function updateArticle(int $id): void
    {
        try {
            // Ajouter la vérification CSRF
            if (!CSRFMiddleware::verifyToken()) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Token CSRF invalide'
                ]);
                return;
            }

            if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Token non fourni']);
                return;
            }

            $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);

            $payload = JWTServices::verify($token);

            if (!$payload) {
                error_log("Token invalide ou non décodé");
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Token invalide']);
                return;
            }

            // Récupérer l'article existant
            $article = $this->articleRepository->findById($id);
            if (!$article) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Article non trouvé']);
                return;
            }

            // Vérifier si l'utilisateur est l'auteur ou un admin
            $isAdmin = is_array($payload['role']) ? in_array('admin', $payload['role']) : $payload['role'] === 'admin';

            if ($article->getUserId() !== (int)$payload['id'] && !$isAdmin) {
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

            // Sanitize les données reçues
            $data = SecurityUtils::sanitizeRequestData($data);

            // Mise à jour des champs avec les données nettoyées
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
            $article->setPublished_at((new DateTime())->format('Y-m-d H:i:s'));

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
            error_log("Erreur lors de la mise à jour: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la mise à jour de l\'article',
                'details' => $e->getMessage()
            ]);
        }
    }
}
