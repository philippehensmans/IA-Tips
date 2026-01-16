<?php
/**
 * Fonctions utilitaires
 */

/**
 * Filtre le HTML pour n'autoriser que les balises de formatage sécurisées
 * Supporte les images, liens, listes, tableaux, code et formatage de texte
 *
 * @param string $html Le HTML à filtrer
 * @return string Le HTML filtré et sécurisé
 */
function filterBasicHtml(?string $html): string {
    if (empty($html)) {
        return '';
    }

    // Liste des balises autorisées
    $allowedTags = '<p><strong><b><em><i><u><br><sub><sup><ul><ol><li><table><thead><tbody><tr><th><td><pre><code><a><img><span><figure><figcaption>';

    // Supprimer toutes les balises sauf celles autorisées
    $filtered = strip_tags($html, $allowedTags);

    // Sécuriser les images: n'autoriser que src et alt
    $filtered = preg_replace_callback('/<img[^>]*>/i', function($matches) {
        $tag = $matches[0];
        $src = '';
        $alt = '';

        // Extraire src
        if (preg_match('/src=["\']([^"\']+)["\']/i', $tag, $srcMatch)) {
            $src = htmlspecialchars($srcMatch[1], ENT_QUOTES, 'UTF-8');
            // N'autoriser que les URLs http(s) et les chemins locaux /uploads/
            if (!preg_match('/^(https?:\/\/|\/uploads\/)/', $src)) {
                return ''; // Supprimer les images avec des URLs suspectes
            }
        } else {
            return ''; // Pas de src = pas d'image
        }

        // Extraire alt
        if (preg_match('/alt=["\']([^"\']+)["\']/i', $tag, $altMatch)) {
            $alt = htmlspecialchars($altMatch[1], ENT_QUOTES, 'UTF-8');
        }

        return '<img src="' . $src . '" alt="' . $alt . '" style="max-width:100%;height:auto;">';
    }, $filtered);

    // Sécuriser les liens: n'autoriser que href et target
    $filtered = preg_replace_callback('/<a[^>]*>(.*?)<\/a>/is', function($matches) {
        $tag = $matches[0];
        $content = $matches[1];
        $href = '';

        // Extraire href
        if (preg_match('/href=["\']([^"\']+)["\']/i', $tag, $hrefMatch)) {
            $href = $hrefMatch[1];
            // N'autoriser que les URLs http(s) et les ancres
            if (!preg_match('/^(https?:\/\/|#|mailto:)/', $href)) {
                return $content; // Retourner le texte sans lien
            }
            $href = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
        } else {
            return $content;
        }

        return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $content . '</a>';
    }, $filtered);

    // Sécuriser les attributs class sur pre (n'autoriser que class="prompt")
    $filtered = preg_replace('/<pre(?![^>]*class=["\']prompt["\'])[^>]*>/i', '<pre>', $filtered);

    // Nettoyer les spans (supprimer les attributs style potentiellement dangereux)
    $filtered = preg_replace('/<span[^>]*>/i', '<span>', $filtered);

    return $filtered;
}

/**
 * Échappe le HTML pour l'affichage dans un formulaire
 * mais préserve le contenu pour l'édition
 *
 * @param string $html Le HTML à échapper
 * @return string Le HTML échappé
 */
function escapeForForm(?string $html): string {
    if (empty($html)) {
        return '';
    }
    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}
