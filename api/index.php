<?php
/**
 * API REST pour IA-Tips
 * Gestion des articles/prompts et analyse via Claude
 */

// Forcer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

// Headers CORS et JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Router simple - supporte mod_rewrite ET accès direct
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Supprimer les préfixes possibles
$path = preg_replace('#^.*/api(/index\.php)?#', '', $path);
$path = trim($path, '/');

// Aussi supporter ?action=xxx pour les serveurs sans mod_rewrite
if (empty($path) && isset($_GET['action'])) {
    $path = $_GET['action'];
}

$segments = $path ? explode('/', $path) : [];
$method = $_SERVER['REQUEST_METHOD'];

// Récupérer le body JSON
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    $response = handleRequest($method, $segments, $input);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Gérer les requêtes
 */
function handleRequest(string $method, array $segments, array $input): array {
    $resource = $segments[0] ?? '';
    $id = $segments[1] ?? null;

    switch ($resource) {
        case 'articles':
            return handleArticles($method, $id, $input, $segments);

        case 'categories':
            return handleCategories($method, $id, $input);

        case 'analyze':
            return handleAnalyze($method, $input);

        case 'health':
            return ['status' => 'ok', 'timestamp' => date('c')];

        default:
            http_response_code(404);
            return ['error' => true, 'message' => 'Endpoint non trouvé'];
    }
}

/**
 * Gérer les articles/prompts
 */
function handleArticles(string $method, ?string $id, array $input, array $segments = []): array {
    $article = new Article();

    // Gérer /articles/{id}/favorite
    $action = $segments[2] ?? null;
    if ($id && $action === 'favorite' && $method === 'POST') {
        $isFavorite = $article->toggleFavorite((int)$id);
        return ['success' => true, 'is_favorite' => $isFavorite];
    }

    switch ($method) {
        case 'GET':
            if ($id) {
                $result = is_numeric($id) ? $article->getById((int)$id) : $article->getBySlug($id);
                if (!$result) {
                    http_response_code(404);
                    return ['error' => true, 'message' => 'Contenu non trouvé'];
                }
                return ['success' => true, 'data' => $result];
            }

            $status = $_GET['status'] ?? null;
            $type = $_GET['type'] ?? null;
            $search = $_GET['search'] ?? null;
            $favorites = $_GET['favorites'] ?? null;

            if ($search) {
                $results = $article->search($search);
            } elseif ($favorites) {
                $results = $article->getFavorites(50, 0, $type);
            } else {
                $results = $article->getAll($status, 50, 0, $type);
            }

            return ['success' => true, 'data' => $results];

        case 'POST':
            if (empty($input['title'])) {
                http_response_code(400);
                return ['error' => true, 'message' => 'Le titre est requis'];
            }

            $articleId = $article->create($input);
            $newArticle = $article->getById($articleId);

            http_response_code(201);
            return ['success' => true, 'data' => $newArticle];

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                return ['error' => true, 'message' => 'ID requis'];
            }

            $existing = $article->getById((int)$id);
            if (!$existing) {
                http_response_code(404);
                return ['error' => true, 'message' => 'Contenu non trouvé'];
            }

            $article->update((int)$id, $input);
            $updated = $article->getById((int)$id);

            return ['success' => true, 'data' => $updated];

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                return ['error' => true, 'message' => 'ID requis'];
            }

            $article->delete((int)$id);
            return ['success' => true, 'message' => 'Contenu supprimé'];

        default:
            http_response_code(405);
            return ['error' => true, 'message' => 'Méthode non autorisée'];
    }
}

/**
 * Gérer les catégories
 */
function handleCategories(string $method, ?string $id, array $input = []): array {
    $category = new Category();
    $type = $_GET['type'] ?? null;

    switch ($method) {
        case 'GET':
            if ($id) {
                $result = is_numeric($id) ? $category->getById((int)$id) : $category->getBySlug($id);
                if (!$result) {
                    http_response_code(404);
                    return ['error' => true, 'message' => 'Catégorie non trouvée'];
                }

                $result['articles'] = $category->getArticles($result['id']);
                return ['success' => true, 'data' => $result];
            }

            return ['success' => true, 'data' => $category->getAll($type)];

        case 'POST':
            if (empty($input['name'])) {
                http_response_code(400);
                return ['error' => true, 'message' => 'Le nom est requis'];
            }
            $catId = $category->create($input);
            $newCat = $category->getById($catId);
            http_response_code(201);
            return ['success' => true, 'data' => $newCat];

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                return ['error' => true, 'message' => 'ID requis'];
            }
            $existing = $category->getById((int)$id);
            if (!$existing) {
                http_response_code(404);
                return ['error' => true, 'message' => 'Catégorie non trouvée'];
            }
            $category->update((int)$id, $input);
            $updated = $category->getById((int)$id);
            return ['success' => true, 'data' => $updated];

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                return ['error' => true, 'message' => 'ID requis'];
            }
            $category->delete((int)$id);
            return ['success' => true, 'message' => 'Catégorie supprimée'];

        default:
            http_response_code(405);
            return ['error' => true, 'message' => 'Méthode non autorisée'];
    }
}

/**
 * Analyser du contenu via Claude
 */
function handleAnalyze(string $method, array $input): array {
    if ($method !== 'POST') {
        http_response_code(405);
        return ['error' => true, 'message' => 'Méthode non autorisée'];
    }

    // Vérifier la clé API (pour l'extension)
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($apiKey !== API_SECRET_KEY && API_SECRET_KEY !== 'change_this_secret_key_in_production') {
        http_response_code(401);
        return ['error' => true, 'message' => 'Clé API invalide'];
    }

    if (empty($input['content'])) {
        http_response_code(400);
        return ['error' => true, 'message' => 'Contenu requis'];
    }

    // Type par défaut: article
    $type = $input['type'] ?? 'article';

    $claude = new ClaudeService();
    $result = $claude->analyzeContent(
        $input['content'],
        $input['source_url'] ?? '',
        $type
    );

    if (isset($result['error'])) {
        http_response_code(500);
        return ['error' => true, 'message' => $result['error']];
    }

    // Optionnellement créer l'article/prompt directement
    if (!empty($input['create_article']) && $input['create_article'] === true) {
        $category = new Category();
        $categoryIds = [];

        if (!empty($result['suggested_categories'])) {
            $cats = $category->getBySlugs($result['suggested_categories']);
            $categoryIds = array_column($cats, 'id');
        }

        $article = new Article();
        $articleId = $article->create([
            'title' => $result['title'],
            'type' => $type,
            'source_url' => $input['source_url'] ?? null,
            'source_content' => $input['content'],
            'summary' => $result['summary'],
            'main_points' => $result['main_points'],
            'analysis' => $result['analysis'] ?? null,
            'formatted_prompt' => $result['formatted_prompt'] ?? null,
            'categories' => $categoryIds,
            'status' => 'draft'
        ]);

        $result['article_id'] = $articleId;
        $result['article_created'] = true;
    }

    return ['success' => true, 'data' => $result];
}
