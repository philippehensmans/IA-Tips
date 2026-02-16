<?php
/**
 * Classe de gestion des articles
 */
class Article {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPdo();
    }

    /**
     * Créer un nouvel article ou prompt
     */
    public function create(array $data): int {
        $slug = $this->generateSlug($data['title']);

        $stmt = $this->db->prepare("
            INSERT INTO articles (title, slug, type, source_url, source_content, summary, main_points, analysis, formatted_prompt, content, status)
            VALUES (:title, :slug, :type, :source_url, :source_content, :summary, :main_points, :analysis, :formatted_prompt, :content, :status)
        ");

        $stmt->execute([
            'title' => $data['title'],
            'slug' => $slug,
            'type' => $data['type'] ?? 'article',
            'source_url' => $data['source_url'] ?? null,
            'source_content' => $data['source_content'] ?? null,
            'summary' => $data['summary'] ?? null,
            'main_points' => $data['main_points'] ?? null,
            'analysis' => $data['analysis'] ?? null,
            'formatted_prompt' => $data['formatted_prompt'] ?? null,
            'content' => $data['content'] ?? null,
            'status' => $data['status'] ?? 'draft'
        ]);

        $articleId = (int)$this->db->lastInsertId();

        // Ajouter les catégories si présentes
        if (!empty($data['categories'])) {
            $this->setCategories($articleId, $data['categories']);
        }

        return $articleId;
    }

    /**
     * Mettre à jour un article ou prompt
     */
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['title', 'type', 'source_url', 'source_content', 'summary', 'main_points', 'analysis', 'formatted_prompt', 'content', 'status'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (isset($data['title'])) {
            $fields[] = "slug = :slug";
            $params['slug'] = $this->generateSlug($data['title'], $id);
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";

        $sql = "UPDATE articles SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);

        // Mettre à jour les catégories si présentes
        if (isset($data['categories'])) {
            $this->setCategories($id, $data['categories']);
        }

        return $result;
    }

    /**
     * Supprimer un article
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM articles WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Récupérer un article par ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $article = $stmt->fetch();

        if ($article) {
            $article['categories'] = $this->getCategories($id);
        }

        return $article ?: null;
    }

    /**
     * Récupérer un article par slug
     */
    public function getBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $article = $stmt->fetch();

        if ($article) {
            $article['categories'] = $this->getCategories($article['id']);
        }

        return $article ?: null;
    }

    /**
     * Récupérer tous les articles/prompts
     */
    public function getAll(string $status = null, int $limit = 50, int $offset = 0, string $type = null): array {
        $sql = "SELECT * FROM articles";
        $params = [];
        $conditions = [];

        if ($status) {
            $conditions[] = "status = :status";
            $params['status'] = $status;
        }

        if ($type) {
            $conditions[] = "type = :type";
            $params['type'] = $type;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();
        $articles = $stmt->fetchAll();

        foreach ($articles as &$article) {
            $article['categories'] = $this->getCategories($article['id']);
        }

        return $articles;
    }

    /**
     * Basculer le statut favori d'un article
     */
    public function toggleFavorite(int $id): bool {
        $stmt = $this->db->prepare("UPDATE articles SET is_favorite = CASE WHEN is_favorite = 1 THEN 0 ELSE 1 END WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $article = $this->getById($id);
        return $article ? (bool)$article['is_favorite'] : false;
    }

    /**
     * Récupérer tous les favoris
     */
    public function getFavorites(int $limit = 50, int $offset = 0, string $type = null): array {
        $sql = "SELECT * FROM articles WHERE is_favorite = 1";
        $params = [];

        if ($type) {
            $sql .= " AND type = :type";
            $params['type'] = $type;
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();
        $articles = $stmt->fetchAll();

        foreach ($articles as &$article) {
            $article['categories'] = $this->getCategories($article['id']);
        }

        return $articles;
    }

    /**
     * Rechercher des articles
     */
    public function search(string $query): array {
        $stmt = $this->db->prepare("
            SELECT * FROM articles
            WHERE title LIKE :query
            OR summary LIKE :query
            OR content LIKE :query
            ORDER BY created_at DESC
        ");
        $stmt->execute(['query' => '%' . $query . '%']);
        return $stmt->fetchAll();
    }

    /**
     * Générer un slug unique
     */
    private function generateSlug(string $title, ?int $excludeId = null): string {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        $baseSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Vérifier si un slug existe
     */
    private function slugExists(string $slug, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM articles WHERE slug = :slug";
        $params = ['slug' => $slug];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Définir les catégories d'un article
     */
    private function setCategories(int $articleId, array $categoryIds): void {
        $this->db->prepare("DELETE FROM article_categories WHERE article_id = ?")->execute([$articleId]);

        $stmt = $this->db->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");
        foreach ($categoryIds as $catId) {
            $stmt->execute([$articleId, $catId]);
        }
    }

    /**
     * Récupérer les catégories d'un article
     */
    private function getCategories(int $articleId): array {
        $stmt = $this->db->prepare("
            SELECT c.* FROM categories c
            JOIN article_categories ac ON c.id = ac.category_id
            WHERE ac.article_id = ?
        ");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }
}
