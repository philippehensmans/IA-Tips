# IA-Tips - Manuel d'utilisation

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
   - [Gestion des prompts](#gestion-des-prompts)
   - [Importation de contenu](#importation-de-contenu)
   - [Favoris](#favoris)
   - [Recherche](#recherche)
   - [Partage](#partage)
   - [Extension Chrome](#extension-chrome)
   - [Administration](#administration)
7. [Gestion des utilisateurs et rôles](#gestion-des-utilisateurs-et-rôles)
8. [Intégration Bluesky](#intégration-bluesky)
9. [API REST](#api-rest)
10. [Sécurité](#sécurité)
11. [Personnalisation](#personnalisation)
12. [Dépannage](#dépannage)

---

## Introduction

**IA-Tips** est une application web PHP permettant de collecter, organiser et analyser des articles et des prompts liés à l'intelligence artificielle. Elle intègre l'API Claude d'Anthropic pour fournir une analyse automatique du contenu : résumé, extraction des points clés et analyse thématique.

### Objectifs

- Centraliser une veille sur l'intelligence artificielle (articles, tutoriels, actualités)
- Constituer une bibliothèque de prompts réutilisables
- Analyser automatiquement le contenu importé grâce à l'IA Claude
- Capturer rapidement du contenu web via une extension Chrome
- Partager les articles sur les réseaux sociaux (WhatsApp, Bluesky)

---

## Fonctionnalités

### Gestion de contenu

| Fonctionnalité | Description |
|----------------|-------------|
| Articles IA | Collecte, résumé automatique et analyse d'articles sur l'IA |
| Prompts | Bibliothèque de prompts formatés, prêts à être réutilisés |
| Importation | Import de contenu depuis une URL ou un fichier avec analyse via Claude |
| Catégories | Organisation par catégories (articles et prompts séparés) |
| Favoris | Marquer des contenus comme favoris pour un accès rapide |
| Recherche | Recherche full-text dans tous les contenus |
| Pages statiques | Pages éditables (accueil, à propos, etc.) |

### Intelligence artificielle

| Fonctionnalité | Description |
|----------------|-------------|
| Résumé automatique | Synthèse du contenu en quelques phrases |
| Extraction des points clés | Liste structurée des informations principales |
| Analyse thématique | Évaluation du contenu sous l'angle IA |
| Catégorisation suggérée | Suggestion automatique de catégories |
| Formatage de prompts | Mise en forme structurée des prompts importés |

### Partage social

| Fonctionnalité | Description |
|----------------|-------------|
| WhatsApp | Bouton de partage rapide avec titre et résumé |
| Bluesky | Partage manuel ou automatique avec carte de lien |

### Extension Chrome

| Fonctionnalité | Description |
|----------------|-------------|
| Capture de contenu | Extraction du texte sélectionné ou de la page |
| Menu contextuel | Clic droit pour analyser du texte |
| Envoi direct | Création d'un brouillon en un clic |
| Choix du type | Sélection article ou prompt avant envoi |

---

## Architecture technique

### Technologies utilisées

| Composant | Technologie | Version |
|-----------|-------------|---------|
| Backend | PHP | 8.0+ |
| Base de données | SQLite | 3.x |
| API | REST/JSON | - |
| Frontend | HTML5/CSS3 | - |
| Éditeur riche | TinyMCE | - |
| Extension | Chrome Manifest | V3 |
| IA | API Claude Anthropic | claude-sonnet-4-20250514 |

### Structure des fichiers

```
IA-Tips/
│
├── config.php                 # Configuration principale & autoloader
├── config.local.php           # Configuration locale (à créer, non versionné)
├── config.local.php.example   # Modèle de configuration locale
│
├── index.php                  # Page d'accueil (articles & prompts récents)
├── articles.php               # Liste des articles/prompts avec filtres
├── article.php                # Affichage d'un article/prompt
├── new.php                    # Création d'un article/prompt
├── edit.php                   # Modification d'un article/prompt
├── import.php                 # Importation & analyse de contenu
├── search.php                 # Recherche full-text
│
├── categories.php             # Liste des catégories
├── category.php               # Affichage d'une catégorie
├── manage-categories.php      # Gestion des catégories (éditeurs+)
│
├── login.php                  # Page de connexion
├── logout.php                 # Déconnexion
├── profile.php                # Profil utilisateur
├── users.php                  # Gestion des utilisateurs (admin)
│
├── share-bluesky.php          # Partage sur Bluesky
├── edit-page.php              # Modification pages statiques (admin)
│
├── includes/
│   ├── Database.php           # Singleton SQLite, migrations
│   ├── Article.php            # Modèle Article/Prompt (CRUD)
│   ├── Category.php           # Modèle Catégorie
│   ├── Auth.php               # Authentification & autorisation (3 rôles)
│   ├── Page.php               # Gestion pages statiques
│   ├── ClaudeService.php      # Intégration API Claude
│   ├── BlueskyService.php     # Intégration Bluesky (AT Protocol)
│   ├── FileParser.php         # Parsing de fichiers (MD, TXT, PDF)
│   └── Helpers.php            # Fonctions utilitaires
│
├── api/
│   └── index.php              # Routeur API REST
│
├── templates/
│   └── layout.php             # Template principal
│
├── assets/
│   ├── css/                   # Feuilles de styles
│   └── js/                    # Scripts JavaScript
│
├── uploads/                   # Fichiers uploadés
│
├── data/
│   └── wikitips.db            # Base de données SQLite (auto-créée)
│
└── chrome-extension/
    ├── manifest.json          # Configuration extension (Manifest V3)
    ├── popup.html             # Interface popup
    ├── popup.js               # Logique popup
    ├── background.js          # Service worker (menu contextuel)
    ├── content.js             # Script de contenu
    └── icons/                 # Icônes de l'extension
```

### Schéma de la base de données

#### Table `articles`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire auto-incrémentée |
| title | TEXT | Titre de l'article ou du prompt |
| slug | TEXT | Identifiant URL unique |
| type | TEXT | `article` ou `prompt` |
| source_url | TEXT | URL de la source originale |
| source_content | TEXT | Contenu source brut |
| summary | TEXT | Résumé généré par Claude |
| main_points | TEXT | Points clés (HTML) |
| analysis | TEXT | Analyse détaillée (HTML) |
| formatted_prompt | TEXT | Prompt formaté (type prompt uniquement) |
| content | TEXT | Notes additionnelles |
| status | TEXT | `draft` ou `published` |
| is_favorite | INTEGER | 0 ou 1 |
| created_at | DATETIME | Date de création |
| updated_at | DATETIME | Date de modification |

#### Table `categories`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire |
| name | TEXT | Nom de la catégorie (unique) |
| slug | TEXT | Identifiant URL (unique) |
| description | TEXT | Description de la catégorie |
| type | TEXT | `article` ou `prompt` |

#### Table `article_categories`

| Colonne | Type | Description |
|---------|------|-------------|
| article_id | INTEGER | Clé étrangère vers articles |
| category_id | INTEGER | Clé étrangère vers categories |

Relation many-to-many : un article peut avoir plusieurs catégories.

#### Table `users`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire |
| username | TEXT | Nom d'utilisateur unique |
| email | TEXT | Adresse email unique |
| password_hash | TEXT | Hash bcrypt du mot de passe |
| role | TEXT | `encodeur`, `editor` ou `admin` |
| created_at | DATETIME | Date d'inscription |
| last_login | DATETIME | Dernière connexion |

#### Table `pages`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire |
| slug | TEXT | Identifiant unique (ex: `home`) |
| title | TEXT | Titre de la page |
| content | TEXT | Contenu HTML |
| updated_at | DATETIME | Dernière modification |

---

## Installation

### Prérequis

#### Serveur web

- **PHP** 8.0 ou supérieur
- **Extensions PHP** requises :
  - `pdo_sqlite` — accès base de données
  - `curl` — appels API Claude
  - `json` — traitement JSON
  - `mbstring` — gestion de l'encodage
- **Serveur web** : Apache, Nginx ou équivalent
- **HTTPS** recommandé pour la production

#### Poste client (extension)

- **Google Chrome** ou navigateur compatible Chromium
- Accès au mode développeur des extensions

### Installation serveur

#### Étape 1 : Télécharger les fichiers

```bash
git clone <url-du-depot> IA-Tips
cd IA-Tips
```

#### Étape 2 : Configurer les permissions

```bash
chmod 755 .
mkdir -p data uploads
chmod 755 data uploads
```

#### Étape 3 : Créer la configuration locale

```bash
cp config.local.php.example config.local.php
```

Éditez `config.local.php` avec vos paramètres :

```php
<?php
// Clé API Anthropic (obligatoire pour l'analyse IA)
define('CLAUDE_API_KEY', 'sk-ant-api03-votre-cle-ici');

// Clé secrète pour l'API REST (extension Chrome)
define('API_SECRET_KEY', 'votre-cle-secrete-aleatoire');

// URL du site (pour le partage)
define('SITE_URL', 'https://votresite.com');

// Mode debug (désactiver en production)
define('DEBUG_MODE', false);
```

#### Étape 4 : Vérifier l'installation

1. Accédez à votre site dans le navigateur
2. La base de données est créée automatiquement
3. Connectez-vous avec les identifiants par défaut :
   - Utilisateur : `admin`
   - Mot de passe : `admin123`
4. **Changez immédiatement le mot de passe admin** via le profil

### Installation extension Chrome

#### Étape 1 : Charger l'extension

1. Ouvrez Chrome et accédez à `chrome://extensions/`
2. Activez le **Mode développeur** (interrupteur en haut à droite)
3. Cliquez sur **Charger l'extension non empaquetée**
4. Sélectionnez le dossier `chrome-extension/`

#### Étape 2 : Configurer l'extension

1. Cliquez sur l'icône de l'extension
2. Renseignez l'URL du serveur IA-Tips
3. Renseignez la clé API (même valeur que `API_SECRET_KEY` dans `config.local.php`)

#### Étape 3 : Épingler l'extension (optionnel)

1. Cliquez sur l'icône puzzle (extensions) dans Chrome
2. Cliquez sur l'épingle à côté d'IA-Tips

---

## Configuration

### Configuration principale (`config.php`)

Ce fichier contient les paramètres par défaut et l'autoloader. **Ne le modifiez pas**, utilisez `config.local.php` pour surcharger les valeurs.

### Configuration locale (`config.local.php`)

| Constante | Description | Exemple |
|-----------|-------------|---------|
| `CLAUDE_API_KEY` | Clé API Anthropic | `sk-ant-api03-xxx` |
| `API_SECRET_KEY` | Clé secrète API REST | `ma-cle-secrete-123` |
| `SITE_URL` | URL publique du site | `https://ia-tips.example.com` |
| `SITE_NAME` | Nom du site | `IA-Tips` |
| `BASE_PATH` | Chemin si sous-répertoire | `/ia-tips` |
| `DB_PATH` | Chemin base de données | `/var/data/wikitips.db` |
| `DEBUG_MODE` | Mode debug | `true` ou `false` |
| `BLUESKY_IDENTIFIER` | Handle Bluesky | `user.bsky.social` |
| `BLUESKY_APP_PASSWORD` | App Password Bluesky | `xxxx-xxxx-xxxx-xxxx` |
| `BLUESKY_AUTO_SHARE` | Partage auto Bluesky | `true` ou `false` |

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
3. Identifiants par défaut : `admin` / `admin123`
4. **Changez immédiatement le mot de passe** via le profil

#### Modifier son profil

1. Cliquez sur votre nom d'utilisateur dans l'en-tête
2. Modifiez vos informations (nom, email, mot de passe)

### Gestion des articles

#### Créer un article

1. Connectez-vous (rôle encodeur minimum)
2. Cliquez sur **Nouveau** puis sélectionnez **Article**
3. Remplissez le formulaire :
   - **Titre** : titre de l'article
   - **URL source** : lien vers la source originale
   - **Contenu** : corps de l'article (éditeur TinyMCE)
   - **Catégories** : sélectionnez une ou plusieurs catégories
   - **Statut** : brouillon ou publié
4. Cliquez sur **Enregistrer**

#### Modifier / Supprimer un article

Requiert le rôle **éditeur** ou **admin** :

1. Ouvrez l'article
2. Cliquez sur **Modifier** ou **Supprimer**

### Gestion des prompts

#### Créer un prompt

1. Connectez-vous (rôle encodeur minimum)
2. Cliquez sur **Nouveau** puis sélectionnez **Prompt**
3. Remplissez le formulaire :
   - **Titre** : titre du prompt
   - **Prompt formaté** : le texte du prompt
   - **Catégories** : sélectionnez une ou plusieurs catégories
4. Cliquez sur **Enregistrer**

#### Copier un prompt

Sur la page d'un prompt, cliquez sur le bouton **Copier** pour copier le texte du prompt dans le presse-papier.

### Importation de contenu

L'importation permet de créer rapidement un article ou un prompt à partir d'une source externe. Requiert le rôle **encodeur** minimum.

#### Depuis une URL

1. Accédez à **Importer**
2. Collez l'URL source
3. Sélectionnez le type (article ou prompt)
4. Cliquez sur **Analyser**
5. Claude génère automatiquement : résumé, points clés, analyse
6. Un brouillon est créé, modifiable avant publication

#### Depuis un fichier

1. Accédez à **Importer**
2. Uploadez un fichier (formats supportés : MD, TXT, PDF)
3. Le contenu est extrait et analysé par Claude

#### En collant du texte

1. Accédez à **Importer**
2. Collez le texte directement dans le champ prévu
3. Lancez l'analyse

### Favoris

- Cliquez sur l'étoile (☆/★) sur n'importe quel article ou prompt pour le marquer comme favori
- Filtrez par favoris dans la liste des articles via le filtre **Favoris**
- Fonctionne pour les articles et les prompts

### Recherche

1. Utilisez la barre de recherche
2. La recherche porte sur : titre, résumé et contenu
3. Les résultats affichent tous les types (articles et prompts)

### Partage

#### WhatsApp

Sur la page d'un article, cliquez sur le bouton **WhatsApp**. Un message pré-formaté est généré avec le titre, un extrait du résumé et le lien vers l'article.

#### Bluesky

Voir la section [Intégration Bluesky](#intégration-bluesky).

### Extension Chrome

#### Capturer du contenu

**Méthode 1 : Via le popup**

1. Naviguez vers la page à capturer
2. Cliquez sur l'icône IA-Tips dans la barre d'outils
3. Sélectionnez le texte ou utilisez **Capturer la sélection**
4. Choisissez le type (article ou prompt)
5. Cliquez sur **Analyser et envoyer**
6. Un brouillon est créé et ouvert dans un nouvel onglet

**Méthode 2 : Via le menu contextuel**

1. Sélectionnez du texte sur une page web
2. Clic droit > **Analyser avec IA-Tips**
3. Le contenu est envoyé et un brouillon est créé automatiquement

### Administration

#### Gérer les catégories (éditeur+)

1. Accédez à **Gérer les catégories**
2. Créez, modifiez ou supprimez des catégories
3. Les catégories sont séparées par type (articles / prompts)

**Catégories articles par défaut** : LLM & Modèles de langage, Agents IA, Vision & Multimodal, RAG & Embeddings, Fine-tuning & Entraînement, Éthique & Sécurité IA, Outils & Frameworks, Actualités IA

**Catégories prompts par défaut** : Développement, Rédaction, Analyse, Créativité, Productivité, Éducation, Business, Système

#### Modifier la page d'accueil (admin)

1. Connectez-vous en tant qu'administrateur
2. Accédez à la page d'accueil
3. Cliquez sur **Modifier cette page**
4. Éditez le contenu
5. Enregistrez

#### Gérer les utilisateurs (admin)

1. Accédez à **Utilisateurs** dans le menu admin
2. Vous pouvez :
   - Voir la liste de tous les utilisateurs
   - Créer de nouveaux comptes (encodeur, éditeur, admin)
   - Changer le rôle d'un utilisateur
   - Supprimer un utilisateur (sauf le dernier admin)

---

## Gestion des utilisateurs et rôles

L'application dispose de trois niveaux de rôles avec des permissions croissantes :

### Tableau des permissions

| Action | Encodeur | Éditeur | Admin |
|--------|----------|---------|-------|
| Se connecter et voir le contenu | oui | oui | oui |
| Créer des articles et prompts | oui | oui | oui |
| Importer du contenu (URL, fichier) | oui | oui | oui |
| Marquer des favoris | oui | oui | oui |
| Modifier des articles/prompts | non | oui | oui |
| Supprimer des articles/prompts | non | oui | oui |
| Gérer les catégories | non | oui | oui |
| Modifier les pages statiques | non | non | oui |
| Gérer les utilisateurs | non | non | oui |
| Changer les rôles | non | non | oui |

### Description des rôles

- **Encodeur** : rôle de base. Peut créer de nouveaux articles, prompts et importer du contenu via URL ou fichier. Ne peut pas modifier ou supprimer le contenu existant.
- **Éditeur** : peut en plus modifier et supprimer des articles/prompts, et gérer les catégories.
- **Administrateur** : accès complet, incluant la gestion des utilisateurs (création de comptes, changement de rôles, suppression).

### Créer un utilisateur (admin)

1. Accédez à la page **Utilisateurs**
2. Remplissez le formulaire de création :
   - Nom d'utilisateur
   - Email
   - Mot de passe
   - Rôle (encodeur, éditeur ou admin)
3. Cliquez sur **Créer**

### Protection du dernier admin

Le système empêche de supprimer ou de rétrograder le dernier compte administrateur, pour garantir qu'il reste toujours au moins un admin.

---

## Intégration Bluesky

IA-Tips permet de partager vos articles sur Bluesky, le réseau social décentralisé basé sur le protocole AT.

### Configuration Bluesky

#### Étape 1 : Créer un App Password

1. Connectez-vous à [bsky.app](https://bsky.app)
2. Allez dans **Settings** > **App Passwords**
3. Cliquez sur **Add App Password**
4. Donnez un nom (ex: "IA-Tips")
5. Copiez le mot de passe généré

#### Étape 2 : Configurer IA-Tips

Ajoutez dans `config.local.php` :

```php
<?php
define('BLUESKY_IDENTIFIER', 'votre-handle.bsky.social');
define('BLUESKY_APP_PASSWORD', 'xxxx-xxxx-xxxx-xxxx');
define('BLUESKY_AUTO_SHARE', true); // optionnel
```

### Partage manuel

1. Ouvrez un article publié
2. Cliquez sur le bouton **Bluesky**
3. Modifiez le texte du post si nécessaire (max 300 caractères)
4. Cliquez sur **Publier sur Bluesky**

Le post inclut automatiquement une carte de lien avec le titre et la description de l'article.

### Partage automatique

Si `BLUESKY_AUTO_SHARE` est activé, une option de partage automatique est proposée (et cochée par défaut) lors de la création d'un article. Le partage n'a lieu que si l'article est publié (pas en brouillon).

### Limitations

| Aspect | Limite |
|--------|--------|
| Longueur du texte | 300 caractères maximum |
| Type de contenu | Articles uniquement (pas les prompts) |
| Images | Non supportées (carte de lien uniquement) |

---

## API REST

### Authentification

Les requêtes POST nécessitent une clé API dans l'en-tête :

```
X-API-Key: votre-cle-secrete
```

### Endpoints

Les endpoints supportent deux formats d'URL : `/api/resource` et `/api/?action=resource`.

#### Health check

```http
GET /api/?action=health
```

```json
{"status": "ok", "timestamp": "2026-03-11T10:30:00+00:00"}
```

#### Articles

```http
GET    /api/?action=articles           # Lister les articles
POST   /api/?action=articles           # Créer un article
PUT    /api/?action=articles&id=1      # Modifier un article
DELETE /api/?action=articles&id=1      # Supprimer un article
```

#### Favoris

```http
POST /api/?action=articles&id=1&sub=favorite   # Toggle favori
```

#### Catégories

```http
GET    /api/?action=categories         # Lister les catégories
POST   /api/?action=categories         # Créer une catégorie
PUT    /api/?action=categories&id=1    # Modifier une catégorie
DELETE /api/?action=categories&id=1    # Supprimer une catégorie
```

#### Analyser du contenu

```http
POST /api/?action=analyze
Content-Type: application/json
X-API-Key: votre-cle-secrete

{
  "content": "Texte à analyser...",
  "source_url": "https://exemple.com",
  "type": "article",
  "create_article": true
}
```

---

## Sécurité

### Mesures implémentées

| Mesure | Description |
|--------|-------------|
| Hash bcrypt | Mots de passe hashés avec `password_hash()` |
| Requêtes préparées | Protection contre l'injection SQL |
| Échappement HTML | Protection XSS via `htmlspecialchars()` |
| Filtrage HTML | `filterBasicHtml()` pour le contenu riche |
| Sessions PHP | Authentification par session sécurisée |
| Clé API | Protection des endpoints POST de l'API |
| Contrôle d'accès | Vérification des rôles sur chaque action |
| Protection admin | Impossible de supprimer le dernier admin |

### Recommandations

1. **Changez le mot de passe admin** dès la première connexion
2. **Utilisez HTTPS** en production
3. **Protégez les fichiers sensibles** :
   ```apache
   # .htaccess
   <Files "config.local.php">
       Require all denied
   </Files>
   ```
4. **Sauvegardez la base de données** régulièrement
5. **Changez `API_SECRET_KEY`** avec une valeur aléatoire forte
6. **Désactivez `DEBUG_MODE`** en production

### Fichiers sensibles à protéger

| Fichier | Action |
|---------|--------|
| `config.local.php` | Bloquer l'accès HTTP |
| `data/wikitips.db` | Bloquer l'accès HTTP |
| `.git/` | Bloquer l'accès HTTP |

---

## Personnalisation

### Modifier le style

Éditez les fichiers dans `assets/css/` pour personnaliser l'apparence.

### Modifier les prompts d'analyse Claude

Éditez `includes/ClaudeService.php` pour ajuster le comportement de l'analyse IA. Deux méthodes séparées gèrent les prompts :

- `buildArticleAnalysisPrompt()` — pour l'analyse d'articles
- `buildPromptAnalysisPrompt()` — pour le formatage de prompts

### Modifier le template

Éditez `templates/layout.php` pour modifier la structure des pages (en-tête, navigation, pied de page).

### Ajouter des catégories

Les catégories se gèrent via l'interface (rôle éditeur ou admin) dans **Gérer les catégories**.

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
2. Vérifiez les crédits disponibles
3. Mettez à jour `config.local.php`

#### "Erreur 404 sur les liens"

**Cause** : Installation dans un sous-répertoire non configuré.

**Solution** :
```php
define('BASE_PATH', '/nom-du-sous-repertoire');
```

#### "L'extension Chrome ne fonctionne pas"

| Problème | Solution |
|----------|----------|
| Icône grisée | Rechargez l'extension dans `chrome://extensions/` |
| "Clé API invalide" | Vérifiez que la clé correspond à `API_SECRET_KEY` |
| Erreur de connexion | Vérifiez l'URL du serveur dans les paramètres de l'extension |

#### "Authentification Bluesky échouée"

1. Vérifiez que `BLUESKY_IDENTIFIER` correspond à votre handle
2. Créez un nouvel App Password (pas votre mot de passe principal)
3. Mettez à jour `BLUESKY_APP_PASSWORD`

### Logs et debug

Pour activer le mode debug :

```php
define('DEBUG_MODE', true);
```

Les erreurs PHP seront alors affichées directement dans le navigateur.

### Support

Pour signaler un bug ou demander de l'aide, ouvrez une issue sur le dépôt GitHub du projet.

---

*Documentation mise à jour le 11 mars 2026*
