<?php
/**
 * WikiTips - Gestion des utilisateurs (Admin)
 */
require_once __DIR__ . '/config.php';

// Admin requis
$auth = new Auth();
$auth->requireAdmin();

$pageTitle = 'Gestion des utilisateurs - ' . SITE_NAME;
$alert = null;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'encodeur';
        $allowedRoles = ['encodeur', 'editor', 'admin'];
        if (!in_array($role, $allowedRoles)) {
            $role = 'encodeur';
        }

        if (empty($username) || empty($email) || empty($password)) {
            $alert = ['type' => 'error', 'message' => 'Tous les champs sont requis.'];
        } elseif (strlen($password) < 8) {
            $alert = ['type' => 'error', 'message' => 'Le mot de passe doit faire au moins 8 caractères.'];
        } else {
            try {
                $auth->createUser($username, $email, $password, $role);
                $alert = ['type' => 'success', 'message' => 'Utilisateur créé avec succès.'];
            } catch (Exception $e) {
                $alert = ['type' => 'error', 'message' => 'Erreur: ' . $e->getMessage()];
            }
        }
    } elseif ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId && $userId !== $_SESSION['user_id']) {
            if ($auth->deleteUser($userId)) {
                $alert = ['type' => 'success', 'message' => 'Utilisateur supprimé.'];
            } else {
                $alert = ['type' => 'error', 'message' => 'Impossible de supprimer cet utilisateur.'];
            }
        }
    } elseif ($action === 'change_role') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newRole = $_POST['new_role'] ?? '';

        if ($userId && $userId !== $_SESSION['user_id']) {
            if ($auth->changeRole($userId, $newRole)) {
                $alert = ['type' => 'success', 'message' => 'Rôle modifié avec succès.'];
            } else {
                $alert = ['type' => 'error', 'message' => 'Impossible de modifier le rôle (dernier administrateur ?).'];
            }
        } else {
            $alert = ['type' => 'error', 'message' => 'Vous ne pouvez pas modifier votre propre rôle.'];
        }
    } elseif ($action === 'change_password') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';

        if ($userId && strlen($newPassword) >= 8) {
            $auth->changePassword($userId, $newPassword);
            $alert = ['type' => 'success', 'message' => 'Mot de passe modifié.'];
        } else {
            $alert = ['type' => 'error', 'message' => 'Le mot de passe doit faire au moins 8 caractères.'];
        }
    }
}

$users = $auth->getAllUsers();

ob_start();
?>

<div class="article-header">
    <h1>Gestion des utilisateurs</h1>
</div>

<?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>">
        <?= htmlspecialchars($alert['message']) ?>
    </div>
<?php endif; ?>

<div class="article-section">
    <h2>Utilisateurs existants</h2>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background: #f6f6f6; border-bottom: 2px solid #a2a9b1;">
                <th style="padding: 10px; text-align: left;">Nom d'utilisateur</th>
                <th style="padding: 10px; text-align: left;">Email</th>
                <th style="padding: 10px; text-align: left;">Rôle</th>
                <th style="padding: 10px; text-align: left;">Dernière connexion</th>
                <th style="padding: 10px; text-align: left;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?= htmlspecialchars($user['username']) ?></td>
                    <td style="padding: 10px;"><?= htmlspecialchars($user['email']) ?></td>
                    <td style="padding: 10px;">
                        <?php
                        $roleBadgeClass = match($user['role']) {
                            'admin' => 'published',
                            'editor' => 'draft',
                            default => 'draft',
                        };
                        $roleLabel = match($user['role']) {
                            'admin' => 'Administrateur',
                            'editor' => 'Éditeur',
                            'encodeur' => 'Encodeur',
                            default => htmlspecialchars($user['role']),
                        };
                        ?>
                        <span class="status-badge status-<?= $roleBadgeClass ?>">
                            <?= $roleLabel ?>
                        </span>
                    </td>
                    <td style="padding: 10px;">
                        <?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais' ?>
                    </td>
                    <td style="padding: 10px;">
                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                            <form method="post" style="display: inline; margin-right: 5px;">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="new_role" style="padding: 3px; font-size: 12px;" onchange="this.form.submit()">
                                    <option value="" disabled selected>Changer rôle...</option>
                                    <option value="encodeur" <?= $user['role'] === 'encodeur' ? 'disabled' : '' ?>>Encodeur</option>
                                    <option value="editor" <?= $user['role'] === 'editor' ? 'disabled' : '' ?>>Éditeur</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'disabled' : '' ?>>Admin</option>
                                </select>
                            </form>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px;">Supprimer</button>
                            </form>
                        <?php else: ?>
                            <span style="color: #999; font-size: 12px;">(vous)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="article-section">
    <h2>Créer un nouvel utilisateur</h2>

    <div class="editor-container" style="max-width: 500px;">
        <form method="post" action="">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label for="username">Nom d'utilisateur *</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe * (min. 8 caractères)</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>

            <div class="form-group">
                <label for="role">Rôle</label>
                <select id="role" name="role">
                    <option value="encodeur">Encodeur (peut créer du contenu)</option>
                    <option value="editor">Éditeur (peut créer et modifier)</option>
                    <option value="admin">Administrateur (accès complet)</option>
                </select>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
