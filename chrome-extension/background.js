/**
 * WikiTips Chrome Extension - Background Service Worker
 */

// Créer le menu contextuel au démarrage
chrome.runtime.onInstalled.addListener(() => {
    chrome.contextMenus.create({
        id: 'wikitips-analyze',
        title: 'Analyser avec WikiTips',
        contexts: ['selection']
    });
});

// Gérer les clics sur le menu contextuel
chrome.contextMenus.onClicked.addListener(async (info, tab) => {
    if (info.menuItemId === 'wikitips-analyze') {
        const selectedText = info.selectionText;
        const sourceUrl = tab.url;

        // Récupérer la configuration
        const config = await chrome.storage.local.get(['serverUrl', 'apiKey']);

        if (!config.serverUrl) {
            // Ouvrir le popup pour configurer
            chrome.action.openPopup();
            return;
        }

        try {
            const response = await fetch(config.serverUrl + '/api/analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': config.apiKey || ''
                },
                body: JSON.stringify({
                    content: selectedText,
                    source_url: sourceUrl,
                    create_article: true
                })
            });

            const data = await response.json();

            if (data.success && data.data.article_id) {
                // Ouvrir l'éditeur dans un nouvel onglet
                chrome.tabs.create({
                    url: config.serverUrl + '/edit.php?id=' + data.data.article_id
                });
            } else {
                // Afficher une notification d'erreur
                chrome.notifications.create({
                    type: 'basic',
                    iconUrl: 'icons/icon48.png',
                    title: 'WikiTips - Erreur',
                    message: data.message || 'Erreur lors de l\'analyse'
                });
            }
        } catch (error) {
            chrome.notifications.create({
                type: 'basic',
                iconUrl: 'icons/icon48.png',
                title: 'WikiTips - Erreur',
                message: 'Erreur de connexion au serveur'
            });
        }
    }
});
