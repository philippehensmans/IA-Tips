<?php
/**
 * WikiTips - Déconnexion
 */
require_once __DIR__ . '/config.php';

$auth = new Auth();
$auth->logout();

header('Location: ' . url());
exit;
