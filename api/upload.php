<?php
/**
 * API d'upload d'images pour TinyMCE
 */
require_once __DIR__ . '/../config.php';

// Vérifier l'authentification
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier si un fichier a été envoyé
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par PHP',
        UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée par le formulaire',
        UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
        UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
        UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload'
    ];
    $error = $errorMessages[$_FILES['file']['error']] ?? 'Erreur d\'upload inconnue';
    echo json_encode(['error' => $error]);
    exit;
}

$file = $_FILES['file'];

// Vérifier le type MIME
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.']);
    exit;
}

// Vérifier la taille (max 5 Mo)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'Le fichier est trop volumineux (max 5 Mo).']);
    exit;
}

// Générer un nom de fichier unique
$extension = match($mimeType) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    default => 'jpg'
};

$filename = uniqid('img_') . '_' . time() . '.' . $extension;
$uploadDir = __DIR__ . '/../uploads/';
$uploadPath = $uploadDir . $filename;

// Créer le dossier si nécessaire
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Déplacer le fichier
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Échec du déplacement du fichier.']);
    exit;
}

// Générer l'URL de l'image
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');
$baseUrl .= $_SERVER['HTTP_HOST'];
$imageUrl = $baseUrl . url('uploads/' . $filename);

// Réponse pour TinyMCE
header('Content-Type: application/json');
echo json_encode([
    'location' => $imageUrl
]);
