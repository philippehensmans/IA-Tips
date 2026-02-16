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
     * Récupérer toutes les catégories (optionnellement filtrées par type)
     */
    public function getAll(string $type = null): array {
        if ($type) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE type = ? ORDER BY name");
            $stmt->execute([$type]);
        } else {
            $stmt = $this->db->query("SELECT * FROM categories ORDER BY name");
        }
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

    /**
     * Créer une nouvelle catégorie
     */
    public function create(array $data): int {
        $slug = $this->generateSlug($data['name']);

        $stmt = $this->db->prepare("INSERT INTO categories (name, slug, description, type) VALUES (:name, :slug, :description, :type)");
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'type' => $data['type'] ?? 'article'
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Mettre à jour une catégorie
     */
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params['name'] = $data['name'];
            $fields[] = "slug = :slug";
            $params['slug'] = $this->generateSlug($data['name'], $id);
        }
        if (isset($data['description'])) {
            $fields[] = "description = :description";
            $params['description'] = $data['description'];
        }
        if (isset($data['type'])) {
            $fields[] = "type = :type";
            $params['type'] = $data['type'];
        }

        if (empty($fields)) return false;

        $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Supprimer une catégorie
     */
    public function delete(int $id): bool {
        // Supprimer les associations article_categories
        $this->db->prepare("DELETE FROM article_categories WHERE category_id = ?")->execute([$id]);
        // Supprimer la catégorie
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Générer un slug unique pour une catégorie
     */
    private function generateSlug(string $name, ?int $excludeId = null): string {
        $slug = strtolower(trim($name));
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
        $sql = "SELECT COUNT(*) FROM categories WHERE slug = :slug";
        $params = ['slug' => $slug];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }
}
