<?php
/**
 * Fonctions utilitaires
 */

/**
 * Filtre le HTML pour n'autoriser que les balises de formatage basiques
 * Balises autorisées: strong, b, em, i, u, br, sub, sup, ul, ol, li, table, thead, tbody, tr, th, td
 *
 * @param string $html Le HTML à filtrer
 * @return string Le HTML filtré et sécurisé
 */
function filterBasicHtml(?string $html): string {
    if (empty($html)) {
        return '';
    }

    // Liste des balises autorisées (formatage basique + listes + tableaux)
    $allowedTags = '<strong><b><em><i><u><br><sub><sup><ul><ol><li><table><thead><tbody><tr><th><td>';

    // Supprimer toutes les balises sauf celles autorisées
    $filtered = strip_tags($html, $allowedTags);

    // Convertir les retours à la ligne en <br> (sauf dans les listes et tableaux)
    // On évite d'ajouter des <br> entre les balises de structure
    $filtered = preg_replace('/\n(?!<\/?(ul|ol|li|table|thead|tbody|tr|th|td))/i', "<br>\n", $filtered);

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
