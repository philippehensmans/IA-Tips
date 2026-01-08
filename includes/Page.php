<?php
/**
 * Classe de gestion des pages statiques
 */
class Page {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPdo();
    }

    /**
     * Récupérer une page par son slug
     */
    public function getBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Mettre à jour une page
     */
    public function update(string $slug, string $title, string $content): bool {
        $stmt = $this->db->prepare("UPDATE pages SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE slug = ?");
        return $stmt->execute([$title, $content, $slug]);
    }

    /**
     * Créer une page si elle n'existe pas
     */
    public function createIfNotExists(string $slug, string $title, string $content = ''): void {
        $stmt = $this->db->prepare("INSERT OR IGNORE INTO pages (slug, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$slug, $title, $content]);
    }
}
