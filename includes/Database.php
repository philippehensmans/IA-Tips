<?php
/**
 * Classe de gestion de la base de données SQLite
 */
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dbDir = dirname(DB_PATH);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->pdo = new PDO('sqlite:' . DB_PATH);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->initTables();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }

    private function initTables(): void {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                source_url TEXT,
                source_content TEXT,
                summary TEXT,
                main_points TEXT,
                human_rights_analysis TEXT,
                content TEXT,
                status TEXT DEFAULT 'draft',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                description TEXT
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS article_categories (
                article_id INTEGER,
                category_id INTEGER,
                PRIMARY KEY (article_id, category_id),
                FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            )
        ");

        // Insérer les catégories par défaut si elles n'existent pas
        $defaultCategories = [
            ['Droits civils et politiques', 'droits-civils-politiques', 'Libertés fondamentales, droit de vote, liberté d\'expression...'],
            ['Droits économiques et sociaux', 'droits-economiques-sociaux', 'Droit au travail, à la santé, à l\'éducation...'],
            ['Droits culturels', 'droits-culturels', 'Droit à la culture, aux pratiques culturelles...'],
            ['Droit international humanitaire', 'droit-humanitaire', 'Conventions de Genève, protection des civils...'],
            ['Droits des réfugiés', 'droits-refugies', 'Convention de 1951, protection internationale...'],
            ['Droits des enfants', 'droits-enfants', 'Convention des droits de l\'enfant...'],
            ['Droits des femmes', 'droits-femmes', 'CEDAW, égalité des genres...'],
            ['Non-discrimination', 'non-discrimination', 'Égalité, lutte contre les discriminations...']
        ];

        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)");
        foreach ($defaultCategories as $cat) {
            $stmt->execute($cat);
        }

        // Table des utilisateurs
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'editor',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME
            )
        ");

        // Créer un utilisateur admin par défaut si aucun n'existe
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        if ((int)$stmt->fetchColumn() === 0) {
            // Mot de passe par défaut: admin123 (à changer immédiatement!)
            $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $this->pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)")
                ->execute(['admin', 'admin@example.com', $defaultPassword, 'admin']);
        }

        // Table des pages (pour contenu modifiable comme l'accueil)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                title TEXT NOT NULL,
                content TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Créer la page d'accueil par défaut si elle n'existe pas
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM pages WHERE slug = 'home'");
        if ((int)$stmt->fetchColumn() === 0) {
            $defaultContent = '<p>Ce wiki est dédié à la veille et à l\'analyse d\'informations sous l\'angle des droits humains.</p>

<p>Chaque article publié ici est analysé pour identifier les points d\'attention concernant :</p>

<ul>
    <li><strong>Les droits civils et politiques</strong> - libertés fondamentales, droit de vote, liberté d\'expression...</li>
    <li><strong>Les droits économiques, sociaux et culturels</strong> - droit au travail, à la santé, à l\'éducation...</li>
    <li><strong>Le droit international humanitaire</strong> - Conventions de Genève, protection des civils en conflit armé...</li>
</ul>';
            $this->pdo->prepare("INSERT INTO pages (slug, title, content) VALUES (?, ?, ?)")
                ->execute(['home', 'Bienvenue', $defaultContent]);
        }
    }
}
