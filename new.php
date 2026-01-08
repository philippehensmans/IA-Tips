<?php
/**
 * IA-Tips - Créer un nouvel article ou prompt
 */
require_once __DIR__ . '/config.php';

// Authentification requise
$auth = new Auth();
$auth->requireLogin();

// Déterminer le type depuis le paramètre GET ou POST
$type = $_GET['type'] ?? $_POST['type'] ?? 'article';
$isPrompt = $type === 'prompt';
$typeLabel = $isPrompt ? 'prompt' : 'article';

$pageTitle = ($isPrompt ? 'Nouveau prompt' : 'Nouvel article') . ' - ' . SITE_NAME;

$categoryModel = new Category();
$categories = $categoryModel->getAll($type);

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
    $analysis = trim($_POST['analysis'] ?? '');
    $formattedPrompt = trim($_POST['formatted_prompt'] ?? '');
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
            'type' => $type,
            'summary' => $summary,
            'main_points' => $mainPoints,
            'analysis' => $analysis,
            'formatted_prompt' => $formattedPrompt,
            'content' => $content,
            'source_url' => $sourceUrl,
            'status' => $status,
            'categories' => array_map('intval', $categoryIds)
        ]);

        $article = $articleModel->getById($articleId);
        $slug = $article['slug'];

        // Partage sur Bluesky si demandé (articles uniquement)
        $shareBluesky = isset($_POST['share_bluesky']) && $_POST['share_bluesky'] === '1';
        $blueskyParam = '';

        if ($shareBluesky && $blueskyConfigured && $status === 'published' && !$isPrompt) {
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
    <h1>
        <span class="type-badge type-<?= $isPrompt ? 'prompt' : 'article' ?>"><?= $isPrompt ? 'Prompt' : 'Article' ?></span>
        <?= $isPrompt ? 'Nouveau prompt' : 'Nouvel article' ?>
    </h1>
</div>

<div class="editor-container">
    <form method="post" action="">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

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
            <label for="summary"><?= $isPrompt ? 'Description' : 'Résumé' ?></label>
            <textarea id="summary" name="summary" rows="4"><?= htmlspecialchars($_POST['summary'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="main_points"><?= $isPrompt ? 'Cas d\'usage (HTML)' : 'Points principaux (HTML)' ?></label>
            <textarea id="main_points" name="main_points" rows="6"><?= htmlspecialchars($_POST['main_points'] ?? '') ?></textarea>
            <p class="help-text">Utilisez des balises &lt;ul&gt;&lt;li&gt; pour la liste</p>
        </div>

        <?php if ($isPrompt): ?>
        <div class="form-group">
            <label for="formatted_prompt">Prompt formaté</label>
            <textarea id="formatted_prompt" name="formatted_prompt" class="code-textarea large"><?= htmlspecialchars($_POST['formatted_prompt'] ?? '') ?></textarea>
            <p class="help-text">Le prompt prêt à être copié et utilisé</p>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="analysis"><?= $isPrompt ? 'Analyse du prompt (HTML)' : 'Analyse (HTML)' ?></label>
            <textarea id="analysis" name="analysis" class="large"><?= htmlspecialchars($_POST['analysis'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="content">Contenu additionnel / Notes</label>
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

        <?php if ($blueskyConfigured && !$isPrompt): ?>
        <div class="form-group bluesky-option">
            <label class="checkbox-label">
                <input type="checkbox" name="share_bluesky" value="1" <?= ($blueskyAutoShare || isset($_POST['share_bluesky'])) ? 'checked' : '' ?>>
                Partager sur Bluesky à la publication
            </label>
            <p class="help-text">L'article sera automatiquement partagé sur Bluesky si le statut est "Publié".</p>
        </div>
        <?php endif; ?>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><?= $isPrompt ? 'Créer le prompt' : 'Créer l\'article' ?></button>
            <a href="<?= url() ?>" class="btn">Annuler</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
