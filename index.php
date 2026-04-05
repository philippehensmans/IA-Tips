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
$categoryModel = new Category();

// Récupérer articles et prompts séparément
$recentArticles = $articleModel->getAll('published', 5, 0, 'article');
$recentPrompts = $articleModel->getAll('published', 5, 0, 'prompt');

// Récupérer les catégories de prompts et d'articles
$promptCategories = $categoryModel->getAll('prompt');
$articleCategories = $categoryModel->getAll('article');

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

<!-- Bloc de recherche style Google - Suggestions depuis la base -->
<div class="suggest-search-box">
    <h2>Posez votre question sur l'IA</h2>
    <p class="suggest-subtitle">Trouvez des réponses dans nos prompts et articles analysés</p>
    <form id="suggestForm" class="suggest-form" onsubmit="return handleSuggestSearch(event)">
        <div class="suggest-input-wrapper">
            <span class="suggest-icon">&#128269;</span>
            <input type="text" id="suggestQuery" name="q" placeholder="Ex : Comment rédiger un prompt efficace ?" autocomplete="off">
            <button type="submit" class="suggest-btn">Rechercher</button>
        </div>
    </form>
    <div id="suggestResults" class="suggest-results" style="display:none;"></div>
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

        <?php if (!empty($articleCategories)): ?>
        <div class="category-dropdown">
            <select onchange="if(this.value) window.location.href=this.value">
                <option value="">Filtrer par catégorie...</option>
                <?php foreach ($articleCategories as $cat): ?>
                    <option value="<?= url('category.php?slug=' . htmlspecialchars($cat['slug'])) ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if (empty($recentArticles)): ?>
            <p class="empty-message">Aucun article publié pour le moment.</p>
            <?php if ($isLoggedIn): ?>
                <p><a href="<?= url('import.php') ?>" class="btn btn-small">Importer un article</a></p>
            <?php endif; ?>
        <?php else: ?>
            <ul class="content-list">
                <?php foreach ($recentArticles as $article): ?>
                    <li class="content-list-item">
                        <h3><button class="favorite-btn favorite-btn-small<?= !empty($article['is_favorite']) ? ' active' : '' ?>" onclick="toggleFavorite(<?= $article['id'] ?>, this)" title="Favori"><?= !empty($article['is_favorite']) ? '&#9733;' : '&#9734;' ?></button> <a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>"><?= htmlspecialchars($article['title']) ?></a></h3>
                        <?php if ($article['summary']): ?>
                            <p class="summary"><?= getTextPreview($article['summary'], 120) ?></p>
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

        <?php if (!empty($promptCategories)): ?>
        <div class="category-dropdown">
            <select onchange="if(this.value) window.location.href=this.value">
                <option value="">Filtrer par catégorie...</option>
                <?php foreach ($promptCategories as $cat): ?>
                    <option value="<?= url('category.php?slug=' . htmlspecialchars($cat['slug'])) ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if (empty($recentPrompts)): ?>
            <p class="empty-message">Aucun prompt publié pour le moment.</p>
            <?php if ($isLoggedIn): ?>
                <p><a href="<?= url('import.php') ?>" class="btn btn-small">Importer un prompt</a></p>
            <?php endif; ?>
        <?php else: ?>
            <ul class="content-list">
                <?php foreach ($recentPrompts as $prompt): ?>
                    <li class="content-list-item prompt-item">
                        <h3><button class="favorite-btn favorite-btn-small<?= !empty($prompt['is_favorite']) ? ' active' : '' ?>" onclick="toggleFavorite(<?= $prompt['id'] ?>, this)" title="Favori"><?= !empty($prompt['is_favorite']) ? '&#9733;' : '&#9734;' ?></button> <a href="<?= url('article.php?slug=' . htmlspecialchars($prompt['slug'])) ?>"><?= htmlspecialchars($prompt['title']) ?></a></h3>
                        <?php if ($prompt['summary']): ?>
                            <p class="summary"><?= getTextPreview($prompt['summary'], 120) ?></p>
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
