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

    // Auto-resize des textareas
    document.querySelectorAll('textarea').forEach(function(textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });

    // Initialiser les barres d'outils de formatage
    initFormattingToolbars();
});

/**
 * Initialise les barres d'outils de formatage pour les textareas avec data-formatting="true"
 */
function initFormattingToolbars() {
    var textareas = document.querySelectorAll('textarea[data-formatting="true"]');
    console.log('Formatting toolbars: found ' + textareas.length + ' textarea(s)');
    textareas.forEach(function(textarea) {
        createFormattingToolbar(textarea);
    });
}

/**
 * Crée une barre d'outils de formatage pour un textarea
 */
function createFormattingToolbar(textarea) {
    // Créer la barre d'outils
    var toolbar = document.createElement('div');
    toolbar.className = 'formatting-toolbar';

    // Boutons de formatage texte
    var textButtons = [
        { tag: 'strong', label: 'G', title: 'Gras', className: 'btn-bold' },
        { tag: 'em', label: 'I', title: 'Italique', className: 'btn-italic' },
        { tag: 'u', label: 'S', title: 'Souligné', className: 'btn-underline' }
    ];

    textButtons.forEach(function(btn) {
        var button = document.createElement('button');
        button.type = 'button';
        button.innerHTML = btn.label;
        button.title = btn.title + ' (<' + btn.tag + '>)';
        button.className = btn.className || '';
        button.addEventListener('click', function(e) {
            e.preventDefault();
            wrapSelection(textarea, '<' + btn.tag + '>', '</' + btn.tag + '>');
        });
        toolbar.appendChild(button);
    });

    // Séparateur
    var separator1 = document.createElement('span');
    separator1.className = 'separator';
    toolbar.appendChild(separator1);

    // Bouton liste à puces
    var btnUl = document.createElement('button');
    btnUl.type = 'button';
    btnUl.innerHTML = '• —';
    btnUl.title = 'Liste à puces';
    btnUl.className = 'btn-list';
    btnUl.addEventListener('click', function(e) {
        e.preventDefault();
        insertList(textarea, 'ul');
    });
    toolbar.appendChild(btnUl);

    // Bouton liste numérotée
    var btnOl = document.createElement('button');
    btnOl.type = 'button';
    btnOl.innerHTML = '1. —';
    btnOl.title = 'Liste numérotée';
    btnOl.className = 'btn-list-ol';
    btnOl.addEventListener('click', function(e) {
        e.preventDefault();
        insertList(textarea, 'ol');
    });
    toolbar.appendChild(btnOl);

    // Séparateur
    var separator2 = document.createElement('span');
    separator2.className = 'separator';
    toolbar.appendChild(separator2);

    // Bouton tableau
    var btnTable = document.createElement('button');
    btnTable.type = 'button';
    btnTable.innerHTML = '⊞';
    btnTable.title = 'Insérer un tableau';
    btnTable.className = 'btn-table';
    btnTable.addEventListener('click', function(e) {
        e.preventDefault();
        insertTable(textarea);
    });
    toolbar.appendChild(btnTable);

    // Aide
    var help = document.createElement('span');
    help.className = 'help-icon';
    help.innerHTML = '?';
    help.title = 'Sélectionnez du texte puis cliquez sur un bouton pour le formater';
    toolbar.appendChild(help);

    // Insérer la barre avant le textarea
    textarea.parentNode.insertBefore(toolbar, textarea);
    textarea.classList.add('textarea-with-toolbar');
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
