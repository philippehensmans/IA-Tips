<?php
/**
 * IA-Tips - Page d'accueil
 */
require_once __DIR__ . '/config.php';

$pageTitle = SITE_NAME . ' - Accueil';

// Charger le contenu de la page d'accueil depuis la BDD
$pageModel = new Page();
$homePage = $pageModel->getBySlug('home');

$articleModel = new Article();
// Récupérer articles et prompts séparément
$recentArticles = $articleModel->getAll('published', 5, 0, 'article');
$recentPrompts = $articleModel->getAll('published', 5, 0, 'prompt');

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
    Vous avez <strong><?= $draftCount ?></strong> élément(s) en brouillon.
    <a href="<?= url('articles.php?status=draft') ?>">Voir les brouillons</a>
</div>
<?php endif; ?>

<div class="home-columns">
    <!-- Colonne Articles -->
    <div class="home-column">
        <div class="column-header">
            <h2>📄 Articles récents</h2>
            <a href="<?= url('articles.php?type=article') ?>" class="view-all">Voir tous</a>
        </div>

        <?php if (empty($recentArticles)): ?>
            <p class="empty-message">Aucun article publié pour le moment.</p>
            <?php if ($isLoggedIn): ?>
                <p><a href="<?= url('import.php') ?>" class="btn btn-small">Importer un article</a></p>
            <?php endif; ?>
        <?php else: ?>
            <ul class="content-list">
                <?php foreach ($recentArticles as $article): ?>
                    <li class="content-list-item">
                        <h3><a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>"><?= htmlspecialchars($article['title']) ?></a></h3>
                        <?php if ($article['summary']): ?>
                            <p class="summary"><?= htmlspecialchars(substr($article['summary'], 0, 120)) ?>...</p>
                        <?php endif; ?>
                        <div class="meta">
                            <?= date('d/m/Y', strtotime($article['created_at'])) ?>
                            <?php if (!empty($article['categories'])): ?>
                                <?php foreach (array_slice($article['categories'], 0, 2) as $cat): ?>
                                    <span class="category-tag"><?= htmlspecialchars($cat['name']) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Colonne Prompts -->
    <div class="home-column">
        <div class="column-header">
            <h2>💬 Prompts récents</h2>
            <a href="<?= url('articles.php?type=prompt') ?>" class="view-all">Voir tous</a>
        </div>

        <?php if (empty($recentPrompts)): ?>
            <p class="empty-message">Aucun prompt publié pour le moment.</p>
            <?php if ($isLoggedIn): ?>
                <p><a href="<?= url('import.php') ?>" class="btn btn-small">Importer un prompt</a></p>
            <?php endif; ?>
        <?php else: ?>
            <ul class="content-list">
                <?php foreach ($recentPrompts as $prompt): ?>
                    <li class="content-list-item prompt-item">
                        <h3><a href="<?= url('article.php?slug=' . htmlspecialchars($prompt['slug'])) ?>"><?= htmlspecialchars($prompt['title']) ?></a></h3>
                        <?php if ($prompt['summary']): ?>
                            <p class="summary"><?= htmlspecialchars(substr($prompt['summary'], 0, 120)) ?>...</p>
                        <?php endif; ?>
                        <div class="meta">
                            <?= date('d/m/Y', strtotime($prompt['created_at'])) ?>
                            <?php if (!empty($prompt['categories'])): ?>
                                <?php foreach (array_slice($prompt['categories'], 0, 2) as $cat): ?>
                                    <span class="category-tag prompt-tag"><?= htmlspecialchars($cat['name']) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
