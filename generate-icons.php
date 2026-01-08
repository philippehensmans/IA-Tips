<?php
/**
 * Script de génération des icônes PNG pour l'extension Chrome
 * Exécutez: php generate-icons.php
 */

$sizes = [16, 48, 128];
$outputDir = __DIR__ . '/chrome-extension/icons/';

foreach ($sizes as $size) {
    // Créer une image
    $img = imagecreatetruecolor($size, $size);

    // Activer la transparence
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    // Couleurs
    $blue = imagecolorallocate($img, 51, 102, 204);      // #3366cc
    $white = imagecolorallocate($img, 255, 255, 255);
    $green = imagecolorallocate($img, 40, 167, 69);      // #28a745

    // Dessiner le fond bleu arrondi
    $radius = (int)($size * 0.125);
    imagefilledrectangle($img, $radius, 0, $size - $radius, $size - 1, $blue);
    imagefilledrectangle($img, 0, $radius, $size - 1, $size - $radius, $blue);
    imagefilledellipse($img, $radius, $radius, $radius * 2, $radius * 2, $blue);
    imagefilledellipse($img, $size - $radius, $radius, $radius * 2, $radius * 2, $blue);
    imagefilledellipse($img, $radius, $size - $radius, $radius * 2, $radius * 2, $blue);
    imagefilledellipse($img, $size - $radius, $size - $radius, $radius * 2, $radius * 2, $blue);

    // Dessiner le "W"
    $fontSize = (int)($size * 0.5);
    $x = (int)($size * 0.25);
    $y = (int)($size * 0.75);

    // Utiliser une police par défaut si disponible
    if (function_exists('imagestring')) {
        $fontScale = (int)($size / 16);
        if ($fontScale < 1) $fontScale = 1;
        if ($fontScale > 5) $fontScale = 5;

        // Centrer le W
        $textWidth = imagefontwidth($fontScale) * 1;
        $textHeight = imagefontheight($fontScale);
        $x = (int)(($size - $textWidth) / 2) - (int)($size * 0.1);
        $y = (int)(($size - $textHeight) / 2);

        imagestring($img, $fontScale, $x, $y, 'W', $white);
    }

    // Petit cercle vert en haut à droite
    $circleRadius = (int)($size * 0.15);
    $circleX = (int)($size * 0.78);
    $circleY = (int)($size * 0.22);
    imagefilledellipse($img, $circleX, $circleY, $circleRadius * 2, $circleRadius * 2, $green);

    // Sauvegarder
    $filename = $outputDir . 'icon' . $size . '.png';
    imagepng($img, $filename);
    imagedestroy($img);

    echo "Créé: $filename\n";
}

echo "\nIcônes générées avec succès!\n";
