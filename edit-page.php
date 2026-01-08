<?php
/**
 * WikiTips - Modifier une page statique
 */
require_once __DIR__ . '/config.php';

// Admin requis
$auth = new Auth();
$auth->requireAdmin();

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . url());
    exit;
}

$pageModel = new Page();
$page = $pageModel->getBySlug($slug);

if (!$page) {
    header('Location: ' . url());
    exit;
}

$pageTitle = 'Modifier : ' . htmlspecialchars($page['title']) . ' - ' . SITE_NAME;
$alert = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';

    if (empty($title)) {
        $alert = ['type' => 'error', 'message' => 'Le titre est requis.'];
    } else {
        $pageModel->update($slug, $title, $content);
        $page = $pageModel->getBySlug($slug);
        $alert = ['type' => 'success', 'message' => 'Page mise à jour avec succès.'];
    }
}

ob_start();
?>

<div class="article-header">
    <h1>Modifier la page : <?= htmlspecialchars($page['title']) ?></h1>
    <div class="article-meta">
        <?php if ($slug === 'home'): ?>
            <a href="<?= url() ?>">Voir la page d'accueil</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>">
        <?= htmlspecialchars($alert['message']) ?>
    </div>
<?php endif; ?>

<div class="editor-container">
    <form method="post" action="">
        <div class="form-group">
            <label for="title">Titre de la page</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($page['title']) ?>">
        </div>

        <div class="form-group">
            <label for="content">Contenu (HTML autorisé)</label>
            <textarea id="content" name="content" class="large" style="min-height: 400px;"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
            <p class="help-text">
                Vous pouvez utiliser du HTML : &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a href=""&gt;, &lt;h2&gt;, &lt;h3&gt;, etc.
            </p>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <?php if ($slug === 'home'): ?>
                <a href="<?= url() ?>" class="btn">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="article-section" style="margin-top: 30px;">
    <h3>Aperçu du contenu actuel</h3>
    <div style="background: #fff; border: 1px solid #ddd; padding: 20px; margin-top: 10px;">
        <?= $page['content'] ?? '<em>Aucun contenu</em>' ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
