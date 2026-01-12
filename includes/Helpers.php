<?php
/**
 * Fonctions utilitaires
 */

/**
 * Filtre le HTML pour n'autoriser que les balises de formatage basiques
 * Balises autorisées: strong, b, em, i, u, br, sub, sup
 *
 * @param string $html Le HTML à filtrer
 * @return string Le HTML filtré et sécurisé
 */
function filterBasicHtml(?string $html): string {
    if (empty($html)) {
        return '';
    }

    // Liste des balises autorisées (formatage basique uniquement)
    $allowedTags = '<strong><b><em><i><u><br><sub><sup>';

    // Supprimer toutes les balises sauf celles autorisées
    $filtered = strip_tags($html, $allowedTags);

    // Convertir les retours à la ligne en <br>
    $filtered = nl2br($filtered, false);

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
