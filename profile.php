<?php
/**
 * WikiTips - Profil utilisateur
 */
require_once __DIR__ . '/config.php';

$auth = new Auth();
$auth->requireLogin();

$currentUser = $auth->getUser();
$pageTitle = 'Mon profil - ' . SITE_NAME;
$alert = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($username) || empty($email)) {
            $alert = ['type' => 'error', 'message' => 'Le nom d\'utilisateur et l\'email sont requis.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alert = ['type' => 'error', 'message' => 'L\'email n\'est pas valide.'];
        } else {
            $db = Database::getInstance()->getPdo();

            // Vérifier si le username est déjà pris par un autre utilisateur
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $currentUser['id']]);
            if ($stmt->fetch()) {
                $alert = ['type' => 'error', 'message' => 'Ce nom d\'utilisateur est déjà utilisé.'];
            } else {
                // Vérifier si l'email est déjà pris par un autre utilisateur
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $currentUser['id']]);
                if ($stmt->fetch()) {
                    $alert = ['type' => 'error', 'message' => 'Cet email est déjà utilisé.'];
                } else {
                    $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $currentUser['id']]);

                    // Mettre à jour la session
                    $_SESSION['username'] = $username;

                    $currentUser = $auth->getUser();
                    $alert = ['type' => 'success', 'message' => 'Profil mis à jour avec succès.'];
                }
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $alert = ['type' => 'error', 'message' => 'Tous les champs de mot de passe sont requis.'];
        } elseif (strlen($newPassword) < 8) {
            $alert = ['type' => 'error', 'message' => 'Le nouveau mot de passe doit faire au moins 8 caractères.'];
        } elseif ($newPassword !== $confirmPassword) {
            $alert = ['type' => 'error', 'message' => 'Les nouveaux mots de passe ne correspondent pas.'];
        } else {
            // Vérifier le mot de passe actuel
            $db = Database::getInstance()->getPdo();
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$currentUser['id']]);
            $user = $stmt->fetch();

            if (!password_verify($currentPassword, $user['password_hash'])) {
                $alert = ['type' => 'error', 'message' => 'Le mot de passe actuel est incorrect.'];
            } else {
                $auth->changePassword($currentUser['id'], $newPassword);
                $alert = ['type' => 'success', 'message' => 'Mot de passe modifié avec succès.'];
            }
        }
    }
}

ob_start();
?>

<div class="article-header">
    <h1>Mon profil</h1>
</div>

<?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>">
        <?= htmlspecialchars($alert['message']) ?>
    </div>
<?php endif; ?>

<div class="article-section">
    <h2>Informations du compte</h2>

    <div class="editor-container" style="max-width: 500px;">
        <form method="post" action="">
            <input type="hidden" name="action" value="update_profile">

            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required
                       value="<?= htmlspecialchars($currentUser['username']) ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($currentUser['email']) ?>">
            </div>

            <div class="form-group">
                <label>Rôle</label>
                <input type="text" disabled value="<?= htmlspecialchars($currentUser['role']) ?>"
                       style="background: #f6f6f6;">
            </div>

            <div class="form-group">
                <label>Membre depuis</label>
                <input type="text" disabled
                       value="<?= date('d/m/Y', strtotime($currentUser['created_at'])) ?>"
                       style="background: #f6f6f6;">
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>

<div class="article-section">
    <h2>Changer le mot de passe</h2>

    <div class="editor-container" style="max-width: 500px;">
        <form method="post" action="">
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label for="current_password">Mot de passe actuel</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password">Nouveau mot de passe (min. 8 caractères)</label>
                <input type="password" id="new_password" name="new_password" required minlength="8">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
