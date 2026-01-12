<?php
/**
 * IA-Tips - Modifier un article ou prompt
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

$isPrompt = ($article['type'] ?? 'article') === 'prompt';
$typeLabel = $isPrompt ? 'prompt' : 'article';

$pageTitle = 'Modifier : ' . htmlspecialchars($article['title']) . ' - ' . SITE_NAME;

$categoryModel = new Category();
// Filtrer les catégories par type
$categories = $categoryModel->getAll($article['type'] ?? 'article');
$articleCategoryIds = array_column($article['categories'], 'id');

$alert = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $mainPoints = trim($_POST['main_points'] ?? '');
    $analysis = trim($_POST['analysis'] ?? '');
    $formattedPrompt = trim($_POST['formatted_prompt'] ?? '');
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
            'analysis' => $analysis,
            'formatted_prompt' => $formattedPrompt,
            'content' => $contentField,
            'source_url' => $sourceUrl,
            'status' => $status,
            'categories' => array_map('intval', $categoryIds)
        ]);

        $article = $articleModel->getById($id);
        $articleCategoryIds = array_column($article['categories'], 'id');
        $alert = ['type' => 'success', 'message' => ucfirst($typeLabel) . ' mis à jour avec succès.'];
    }
}

ob_start();
?>

<div class="article-header">
    <h1>
        <span class="type-badge type-<?= $isPrompt ? 'prompt' : 'article' ?>"><?= $isPrompt ? 'Prompt' : 'Article' ?></span>
        <?= htmlspecialchars($article['title']) ?>
    </h1>
    <div class="article-meta">
        <a href="<?= url('article.php?slug=' . htmlspecialchars($article['slug'])) ?>">Voir <?= $isPrompt ? 'le prompt' : "l'article" ?></a>
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
            <label for="summary"><?= $isPrompt ? 'Description' : 'Résumé' ?></label>
            <textarea id="summary" name="summary" rows="4" data-formatting="true"><?= htmlspecialchars($article['summary'] ?? '') ?></textarea>
            <p class="help-text">Utilisez les boutons pour mettre en <strong>gras</strong>, <em>italique</em> ou <u>souligné</u></p>
        </div>

        <div class="form-group">
            <label for="main_points"><?= $isPrompt ? 'Cas d\'usage (HTML)' : 'Points principaux (HTML)' ?></label>
            <textarea id="main_points" name="main_points" rows="6"><?= htmlspecialchars($article['main_points'] ?? '') ?></textarea>
            <p class="help-text">Utilisez des balises &lt;ul&gt;&lt;li&gt; pour la liste</p>
        </div>

        <?php if ($isPrompt): ?>
        <div class="form-group">
            <label for="formatted_prompt">Prompt formaté</label>
            <textarea id="formatted_prompt" name="formatted_prompt" class="code-textarea large"><?= htmlspecialchars($article['formatted_prompt'] ?? '') ?></textarea>
            <p class="help-text">Le prompt prêt à être copié et utilisé</p>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="analysis"><?= $isPrompt ? 'Analyse du prompt (HTML)' : 'Analyse (HTML)' ?></label>
            <textarea id="analysis" name="analysis" class="large"><?= htmlspecialchars($article['analysis'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="content">Contenu additionnel / Notes</label>
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
    if (confirm('Êtes-vous sûr de vouloir supprimer ce <?= $typeLabel ?> ?')) {
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
