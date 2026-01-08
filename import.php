<?php
/**
 * WikiTips - Importer et analyser du contenu
 */
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

    if (empty($content)) {
        $alert = ['type' => 'error', 'message' => 'Le contenu est requis.'];
    } else {
        // Vérifier que la clé API Claude est configurée
        if (CLAUDE_API_KEY === 'YOUR_API_KEY_HERE') {
            $alert = ['type' => 'error', 'message' => 'La clé API Claude n\'est pas configurée. Modifiez le fichier config.php ou définissez la variable d\'environnement CLAUDE_API_KEY.'];
        } else {
            $claude = new ClaudeService();
            $result = $claude->analyzeContent($content, $sourceUrl);

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
                    'source_url' => $sourceUrl,
                    'source_content' => $content,
                    'summary' => $result['summary'],
                    'main_points' => $result['main_points'],
                    'human_rights_analysis' => $result['human_rights_analysis'],
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

ob_start();
?>

<div class="article-header">
    <h1>Importer et analyser du contenu</h1>
</div>

<div class="article-section">
    <p>
        Collez ici le contenu que vous souhaitez analyser. L'IA identifiera les points principaux
        et les analysera sous l'angle des droits humains et du droit international humanitaire.
    </p>
</div>

<div class="editor-container">
    <form method="post" action="">
        <div class="form-group">
            <label for="source_url">URL source (optionnel)</label>
            <input type="url" id="source_url" name="source_url" placeholder="https://..." value="<?= htmlspecialchars($_POST['source_url'] ?? '') ?>">
            <p class="help-text">L'URL d'où provient le contenu</p>
        </div>

        <div class="form-group">
            <label for="content">Contenu à analyser *</label>
            <textarea id="content" name="content" class="large" required placeholder="Collez ici le texte de l'article, du rapport ou du document à analyser..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
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
