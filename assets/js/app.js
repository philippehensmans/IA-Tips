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
    document.querySelectorAll('textarea[data-formatting="true"]').forEach(function(textarea) {
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

    // Boutons de formatage
    var buttons = [
        { tag: 'strong', label: 'G', title: 'Gras', className: 'btn-bold' },
        { tag: 'em', label: 'I', title: 'Italique', className: 'btn-italic' },
        { tag: 'u', label: 'S', title: 'Souligné', className: 'btn-underline' }
    ];

    buttons.forEach(function(btn) {
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
