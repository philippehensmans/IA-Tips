<?php
/**
 * WikiTips - Recherche
 */
require_once __DIR__ . '/config.php';

$query = trim($_GET['q'] ?? '');
$pageTitle = 'Recherche' . ($query ? ' : ' . htmlspecialchars($query) : '') . ' - ' . SITE_NAME;

$results = [];
if ($query) {
    $articleModel = new Article();
    $results = $articleModel->search($query);
}

ob_start();
?>

<div class="article-header">
    <h1>Recherche<?= $query ? ' : ' . htmlspecialchars($query) : '' ?></h1>
</div>

<div class="article-section">
    <form action="<?= url('search.php') ?>" method="get" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 10px;">
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Rechercher..." style="flex: 1; padding: 10px; font-size: 16px;">
            <button type="submit" class="btn btn-primary">Rechercher</button>
        </div>
    </form>
</div>

<?php if ($query): ?>
    <?php if (empty($results)): ?>
        <div class="alert alert-info">
            Aucun résultat trouvé pour "<?= htmlspecialchars($query) ?>".
        </div>
    <?php else: ?>
        <div class="article-section">
            <p><?= count($results) ?> résultat(s) trouvé(s)</p>
        </div>

        <ul class="article-list">
            <?php foreach ($results as $article): ?>
                <li class="article-list-item">
                    <h3>
                        <a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>"><?= htmlspecialchars($article['title']) ?></a>
                        <span class="status-badge status-<?= $article['status'] ?>"><?= $article['status'] === 'published' ? 'Publié' : 'Brouillon' ?></span>
                    </h3>
                    <?php if ($article['summary']): ?>
                        <p class="summary"><?= htmlspecialchars(substr($article['summary'], 0, 250)) ?>...</p>
                    <?php endif; ?>
                    <div class="meta">
                        <?= date('d/m/Y', strtotime($article['created_at'])) ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
