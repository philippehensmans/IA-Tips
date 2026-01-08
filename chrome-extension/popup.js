/**
 * WikiTips Chrome Extension - Popup Script
 */

document.addEventListener('DOMContentLoaded', async function() {
    const statusDiv = document.getElementById('status');
    const configSection = document.getElementById('configSection');
    const captureSection = document.getElementById('captureSection');
    const serverUrlInput = document.getElementById('serverUrl');
    const apiKeyInput = document.getElementById('apiKey');
    const sourceUrlInput = document.getElementById('sourceUrl');
    const contentTextarea = document.getElementById('content');
    const captureBtn = document.getElementById('captureSelection');
    const analyzeBtn = document.getElementById('analyzeBtn');
    const saveConfigBtn = document.getElementById('saveConfig');
    const settingsLink = document.getElementById('settingsLink');

    // Charger la configuration
    const config = await chrome.storage.local.get(['serverUrl', 'apiKey']);

    // Récupérer l'URL de la page active
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    sourceUrlInput.value = tab.url;

    // Afficher la configuration ou le formulaire de capture
    if (!config.serverUrl) {
        showConfigSection();
    } else {
        serverUrlInput.value = config.serverUrl;
        apiKeyInput.value = config.apiKey || '';
    }

    // Lien vers les paramètres
    settingsLink.addEventListener('click', function(e) {
        e.preventDefault();
        if (configSection.classList.contains('hidden')) {
            showConfigSection();
            settingsLink.textContent = 'Retour';
        } else {
            hideConfigSection();
            settingsLink.textContent = 'Configuration';
        }
    });

    // Sauvegarder la configuration
    saveConfigBtn.addEventListener('click', async function() {
        const serverUrl = serverUrlInput.value.trim();
        const apiKey = apiKeyInput.value.trim();

        if (!serverUrl) {
            showStatus('Veuillez entrer l\'URL du serveur', 'error');
            return;
        }

        await chrome.storage.local.set({ serverUrl, apiKey });
        showStatus('Configuration enregistrée', 'success');
        setTimeout(() => {
            hideConfigSection();
            settingsLink.textContent = 'Configuration';
        }, 1000);
    });

    // Capturer la sélection de la page
    captureBtn.addEventListener('click', async function() {
        try {
            const [result] = await chrome.scripting.executeScript({
                target: { tabId: tab.id },
                func: () => window.getSelection().toString()
            });

            if (result.result) {
                contentTextarea.value = result.result;
                showStatus('Texte capturé!', 'success');
                setTimeout(() => hideStatus(), 2000);
            } else {
                showStatus('Aucun texte sélectionné sur la page', 'error');
            }
        } catch (error) {
            showStatus('Erreur: ' + error.message, 'error');
        }
    });

    // Analyser et envoyer
    analyzeBtn.addEventListener('click', async function() {
        const content = contentTextarea.value.trim();
        const sourceUrl = sourceUrlInput.value;

        if (!content) {
            showStatus('Veuillez entrer du contenu à analyser', 'error');
            return;
        }

        const config = await chrome.storage.local.get(['serverUrl', 'apiKey']);
        if (!config.serverUrl) {
            showStatus('Veuillez configurer le serveur', 'error');
            showConfigSection();
            return;
        }

        analyzeBtn.disabled = true;
        analyzeBtn.textContent = 'Analyse en cours...';
        showStatus('Envoi au serveur et analyse via Claude AI...', 'loading');

        try {
            // Construire l'URL de l'API (supporte /api/analyze ou /api/index.php?action=analyze)
            let apiUrl = config.serverUrl.replace(/\/+$/, ''); // Retirer les slashes finaux
            if (apiUrl.includes('/api/index.php')) {
                apiUrl += '?action=analyze';
            } else if (apiUrl.endsWith('/api')) {
                apiUrl += '/analyze';
            } else {
                apiUrl += '/api/index.php?action=analyze';
            }

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': config.apiKey || ''
                },
                body: JSON.stringify({
                    content: content,
                    source_url: sourceUrl,
                    create_article: true
                })
            });

            const data = await response.json();

            if (data.success) {
                showStatus('Article créé avec succès! Redirection...', 'success');

                // Ouvrir l'éditeur dans un nouvel onglet
                if (data.data.article_id) {
                    // Construire l'URL de base du site
                    let baseUrl = config.serverUrl.replace(/\/+$/, '');
                    baseUrl = baseUrl.replace(/\/api(\/index\.php)?$/, '');
                    chrome.tabs.create({
                        url: baseUrl + '/edit.php?id=' + data.data.article_id
                    });
                }
            } else {
                showStatus('Erreur: ' + (data.message || 'Erreur inconnue'), 'error');
            }
        } catch (error) {
            showStatus('Erreur de connexion: ' + error.message, 'error');
        } finally {
            analyzeBtn.disabled = false;
            analyzeBtn.textContent = 'Analyser et envoyer';
        }
    });

    function showStatus(message, type) {
        statusDiv.textContent = message;
        statusDiv.className = 'status status-' + type;
        statusDiv.classList.remove('hidden');
    }

    function hideStatus() {
        statusDiv.classList.add('hidden');
    }

    function showConfigSection() {
        configSection.classList.remove('hidden');
        captureSection.classList.add('hidden');
    }

    function hideConfigSection() {
        configSection.classList.add('hidden');
        captureSection.classList.remove('hidden');
    }
});
