<?php
/**
 * Classe de gestion de l'authentification
 */
class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPdo();

        // Démarrer la session si pas déjà fait
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Tenter une connexion
     */
    public function login(string $username, string $password): bool {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Mettre à jour la date de dernière connexion
        $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$user['id']]);

        // Stocker l'utilisateur en session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        return true;
    }

    /**
     * Déconnecter l'utilisateur
     */
    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    /**
     * Obtenir l'utilisateur connecté
     */
    public function getUser(): ?array {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin(): bool {
        return $this->isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
    }

    /**
     * Vérifier si l'utilisateur est éditeur ou admin
     */
    public function isEditor(): bool {
        return $this->isLoggedIn() && in_array($_SESSION['role'] ?? '', ['editor', 'admin']);
    }

    /**
     * Vérifier si l'utilisateur peut encoder (encodeur, éditeur ou admin)
     */
    public function canEncode(): bool {
        return $this->isLoggedIn() && in_array($_SESSION['role'] ?? '', ['encodeur', 'editor', 'admin']);
    }

    /**
     * Exiger une connexion (rediriger si non connecté)
     */
    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            $returnUrl = $_SERVER['REQUEST_URI'];
            header('Location: ' . url('login.php?return=' . urlencode($returnUrl)));
            exit;
        }
    }

    /**
     * Exiger le rôle admin
     */
    public function requireAdmin(): void {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            http_response_code(403);
            die('Accès refusé');
        }
    }

    /**
     * Exiger le rôle éditeur ou admin
     */
    public function requireEditor(): void {
        $this->requireLogin();
        if (!$this->isEditor()) {
            http_response_code(403);
            die('Accès refusé - Rôle éditeur ou administrateur requis');
        }
    }

    /**
     * Exiger le droit d'encoder (encodeur, éditeur ou admin)
     */
    public function requireEncoder(): void {
        $this->requireLogin();
        if (!$this->canEncode()) {
            http_response_code(403);
            die('Accès refusé - Rôle encodeur, éditeur ou administrateur requis');
        }
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function createUser(string $username, string $email, string $password, string $role = 'editor'): int {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hash, $role]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Changer le rôle d'un utilisateur
     */
    public function changeRole(int $userId, string $newRole): bool {
        $allowedRoles = ['encodeur', 'editor', 'admin'];
        if (!in_array($newRole, $allowedRoles)) {
            return false;
        }

        // Ne pas rétrograder le dernier admin
        if ($newRole !== 'admin') {
            $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ($user && $user['role'] === 'admin') {
                $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                $adminCount = (int)$stmt->fetchColumn();
                if ($adminCount <= 1) {
                    return false;
                }
            }
        }

        $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$newRole, $userId]);
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(int $userId, string $newPassword): bool {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$hash, $userId]);
    }

    /**
     * Lister tous les utilisateurs (admin seulement)
     */
    public function getAllUsers(): array {
        $stmt = $this->db->query("SELECT id, username, email, role, created_at, last_login FROM users ORDER BY username");
        return $stmt->fetchAll();
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser(int $userId): bool {
        // Ne pas supprimer le dernier admin
        $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $adminCount = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && $user['role'] === 'admin' && $adminCount <= 1) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}
