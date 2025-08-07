<?php

declare(strict_types=1);

namespace App\repository;

use App\core\Database;
use App\model\Article;
use PDO;

class ArticleRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnexion();
    }

    /**
     * Récupère un article par son slug
     * @param string $slug Le slug de l'article
     * @return Article|null L'article trouvé ou null si non trouvé
     */
    public function findBySlug(string $slug): ?Article
    {
        try {
            $query = "SELECT a.*, u.username as author_name 
                     FROM article a 
                     LEFT JOIN user u ON a.id_user = u.id_user 
                     WHERE a.slug = :slug";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['slug' => $slug]);

            $articleData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$articleData) {
                return null;
            }

            // Récupération des catégories
            $queryCategories = "SELECT c.* 
                              FROM categories c 
                              JOIN articles_categories ac ON c.id_categories = ac.id_categories 
                              WHERE ac.id_article = :article_id";

            $stmtCategories = $this->db->prepare($queryCategories);
            $stmtCategories->execute(['article_id' => $articleData['id_article']]);

            $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

            // Ajout des catégories aux données
            $articleData['categories'] = array_map(function ($category) {
                return $category['id_categories'];
            }, $categories);

            return new Article($articleData);
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération de l'article par slug : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère un article par son ID
     * @param int $id L'ID de l'article à récupérer
     * @return Article|null L'article trouvé ou null si non trouvé
     */
    public function findById(int $id): ?Article
    {
        try {
            $query = "SELECT a.*, u.username as author_name 
                     FROM article a 
                     LEFT JOIN user u ON a.id_user = u.id_user 
                     WHERE a.id_article = :id";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);

            $articleData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$articleData) {
                return null;
            }

            // Récupération des catégories
            $queryCategories = "SELECT c.* 
                              FROM categories c 
                              JOIN articles_categories ac ON c.id_categories = ac.id_categories 
                              WHERE ac.id_article = :article_id";

            $stmtCategories = $this->db->prepare($queryCategories);
            $stmtCategories->execute(['article_id' => $id]);

            $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

            // Ajout des catégories aux données
            $articleData['categories'] = array_map(function ($category) {
                return $category['id_categories'];
            }, $categories);

            return new Article($articleData);
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération de l'article : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Met à jour un article dans la base de données
     * @param Article $article L'article à mettre à jour
     * @return bool True si la mise à jour a réussi, false sinon
     */
    public function update(Article $article): bool
    {
        try {
            $this->db->beginTransaction();

            // Log des données avant mise à jour
            error_log("Tentative de mise à jour de l'article : " . print_r($article, true));

            $query = "UPDATE article SET 
                     cover_image = :cover_image,
                     title = :title,
                     slug = :slug,
                     introduction = :introduction,
                     content = :content,
                     published_at = :published_at,
                     tags = :tags
                     WHERE id_article = :id";

            $stmt = $this->db->prepare($query);

            $params = [
                'id' => $article->getId(),
                'cover_image' => $article->getCoverImage(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'introduction' => $article->getIntroduction(),
                'content' => $article->getContent(),
                'published_at' => $article->getPublishedAt(),
                'tags' => $article->getTags() ? json_encode($article->getTags()) : null
            ];

            // Log des paramètres de la requête
            error_log("Paramètres de la requête de mise à jour : " . print_r($params, true));

            $success = $stmt->execute($params);

            // Mise à jour des catégories si nécessaire
            if ($article->getCategories() !== null) {
                error_log("Mise à jour des catégories pour l'article ID " . $article->getId());
                // Suppression des anciennes catégories
                $deleteStmt = $this->db->prepare("DELETE FROM articles_categories WHERE id_article = :article_id");
                $deleteStmt->execute(['article_id' => $article->getId()]);
                error_log("Anciennes catégories supprimées");

                // Insertion des nouvelles catégories
                $insertStmt = $this->db->prepare("INSERT INTO articles_categories (id_article, id_categories) VALUES (:article_id, :category_id)");
                foreach ($article->getCategories() as $categoryId) {
                    $insertStmt->execute([
                        'article_id' => $article->getId(),
                        'category_id' => $categoryId
                    ]);
                    error_log("Catégorie ID $categoryId ajoutée à l'article");
                }
                error_log("Toutes les nouvelles catégories ont été ajoutées");
            }

            $this->db->commit();
            return $success;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la mise à jour de l'article : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un article de la base de données
     * @param int $id L'ID de l'article à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->beginTransaction();

            // Suppression des relations avec les catégories
            $queryCategories = "DELETE FROM articles_categories WHERE id_article = :id";
            $stmtCategories = $this->db->prepare($queryCategories);
            $stmtCategories->execute(['id' => $id]);

            // Suppression de l'article
            $query = "DELETE FROM article WHERE id_article = :id";
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute(['id' => $id]);

            $this->db->commit();
            return $success;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la suppression de l'article : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les articles avec pagination et filtres
     * @param int $page Numéro de la page
     * @param int $limit Nombre d'articles par page
     * @param array $filters Filtres optionnels (date, author, tags)
     * @return array ['articles' => Article[], 'total' => int]
     */
    public function findAll(int $page = 1, int $limit = 10, array $filters = []): array
    {
        try {
            $offset = ($page - 1) * $limit;

            // Construction de la requête de base
            $query = "SELECT a.*, u.username as author_name 
                     FROM article a 
                     LEFT JOIN user u ON a.id_user = u.id_user";
            $countQuery = "SELECT COUNT(*) FROM article a";

            $whereConditions = [];
            $params = [];

            // Ajout des filtres
            if (!empty($filters['date'])) {
                $whereConditions[] = "DATE(a.published_at) = :date";
                $params['date'] = $filters['date'];
            }

            if (!empty($filters['author'])) {
                $whereConditions[] = "u.username LIKE :author";
                $params['author'] = "%{$filters['author']}%";
            }

            if (!empty($filters['tags'])) {
                $whereConditions[] = "a.tags LIKE :tags";
                $params['tags'] = "%{$filters['tags']}%";
            }

            // Ajout des conditions WHERE si nécessaire
            if (!empty($whereConditions)) {
                $whereClause = " WHERE " . implode(" AND ", $whereConditions);
                $query .= $whereClause;
                $countQuery .= $whereClause;
            }

            // Ajout de l'ordre et de la pagination
            $query .= " ORDER BY a.published_at DESC LIMIT :limit OFFSET :offset";

            // Exécution de la requête de comptage
            $stmtCount = $this->db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $stmtCount->bindValue($key, $value);
            }
            $stmtCount->execute();
            $total = (int)$stmtCount->fetchColumn();

            // Exécution de la requête principale
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $articlesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $articles = [];

            // Construction des objets Article
            foreach ($articlesData as $articleData) {
                $articles[] = new Article($articleData);
            }

            return [
                'articles' => $articles,
                'total' => $total
            ];
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des articles : " . $e->getMessage());
            return [
                'articles' => [],
                'total' => 0
            ];
        }
    }

    public function create(Article $article): ?int
    {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO article (cover_image, title, slug, introduction, content, id_user, published_at, tags) 
                     VALUES (:cover_image, :title, :slug, :introduction, :content, :id_user, :published_at, :tags)";

            $stmt = $this->db->prepare($query);

            $params = [
                'cover_image' => $article->getCoverImage(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'introduction' => $article->getIntroduction(),
                'content' => $article->getContent(),
                'id_user' => $article->getUserId(),
                'published_at' => $article->getPublishedAt(),
                'tags' => $article->getTags() ? json_encode($article->getTags()) : null
            ];

            $stmt->execute($params);
            $articleId = (int)$this->db->lastInsertId();

            // Si des catégories sont spécifiées, on les insère
            if ($article->getCategories()) {
                $queryCategories = "INSERT INTO articles_categories (id_article, id_categories) VALUES (:article_id, :category_id)";
                $stmtCategories = $this->db->prepare($queryCategories);

                foreach ($article->getCategories() as $categoryId) {
                    $stmtCategories->execute([
                        'article_id' => $articleId,
                        'category_id' => $categoryId
                    ]);
                }
            }

            $this->db->commit();
            return $articleId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la création de l'article : " . $e->getMessage());
            return null;
        }
    }
}
