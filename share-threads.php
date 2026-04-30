<?php
/**
 * IA-Tips - Partage sur Threads
 */
require_once __DIR__ . '/config.php';

$auth = new Auth();
$auth->requireLogin();

$threads = new ThreadsService();

if (!$threads->isConfigured()) {
    header('Location: ' . url('?error=threads_not_configured'));
    exit;
}

$articleId = (int)($_GET['id'] ?? 0);
if (!$articleId) {
    header('Location: ' . url());
    exit;
}

$articleModel = new Article();
$article = $articleModel->getById($articleId);

if (!$article) {
    header('Location: ' . url('?error=article_not_found'));
    exit;
}

$articleUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');
$articleUrl .= $_SERVER['HTTP_HOST'];
$articleUrl .= url('article.php?slug=' . urlencode($article['slug']));

$defaultText = $threads->formatArticlePost($article, $articleUrl);

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['text'] ?? $defaultText;

    if (mb_strlen($text) > 500) {
        $error = 'Le texte ne doit pas dépasser 500 caractères.';
    } else {
        $result = $threads->createPost($text);

        if ($result['success']) {
            header('Location: ' . url('article.php?slug=' . urlencode($article['slug']) . '&threads=success'));
            exit;
        } else {
            $error = $result['error'] ?? 'Erreur lors de la publication.';
        }
    }
}

$pageTitle = 'Partager sur Threads - ' . SITE_NAME;
ob_start();
?>

<div class="article-header">
    <h1>🧵 Partager sur Threads</h1>
</div>

<div class="share-preview">
    <h2>Article à partager</h2>
    <div class="infobox">
        <div class="infobox-header"><?= htmlspecialchars($article['title']) ?></div>
        <div class="infobox-content">
            <?php if ($article['summary']): ?>
            <p><?= htmlspecialchars(mb_substr(strip_tags($article['summary']), 0, 200)) ?><?= mb_strlen(strip_tags($article['summary'])) > 200 ? '...' : '' ?></p>
            <?php endif; ?>
            <p><small>🔗 <?= htmlspecialchars($articleUrl) ?></small></p>
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="error-message">
    ✗ <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="post" class="wiki-form">
    <div class="form-group">
        <label for="text">Texte du post (max 500 caractères)</label>
        <textarea name="text" id="text" rows="8" maxlength="500" required><?= htmlspecialchars($_POST['text'] ?? $defaultText) ?></textarea>
        <div class="char-counter">
            <span id="charCount"><?= mb_strlen($_POST['text'] ?? $defaultText) ?></span>/500 caractères
        </div>
    </div>

    <div class="form-info">
        <p><strong>Note :</strong> Le lien vers l'article est inclus dans le texte du post.</p>
    </div>

    <div class="form-actions">
        <a href="<?= url('article.php?slug=' . urlencode($article['slug'])) ?>" class="btn-secondary">Annuler</a>
        <button type="submit" class="btn-threads-large">🧵 Publier sur Threads</button>
    </div>
</form>

<script>
document.getElementById('text').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});
</script>

<style>
.share-preview { margin-bottom: 2rem; }
.share-preview h2 { font-size: 1.1rem; margin-bottom: 1rem; }
.char-counter { text-align: right; font-size: 0.9em; color: #666; margin-top: 0.5rem; }
.form-info { background: #f8f9fa; border: 1px solid #eaecf0; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
.form-info p { margin: 0; }
.btn-threads-large {
    background: #000;
    color: #fff;
    border: none;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    cursor: pointer;
    border-radius: 4px;
}
.btn-threads-large:hover { background: #333; }
.btn-secondary {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #a2a9b1;
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    border-radius: 4px;
    margin-right: 1rem;
}
.btn-secondary:hover { background: #eaecf0; }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
