<?php
/**
 * Fonctions utilitaires
 */

/**
 * Filtre le HTML pour n'autoriser que les balises de formatage basiques
 * Balises autorisées: strong, b, em, i, u, br, sub, sup, ul, ol, li, table, thead, tbody, tr, th, td, pre
 *
 * @param string $html Le HTML à filtrer
 * @return string Le HTML filtré et sécurisé
 */
function filterBasicHtml(?string $html): string {
    if (empty($html)) {
        return '';
    }

    // Liste des balises autorisées (formatage basique + listes + tableaux + pre)
    $allowedTags = '<strong><b><em><i><u><br><sub><sup><ul><ol><li><table><thead><tbody><tr><th><td><pre>';

    // Supprimer toutes les balises sauf celles autorisées
    $filtered = strip_tags($html, $allowedTags);

    // Sécuriser les attributs class sur pre (n'autoriser que class="prompt")
    $filtered = preg_replace('/<pre[^>]*class=["\'](?!prompt)[^"\']*["\'][^>]*>/i', '<pre>', $filtered);

    // Convertir les retours à la ligne en <br> (sauf dans les listes, tableaux et pre)
    // On évite d'ajouter des <br> entre les balises de structure
    $filtered = preg_replace('/\n(?!<\/?(ul|ol|li|table|thead|tbody|tr|th|td|pre))/i', "<br>\n", $filtered);

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
