<?php
/**
 * IA-Tips - Gestion des catégories
 */
require_once __DIR__ . '/config.php';

// Authentification requise
$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Gérer les catégories - ' . SITE_NAME;

$categoryModel = new Category();
$alert = null;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? 'article';

        if (empty($name)) {
            $alert = ['type' => 'error', 'message' => 'Le nom de la catégorie est requis.'];
        } else {
            try {
                $categoryModel->create([
                    'name' => $name,
                    'description' => $description,
                    'type' => $type
                ]);
                $alert = ['type' => 'success', 'message' => 'Catégorie "' . htmlspecialchars($name) . '" créée avec succès.'];
            } catch (Exception $e) {
                $alert = ['type' => 'error', 'message' => 'Erreur : cette catégorie existe peut-être déjà.'];
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? 'article';

        if (empty($name) || !$id) {
            $alert = ['type' => 'error', 'message' => 'Le nom et l\'ID sont requis.'];
        } else {
            try {
                $categoryModel->update($id, [
                    'name' => $name,
                    'description' => $description,
                    'type' => $type
                ]);
                $alert = ['type' => 'success', 'message' => 'Catégorie mise à jour avec succès.'];
            } catch (Exception $e) {
                $alert = ['type' => 'error', 'message' => 'Erreur lors de la mise à jour.'];
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $categoryModel->delete($id);
            $alert = ['type' => 'success', 'message' => 'Catégorie supprimée.'];
        }
    }
}

$categories = $categoryModel->getAll();

ob_start();
?>

<div class="article-header">
    <h1>Gérer les catégories</h1>
</div>

<div class="add-category-form">
    <h3>Ajouter une catégorie</h3>
    <form method="post" action="">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label for="new-name">Nom *</label>
                <input type="text" id="new-name" name="name" required placeholder="Nom de la catégorie">
            </div>
            <div class="form-group">
                <label for="new-description">Description</label>
                <input type="text" id="new-description" name="description" placeholder="Description courte...">
            </div>
            <div class="form-group">
                <label for="new-type">Type</label>
                <select id="new-type" name="type">
                    <option value="article">Article</option>
                    <option value="prompt">Prompt</option>
                </select>
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
        </div>
    </form>
</div>

<div class="article-section">
    <h2>Catégories Articles</h2>
    <ul class="category-management-list">
        <?php foreach ($categories as $cat): ?>
            <?php if ($cat['type'] === 'article'): ?>
            <li class="category-management-item" id="cat-<?= $cat['id'] ?>">
                <div class="category-info" id="cat-display-<?= $cat['id'] ?>">
                    <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                    <span class="category-type-badge type-article">Article</span>
                    <?php if ($cat['description']): ?>
                        <div class="category-desc"><?= htmlspecialchars($cat['description']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="category-actions" id="cat-actions-<?= $cat['id'] ?>">
                    <button class="btn" onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($cat['description'] ?? ''), ENT_QUOTES) ?>', '<?= $cat['type'] ?>')">Modifier</button>
                    <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cette catégorie ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>

<div class="article-section">
    <h2>Catégories Prompts</h2>
    <ul class="category-management-list">
        <?php foreach ($categories as $cat): ?>
            <?php if ($cat['type'] === 'prompt'): ?>
            <li class="category-management-item" id="cat-<?= $cat['id'] ?>">
                <div class="category-info" id="cat-display-<?= $cat['id'] ?>">
                    <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                    <span class="category-type-badge type-prompt">Prompt</span>
                    <?php if ($cat['description']): ?>
                        <div class="category-desc"><?= htmlspecialchars($cat['description']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="category-actions" id="cat-actions-<?= $cat['id'] ?>">
                    <button class="btn" onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($cat['description'] ?? ''), ENT_QUOTES) ?>', '<?= $cat['type'] ?>')">Modifier</button>
                    <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cette catégorie ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>

<script>
function editCategory(id, name, description, type) {
    var display = document.getElementById('cat-display-' + id);
    var actions = document.getElementById('cat-actions-' + id);

    display.innerHTML = '<form method="post" class="edit-category-form">' +
        '<input type="hidden" name="action" value="edit">' +
        '<input type="hidden" name="id" value="' + id + '">' +
        '<input type="text" name="name" value="' + name.replace(/"/g, '&quot;') + '" required>' +
        '<input type="text" name="description" value="' + description.replace(/"/g, '&quot;') + '" placeholder="Description...">' +
        '<select name="type">' +
            '<option value="article"' + (type === 'article' ? ' selected' : '') + '>Article</option>' +
            '<option value="prompt"' + (type === 'prompt' ? ' selected' : '') + '>Prompt</option>' +
        '</select>' +
        '<button type="submit" class="btn btn-primary">Enregistrer</button>' +
        '<button type="button" class="btn" onclick="location.reload()">Annuler</button>' +
    '</form>';
    actions.style.display = 'none';
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
