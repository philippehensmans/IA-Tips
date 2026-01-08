<?php
/**
 * WikiTips - Affichage d'une catégorie
 */
require_once __DIR__ . '/config.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . url('categories.php'));
    exit;
}

$categoryModel = new Category();
$category = $categoryModel->getBySlug($slug);

if (!$category) {
    http_response_code(404);
    $pageTitle = 'Catégorie non trouvée';
    ob_start();
    ?>
    <div class="article-header">
        <h1>Catégorie non trouvée</h1>
    </div>
    <p>La catégorie demandée n'existe pas. <a href="<?= url('categories.php') ?>">Voir toutes les catégories</a></p>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/templates/layout.php';
    exit;
}

$articles = $categoryModel->getArticles($category['id']);
$pageTitle = htmlspecialchars($category['name']) . ' - ' . SITE_NAME;

ob_start();
?>

<div class="article-header">
    <h1>Catégorie : <?= htmlspecialchars($category['name']) ?></h1>
</div>

<?php if ($category['description']): ?>
<div class="article-section">
    <p><?= htmlspecialchars($category['description']) ?></p>
</div>
<?php endif; ?>

<?php if (empty($articles)): ?>
    <div class="alert alert-info">
        Aucun article dans cette catégorie pour le moment.
    </div>
<?php else: ?>
    <ul class="article-list">
        <?php foreach ($articles as $article): ?>
            <li class="article-list-item">
                <h3><a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>"><?= htmlspecialchars($article['title']) ?></a></h3>
                <?php if ($article['summary']): ?>
                    <p class="summary"><?= htmlspecialchars(substr($article['summary'], 0, 200)) ?>...</p>
                <?php endif; ?>
                <div class="meta">
                    <?= date('d/m/Y', strtotime($article['created_at'])) ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
