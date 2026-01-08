<?php
/**
 * WikiTips - Créer un nouvel article
 */
require_once __DIR__ . '/config.php';

// Authentification requise
$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Nouvel article - ' . SITE_NAME;

$categoryModel = new Category();
$categories = $categoryModel->getAll();

// Vérifier si Bluesky est configuré
$bluesky = new BlueskyService();
$blueskyConfigured = $bluesky->isConfigured();
$blueskyAutoShare = defined('BLUESKY_AUTO_SHARE') && BLUESKY_AUTO_SHARE;

$alert = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $mainPoints = trim($_POST['main_points'] ?? '');
    $humanRightsAnalysis = trim($_POST['human_rights_analysis'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $sourceUrl = trim($_POST['source_url'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $categoryIds = $_POST['categories'] ?? [];

    if (empty($title)) {
        $alert = ['type' => 'error', 'message' => 'Le titre est requis.'];
    } else {
        $articleModel = new Article();
        $articleId = $articleModel->create([
            'title' => $title,
            'summary' => $summary,
            'main_points' => $mainPoints,
            'human_rights_analysis' => $humanRightsAnalysis,
            'content' => $content,
            'source_url' => $sourceUrl,
            'status' => $status,
            'categories' => array_map('intval', $categoryIds)
        ]);

        $article = $articleModel->getById($articleId);
        $slug = $article['slug'];

        // Partage sur Bluesky si demandé
        $shareBluesky = isset($_POST['share_bluesky']) && $_POST['share_bluesky'] === '1';
        $blueskyParam = '';

        if ($shareBluesky && $blueskyConfigured && $status === 'published') {
            // Construire l'URL de l'article
            $articleUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');
            $articleUrl .= $_SERVER['HTTP_HOST'];
            $articleUrl .= url('article.php?slug=' . urlencode($slug));

            $result = $bluesky->shareArticle($article, $articleUrl);

            if ($result['success']) {
                $blueskyParam = '&bluesky=success';
            } else {
                $blueskyParam = '&bluesky=error&error=' . urlencode($result['error'] ?? 'Erreur inconnue');
            }
        }

        header('Location: ' . url('article.php?slug=' . $slug . $blueskyParam));
        exit;
    }
}

ob_start();
?>

<div class="article-header">
    <h1>Créer un nouvel article</h1>
</div>

<div class="editor-container">
    <form method="post" action="">
        <div class="form-group">
            <label for="title">Titre *</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="source_url">URL source</label>
            <input type="url" id="source_url" name="source_url" placeholder="https://..." value="<?= htmlspecialchars($_POST['source_url'] ?? '') ?>">
            <p class="help-text">L'URL de la source originale (optionnel)</p>
        </div>

        <div class="form-group">
            <label for="summary">Résumé</label>
            <textarea id="summary" name="summary" rows="4"><?= htmlspecialchars($_POST['summary'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="main_points">Points principaux (HTML)</label>
            <textarea id="main_points" name="main_points" rows="6"><?= htmlspecialchars($_POST['main_points'] ?? '') ?></textarea>
            <p class="help-text">Utilisez des balises &lt;ul&gt;&lt;li&gt; pour la liste des points</p>
        </div>

        <div class="form-group">
            <label for="human_rights_analysis">Analyse des droits humains (HTML)</label>
            <textarea id="human_rights_analysis" name="human_rights_analysis" class="large"><?= htmlspecialchars($_POST['human_rights_analysis'] ?? '') ?></textarea>
            <p class="help-text">Analyse sous l'angle des droits humains, droit international humanitaire, etc.</p>
        </div>

        <div class="form-group">
            <label for="content">Contenu additionnel</label>
            <textarea id="content" name="content" rows="6"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Catégories</label>
            <div class="checkbox-group">
                <?php foreach ($categories as $cat): ?>
                    <label>
                        <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                            <?= in_array($cat['id'], $_POST['categories'] ?? []) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="draft" <?= ($_POST['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                <option value="published" <?= ($_POST['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publié</option>
            </select>
        </div>

        <?php if ($blueskyConfigured): ?>
        <div class="form-group bluesky-option">
            <label class="checkbox-label">
                <input type="checkbox" name="share_bluesky" value="1" <?= ($blueskyAutoShare || isset($_POST['share_bluesky'])) ? 'checked' : '' ?>>
                🦋 Partager sur Bluesky à la publication
            </label>
            <p class="help-text">L'article sera automatiquement partagé sur Bluesky si le statut est "Publié".</p>
        </div>
        <?php endif; ?>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Créer l'article</button>
            <a href="<?= url() ?>" class="btn">Annuler</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
