/**
 * WikiTips Chrome Extension - Content Script
 * Ce script s'exécute sur chaque page pour permettre la capture de texte
 */

// Écouter les messages du popup
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'getSelection') {
        const selection = window.getSelection().toString();
        sendResponse({ selection: selection });
    }
    return true;
});
