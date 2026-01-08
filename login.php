<?php
/**
 * WikiTips - Page de connexion
 */
require_once __DIR__ . '/config.php';

$auth = new Auth();

// Déjà connecté ?
if ($auth->isLoggedIn()) {
    header('Location: ' . url());
    exit;
}

$pageTitle = 'Connexion - ' . SITE_NAME;
$alert = null;
$returnUrl = $_GET['return'] ?? url();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $alert = ['type' => 'error', 'message' => 'Veuillez remplir tous les champs.'];
    } elseif ($auth->login($username, $password)) {
        $returnUrl = $_POST['return'] ?? url();
        // Sécurité: vérifier que l'URL de retour est locale
        if (!str_starts_with($returnUrl, '/')) {
            $returnUrl = url();
        }
        header('Location: ' . $returnUrl);
        exit;
    } else {
        $alert = ['type' => 'error', 'message' => 'Identifiants incorrects.'];
    }
}

ob_start();
?>

<div class="article-header">
    <h1>Connexion</h1>
</div>

<div class="editor-container" style="max-width: 400px;">
    <?php if ($alert): ?>
        <div class="alert alert-<?= $alert['type'] ?>">
            <?= htmlspecialchars($alert['message']) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>">

        <div class="form-group">
            <label for="username">Nom d'utilisateur ou email</label>
            <input type="text" id="username" name="username" required
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required
                   autocomplete="current-password">
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Se connecter</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
