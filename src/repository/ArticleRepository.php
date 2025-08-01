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
     * Crée un nouvel article dans la base de données
     * @param Article $article L'article à créer
     * @return int|null L'ID de l'article créé ou null en cas d'échec
     */
    public function create(Article $article): ?int
    {
        try {
            // Début de la transaction
            $this->db->beginTransaction();

            // Log des données avant insertion
            error_log("Tentative d'insertion d'article : " . print_r($article, true));

            // Insertion de l'article
            $query = "INSERT INTO article (cover_image, title, slug, introduction, content, id_user, published_at, tags) 
                     VALUES (:cover_image, :title, :slug, :introduction, :content, :id_user, :published_at, :tags)";

            $stmt = $this->db->prepare($query);

            // Si le slug n'est pas défini, on le génère
            if (empty($article->getSlug())) {
                $article->generateSlug();
            }

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

            // Log des paramètres
            error_log("Paramètres de la requête : " . print_r($params, true));

            $success = $stmt->execute($params);

            $articleId = (int)$this->db->lastInsertId();

            // Si des catégories sont spécifiées, on les insère
            if ($article->getCategories()) {
                $queryCategories = "INSERT INTO articles_categories (id_article, id_categories) 
                                  VALUES (:article_id, :category_id)";
                $stmtCategories = $this->db->prepare($queryCategories);

                foreach ($article->getCategories() as $categoryId) {
                    $stmtCategories->execute([
                        'article_id' => $articleId,
                        'category_id' => $categoryId
                    ]);
                }
            }

            // Validation de la transaction
            $this->db->commit();

            return $articleId;
        } catch (\Exception $e) {
            // En cas d'erreur, on annule la transaction
            $this->db->rollBack();
            error_log("Erreur lors de la création de l'article : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère un article par son ID avec ses catégories associées
     * @param int $id L'ID de l'article à récupérer
     * @return Article|null L'article trouvé ou null si non trouvé
     */
    public function getById(int $id): ?Article
    {
        try {
            // Récupération de l'article
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

            // Récupération des catégories de l'article
            $queryCategories = "SELECT c.* 
                              FROM categories c 
                              JOIN articles_categories ac ON c.id_categories = ac.id_categories 
                              WHERE ac.id_article = :article_id";

            $stmtCategories = $this->db->prepare($queryCategories);
            $stmtCategories->execute(['article_id' => $id]);

            $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

            // Ajout des catégories aux données de l'article
            $articleData['categories'] = array_map(function ($category) {
                return $category['id_categories'];
            }, $categories);

            // Création et hydratation de l'objet Article
            return new Article($articleData);
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération de l'article : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère un article par son slug avec ses catégories associées
     * @param string $slug Le slug de l'article à récupérer
     * @return Article|null L'article trouvé ou null si non trouvé
     */
    public function getBySlug(string $slug): ?Article
    {
        try {
            // Récupération de l'article
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

            // Récupération des catégories de l'article
            $queryCategories = "SELECT c.* 
                              FROM categories c 
                              JOIN articles_categories ac ON c.id_categories = ac.id_categories 
                              WHERE ac.id_article = :article_id";

            $stmtCategories = $this->db->prepare($queryCategories);
            $stmtCategories->execute(['article_id' => $articleData['id_article']]);

            $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

            // Ajout des catégories aux données de l'article
            $articleData['categories'] = array_map(function ($category) {
                return $category['id_categories'];
            }, $categories);

            // Création et hydratation de l'objet Article
            return new Article($articleData);
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération de l'article par slug : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère tous les articles avec pagination et filtres
     * @param int $page Numéro de la page (commence à 1)
     * @param int $perPage Nombre d'articles par page
     * @param array $filters Filtres optionnels (catégorie, date, auteur, tags)
     * @return array{articles: Article[], total: int} Articles et nombre total
     */
    public function getAll(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $params = [];
            $whereConditions = [];

            // Construction de la requête de base
            $baseQuery = "FROM article a 
                         LEFT JOIN user u ON a.id_user = u.id_user";

            // Ajout des conditions de filtrage
            if (!empty($filters['category'])) {
                $baseQuery .= " JOIN articles_categories ac ON a.id_article = ac.id_article";
                $whereConditions[] = "ac.id_categories = :category_id";
                $params['category_id'] = $filters['category'];
            }

            if (!empty($filters['author'])) {
                $whereConditions[] = "u.username LIKE :author";
                $params['author'] = "%{$filters['author']}%";
            }

            if (!empty($filters['date'])) {
                $whereConditions[] = "DATE(a.published_at) = :date";
                $params['date'] = $filters['date'];
            }

            if (!empty($filters['tags'])) {
                $whereConditions[] = "JSON_CONTAINS(a.tags, :tags)";
                $params['tags'] = json_encode($filters['tags']);
            }

            // Assemblage de la clause WHERE
            $whereClause = !empty($whereConditions)
                ? "WHERE " . implode(" AND ", $whereConditions)
                : "";

            // Compte total des articles
            $countQuery = "SELECT COUNT(DISTINCT a.id_article) as total " . $baseQuery . " " . $whereClause;
            $stmtCount = $this->db->prepare($countQuery);
            $stmtCount->execute($params);
            $total = (int)$stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

            // Récupération des articles
            $query = "SELECT DISTINCT a.*, u.username as author_name " .
                $baseQuery . " " . $whereClause .
                " ORDER BY a.published_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            // Bind des autres paramètres
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }

            $stmt->execute();
            $articlesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Création des objets Article
            $articles = [];
            foreach ($articlesData as $articleData) {
                // Récupération des catégories pour chaque article
                $queryCategories = "SELECT c.* 
                                  FROM categories c 
                                  JOIN articles_categories ac ON c.id_categories = ac.id_categories 
                                  WHERE ac.id_article = :article_id";

                $stmtCategories = $this->db->prepare($queryCategories);
                $stmtCategories->execute(['article_id' => $articleData['id_article']]);
                $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

                // Ajout des catégories aux données de l'article
                $articleData['categories'] = array_map(function ($category) {
                    return $category['id_categories'];
                }, $categories);

                // Création de l'objet Article
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
}
