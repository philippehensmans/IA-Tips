<?php
/**
 * Classe de gestion des catégories
 */
class Category {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPdo();
    }

    /**
     * Récupérer toutes les catégories
     */
    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll();
    }

    /**
     * Récupérer une catégorie par ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Récupérer une catégorie par slug
     */
    public function getBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Récupérer les catégories par leurs slugs
     */
    public function getBySlugs(array $slugs): array {
        if (empty($slugs)) return [];

        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE slug IN ($placeholders)");
        $stmt->execute($slugs);
        return $stmt->fetchAll();
    }

    /**
     * Récupérer les articles d'une catégorie
     */
    public function getArticles(int $categoryId, int $limit = 50): array {
        $stmt = $this->db->prepare("
            SELECT a.* FROM articles a
            JOIN article_categories ac ON a.id = ac.article_id
            WHERE ac.category_id = ? AND a.status = 'published'
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$categoryId, $limit]);
        return $stmt->fetchAll();
    }
}
