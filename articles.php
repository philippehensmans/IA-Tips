<?php
/**
 * IA-Tips - Liste des articles et prompts
 */
require_once __DIR__ . '/config.php';

$status = $_GET['status'] ?? null;
$type = $_GET['type'] ?? null;
$favorites = $_GET['favorites'] ?? null;

$typeLabel = $type === 'prompt' ? 'Prompts' : ($type === 'article' ? 'Articles' : 'Tous les contenus');
$pageTitle = ($favorites ? 'Favoris - ' : '') . $typeLabel . ($status === 'draft' ? ' - Brouillons' : '') . ' - ' . SITE_NAME;

$articleModel = new Article();
if ($favorites) {
    $articles = $articleModel->getFavorites(50, 0, $type);
} else {
    $articles = $articleModel->getAll($status, 50, 0, $type);
}

ob_start();
?>

<div class="article-header">
    <h1><?= $favorites ? 'Favoris - ' : '' ?><?= $status === 'draft' ? 'Brouillons - ' : '' ?><?= $typeLabel ?></h1>
</div>

<div class="article-section">
    <div class="filter-bar">
        <div class="filter-group">
            <span class="filter-label">Type :</span>
            <a href="<?= url('articles.php' . ($status ? '?status=' . $status : '')) ?>" class="btn <?= !$type ? 'btn-primary' : '' ?>">Tous</a>
            <a href="<?= url('articles.php?type=article' . ($status ? '&status=' . $status : '')) ?>" class="btn <?= $type === 'article' ? 'btn-primary' : '' ?>">Articles</a>
            <a href="<?= url('articles.php?type=prompt' . ($status ? '&status=' . $status : '')) ?>" class="btn <?= $type === 'prompt' ? 'btn-primary' : '' ?>">Prompts</a>
        </div>
        <div class="filter-group">
            <span class="filter-label">Statut :</span>
            <a href="<?= url('articles.php' . ($type ? '?type=' . $type : '')) ?>" class="btn <?= !$status ? 'btn-primary' : '' ?>">Tous</a>
            <a href="<?= url('articles.php?status=published' . ($type ? '&type=' . $type : '')) ?>" class="btn <?= $status === 'published' ? 'btn-primary' : '' ?>">Publiés</a>
            <a href="<?= url('articles.php?status=draft' . ($type ? '&type=' . $type : '')) ?>" class="btn <?= $status === 'draft' ? 'btn-primary' : '' ?>">Brouillons</a>
        </div>
        <div class="filter-group">
            <a href="<?= url('articles.php?favorites=1' . ($type ? '&type=' . $type : '')) ?>" class="btn <?= $favorites ? 'btn-primary' : '' ?>" title="Afficher les favoris">&#9733; Favoris</a>
        </div>
        <div class="filter-group">
            <a href="<?= url('new.php?type=article') ?>" class="btn btn-success">+ Article</a>
            <a href="<?= url('new.php?type=prompt') ?>" class="btn btn-success">+ Prompt</a>
            <a href="<?= url('import.php') ?>" class="btn">Importer</a>
        </div>
    </div>
</div>

<?php if (empty($articles)): ?>
    <div class="alert alert-info">
        Aucun contenu trouvé.
        <a href="<?= url('new.php?type=article') ?>">Créer un article</a>,
        <a href="<?= url('new.php?type=prompt') ?>">créer un prompt</a> ou
        <a href="<?= url('import.php') ?>">importer du contenu</a>.
    </div>
<?php else: ?>
    <ul class="article-list">
        <?php foreach ($articles as $article): ?>
            <?php $isPrompt = ($article['type'] ?? 'article') === 'prompt'; ?>
            <li class="article-list-item">
                <h3>
                    <button class="favorite-btn favorite-btn-small<?= !empty($article['is_favorite']) ? ' active' : '' ?>" onclick="toggleFavorite(<?= $article['id'] ?>, this)" title="<?= !empty($article['is_favorite']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>"><?= !empty($article['is_favorite']) ? '&#9733;' : '&#9734;' ?></button>
                    <span class="type-badge type-<?= $isPrompt ? 'prompt' : 'article' ?>"><?= $isPrompt ? 'Prompt' : 'Article' ?></span>
                    <a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>"><?= htmlspecialchars($article['title']) ?></a>
                    <span class="status-badge status-<?= $article['status'] ?>"><?= $article['status'] === 'published' ? 'Publié' : 'Brouillon' ?></span>
                </h3>
                <?php if ($article['summary']): ?>
                    <p class="summary"><?= getTextPreview($article['summary'], 250) ?></p>
                <?php endif; ?>
                <div class="meta">
                    <?= date('d/m/Y à H:i', strtotime($article['created_at'])) ?>
                    <?php if (!empty($article['categories'])): ?>
                        |
                        <?php foreach ($article['categories'] as $cat): ?>
                            <span class="category-tag"><?= htmlspecialchars($cat['name']) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    |
                    <a href="<?= url('edit.php?id=' . $article['id']) ?>">Modifier</a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
