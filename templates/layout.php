<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? SITE_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="icon" href="<?= url('assets/images/favicon.ico') ?>" type="image/x-icon">
</head>
<body>
    <?php
    $auth = new Auth();
    $isLoggedIn = $auth->isLoggedIn();
    $currentUser = $isLoggedIn ? $auth->getUser() : null;
    ?>

    <header class="wiki-header">
        <div class="wiki-header-inner">
            <a href="<?= url() ?>" class="wiki-logo">
                <div>
                    <h1><?= SITE_NAME ?></h1>
                    <div class="subtitle"><?= SITE_DESCRIPTION ?></div>
                </div>
            </a>
            <div style="display: flex; align-items: center; gap: 15px;">
                <form class="wiki-search" action="<?= url('search.php') ?>" method="get">
                    <input type="text" name="q" placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    <button type="submit">Rechercher</button>
                </form>
                <div class="user-menu">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= url('profile.php') ?>" style="font-size: 12px; color: #666;"><?= htmlspecialchars($currentUser['username']) ?></a>
                        <a href="<?= url('logout.php') ?>" style="font-size: 12px;">Déconnexion</a>
                    <?php else: ?>
                        <a href="<?= url('login.php') ?>" style="font-size: 12px;">Connexion</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <nav class="wiki-nav">
        <a href="<?= url() ?>">Accueil</a>
        <a href="<?= url('articles.php') ?>">Articles</a>
        <a href="<?= url('categories.php') ?>">Catégories</a>
        <?php if ($isLoggedIn): ?>
            <a href="<?= url('new.php') ?>">Nouvel article</a>
            <a href="<?= url('import.php') ?>">Importer</a>
        <?php endif; ?>
    </nav>

    <div class="wiki-container">
        <aside class="wiki-sidebar">
            <div class="sidebar-section">
                <h3>Navigation</h3>
                <ul>
                    <li><a href="<?= url() ?>">Page d'accueil</a></li>
                    <li><a href="<?= url('articles.php') ?>">Tous les articles</a></li>
                    <?php if ($isLoggedIn): ?>
                        <li><a href="<?= url('articles.php?status=draft') ?>">Brouillons</a></li>
                        <li><a href="<?= url('new.php') ?>">Créer un article</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="sidebar-section">
                <h3>Catégories</h3>
                <ul>
                    <?php
                    $categoryModel = new Category();
                    $categories = $categoryModel->getAll();
                    foreach ($categories as $cat): ?>
                        <li><a href="<?= url('category.php?slug=' . htmlspecialchars($cat['slug'])) ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($isLoggedIn): ?>
            <div class="sidebar-section">
                <h3>Outils</h3>
                <ul>
                    <li><a href="<?= url('import.php') ?>">Importer du contenu</a></li>
                    <li><a href="<?= url('api/index.php?action=health') ?>" target="_blank">État de l'API</a></li>
                    <?php if ($auth->isAdmin()): ?>
                        <li><a href="<?= url('users.php') ?>">Gérer les utilisateurs</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!$isLoggedIn): ?>
            <div class="sidebar-section">
                <h3>Compte</h3>
                <ul>
                    <li><a href="<?= url('login.php') ?>">Se connecter</a></li>
                </ul>
            </div>
            <?php endif; ?>
        </aside>

        <main class="wiki-content">
            <?php if (isset($alert)): ?>
                <div class="alert alert-<?= $alert['type'] ?>">
                    <?= htmlspecialchars($alert['message']) ?>
                </div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </main>
    </div>

    <footer class="wiki-footer">
        <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Veille et analyse sous l'angle des droits humains.</p>
        <p>Les analyses sont générées avec l'aide de l'IA et doivent être vérifiées.</p>
    </footer>

    <script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
