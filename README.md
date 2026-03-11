# IA-Tips

Compilation d'articles et astuces liés à l'usage de l'IA, mais aussi aux enjeux et défis.

## Fonctionnalités

- **Articles IA** : collecte, résumé automatique et analyse d'articles sur l'intelligence artificielle
- **Prompts** : bibliothèque de prompts formatés, prêts à être réutilisés
- **Importation** : import de contenu depuis une URL avec analyse automatique via Claude
- **Catégories** : organisation par catégories (articles et prompts)
- **Favoris** : marquer des contenus comme favoris pour un accès rapide
- **Recherche** : recherche full-text dans tous les contenus
- **Partage** : partage sur WhatsApp et Bluesky
- **Extension Chrome** : capture rapide de contenu depuis le navigateur

## Gestion des utilisateurs

L'application dispose de trois niveaux de rôles :

| Action | Encodeur | Éditeur | Admin |
|--------|----------|---------|-------|
| Créer des articles/prompts | oui | oui | oui |
| Importer du contenu | oui | oui | oui |
| Modifier/Supprimer du contenu | non | oui | oui |
| Gérer les catégories | non | oui | oui |
| Gérer les utilisateurs | non | non | oui |

- **Encodeur** : peut créer de nouveaux articles, prompts et importer du contenu
- **Éditeur** : peut en plus modifier, supprimer du contenu et gérer les catégories
- **Administrateur** : accès complet, incluant la gestion des utilisateurs et des rôles

## Installation

1. Copier `config.local.php.example` vers `config.local.php`
2. Configurer la clé API Claude et les autres paramètres dans `config.local.php`
3. L'application crée automatiquement la base SQLite et un compte admin par défaut :
   - Utilisateur : `admin`
   - Mot de passe : `admin123` (a changer immédiatement)

## Stack technique

- PHP (sans framework)
- SQLite
- TinyMCE pour l'édition riche
- API Claude (Anthropic) pour l'analyse de contenu
