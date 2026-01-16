<?php
/**
 * API d'upload d'images pour TinyMCE
 */

// Toujours retourner du JSON
header('Content-Type: application/json');

// Gestion des erreurs
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur PHP: ' . $errstr]);
    exit;
});

try {
    require_once __DIR__ . '/../config.php';

    // Vérifier l'authentification
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Non autorisé - veuillez vous connecter']);
        exit;
    }

    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        exit;
    }

    // Vérifier si un fichier a été envoyé
    if (empty($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Aucun fichier reçu']);
        exit;
    }

    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par PHP',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture sur le disque',
            UPLOAD_ERR_EXTENSION => 'Upload arrêté par une extension'
        ];
        $error = $errorMessages[$file['error']] ?? 'Erreur d\'upload: ' . $file['error'];
        echo json_encode(['error' => $error]);
        exit;
    }

    // Vérifier le type MIME
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Type non autorisé (' . $mimeType . '). Utilisez JPG, PNG, GIF ou WebP.']);
        exit;
    }

    // Vérifier la taille (max 5 Mo)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'Fichier trop volumineux (max 5 Mo).']);
        exit;
    }

    // Extension basée sur le type MIME
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    $extension = $extensions[$mimeType] ?? 'jpg';

    // Générer un nom de fichier unique
    $filename = uniqid('img_') . '_' . time() . '.' . $extension;
    $uploadDir = __DIR__ . '/../uploads/';
    $uploadPath = $uploadDir . $filename;

    // Créer le dossier si nécessaire
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Impossible de créer le dossier uploads']);
            exit;
        }
    }

    // Vérifier que le dossier est accessible en écriture
    if (!is_writable($uploadDir)) {
        http_response_code(500);
        echo json_encode(['error' => 'Le dossier uploads n\'est pas accessible en écriture']);
        exit;
    }

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Échec du déplacement du fichier']);
        exit;
    }

    // Générer l'URL de l'image avec le BASE_PATH
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    $imageUrl = $basePath . '/uploads/' . $filename;

    // Réponse pour TinyMCE
    echo json_encode(['location' => $imageUrl]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur fatale: ' . $e->getMessage()]);
}
