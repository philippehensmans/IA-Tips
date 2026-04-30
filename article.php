<?php
/**
 * IA-Tips - Affichage d'un article ou prompt
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
    $pageTitle = 'Contenu non trouvé';
    ob_start();
    ?>
    <div class="article-header">
        <h1>Contenu non trouvé</h1>
    </div>
    <p>Le contenu demandé n'existe pas. <a href="<?= url() ?>">Retour à l'accueil</a></p>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/templates/layout.php';
    exit;
}

$isPrompt = ($article['type'] ?? 'article') === 'prompt';
$typeLabel = $isPrompt ? 'prompt' : 'article';

$pageTitle = htmlspecialchars($article['title']) . ' - ' . SITE_NAME;

// Construire l'URL pour le partage
$articleUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');
$articleUrl .= $_SERVER['HTTP_HOST'];
$articleUrl .= url('article.php?slug=' . urlencode($article['slug']));

// Message WhatsApp
$whatsappText = ($isPrompt ? "💬 " : "📄 ") . $article['title'] . "\n\n";
if (!empty($article['summary'])) {
    // Nettoyer le HTML pour WhatsApp (supprimer balises et décoder entités)
    $summaryClean = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES, 'UTF-8');
    $summaryClean = preg_replace('/\s+/', ' ', trim($summaryClean)); // Normaliser espaces
    $summaryShort = mb_substr($summaryClean, 0, 150);
    if (mb_strlen($summaryClean) > 150) {
        $summaryShort .= '...';
    }
    $whatsappText .= $summaryShort . "\n\n";
}
$whatsappText .= $articleUrl;
$whatsappUrl = 'https://wa.me/?text=' . rawurlencode($whatsappText);

// Message Threads
$threadsText = ($isPrompt ? "💬 " : "💡 ") . $article['title'] . "\n\n";
if (!empty($article['summary'])) {
    $summaryClean = html_entity_decode(strip_tags($article['summary']), ENT_QUOTES, 'UTF-8');
    $summaryClean = preg_replace('/\s+/', ' ', trim($summaryClean));
    $summaryShort = mb_substr($summaryClean, 0, 200);
    if (mb_strlen($summaryClean) > 200) {
        $summaryShort .= '...';
    }
    $threadsText .= $summaryShort . "\n\n";
}
$threadsText .= $articleUrl;
$threadsUrl = 'https://www.threads.net/intent/post?text=' . rawurlencode($threadsText);

ob_start();
?>

<?php if ($blueskyMessage === 'success'): ?>
<div class="success-message">
    Article partagé sur Bluesky avec succès !
</div>
<?php elseif ($blueskyMessage === 'error'): ?>
<div class="error-message">
    Erreur lors du partage sur Bluesky. <?= htmlspecialchars($_GET['error'] ?? '') ?>
</div>
<?php endif; ?>


<?php $articleAuth = new Auth(); ?>
<div class="article-header">
    <div class="article-actions">
        <?php if ($articleAuth->isEditor()): ?>
            <a href="<?= url('edit.php?id=' . $article['id']) ?>">Modifier</a>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($whatsappUrl) ?>" class="btn-whatsapp" target="_blank" title="Partager sur WhatsApp">WhatsApp</a>
        <?php if ($blueskyConfigured && !$isPrompt): ?>
        <a href="<?= url('share-bluesky.php?id=' . $article['id']) ?>" class="btn-bluesky" title="Partager sur Bluesky">Bluesky</a>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($threadsUrl) ?>" class="btn-threads" target="_blank" title="Partager sur Threads">Threads</a>
        <?php if ($articleAuth->isEditor()): ?>
            <a href="#" onclick="confirmDelete(<?= $article['id'] ?>); return false;">Supprimer</a>
        <?php endif; ?>
    </div>
    <h1>
        <button class="favorite-btn<?= !empty($article['is_favorite']) ? ' active' : '' ?>" onclick="toggleFavorite(<?= $article['id'] ?>, this)" title="<?= !empty($article['is_favorite']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>"><?= !empty($article['is_favorite']) ? '&#9733;' : '&#9734;' ?></button>
        <span class="type-badge type-<?= $isPrompt ? 'prompt' : 'article' ?>"><?= $isPrompt ? 'Prompt' : 'Article' ?></span>
        <?= htmlspecialchars($article['title']) ?>
    </h1>
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
            <div class="infobox-label">Type</div>
            <div class="infobox-value"><?= $isPrompt ? 'Prompt' : 'Article' ?></div>
        </div>
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
    <h2><?= $isPrompt ? 'Description' : 'Résumé' ?></h2>
    <div class="summary-content"><?= filterBasicHtml(formatSummaryForDisplay($article['summary'])) ?></div>
</div>
<?php endif; ?>

<?php if ($isPrompt && $article['formatted_prompt']): ?>
<div class="prompt-box">
    <div class="prompt-header">
        <h2>Prompt</h2>
        <button class="btn btn-copy" onclick="copyPrompt()" title="Copier le prompt">Copier</button>
    </div>
    <pre class="prompt-content" id="promptContent"><?= htmlspecialchars($article['formatted_prompt']) ?></pre>
</div>
<?php endif; ?>

<?php if ($article['main_points']): ?>
<div class="article-section">
    <h2><?= $isPrompt ? 'Cas d\'usage' : 'Points principaux' ?></h2>
    <?= $article['main_points'] ?>
</div>
<?php endif; ?>

<?php if ($article['analysis']): ?>
<div class="analysis-box">
    <h3><?= $isPrompt ? 'Analyse du prompt' : 'Analyse' ?></h3>
    <?= $article['analysis'] ?>
</div>
<?php endif; ?>

<?php if ($article['content']): ?>
<div class="article-section">
    <h2>Notes</h2>
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
    if (confirm('Êtes-vous sûr de vouloir supprimer ce <?= $typeLabel ?> ?')) {
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

function copyPrompt() {
    var promptText = document.getElementById('promptContent').innerText;
    navigator.clipboard.writeText(promptText).then(function() {
        var btn = document.querySelector('.btn-copy');
        btn.textContent = 'Copié !';
        setTimeout(function() {
            btn.textContent = 'Copier';
        }, 2000);
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
