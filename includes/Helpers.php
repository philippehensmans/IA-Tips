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
            // N'autoriser que les URLs http(s) et les chemins contenant /uploads/
            if (!preg_match('/^https?:\/\//', $src) && !preg_match('/\/uploads\//', $src)) {
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

    // Sécuriser les liens: n'autoriser que href
    $filtered = preg_replace_callback('/<a\s+([^>]*)>([\s\S]*?)<\/a>/i', function($matches) {
        $attributes = $matches[1];
        $content = $matches[2];

        // Extraire href
        if (preg_match('/href\s*=\s*["\']([^"\']+)["\']/i', $attributes, $hrefMatch)) {
            $href = $hrefMatch[1];
            // Décoder les entités HTML dans l'URL
            $href = html_entity_decode($href, ENT_QUOTES, 'UTF-8');
            // N'autoriser que les URLs http(s), les ancres et mailto
            if (!preg_match('/^(https?:\/\/|#|mailto:)/i', $href)) {
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

/**
 * Extrait un aperçu texte d'un contenu HTML pour l'affichage en liste
 * Supprime les balises et décode les entités HTML
 *
 * @param string $html Le HTML à traiter
 * @param int $maxLength Longueur maximale du texte
 * @return string Le texte tronqué et sécurisé
 */
function getTextPreview(?string $html, int $maxLength = 120): string {
    if (empty($html)) {
        return '';
    }

    // Décoder les entités HTML, supprimer les balises, puis tronquer
    $text = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', trim($text)); // Normaliser les espaces

    if (mb_strlen($text) > $maxLength) {
        $text = mb_substr($text, 0, $maxLength) . '...';
    }

    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
