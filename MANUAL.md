# WikiTips - Manuel d'utilisation

## Table des matières

1. [Introduction](#introduction)
2. [Fonctionnalités](#fonctionnalités)
3. [Architecture technique](#architecture-technique)
4. [Installation](#installation)
   - [Prérequis](#prérequis)
   - [Installation serveur](#installation-serveur)
   - [Installation extension Chrome](#installation-extension-chrome)
5. [Configuration](#configuration)
6. [Utilisation](#utilisation)
   - [Authentification](#authentification)
   - [Gestion des articles](#gestion-des-articles)
   - [Extension Chrome](#extension-chrome)
   - [Analyse IA](#analyse-ia)
   - [Administration](#administration)
7. [Intégration Bluesky](#intégration-bluesky)
   - [Configuration Bluesky](#configuration-bluesky)
   - [Partage manuel](#partage-manuel)
   - [Partage automatique](#partage-automatique)
8. [API REST](#api-rest)
9. [Sécurité](#sécurité)
10. [Personnalisation](#personnalisation)
11. [Dépannage](#dépannage)

---

## Introduction

**WikiTips** est une application web PHP permettant de collecter, publier et analyser rapidement des informations provenant du web. Elle intègre l'intelligence artificielle Claude d'Anthropic pour fournir une analyse automatique du contenu sous l'angle des droits humains.

### Objectifs

- Capturer facilement du contenu web via une extension Chrome
- Publier des articles avec un style visuel inspiré de Wikipedia
- Analyser automatiquement le contenu selon plusieurs perspectives :
  - Droits civils et politiques
  - Droits économiques, sociaux et culturels
  - Droit international humanitaire
- Permettre la collaboration entre plusieurs éditeurs

---

## Fonctionnalités

### Gestion de contenu

| Fonctionnalité | Description |
|----------------|-------------|
| Création d'articles | Formulaire complet avec titre, contenu, source, catégorie |
| Modification | Édition des articles existants |
| Catégorisation | Organisation par thématiques |
| Pages statiques | Pages éditables (accueil, à propos, etc.) |

### Intelligence artificielle

| Fonctionnalité | Description |
|----------------|-------------|
| Résumé automatique | Synthèse du contenu en quelques phrases |
| Extraction des points clés | Liste des informations principales |
| Analyse droits humains | Évaluation selon les conventions internationales |
| Recommandations | Suggestions d'actions ou de réflexions |

### Utilisateurs

| Fonctionnalité | Description |
|----------------|-------------|
| Inscription | Création de compte avec validation |
| Connexion sécurisée | Sessions PHP avec hash bcrypt |
| Profil utilisateur | Modification des informations personnelles |
| Rôles | Administrateur et éditeur |

### Extension Chrome

| Fonctionnalité | Description |
|----------------|-------------|
| Capture de page | Extraction du titre et contenu |
| Menu contextuel | Clic droit pour analyser |
| Envoi direct | Transfert vers WikiTips en un clic |

### Partage social (Bluesky)

| Fonctionnalité | Description |
|----------------|-------------|
| Partage manuel | Bouton pour partager un article sur Bluesky |
| Partage automatique | Option pour publier automatiquement à la création |
| Personnalisation | Texte du post modifiable avant envoi |
| Carte de lien | Aperçu riche avec titre et description |

---

## Architecture technique

### Technologies utilisées

| Composant | Technologie | Version |
|-----------|-------------|---------|
| Backend | PHP | 8.0+ |
| Base de données | SQLite | 3.x |
| API | REST/JSON | - |
| Frontend | HTML5/CSS3 | - |
| Extension | Chrome Manifest | V3 |
| IA | API Claude Anthropic | claude-sonnet-4-20250514 |

### Structure des fichiers

```
wikitips/
│
├── config.php                 # Configuration principale
├── config.local.php           # Configuration locale (à créer)
│
├── index.php                  # Page d'accueil
├── article.php                # Affichage d'un article
├── create.php                 # Création d'article
├── edit.php                   # Modification d'article
├── edit-page.php              # Modification pages statiques
│
├── login.php                  # Page de connexion
├── logout.php                 # Déconnexion
├── register.php               # Inscription
├── profile.php                # Profil utilisateur
├── share-bluesky.php          # Partage sur Bluesky
│
├── includes/
│   ├── Database.php           # Classe de gestion SQLite
│   ├── Article.php            # Modèle Article (CRUD)
│   ├── Category.php           # Modèle Catégorie
│   ├── Auth.php               # Gestion authentification
│   ├── Page.php               # Gestion pages statiques
│   ├── ClaudeService.php      # Intégration API Claude
│   └── BlueskyService.php     # Intégration Bluesky (AT Protocol)
│
├── api/
│   ├── index.php              # Routeur API REST
│   └── .htaccess              # Réécriture URL (Apache)
│
├── templates/
│   └── layout.php             # Template principal Wikipedia
│
├── css/
│   └── style.css              # Feuille de styles Wikipedia
│
├── data/
│   └── wikitips.db            # Base de données SQLite (auto-créée)
│
└── chrome-extension/
    ├── manifest.json          # Configuration extension
    ├── popup.html             # Interface popup
    ├── popup.js               # Logique popup
    ├── popup.css              # Styles popup
    ├── background.js          # Service worker
    ├── content.js             # Script d'extraction
    └── icons/
        ├── icon16.png
        ├── icon48.png
        └── icon128.png
```

### Schéma de la base de données

#### Table `articles`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire auto-incrémentée |
| title | TEXT | Titre de l'article |
| content | TEXT | Contenu complet |
| summary | TEXT | Résumé généré par Claude |
| source_url | TEXT | URL de la source originale |
| category_id | INTEGER | Clé étrangère vers categories |
| author_id | INTEGER | Clé étrangère vers users |
| created_at | DATETIME | Date de création |
| updated_at | DATETIME | Date de modification |

#### Table `categories`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire |
| name | TEXT | Nom de la catégorie |
| slug | TEXT | Identifiant URL |

#### Table `users`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire |
| username | TEXT | Nom d'utilisateur unique |
| email | TEXT | Adresse email unique |
| password | TEXT | Hash bcrypt du mot de passe |
| role | TEXT | 'admin' ou 'editor' |
| created_at | DATETIME | Date d'inscription |

#### Table `pages`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire |
| slug | TEXT | Identifiant unique (ex: 'home') |
| title | TEXT | Titre de la page |
| content | TEXT | Contenu HTML/Markdown |
| updated_at | DATETIME | Dernière modification |

---

## Installation

### Prérequis

#### Serveur web

- **PHP** 8.0 ou supérieur
- **Extensions PHP** requises :
  - `pdo_sqlite` - Accès base de données
  - `curl` - Appels API Claude
  - `json` - Traitement JSON
  - `gd` (optionnel) - Génération d'icônes
- **Serveur web** : Apache, Nginx ou équivalent
- **HTTPS** recommandé pour la production

#### Poste client

- **Google Chrome** ou navigateur compatible Chromium
- Accès au mode développeur des extensions

### Installation serveur

#### Étape 1 : Télécharger les fichiers

```bash
# Cloner le dépôt ou télécharger l'archive
git clone <url-du-depot> wikitips

# Ou extraire l'archive
unzip wikitips.zip -d /var/www/html/
```

#### Étape 2 : Configurer les permissions

```bash
# Le dossier doit être accessible en écriture pour SQLite
cd /var/www/html/wikitips
chmod 755 .
chmod 755 data/  # Créer le dossier si nécessaire
```

#### Étape 3 : Créer la configuration locale

Créez le fichier `config.local.php` à la racine :

```php
<?php
/**
 * Configuration locale WikiTips
 * Ce fichier n'est pas versionné (ajoutez-le à .gitignore)
 */

// Clé API Anthropic (obligatoire pour l'analyse IA)
// Obtenez-la sur https://console.anthropic.com/
define('CLAUDE_API_KEY', 'sk-ant-api03-votre-cle-ici');

// Clé secrète pour sécuriser l'API REST
// Inventez une chaîne aléatoire complexe
define('API_SECRET_KEY', 'votre-cle-secrete-aleatoire-32-caracteres');

// Chemin de base (si installé dans un sous-répertoire)
// Décommentez et ajustez si nécessaire
// define('BASE_PATH', '/wikitips');
```

#### Étape 4 : Vérifier l'installation

1. Accédez à `https://votresite.com/wikitips/`
2. La base de données est créée automatiquement
3. Connectez-vous avec `admin` / `admin123`
4. **Changez immédiatement le mot de passe admin**

### Installation extension Chrome

#### Étape 1 : Configurer l'extension

Éditez le fichier `chrome-extension/popup.js` :

```javascript
// Ligne ~1-5 : Configurez l'URL de votre installation
const WIKITIPS_URL = 'https://votresite.com/wikitips';

// Ligne ~10 : Même clé que dans config.local.php
const API_KEY = 'votre-cle-secrete-aleatoire-32-caracteres';
```

#### Étape 2 : Charger l'extension

1. Ouvrez Chrome et accédez à `chrome://extensions/`
2. Activez le **Mode développeur** (interrupteur en haut à droite)
3. Cliquez sur **Charger l'extension non empaquetée**
4. Sélectionnez le dossier `chrome-extension/`
5. L'icône WikiTips apparaît dans la barre d'outils

#### Étape 3 : Épingler l'extension (optionnel)

1. Cliquez sur l'icône puzzle (extensions) dans Chrome
2. Cliquez sur l'épingle à côté de WikiTips

---

## Configuration

### Configuration principale (`config.php`)

Ce fichier contient les paramètres par défaut. **Ne le modifiez pas directement**, utilisez `config.local.php` pour surcharger les valeurs.

```php
<?php
// Paramètres par défaut
define('DB_PATH', __DIR__ . '/data/wikitips.db');
define('CLAUDE_API_KEY', '');  // À définir dans config.local.php
define('CLAUDE_MODEL', 'claude-sonnet-4-20250514');
define('API_SECRET_KEY', '');  // À définir dans config.local.php

// Chargement de la configuration locale
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
```

### Configuration locale (`config.local.php`)

Ce fichier contient vos paramètres spécifiques :

| Constante | Description | Exemple |
|-----------|-------------|---------|
| `CLAUDE_API_KEY` | Clé API Anthropic | `sk-ant-api03-xxx` |
| `API_SECRET_KEY` | Clé secrète API REST | `ma-cle-secrete-123` |
| `BASE_PATH` | Chemin si sous-répertoire | `/wikitips` |
| `DB_PATH` | Chemin base de données | `/var/data/wiki.db` |

### Obtenir une clé API Anthropic

1. Créez un compte sur [console.anthropic.com](https://console.anthropic.com/)
2. Accédez à **API Keys**
3. Cliquez **Create Key**
4. Copiez la clé (elle ne sera plus affichée)
5. Ajoutez des crédits si nécessaire

---

## Utilisation

### Authentification

#### Première connexion

1. Accédez à la page d'accueil
2. Cliquez sur **Connexion**
3. Utilisez les identifiants par défaut :
   - Utilisateur : `admin`
   - Mot de passe : `admin123`
4. **Important** : Changez immédiatement le mot de passe via **Profil**

#### Créer un compte

1. Cliquez sur **Inscription**
2. Remplissez le formulaire :
   - Nom d'utilisateur (unique)
   - Adresse email (unique)
   - Mot de passe (min. 6 caractères)
3. Les nouveaux comptes ont le rôle **éditeur**

#### Modifier son profil

1. Connectez-vous
2. Cliquez sur votre nom d'utilisateur dans l'en-tête
3. Modifiez vos informations :
   - Nom d'utilisateur
   - Adresse email
   - Mot de passe (nécessite le mot de passe actuel)

### Gestion des articles

#### Créer un article manuellement

1. Connectez-vous
2. Cliquez sur **Créer un article**
3. Remplissez le formulaire :
   - **Titre** : Titre de l'article
   - **URL source** : Lien vers la source originale
   - **Catégorie** : Sélectionnez ou créez une catégorie
   - **Contenu** : Corps de l'article
4. Optionnel : Cliquez sur **Analyser avec Claude** pour générer :
   - Un résumé
   - Les points principaux
   - L'analyse des droits humains
5. Cliquez sur **Enregistrer**

#### Modifier un article

1. Ouvrez l'article à modifier
2. Cliquez sur **Modifier** (visible si connecté)
3. Effectuez vos modifications
4. Cliquez sur **Enregistrer**

#### Supprimer un article

La suppression se fait directement en base de données (fonctionnalité admin à implémenter selon vos besoins).

### Extension Chrome

#### Capturer une page web

**Méthode 1 : Via le popup**

1. Naviguez vers la page à capturer
2. Cliquez sur l'icône WikiTips
3. Vérifiez le titre et l'URL affichés
4. Cliquez sur **Envoyer vers WikiTips**
5. WikiTips s'ouvre avec le formulaire pré-rempli

**Méthode 2 : Via le menu contextuel**

1. Faites un clic droit n'importe où sur la page
2. Sélectionnez **Analyser avec WikiTips**
3. WikiTips s'ouvre automatiquement

#### Résolution des problèmes de l'extension

| Problème | Solution |
|----------|----------|
| Icône grisée | Rechargez l'extension dans `chrome://extensions/` |
| "Clé API invalide" | Vérifiez que `API_KEY` est identique dans popup.js et config.local.php |
| Erreur de connexion | Vérifiez l'URL dans `WIKITIPS_URL` |
| Contenu non capturé | Certains sites bloquent les scripts, copiez manuellement |

### Analyse IA

#### Fonctionnement

L'analyse Claude examine le contenu sous plusieurs angles :

1. **Résumé** : Synthèse concise du contenu
2. **Points principaux** : Liste des informations clés
3. **Analyse des droits humains** :
   - Droits civils et politiques (liberté d'expression, vie privée, etc.)
   - Droits économiques, sociaux et culturels (travail, santé, éducation)
   - Droit international humanitaire (si applicable)
4. **Recommandations** : Actions suggérées

#### Lancer une analyse

1. Dans le formulaire de création/modification
2. Cliquez sur **Analyser avec Claude**
3. Patientez quelques secondes
4. Les champs sont automatiquement remplis
5. Vous pouvez modifier le résultat avant d'enregistrer

### Administration

#### Modifier la page d'accueil

1. Connectez-vous en tant qu'administrateur
2. Accédez à la page d'accueil
3. Cliquez sur **Modifier cette page** (lien en bas)
4. Éditez le contenu HTML
5. Cliquez sur **Enregistrer**

#### Gérer les utilisateurs

La gestion des utilisateurs se fait actuellement en base de données. Pour promouvoir un utilisateur en admin :

```sql
UPDATE users SET role = 'admin' WHERE username = 'nom_utilisateur';
```

---

## Intégration Bluesky

WikiTips permet de partager automatiquement vos articles sur Bluesky, le réseau social décentralisé basé sur le protocole AT.

### Configuration Bluesky

#### Étape 1 : Créer un App Password

Pour des raisons de sécurité, Bluesky utilise des "App Passwords" plutôt que votre mot de passe principal.

1. Connectez-vous à [bsky.app](https://bsky.app)
2. Allez dans **Settings** (Paramètres)
3. Cliquez sur **App Passwords**
4. Cliquez sur **Add App Password**
5. Donnez un nom (ex: "WikiTips")
6. Copiez le mot de passe généré (il ne sera plus affiché)

#### Étape 2 : Configurer WikiTips

Ajoutez dans votre fichier `config.local.php` :

```php
<?php
// Configuration Bluesky
define('BLUESKY_IDENTIFIER', 'votre-handle.bsky.social'); // ou votre email
define('BLUESKY_APP_PASSWORD', 'xxxx-xxxx-xxxx-xxxx');    // App Password créé

// Optionnel : activer le partage automatique par défaut
define('BLUESKY_AUTO_SHARE', true);
```

| Paramètre | Description | Exemple |
|-----------|-------------|---------|
| `BLUESKY_IDENTIFIER` | Votre handle Bluesky ou email | `user.bsky.social` |
| `BLUESKY_APP_PASSWORD` | App Password (pas votre vrai mot de passe !) | `abcd-1234-efgh-5678` |
| `BLUESKY_AUTO_SHARE` | Cocher par défaut l'option de partage | `true` ou `false` |

### Partage manuel

Une fois Bluesky configuré, un bouton **🦋 Bluesky** apparaît sur chaque article.

#### Procédure

1. Ouvrez l'article que vous souhaitez partager
2. Cliquez sur le bouton **🦋 Bluesky** dans les actions
3. Une page de prévisualisation s'affiche avec :
   - Le texte du post (modifiable, max 300 caractères)
   - Un aperçu de l'article
4. Modifiez le texte si nécessaire
5. Cliquez sur **Publier sur Bluesky**
6. Vous êtes redirigé vers l'article avec un message de confirmation

#### Format du post

Le post généré automatiquement comprend :

```
📰 Titre de l'article

Résumé de l'article (jusqu'à 200 caractères)...

#DroitsHumains #WikiTips
```

Plus une **carte de lien** avec :
- Le titre de l'article
- Une description (extrait du résumé)
- L'URL vers l'article complet

### Partage automatique

Vous pouvez partager automatiquement chaque nouvel article publié sur Bluesky.

#### Option 1 : À la création de l'article

1. Lors de la création d'un article, une option **🦋 Partager sur Bluesky à la publication** apparaît
2. Cochez cette option
3. Sélectionnez le statut **Publié**
4. Cliquez sur **Créer l'article**
5. L'article est créé ET partagé sur Bluesky automatiquement

> **Note** : Le partage automatique ne fonctionne que si l'article est publié (pas en brouillon).

#### Option 2 : Activer par défaut

Pour que l'option soit cochée par défaut sur tous les nouveaux articles :

```php
define('BLUESKY_AUTO_SHARE', true);
```

### Limitations

| Aspect | Limite |
|--------|--------|
| Longueur du texte | 300 caractères maximum |
| Images | Non supportées (carte de lien uniquement) |
| Fréquence | Pas de limite côté WikiTips |

### Résolution des problèmes Bluesky

#### "Authentification Bluesky échouée"

**Causes possibles :**
- Handle incorrect (vérifiez l'orthographe)
- App Password expiré ou révoqué
- Utilisation du mot de passe principal au lieu de l'App Password

**Solution :**
1. Vérifiez que `BLUESKY_IDENTIFIER` correspond exactement à votre handle
2. Créez un nouvel App Password sur bsky.app
3. Mettez à jour `BLUESKY_APP_PASSWORD`

#### "Le bouton Bluesky n'apparaît pas"

**Cause :** Bluesky n'est pas configuré.

**Solution :** Vérifiez que `BLUESKY_IDENTIFIER` et `BLUESKY_APP_PASSWORD` sont définis dans `config.local.php`.

#### "Erreur lors de la publication"

**Causes possibles :**
- Texte trop long (> 300 caractères)
- Problème de connexion réseau
- API Bluesky temporairement indisponible

**Solution :**
1. Réduisez la longueur du texte
2. Réessayez plus tard
3. Vérifiez le status de Bluesky sur [status.bsky.app](https://status.bsky.app)

---

## API REST

### Authentification

Toutes les requêtes POST nécessitent une clé API dans l'en-tête :

```
X-API-Key: votre-cle-secrete
```

### Endpoints

#### Vérifier l'état de l'API

```http
GET /api/?action=health
```

**Réponse :**
```json
{
  "status": "ok",
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

#### Lister les articles

```http
GET /api/?action=articles
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Titre de l'article",
      "summary": "Résumé...",
      "created_at": "2024-01-15 10:30:00"
    }
  ]
}
```

#### Créer un article

```http
POST /api/?action=articles
Content-Type: application/json
X-API-Key: votre-cle-secrete

{
  "title": "Titre",
  "content": "Contenu de l'article",
  "source_url": "https://exemple.com/source",
  "category_id": 1
}
```

#### Analyser du contenu

```http
POST /api/?action=analyze
Content-Type: application/json
X-API-Key: votre-cle-secrete

{
  "content": "Texte à analyser..."
}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "summary": "Résumé du contenu...",
    "main_points": ["Point 1", "Point 2"],
    "human_rights_analysis": {
      "civil_political": "Analyse...",
      "economic_social_cultural": "Analyse...",
      "international_humanitarian": "Analyse...",
      "recommendations": ["Recommandation 1"]
    }
  }
}
```

#### Lister les catégories

```http
GET /api/?action=categories
```

---

## Sécurité

### Mesures implémentées

| Mesure | Description |
|--------|-------------|
| Hash bcrypt | Mots de passe hashés avec `password_hash()` |
| Sessions PHP | Authentification par session sécurisée |
| Clé API | Protection des endpoints POST |
| Validation | Nettoyage des entrées utilisateur |
| HTTPS | Recommandé pour la production |

### Recommandations

1. **Changez le mot de passe admin** dès la première connexion
2. **Utilisez HTTPS** en production
3. **Protégez `config.local.php`** :
   ```apache
   # .htaccess
   <Files "config.local.php">
       Require all denied
   </Files>
   ```
4. **Sauvegardez la base de données** régulièrement
5. **Limitez les permissions** du dossier `data/`

### Fichiers sensibles

| Fichier | Action |
|---------|--------|
| `config.local.php` | Bloquer l'accès HTTP |
| `data/wikitips.db` | Bloquer l'accès HTTP |
| `.git/` | Bloquer l'accès HTTP |

---

## Personnalisation

### Modifier le style

Éditez `css/style.css` pour personnaliser l'apparence :

```css
/* Couleur principale */
:root {
    --wiki-blue: #0645ad;
    --wiki-visited: #0b0080;
}

/* En-tête */
.wiki-header {
    background: #f6f6f6;
    border-bottom: 1px solid #a7d7f9;
}
```

### Modifier le prompt Claude

Éditez `includes/ClaudeService.php` pour ajuster l'analyse :

```php
private function buildPrompt(string $content): string
{
    return "Analysez le contenu suivant...

    Votre prompt personnalisé ici...

    Contenu : {$content}";
}
```

### Ajouter des catégories

Les catégories sont créées automatiquement. Pour en ajouter manuellement :

```sql
INSERT INTO categories (name, slug) VALUES ('Nouvelle catégorie', 'nouvelle-categorie');
```

### Modifier le template

Éditez `templates/layout.php` pour modifier la structure des pages.

---

## Dépannage

### Problèmes courants

#### "Base de données non trouvée"

**Cause** : Le dossier `data/` n'existe pas ou n'est pas accessible en écriture.

**Solution** :
```bash
mkdir -p data
chmod 755 data
```

#### "Clé API Claude invalide"

**Cause** : La clé `CLAUDE_API_KEY` est incorrecte ou expirée.

**Solution** :
1. Vérifiez la clé sur [console.anthropic.com](https://console.anthropic.com/)
2. Assurez-vous qu'elle commence par `sk-ant-api03-`
3. Vérifiez les crédits disponibles

#### "Erreur 404 sur les liens"

**Cause** : Installation dans un sous-répertoire non configuré.

**Solution** : Ajoutez dans `config.local.php` :
```php
define('BASE_PATH', '/nom-du-sous-repertoire');
```

#### "Extension Chrome : icône manquante"

**Cause** : Les fichiers PNG n'existent pas.

**Solution** :
```bash
php generate-icons.php
```

#### "Erreur de connexion API : JSON invalide"

**Cause** : Le serveur ne supporte pas la réécriture d'URL.

**Solution** : Utilisez le format `?action=xxx` au lieu de `/api/xxx`

### Logs et debug

Pour activer le mode debug, ajoutez dans `config.local.php` :

```php
define('DEBUG_MODE', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Support

Pour signaler un bug ou demander de l'aide :
1. Vérifiez d'abord cette documentation
2. Consultez les logs du serveur web
3. Ouvrez une issue sur le dépôt GitHub

---

## Licence

WikiTips est distribué sous licence MIT.

---

*Documentation générée le 7 janvier 2026*
