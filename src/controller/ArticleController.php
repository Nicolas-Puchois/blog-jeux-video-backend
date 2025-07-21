<?php

declare(strict_types=1);

namespace App\controller;

use App\core\attributes\Route;
use App\model\Article;
use App\repository\ArticleRepository;
use DateTime;

class ArticleController
{
    private ArticleRepository $articleRepository;

    public function __construct()
    {
        $this->articleRepository = new ArticleRepository;
    }

    #[Route('/api/articles', 'POST')]
    public function create(): void
    {
        // Activer la capture de la sortie

        try {
            // Debug des données reçues
            error_log("=== Début de la requête de création d'article ===");
            error_log('Données POST reçues : ' . print_r($_POST, true));
            error_log('Fichiers reçus : ' . print_r($_FILES, true));
            error_log('Taille maximale autorisée : ' . ini_get('upload_max_filesize'));
            error_log('Taille maximale du POST : ' . ini_get('post_max_size'));
            if (isset($_FILES['image'])) {
                error_log('Fichier reçu : ' . $_FILES['image']['name']);
                error_log('Taille du fichier : ' . $_FILES['image']['size']);
                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    error_log('Erreur upload : ' . $_FILES['image']['error']);
                }
            } else {
                error_log('Aucun fichier reçu');
            }
            error_log('POST reçu : ' . print_r($_POST, true));

            // Vérification de l'authentification
            if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Token non fourni']);
                return;
            }

            // Vérification du rôle (à implémenter avec le service JWT)
            // TODO: Récupérer l'ID de l'utilisateur depuis le token JWT

            // Validation des données requises
            if (empty($_POST['title']) || empty($_POST['content'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Titre et contenu requis']);
                return;
            }

            // Création de l'article
            $article = new Article([

                'title' => $_POST['title'],
                'content' => $_POST['content'],
                'introduction' => $_POST['introduction'] ?? '',
                'cover_image' => null,
                'id_user' => 1,
                'published_at' => (new DateTime())->format('Y-m-d H:i:s'),
                'created_at' => (new DateTime())->format('Y-m-d H:i:s')
            ]);

            // Génération automatique du slug
            $article->generateSlug();

            // Gestion des tags
            if (!empty($_POST['tags'])) {
                $tags = json_decode($_POST['tags'], true);
                if (is_array($tags)) {
                    $article->setTags($tags);
                }
                error_log('Tags reçus : ' . $_POST['tags']);
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
}
