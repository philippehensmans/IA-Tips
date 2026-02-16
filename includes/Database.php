<?php
/**
 * Classe de gestion de la base de données SQLite
 * Application de collecte d'articles et prompts IA
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
        $this->runMigrations();
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
                type TEXT DEFAULT 'article',
                source_url TEXT,
                source_content TEXT,
                summary TEXT,
                main_points TEXT,
                analysis TEXT,
                formatted_prompt TEXT,
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
                description TEXT,
                type TEXT DEFAULT 'article'
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
        // Catégories pour les articles IA
        $defaultCategories = [
            // Articles IA
            ['LLM & Modèles de langage', 'llm-modeles-langage', 'GPT, Claude, Llama, modèles de fondation...', 'article'],
            ['Agents IA', 'agents-ia', 'Agents autonomes, workflows, automatisation...', 'article'],
            ['Vision & Multimodal', 'vision-multimodal', 'Génération d\'images, vidéo, reconnaissance visuelle...', 'article'],
            ['RAG & Embeddings', 'rag-embeddings', 'Retrieval Augmented Generation, bases vectorielles...', 'article'],
            ['Fine-tuning & Entraînement', 'fine-tuning-entrainement', 'Personnalisation de modèles, techniques d\'entraînement...', 'article'],
            ['Éthique & Sécurité IA', 'ethique-securite-ia', 'Biais, alignement, sécurité, réglementation...', 'article'],
            ['Outils & Frameworks', 'outils-frameworks', 'LangChain, LlamaIndex, bibliothèques, APIs...', 'article'],
            ['Actualités IA', 'actualites-ia', 'Nouvelles, annonces, tendances du secteur...', 'article'],
            // Catégories pour les prompts
            ['Développement', 'prompt-developpement', 'Prompts pour la programmation, le code, le debugging...', 'prompt'],
            ['Rédaction', 'prompt-redaction', 'Prompts pour l\'écriture, la rédaction, le copywriting...', 'prompt'],
            ['Analyse', 'prompt-analyse', 'Prompts pour l\'analyse de données, documents, recherche...', 'prompt'],
            ['Créativité', 'prompt-creativite', 'Prompts pour le brainstorming, l\'idéation, la création...', 'prompt'],
            ['Productivité', 'prompt-productivite', 'Prompts pour l\'organisation, la planification, les tâches...', 'prompt'],
            ['Éducation', 'prompt-education', 'Prompts pour l\'apprentissage, l\'enseignement, l\'explication...', 'prompt'],
            ['Business', 'prompt-business', 'Prompts pour le marketing, les ventes, la stratégie...', 'prompt'],
            ['Système', 'prompt-systeme', 'System prompts, instructions de base, rôles...', 'prompt']
        ];

        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO categories (name, slug, description, type) VALUES (?, ?, ?, ?)");
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
            $defaultContent = '<p>Bienvenue sur votre base de connaissances personnelle dédiée à l\'Intelligence Artificielle.</p>

<p>Cette application vous permet de collecter et organiser :</p>

<ul>
    <li><strong>Des articles sur l\'IA</strong> - Actualités, tutoriels, analyses... L\'IA génère automatiquement un résumé et extrait les points clés.</li>
    <li><strong>Des prompts</strong> - Vos meilleurs prompts, formatés et prêts à être réutilisés dans vos projets.</li>
</ul>

<p>Utilisez l\'extension Chrome pour capturer rapidement du contenu depuis n\'importe quelle page web.</p>';
            $this->pdo->prepare("INSERT INTO pages (slug, title, content) VALUES (?, ?, ?)")
                ->execute(['home', 'Bienvenue', $defaultContent]);
        }
    }

    /**
     * Exécuter les migrations pour mettre à jour la structure existante
     */
    private function runMigrations(): void {
        // Vérifier et ajouter la colonne 'type' à la table articles si elle n'existe pas
        $stmt = $this->pdo->query("PRAGMA table_info(articles)");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'name');

        if (!in_array('type', $columnNames)) {
            $this->pdo->exec("ALTER TABLE articles ADD COLUMN type TEXT DEFAULT 'article'");
        }
        if (!in_array('analysis', $columnNames)) {
            $this->pdo->exec("ALTER TABLE articles ADD COLUMN analysis TEXT");
        }
        if (!in_array('formatted_prompt', $columnNames)) {
            $this->pdo->exec("ALTER TABLE articles ADD COLUMN formatted_prompt TEXT");
        }

        // Vérifier et ajouter la colonne 'type' à la table categories si elle n'existe pas
        $stmt = $this->pdo->query("PRAGMA table_info(categories)");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'name');

        if (!in_array('type', $columnNames)) {
            $this->pdo->exec("ALTER TABLE categories ADD COLUMN type TEXT DEFAULT 'article'");
        }

        // Ajouter la colonne is_favorite à la table articles
        $stmt = $this->pdo->query("PRAGMA table_info(articles)");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'name');

        if (!in_array('is_favorite', $columnNames)) {
            $this->pdo->exec("ALTER TABLE articles ADD COLUMN is_favorite INTEGER DEFAULT 0");
        }
    }
}
