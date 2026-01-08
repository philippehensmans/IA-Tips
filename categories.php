<?php
/**
 * WikiTips - Liste des catégories
 */
require_once __DIR__ . '/config.php';

$pageTitle = 'Catégories - ' . SITE_NAME;

$categoryModel = new Category();
$categories = $categoryModel->getAll();

ob_start();
?>

<div class="article-header">
    <h1>Catégories</h1>
</div>

<div class="article-section">
    <p>Les articles sont classés selon les domaines des droits humains concernés.</p>
</div>

<ul class="article-list">
    <?php foreach ($categories as $category): ?>
        <li class="article-list-item">
            <h3><a href="<?= url('category.php?slug=' . htmlspecialchars($category['slug'])) ?>"><?= htmlspecialchars($category['name']) ?></a></h3>
            <?php if ($category['description']): ?>
                <p class="summary"><?= htmlspecialchars($category['description']) ?></p>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
