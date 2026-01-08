<?php
/**
 * WikiTips - Page d'accueil
 */
require_once __DIR__ . '/config.php';

$pageTitle = SITE_NAME . ' - Accueil';

// Charger le contenu de la page d'accueil depuis la BDD
$pageModel = new Page();
$homePage = $pageModel->getBySlug('home');

$articleModel = new Article();
$recentArticles = $articleModel->getAll('published', 10);

$auth = new Auth();
$isLoggedIn = $auth->isLoggedIn();
$draftCount = $isLoggedIn ? count($articleModel->getAll('draft')) : 0;

ob_start();
?>

<div class="article-header">
    <?php if ($auth->isAdmin()): ?>
        <div class="article-actions">
            <a href="<?= url('edit-page.php?slug=home') ?>">Modifier cette page</a>
        </div>
    <?php endif; ?>
    <h1><?= htmlspecialchars($homePage['title'] ?? 'Bienvenue') ?> sur <?= SITE_NAME ?></h1>
</div>

<div class="article-section">
    <?= $homePage['content'] ?? '' ?>
</div>

<?php if ($isLoggedIn && $draftCount > 0): ?>
<div class="alert alert-info">
    Vous avez <strong><?= $draftCount ?></strong> article(s) en brouillon.
    <a href="<?= url('articles.php?status=draft') ?>">Voir les brouillons</a>
</div>
<?php endif; ?>

<div class="article-section">
    <h2>Articles récents</h2>

    <?php if (empty($recentArticles)): ?>
        <p>Aucun article publié pour le moment.</p>
        <?php if ($isLoggedIn): ?>
            <p><a href="<?= url('new.php') ?>" class="btn btn-primary">Créer votre premier article</a></p>
        <?php endif; ?>
    <?php else: ?>
        <ul class="article-list">
            <?php foreach ($recentArticles as $article): ?>
                <li class="article-list-item">
                    <h3><a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>"><?= htmlspecialchars($article['title']) ?></a></h3>
                    <?php if ($article['summary']): ?>
                        <p class="summary"><?= htmlspecialchars(substr($article['summary'], 0, 200)) ?>...</p>
                    <?php endif; ?>
                    <div class="meta">
                        Publié le <?= date('d/m/Y à H:i', strtotime($article['created_at'])) ?>
                        <?php if (!empty($article['categories'])): ?>
                            |
                            <?php foreach ($article['categories'] as $cat): ?>
                                <span class="category-tag"><?= htmlspecialchars($cat['name']) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
