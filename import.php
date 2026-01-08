<?php
/**
 * IA-Tips - Importer et analyser du contenu (article ou prompt)
 */

// Forcer l'affichage des erreurs (utile sur serveurs partagés)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gestionnaire d'erreurs personnalisé (ignore les warnings de constantes redéfinies)
set_error_handler(function($severity, $message, $file, $line) {
    // Ignorer les avertissements de constantes déjà définies
    if (strpos($message, 'Constant') !== false && strpos($message, 'already defined') !== false) {
        return true; // Ignorer silencieusement
    }
    // Convertir les autres erreurs en exceptions
    if ($severity & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false; // Laisser PHP gérer les autres erreurs normalement
});

try {
    require_once __DIR__ . '/config.php';

    // Authentification requise
    $auth = new Auth();
    $auth->requireLogin();

    $pageTitle = 'Importer du contenu - ' . SITE_NAME;

    $alert = null;
    $analysisResult = null;

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $content = trim($_POST['content'] ?? '');
        $sourceUrl = trim($_POST['source_url'] ?? '');
        $type = $_POST['type'] ?? 'article';

        // Gestion de l'upload de fichier
        if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $parseResult = FileParser::parse($_FILES['file']);
            if (isset($parseResult['error'])) {
                $alert = ['type' => 'error', 'message' => 'Erreur fichier : ' . $parseResult['error']];
            } else {
                $content = $parseResult['content'];
                // Utiliser le nom du fichier comme source si pas d'URL
                if (empty($sourceUrl)) {
                    $sourceUrl = 'Fichier: ' . $_FILES['file']['name'];
                }
            }
        }

        if (empty($content) && $alert === null) {
            $alert = ['type' => 'error', 'message' => 'Le contenu est requis (collez du texte ou uploadez un fichier).'];
        } elseif ($alert === null) {
            // Vérifier que la clé API Claude est configurée
            if (CLAUDE_API_KEY === 'YOUR_API_KEY_HERE') {
                $alert = ['type' => 'error', 'message' => 'La clé API Claude n\'est pas configurée. Modifiez le fichier config.php ou définissez la variable d\'environnement CLAUDE_API_KEY.'];
            } else {
                $claude = new ClaudeService();
                $result = $claude->analyzeContent($content, $sourceUrl, $type);

                if (isset($result['error'])) {
                    $alert = ['type' => 'error', 'message' => 'Erreur d\'analyse : ' . $result['error']];
                } else {
                    $analysisResult = $result;

                    // Créer automatiquement un brouillon
                    $categoryModel = new Category();
                    $categoryIds = [];
                    if (!empty($result['suggested_categories'])) {
                        $cats = $categoryModel->getBySlugs($result['suggested_categories']);
                        $categoryIds = array_column($cats, 'id');
                    }

                    $articleModel = new Article();
                    $articleId = $articleModel->create([
                        'title' => $result['title'],
                        'type' => $type,
                        'source_url' => $sourceUrl,
                        'source_content' => $content,
                        'summary' => $result['summary'],
                        'main_points' => $result['main_points'],
                        'analysis' => $result['analysis'] ?? null,
                        'formatted_prompt' => $result['formatted_prompt'] ?? null,
                        'categories' => $categoryIds,
                        'status' => 'draft'
                    ]);

                    $newArticle = $articleModel->getById($articleId);

                    header('Location: ' . url('edit.php?id=' . $articleId));
                    exit;
                }
            }
        }
    }
} catch (Throwable $e) {
    // Afficher l'erreur de manière visible
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Erreur</title></head><body>';
    echo '<h1 style="color: red;">Erreur PHP détectée</h1>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>Fichier:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
    echo '<p><strong>Ligne:</strong> ' . $e->getLine() . '</p>';
    echo '<h2>Stack trace:</h2>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</body></html>';
    exit;
}

ob_start();
?>

<div class="article-header">
    <h1>Importer du contenu</h1>
</div>

<div class="article-section">
    <p>
        Collez du texte ou importez un fichier (MD, PDF, TXT). Sélectionnez le type de contenu :
    </p>
    <ul>
        <li><strong>Article</strong> : L'IA génèrera un résumé et extraira les points clés.</li>
        <li><strong>Prompt</strong> : L'IA reformatera le prompt pour qu'il soit directement utilisable.</li>
    </ul>
</div>

<div class="editor-container">
    <form method="post" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="type">Type de contenu *</label>
            <div class="type-selector">
                <label class="type-option">
                    <input type="radio" name="type" value="article" <?= ($_POST['type'] ?? 'article') === 'article' ? 'checked' : '' ?>>
                    <span class="type-card">
                        <span class="type-icon">📄</span>
                        <span class="type-label">Article</span>
                        <span class="type-desc">Actualité, tutoriel, analyse sur l'IA</span>
                    </span>
                </label>
                <label class="type-option">
                    <input type="radio" name="type" value="prompt" <?= ($_POST['type'] ?? '') === 'prompt' ? 'checked' : '' ?>>
                    <span class="type-card">
                        <span class="type-icon">💬</span>
                        <span class="type-label">Prompt</span>
                        <span class="type-desc">Prompt à reformater et sauvegarder</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="source_url">URL source (optionnel)</label>
            <input type="url" id="source_url" name="source_url" placeholder="https://..." value="<?= htmlspecialchars($_POST['source_url'] ?? '') ?>">
            <p class="help-text">L'URL d'où provient le contenu</p>
        </div>

        <div class="form-group">
            <label for="file">Importer un fichier (optionnel)</label>
            <input type="file" id="file" name="file" accept=".md,.txt,.pdf">
            <p class="help-text">Formats acceptés : Markdown (.md), Texte (.txt), PDF (.pdf) - Max 10 Mo</p>
        </div>

        <div class="form-group">
            <label for="content">Ou collez le contenu ici</label>
            <textarea id="content" name="content" class="large" placeholder="Collez ici le texte de l'article ou le prompt à analyser..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
            <p class="help-text">Si vous importez un fichier, ce champ sera ignoré</p>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary" id="analyzeBtn">
                Analyser et créer un brouillon
            </button>
            <a href="<?= url() ?>" class="btn">Annuler</a>
        </div>

        <p class="help-text" style="margin-top: 15px;">
            L'analyse peut prendre quelques secondes. Un brouillon sera créé automatiquement
            que vous pourrez ensuite modifier avant publication.
        </p>
    </form>
</div>

<script>
document.querySelector('form').addEventListener('submit', function() {
    var btn = document.getElementById('analyzeBtn');
    btn.disabled = true;
    btn.textContent = 'Analyse en cours...';
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
