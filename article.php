<?php
/**
 * WikiTips - Affichage d'un article
 */
require_once __DIR__ . '/config.php';

$bluesky = new BlueskyService();
$blueskyConfigured = $bluesky->isConfigured();
$blueskyMessage = $_GET['bluesky'] ?? '';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . url());
    exit;
}

$articleModel = new Article();
$article = $articleModel->getBySlug($slug);

if (!$article) {
    http_response_code(404);
    $pageTitle = 'Article non trouvé';
    ob_start();
    ?>
    <div class="article-header">
        <h1>Article non trouvé</h1>
    </div>
    <p>L'article demandé n'existe pas. <a href="<?= url() ?>">Retour à l'accueil</a></p>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/templates/layout.php';
    exit;
}

$pageTitle = htmlspecialchars($article['title']) . ' - ' . SITE_NAME;

// Construire l'URL de l'article pour le partage
$articleUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');
$articleUrl .= $_SERVER['HTTP_HOST'];
$articleUrl .= url('article.php?slug=' . urlencode($article['slug']));

// Message WhatsApp
$whatsappText = "📰 " . $article['title'] . "\n\n";
if (!empty($article['summary'])) {
    $summaryShort = mb_substr($article['summary'], 0, 150);
    if (mb_strlen($article['summary']) > 150) {
        $summaryShort .= '...';
    }
    $whatsappText .= $summaryShort . "\n\n";
}
$whatsappText .= "🔗 " . $articleUrl;
$whatsappUrl = 'https://wa.me/?text=' . rawurlencode($whatsappText);

ob_start();
?>

<?php if ($blueskyMessage === 'success'): ?>
<div class="success-message">
    ✓ Article partagé sur Bluesky avec succès !
</div>
<?php elseif ($blueskyMessage === 'error'): ?>
<div class="error-message">
    ✗ Erreur lors du partage sur Bluesky. <?= htmlspecialchars($_GET['error'] ?? '') ?>
</div>
<?php endif; ?>

<div class="article-header">
    <div class="article-actions">
        <a href="<?= url('edit.php?id=' . $article['id']) ?>">Modifier</a>
        <a href="<?= htmlspecialchars($whatsappUrl) ?>" class="btn-whatsapp" target="_blank" title="Partager sur WhatsApp">💬 WhatsApp</a>
        <?php if ($blueskyConfigured): ?>
        <a href="<?= url('share-bluesky.php?id=' . $article['id']) ?>" class="btn-bluesky" title="Partager sur Bluesky">🦋 Bluesky</a>
        <?php endif; ?>
        <a href="#" onclick="confirmDelete(<?= $article['id'] ?>); return false;">Supprimer</a>
    </div>
    <h1><?= htmlspecialchars($article['title']) ?></h1>
    <div class="article-meta">
        <span class="status-badge status-<?= $article['status'] ?>"><?= $article['status'] === 'published' ? 'Publié' : 'Brouillon' ?></span>
        &bull; Créé le <?= date('d/m/Y à H:i', strtotime($article['created_at'])) ?>
        <?php if ($article['updated_at'] !== $article['created_at']): ?>
            &bull; Modifié le <?= date('d/m/Y à H:i', strtotime($article['updated_at'])) ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($article['source_url']): ?>
<div class="source-box">
    <strong>Source :</strong> <a href="<?= htmlspecialchars($article['source_url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($article['source_url']) ?></a>
</div>
<?php endif; ?>

<div class="infobox">
    <div class="infobox-header">Informations</div>
    <div class="infobox-content">
        <div class="infobox-row">
            <div class="infobox-label">Statut</div>
            <div class="infobox-value"><?= $article['status'] === 'published' ? 'Publié' : 'Brouillon' ?></div>
        </div>
        <div class="infobox-row">
            <div class="infobox-label">Date</div>
            <div class="infobox-value"><?= date('d/m/Y', strtotime($article['created_at'])) ?></div>
        </div>
        <?php if (!empty($article['categories'])): ?>
        <div class="infobox-row">
            <div class="infobox-label">Catégories</div>
            <div class="infobox-value">
                <?php foreach ($article['categories'] as $cat): ?>
                    <a href="<?= url('category.php?slug=' . htmlspecialchars($cat['slug'])) ?>"><?= htmlspecialchars($cat['name']) ?></a><br>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($article['summary']): ?>
<div class="article-section">
    <h2>Résumé</h2>
    <p><?= nl2br(htmlspecialchars($article['summary'])) ?></p>
</div>
<?php endif; ?>

<?php if ($article['main_points']): ?>
<div class="article-section">
    <h2>Points principaux</h2>
    <?= $article['main_points'] ?>
</div>
<?php endif; ?>

<?php if ($article['human_rights_analysis']): ?>
<div class="human-rights-box">
    <h3>Analyse sous l'angle des droits humains</h3>
    <?= $article['human_rights_analysis'] ?>
</div>
<?php endif; ?>

<?php if ($article['content']): ?>
<div class="article-section">
    <h2>Contenu</h2>
    <?= nl2br(htmlspecialchars($article['content'])) ?>
</div>
<?php endif; ?>

<?php if (!empty($article['categories'])): ?>
<div class="article-categories">
    <strong>Catégories :</strong>
    <?php foreach ($article['categories'] as $cat): ?>
        <a href="<?= url('category.php?slug=' . htmlspecialchars($cat['slug'])) ?>" class="category-tag"><?= htmlspecialchars($cat['name']) ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        fetch('<?= url('api/index.php?action=articles') ?>' + '/' + id, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '<?= url('articles.php') ?>';
                } else {
                    alert('Erreur lors de la suppression');
                }
            });
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
