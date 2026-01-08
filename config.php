<?php
/**
 * WikiTips - Configuration
 * Application de publication d'articles avec analyse des droits humains
 */

// Charger la configuration locale si elle existe (non versionnée)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Mode debug (à désactiver en production)
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}

// Configuration de la base de données SQLite
if (!defined('DB_PATH')) {
    define('DB_PATH', __DIR__ . '/data/wikitips.db');
}

// Configuration de l'API Claude
if (!defined('CLAUDE_API_KEY')) {
    define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: 'YOUR_API_KEY_HERE');
}
if (!defined('CLAUDE_API_URL')) {
    define('CLAUDE_API_URL', 'https://api.anthropic.com/v1/messages');
}
if (!defined('CLAUDE_MODEL')) {
    define('CLAUDE_MODEL', 'claude-sonnet-4-20250514');
}

// Configuration du site
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'WikiTips - Droits Humains');
}
if (!defined('SITE_DESCRIPTION')) {
    define('SITE_DESCRIPTION', 'Veille et analyse sous l\'angle des droits humains');
}
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost:8080');
}

// Configuration de sécurité
if (!defined('API_SECRET_KEY')) {
    define('API_SECRET_KEY', getenv('API_SECRET_KEY') ?: 'change_this_secret_key_in_production');
}

// Configuration Bluesky (AT Protocol)
// Créez un "App Password" sur https://bsky.app/settings/app-passwords
if (!defined('BLUESKY_IDENTIFIER')) {
    define('BLUESKY_IDENTIFIER', ''); // Votre handle (ex: user.bsky.social) ou email
}
if (!defined('BLUESKY_APP_PASSWORD')) {
    define('BLUESKY_APP_PASSWORD', ''); // App Password (pas votre mot de passe principal)
}
if (!defined('BLUESKY_AUTO_SHARE')) {
    define('BLUESKY_AUTO_SHARE', false); // Partage automatique à la création d'article
}

// Chemin de base (auto-détecté ou défini manuellement)
// Ex: si installé dans /wikitips/, définir BASE_PATH = '/wikitips'
if (!defined('BASE_PATH')) {
    // Auto-détection du chemin de base
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = rtrim(dirname($scriptName), '/');
    // Éviter les doubles slashes pour la racine
    define('BASE_PATH', $basePath === '/' ? '' : $basePath);
}

/**
 * Génère une URL avec le chemin de base
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . $path;
}

// Fuseau horaire
date_default_timezone_set('Europe/Brussels');

// Gestion des erreurs
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Autoloader simple
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/includes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
