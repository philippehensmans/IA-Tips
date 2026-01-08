<?php
/**
 * WikiTips - Modifier un article
 */
require_once __DIR__ . '/config.php';

// Authentification requise
$auth = new Auth();
$auth->requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . url('articles.php'));
    exit;
}

$articleModel = new Article();
$article = $articleModel->getById($id);

if (!$article) {
    header('Location: ' . url('articles.php'));
    exit;
}

$pageTitle = 'Modifier : ' . htmlspecialchars($article['title']) . ' - ' . SITE_NAME;

$categoryModel = new Category();
$categories = $categoryModel->getAll();
$articleCategoryIds = array_column($article['categories'], 'id');

$alert = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $mainPoints = trim($_POST['main_points'] ?? '');
    $humanRightsAnalysis = trim($_POST['human_rights_analysis'] ?? '');
    $contentField = trim($_POST['content'] ?? '');
    $sourceUrl = trim($_POST['source_url'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $categoryIds = $_POST['categories'] ?? [];

    if (empty($title)) {
        $alert = ['type' => 'error', 'message' => 'Le titre est requis.'];
    } else {
        $articleModel->update($id, [
            'title' => $title,
            'summary' => $summary,
            'main_points' => $mainPoints,
            'human_rights_analysis' => $humanRightsAnalysis,
            'content' => $contentField,
            'source_url' => $sourceUrl,
            'status' => $status,
            'categories' => array_map('intval', $categoryIds)
        ]);

        $article = $articleModel->getById($id);
        $articleCategoryIds = array_column($article['categories'], 'id');
        $alert = ['type' => 'success', 'message' => 'Article mis à jour avec succès.'];
    }
}

ob_start();
?>

<div class="article-header">
    <h1>Modifier : <?= htmlspecialchars($article['title']) ?></h1>
    <div class="article-meta">
        <a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>">Voir l'article</a>
    </div>
</div>

<div class="editor-container">
    <form method="post" action="">
        <div class="form-group">
            <label for="title">Titre *</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($article['title']) ?>">
        </div>

        <div class="form-group">
            <label for="source_url">URL source</label>
            <input type="url" id="source_url" name="source_url" placeholder="https://..." value="<?= htmlspecialchars($article['source_url'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="summary">Résumé</label>
            <textarea id="summary" name="summary" rows="4"><?= htmlspecialchars($article['summary'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="main_points">Points principaux (HTML)</label>
            <textarea id="main_points" name="main_points" rows="6"><?= htmlspecialchars($article['main_points'] ?? '') ?></textarea>
            <p class="help-text">Utilisez des balises &lt;ul&gt;&lt;li&gt; pour la liste des points</p>
        </div>

        <div class="form-group">
            <label for="human_rights_analysis">Analyse des droits humains (HTML)</label>
            <textarea id="human_rights_analysis" name="human_rights_analysis" class="large"><?= htmlspecialchars($article['human_rights_analysis'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="content">Contenu additionnel</label>
            <textarea id="content" name="content" rows="6"><?= htmlspecialchars($article['content'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Catégories</label>
            <div class="checkbox-group">
                <?php foreach ($categories as $cat): ?>
                    <label>
                        <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                            <?= in_array($cat['id'], $articleCategoryIds) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Publié</option>
            </select>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>" class="btn">Annuler</a>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Supprimer</button>
        </div>
    </form>
</div>

<script>
function confirmDelete() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        fetch('<?= url('api/index.php?action=articles') ?>/<?= $id ?>', { method: 'DELETE' })
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
