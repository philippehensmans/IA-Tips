/**
 * WikiTips - JavaScript principal
 */

document.addEventListener('DOMContentLoaded', function() {
    // Confirmation de suppression
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Auto-resize des textareas (sauf ceux avec TinyMCE)
    document.querySelectorAll('textarea:not([data-formatting="true"])').forEach(function(textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });

    // Initialiser TinyMCE pour les textareas avec data-formatting="true"
    initTinyMCE();

    // Menu hamburger pour mobile
    initMobileMenu();
});

/**
 * Initialise le menu mobile (hamburger)
 */
function initMobileMenu() {
    var menuToggle = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            // Changer l'icône
            this.textContent = sidebar.classList.contains('active') ? '✕' : '☰';
        });

        // Fermer le menu quand on clique sur un lien
        sidebar.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    menuToggle.textContent = '☰';
                }
            });
        });

        // Fermer le menu quand on clique en dehors
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 &&
                sidebar.classList.contains('active') &&
                !sidebar.contains(e.target) &&
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                menuToggle.textContent = '☰';
            }
        });
    }
}

/**
 * Initialise TinyMCE pour les textareas avec data-formatting="true"
 */
function initTinyMCE() {
    var textareas = document.querySelectorAll('textarea[data-formatting="true"]');
    if (textareas.length === 0) return;

    // Vérifier que TinyMCE est chargé
    if (typeof tinymce === 'undefined') {
        console.error('TinyMCE n\'est pas chargé');
        return;
    }

    tinymce.init({
        selector: 'textarea[data-formatting="true"]',
        language: 'fr_FR',
        language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@23.10.9/langs6/fr_FR.min.js',
        height: 350,
        menubar: false,
        branding: false,
        promotion: false,
        plugins: [
            'lists', 'table', 'image', 'link', 'code', 'codesample'
        ],
        toolbar: 'undo redo | bold italic underline | bullist numlist | table | image link | codesample | code',
        toolbar_mode: 'wrap',
        // Configuration upload d'images
        images_upload_url: (window.APP_CONFIG ? window.APP_CONFIG.uploadUrl : '/api/upload.php'),
        images_upload_credentials: true,
        automatic_uploads: true,
        file_picker_types: 'image',
        // Permettre le glisser-déposer d'images
        paste_data_images: true,
        // Style de l'éditeur
        content_style: `
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                font-size: 14px;
                line-height: 1.6;
                padding: 10px;
            }
            pre.prompt {
                background: linear-gradient(135deg, #1e1e2e 0%, #2d2d3d 100%);
                color: #e0e0e0;
                padding: 16px 20px;
                border-radius: 8px;
                font-family: monospace;
                font-size: 13px;
                border-left: 4px solid #6366f1;
                white-space: pre-wrap;
            }
            table {
                border-collapse: collapse;
                width: 100%;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background: #f5f5f5;
            }
            img {
                max-width: 100%;
                height: auto;
            }
        `,
        // Formats personnalisés
        formats: {
            prompt: { block: 'pre', classes: 'prompt' }
        },
        // Configuration du bouton codesample pour les prompts
        codesample_languages: [
            { text: 'Prompt', value: 'prompt' },
            { text: 'JavaScript', value: 'javascript' },
            { text: 'Python', value: 'python' },
            { text: 'HTML', value: 'markup' },
            { text: 'CSS', value: 'css' },
            { text: 'Bash', value: 'bash' }
        ],
        // Callback après initialisation
        setup: function(editor) {
            // Bouton personnalisé pour les blocs prompt
            editor.ui.registry.addButton('promptblock', {
                text: '{ }',
                tooltip: 'Bloc Prompt',
                onAction: function() {
                    var selectedText = editor.selection.getContent({ format: 'text' });
                    if (selectedText) {
                        editor.insertContent('<pre class="prompt">' + selectedText + '</pre>');
                    } else {
                        editor.insertContent('<pre class="prompt">Votre prompt ici...</pre>');
                    }
                }
            });
        },
        // Gestionnaire d'upload personnalisé
        images_upload_handler: function(blobInfo, progress) {
            return new Promise(function(resolve, reject) {
                var xhr = new XMLHttpRequest();
                var uploadUrl = window.APP_CONFIG ? window.APP_CONFIG.uploadUrl : '/api/upload.php';
                xhr.open('POST', uploadUrl);
                xhr.withCredentials = true;

                xhr.upload.onprogress = function(e) {
                    progress(e.loaded / e.total * 100);
                };

                xhr.onload = function() {
                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject('Erreur HTTP: ' + xhr.status);
                        return;
                    }

                    var json;
                    try {
                        json = JSON.parse(xhr.responseText);
                    } catch (e) {
                        reject('Réponse JSON invalide');
                        return;
                    }

                    if (json.error) {
                        reject(json.error);
                        return;
                    }

                    if (!json.location) {
                        reject('Pas d\'URL d\'image dans la réponse');
                        return;
                    }

                    resolve(json.location);
                };

                xhr.onerror = function() {
                    reject('Erreur réseau lors de l\'upload');
                };

                var formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                xhr.send(formData);
            });
        }
    });
}

/**
 * Entoure la sélection avec des balises
 */
function wrapSelection(textarea, openTag, closeTag) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var text = textarea.value;
    var selectedText = text.substring(start, end);

    // Si pas de sélection, insérer les balises vides
    if (start === end) {
        var newText = text.substring(0, start) + openTag + closeTag + text.substring(end);
        textarea.value = newText;
        // Placer le curseur entre les balises
        textarea.selectionStart = textarea.selectionEnd = start + openTag.length;
    } else {
        // Entourer la sélection
        var newText = text.substring(0, start) + openTag + selectedText + closeTag + text.substring(end);
        textarea.value = newText;
        // Sélectionner le texte formaté
        textarea.selectionStart = start;
        textarea.selectionEnd = end + openTag.length + closeTag.length;
    }

    textarea.focus();
    // Déclencher l'événement input pour l'auto-resize
    textarea.dispatchEvent(new Event('input'));
}

/**
 * Insère une liste (à puces ou numérotée)
 */
function insertList(textarea, type) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var text = textarea.value;
    var selectedText = text.substring(start, end);

    var listHtml;
    var tagName = type === 'ol' ? 'ol' : 'ul';

    if (selectedText) {
        // Convertir les lignes sélectionnées en éléments de liste
        var lines = selectedText.split('\n').filter(function(line) {
            return line.trim() !== '';
        });
        if (lines.length > 0) {
            var items = lines.map(function(line) {
                return '<li>' + line.trim() + '</li>';
            }).join('\n  ');
            listHtml = '<' + tagName + '>\n  ' + items + '\n</' + tagName + '>';
        } else {
            listHtml = '<' + tagName + '>\n  <li>Élément 1</li>\n  <li>Élément 2</li>\n  <li>Élément 3</li>\n</' + tagName + '>';
        }
    } else {
        // Insérer une liste vide avec des éléments par défaut
        listHtml = '<' + tagName + '>\n  <li>Élément 1</li>\n  <li>Élément 2</li>\n  <li>Élément 3</li>\n</' + tagName + '>';
    }

    var newText = text.substring(0, start) + listHtml + text.substring(end);
    textarea.value = newText;
    textarea.selectionStart = start;
    textarea.selectionEnd = start + listHtml.length;
    textarea.focus();
    textarea.dispatchEvent(new Event('input'));
}

/**
 * Insère un tableau HTML
 */
function insertTable(textarea) {
    var start = textarea.selectionStart;
    var text = textarea.value;

    var tableHtml = '<table>\n' +
        '  <thead>\n' +
        '    <tr>\n' +
        '      <th>En-tête 1</th>\n' +
        '      <th>En-tête 2</th>\n' +
        '      <th>En-tête 3</th>\n' +
        '    </tr>\n' +
        '  </thead>\n' +
        '  <tbody>\n' +
        '    <tr>\n' +
        '      <td>Cellule 1</td>\n' +
        '      <td>Cellule 2</td>\n' +
        '      <td>Cellule 3</td>\n' +
        '    </tr>\n' +
        '    <tr>\n' +
        '      <td>Cellule 4</td>\n' +
        '      <td>Cellule 5</td>\n' +
        '      <td>Cellule 6</td>\n' +
        '    </tr>\n' +
        '  </tbody>\n' +
        '</table>';

    var newText = text.substring(0, start) + tableHtml + text.substring(start);
    textarea.value = newText;
    textarea.selectionStart = start;
    textarea.selectionEnd = start + tableHtml.length;
    textarea.focus();
    textarea.dispatchEvent(new Event('input'));
}

/**
 * Insère un bloc prompt
 */
function insertPrompt(textarea) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var text = textarea.value;
    var selectedText = text.substring(start, end);

    var promptHtml;
    if (selectedText) {
        // Entourer le texte sélectionné
        promptHtml = '<pre class="prompt">' + selectedText + '</pre>';
    } else {
        // Insérer un bloc vide
        promptHtml = '<pre class="prompt">Votre prompt ici...</pre>';
    }

    var newText = text.substring(0, start) + promptHtml + text.substring(end);
    textarea.value = newText;
    textarea.selectionStart = start;
    textarea.selectionEnd = start + promptHtml.length;
    textarea.focus();
    textarea.dispatchEvent(new Event('input'));
}

/**
 * Recherche de suggestions homepage (style Google Answer)
 */
var suggestTimeout = null;

function handleSuggestSearch(event) {
    event.preventDefault();
    var query = document.getElementById('suggestQuery').value.trim();
    if (query.length < 2) return false;
    performSuggestSearch(query);
    return false;
}

function performSuggestSearch(query) {
    var resultsDiv = document.getElementById('suggestResults');
    if (!resultsDiv) return;

    resultsDiv.style.display = 'block';
    resultsDiv.innerHTML = '<div class="suggest-loading">Recherche en cours...</div>';

    var apiUrl = window.APP_CONFIG ? window.APP_CONFIG.apiUrl : '/api/index.php?action=';
    var separator = apiUrl.indexOf('?') !== -1 ? '&' : '?';
    var url = apiUrl + 'suggest' + separator + 'q=' + encodeURIComponent(query);

    fetch(url)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (!data.success || !data.data || data.data.length === 0) {
                resultsDiv.innerHTML = '<div class="suggest-empty">' +
                    '<strong>Aucun résultat trouvé</strong> pour &laquo; ' + escapeHtml(query) + ' &raquo;' +
                    '<p>Essayez avec d\'autres mots-clés, ou <a href="search.php?q=' + encodeURIComponent(query) + '">lancez une recherche complète</a>.</p>' +
                    '</div>';
                return;
            }

            var html = '';

            // Featured snippet : le résultat le plus pertinent
            var best = data.data[0];
            html += buildFeaturedSnippet(best, query);

            // Autres résultats pertinents
            if (data.data.length > 1) {
                html += '<div class="suggest-others">';
                html += '<h4>Autres résultats pertinents</h4>';
                html += '<ul class="suggest-list">';
                for (var i = 1; i < data.data.length; i++) {
                    var item = data.data[i];
                    var typeLabel = item.type === 'prompt' ? '💬 Prompt' : '📄 Article';
                    var baseUrl = window.APP_CONFIG ? window.APP_CONFIG.baseUrl : '';
                    html += '<li class="suggest-list-item">';
                    html += '<a href="' + baseUrl + '/article.php?slug=' + encodeURIComponent(item.slug) + '">';
                    html += '<span class="suggest-type-badge suggest-type-' + item.type + '">' + typeLabel + '</span> ';
                    html += escapeHtml(item.title);
                    html += '</a>';
                    if (item.categories && item.categories.length > 0) {
                        html += ' <span class="suggest-cats">' + item.categories.slice(0, 2).map(escapeHtml).join(', ') + '</span>';
                    }
                    html += '</li>';
                }
                html += '</ul>';
                html += '</div>';
            }

            resultsDiv.innerHTML = html;

            // Activer le bouton IA si admin et résultats trouvés
            var aiBtn = document.getElementById('suggestAIBtn');
            if (aiBtn) {
                aiBtn.disabled = false;
                var aiHint = document.querySelector('.suggest-ai-hint');
                if (aiHint) aiHint.textContent = 'Admin uniquement - consomme des tokens API';
                // Réinitialiser le résultat IA précédent
                var aiResult = document.getElementById('suggestAIResult');
                if (aiResult) aiResult.style.display = 'none';
            }
        })
        .catch(function(err) {
            console.error('Erreur suggest:', err);
            resultsDiv.innerHTML = '<div class="suggest-empty">Erreur lors de la recherche. Réessayez.</div>';
        });
}

/**
 * Demander une réponse IA (admin uniquement)
 */
function requestAIAnswer() {
    var query = document.getElementById('suggestQuery').value.trim();
    if (query.length < 2) return;

    var aiBtn = document.getElementById('suggestAIBtn');
    var aiResult = document.getElementById('suggestAIResult');
    if (!aiBtn || !aiResult) return;

    // État loading
    aiBtn.disabled = true;
    aiBtn.innerHTML = '&#8987; Analyse par Claude en cours...';
    aiResult.style.display = 'block';
    aiResult.innerHTML = '<div class="suggest-loading">Claude analyse votre question et les résultats trouvés...</div>';

    var apiUrl = window.APP_CONFIG ? window.APP_CONFIG.apiUrl : '/api/index.php?action=';

    fetch(apiUrl + 'suggest-ai', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query: query })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        aiBtn.disabled = false;
        aiBtn.innerHTML = '&#129302; Réponse IA (Claude)';

        if (!data.success) {
            aiResult.innerHTML = '<div class="suggest-ai-error">Erreur : ' + escapeHtml(data.message || 'Erreur inconnue') + '</div>';
            return;
        }

        var ai = data.data;
        var html = '<div class="suggest-ai-card">';
        html += '<div class="suggest-ai-card-header">';
        html += '<span class="suggest-ai-badge">&#129302; Réponse IA</span>';

        // Indicateur de confiance
        var confLabel = { haute: 'Confiance haute', moyenne: 'Confiance moyenne', basse: 'Confiance basse' };
        var confClass = { haute: 'conf-high', moyenne: 'conf-medium', basse: 'conf-low' };
        var conf = ai.confidence || 'moyenne';
        html += '<span class="suggest-ai-conf ' + (confClass[conf] || 'conf-medium') + '">' + (confLabel[conf] || conf) + '</span>';
        html += '</div>';

        // Réponse synthétique
        html += '<p class="suggest-ai-answer">' + escapeHtml(ai.answer || '') + '</p>';

        // Points clés
        if (ai.key_points && ai.key_points.length > 0) {
            html += '<ul class="suggest-ai-points">';
            for (var i = 0; i < ai.key_points.length; i++) {
                html += '<li>' + escapeHtml(ai.key_points[i]) + '</li>';
            }
            html += '</ul>';
        }

        // Contenus recommandés
        if (data.recommended && data.recommended.length > 0) {
            html += '<div class="suggest-ai-recommended">';
            html += '<strong>Contenus recommandés :</strong> ';
            var baseUrl = window.APP_CONFIG ? window.APP_CONFIG.baseUrl : '';
            for (var j = 0; j < data.recommended.length; j++) {
                var rec = data.recommended[j];
                if (j > 0) html += ', ';
                html += '<a href="' + baseUrl + '/article.php?slug=' + encodeURIComponent(rec.slug) + '">' + escapeHtml(rec.title) + '</a>';
            }
            html += '</div>';
        }

        html += '</div>';
        aiResult.innerHTML = html;
    })
    .catch(function(err) {
        console.error('Erreur suggest-ai:', err);
        aiBtn.disabled = false;
        aiBtn.innerHTML = '&#129302; Réponse IA (Claude)';
        aiResult.innerHTML = '<div class="suggest-ai-error">Erreur réseau. Réessayez.</div>';
    });
}

function buildFeaturedSnippet(item, query) {
    var typeLabel = item.type === 'prompt' ? '💬 Prompt' : '📄 Article';
    var baseUrl = window.APP_CONFIG ? window.APP_CONFIG.baseUrl : '';
    var link = baseUrl + '/article.php?slug=' + encodeURIComponent(item.slug);

    var html = '<div class="suggest-featured">';
    html += '<div class="suggest-featured-header">';
    html += '<span class="suggest-type-badge suggest-type-' + item.type + '">' + typeLabel + '</span>';
    html += '<h3><a href="' + link + '">' + escapeHtml(item.title) + '</a></h3>';
    html += '</div>';

    // Résumé court (extrait du summary, max 300 chars texte)
    if (item.summary) {
        var summaryText = stripHtml(item.summary);
        if (summaryText.length > 300) {
            summaryText = summaryText.substring(0, 300) + '...';
        }
        html += '<p class="suggest-featured-summary">' + escapeHtml(summaryText) + '</p>';
    }

    // Points clés (max 4)
    if (item.main_points) {
        var pointsHtml = item.main_points;
        // Extraire les <li> du HTML
        var liRegex = /<li[^>]*>(.*?)<\/li>/gi;
        var matches = [];
        var match;
        while ((match = liRegex.exec(pointsHtml)) !== null && matches.length < 4) {
            matches.push(stripHtml(match[1]));
        }
        if (matches.length > 0) {
            html += '<ul class="suggest-featured-points">';
            for (var j = 0; j < matches.length; j++) {
                html += '<li>' + escapeHtml(matches[j]) + '</li>';
            }
            html += '</ul>';
        }
    }

    // Catégories
    if (item.categories && item.categories.length > 0) {
        html += '<div class="suggest-featured-cats">';
        for (var k = 0; k < item.categories.length; k++) {
            html += '<span class="category-tag' + (item.type === 'prompt' ? ' prompt-tag' : '') + '">' + escapeHtml(item.categories[k]) + '</span>';
        }
        html += '</div>';
    }

    html += '<a href="' + link + '" class="suggest-featured-link">Voir le détail complet &rarr;</a>';
    html += '</div>';

    return html;
}

function stripHtml(html) {
    var tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// Debounce sur l'input pour suggestions en temps réel
document.addEventListener('DOMContentLoaded', function() {
    var suggestInput = document.getElementById('suggestQuery');
    if (suggestInput) {
        suggestInput.addEventListener('input', function() {
            clearTimeout(suggestTimeout);
            var query = this.value.trim();
            if (query.length < 3) {
                var resultsDiv = document.getElementById('suggestResults');
                if (resultsDiv) resultsDiv.style.display = 'none';
                return;
            }
            suggestTimeout = setTimeout(function() {
                performSuggestSearch(query);
            }, 400);
        });
    }
});

/**
 * Fonction utilitaire pour les appels API
 */
async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (data) {
        options.body = JSON.stringify(data);
    }

    const response = await fetch('/api/' + endpoint, options);
    return response.json();
}

/**
 * Supprimer un article
 */
async function deleteArticle(id, redirectUrl = '/articles.php') {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        const result = await apiCall('articles/' + id, 'DELETE');
        if (result.success) {
            window.location.href = redirectUrl;
        } else {
            alert('Erreur lors de la suppression: ' + (result.message || 'Erreur inconnue'));
        }
    }
}

/**
 * Analyser du contenu via l'API
 */
async function analyzeContent(content, sourceUrl = '') {
    return apiCall('analyze', 'POST', {
        content: content,
        source_url: sourceUrl
    });
}

/**
 * Basculer le statut favori d'un article
 */
function toggleFavorite(articleId, btn) {
    var apiUrl = window.APP_CONFIG ? window.APP_CONFIG.apiUrl : '/api/index.php?action=';
    fetch(apiUrl + 'articles/' + articleId + '/favorite', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            var isFav = data.is_favorite;
            btn.classList.toggle('active', isFav);
            btn.innerHTML = isFav ? '&#9733;' : '&#9734;';
            btn.title = isFav ? 'Retirer des favoris' : 'Ajouter aux favoris';
        }
    })
    .catch(function(err) {
        console.error('Erreur toggle favori:', err);
    });
}
