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
});

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
