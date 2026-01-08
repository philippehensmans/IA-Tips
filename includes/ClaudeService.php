<?php
/**
 * Service d'intégration avec l'API Claude
 * Analyse d'articles IA et formatage de prompts
 */
class ClaudeService {

    private $apiKey;
    private $apiUrl;
    private $model;

    // Catégories disponibles pour les articles
    private $articleCategories = [
        'llm-modeles-langage',
        'agents-ia',
        'vision-multimodal',
        'rag-embeddings',
        'fine-tuning-entrainement',
        'ethique-securite-ia',
        'outils-frameworks',
        'actualites-ia'
    ];

    // Catégories disponibles pour les prompts
    private $promptCategories = [
        'prompt-developpement',
        'prompt-redaction',
        'prompt-analyse',
        'prompt-creativite',
        'prompt-productivite',
        'prompt-education',
        'prompt-business',
        'prompt-systeme'
    ];

    public function __construct() {
        $this->apiKey = CLAUDE_API_KEY;
        $this->apiUrl = CLAUDE_API_URL;
        $this->model = CLAUDE_MODEL;
    }

    /**
     * Analyser un contenu selon son type (article ou prompt)
     */
    public function analyzeContent(string $content, string $sourceUrl = '', string $type = 'article'): array {
        if ($type === 'prompt') {
            $prompt = $this->buildPromptAnalysisPrompt($content, $sourceUrl);
        } else {
            $prompt = $this->buildArticleAnalysisPrompt($content, $sourceUrl);
        }

        $response = $this->callApi($prompt);

        if (isset($response['error'])) {
            return ['error' => $response['error']];
        }

        if ($type === 'prompt') {
            return $this->parsePromptResponse($response);
        } else {
            return $this->parseArticleResponse($response);
        }
    }

    /**
     * Construire le prompt d'analyse pour un article IA
     */
    private function buildArticleAnalysisPrompt(string $content, string $sourceUrl): string {
        $categories = implode(', ', $this->articleCategories);
        return <<<PROMPT
Tu es un expert en Intelligence Artificielle. Analyse le contenu suivant (article, tutoriel, ou actualité sur l'IA) et fournis une réponse structurée en JSON.

SOURCE: $sourceUrl

CONTENU À ANALYSER:
$content

---

Réponds UNIQUEMENT avec un objet JSON valide (sans markdown, sans ```json) contenant exactement cette structure:

{
    "title": "Titre proposé pour l'article (concis et informatif)",
    "summary": "Résumé détaillé du contenu en 3-4 paragraphes. Expliquer le sujet, les concepts clés, les implications pratiques et les perspectives. Le résumé doit permettre de comprendre l'essentiel sans lire l'article original.",
    "main_points": [
        "Point clé 1 - aspect important du contenu",
        "Point clé 2 - concept ou technique expliqué",
        "Point clé 3 - implication pratique",
        "Point clé 4 - autre élément notable",
        "Point clé 5 - conclusion ou perspective"
    ],
    "analysis": {
        "topic_type": "Type de contenu (tutoriel, actualité, recherche, opinion, comparatif...)",
        "difficulty_level": "Niveau de difficulté (débutant, intermédiaire, avancé)",
        "technologies_mentioned": ["Liste des technologies/outils mentionnés"],
        "key_takeaways": ["Ce qu'il faut retenir 1", "Ce qu'il faut retenir 2", "Ce qu'il faut retenir 3"],
        "practical_applications": ["Application pratique 1", "Application pratique 2"]
    },
    "suggested_categories": ["llm-modeles-langage", "outils-frameworks"]
}

Les catégories disponibles sont: $categories

Assure-toi que le JSON est valide et complet.
PROMPT;
    }

    /**
     * Construire le prompt d'analyse pour un prompt IA
     */
    private function buildPromptAnalysisPrompt(string $content, string $sourceUrl): string {
        $categories = implode(', ', $this->promptCategories);
        return <<<PROMPT
Tu es un expert en prompt engineering. Analyse le prompt suivant et reformate-le pour qu'il soit directement utilisable.

SOURCE: $sourceUrl

PROMPT À ANALYSER:
$content

---

Réponds UNIQUEMENT avec un objet JSON valide (sans markdown, sans ```json) contenant exactement cette structure:

{
    "title": "Nom descriptif du prompt (ex: 'Générateur de code Python avec explications')",
    "summary": "Description courte du prompt: ce qu'il fait, quand l'utiliser, et les résultats attendus (2-3 phrases).",
    "main_points": [
        "Cas d'usage 1",
        "Cas d'usage 2",
        "Cas d'usage 3"
    ],
    "formatted_prompt": "Le prompt reformaté, nettoyé et optimisé. Il doit être directement copiable et utilisable. Utilise des placeholders clairs entre crochets [comme ceci] pour les variables. Garde la structure originale si elle est bonne, sinon améliore-la.",
    "analysis": {
        "prompt_type": "Type de prompt (system prompt, user prompt, few-shot, chain of thought...)",
        "complexity": "Complexité (simple, modéré, complexe)",
        "variables": ["Liste des variables/placeholders du prompt"],
        "best_practices": ["Bonne pratique utilisée 1", "Bonne pratique utilisée 2"],
        "suggestions": ["Suggestion d'amélioration 1", "Suggestion d'amélioration 2"]
    },
    "suggested_categories": ["prompt-developpement"]
}

Les catégories disponibles sont: $categories

IMPORTANT: Le champ "formatted_prompt" doit contenir le prompt prêt à l'emploi, bien structuré avec des sauts de ligne appropriés (\\n pour les retours à la ligne).

Assure-toi que le JSON est valide et complet.
PROMPT;
    }

    /**
     * Appeler l'API Claude
     */
    private function callApi(string $prompt): array {
        $data = [
            'model' => $this->model,
            'max_tokens' => 4096,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_TIMEOUT => 120
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => 'Erreur cURL: ' . $error];
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            return ['error' => 'Erreur API (' . $httpCode . '): ' . ($errorData['error']['message'] ?? $response)];
        }

        $result = json_decode($response, true);

        if (!isset($result['content'][0]['text'])) {
            return ['error' => 'Réponse API invalide'];
        }

        return ['text' => $result['content'][0]['text']];
    }

    /**
     * Parser la réponse pour un article IA
     */
    private function parseArticleResponse(array $response): array {
        if (!isset($response['text'])) {
            return ['error' => 'Réponse vide'];
        }

        $text = trim($response['text']);

        // Nettoyer le JSON si nécessaire
        $text = preg_replace('/^```json\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Erreur de parsing JSON: ' . json_last_error_msg(), 'raw' => $text];
        }

        // Formater les points principaux en HTML
        $mainPointsHtml = '<ul>';
        foreach ($data['main_points'] ?? [] as $point) {
            $mainPointsHtml .= '<li>' . htmlspecialchars($point) . '</li>';
        }
        $mainPointsHtml .= '</ul>';

        // Formater l'analyse en HTML
        $analysisHtml = $this->formatArticleAnalysis($data['analysis'] ?? []);

        return [
            'type' => 'article',
            'title' => $data['title'] ?? 'Sans titre',
            'summary' => $data['summary'] ?? '',
            'main_points' => $mainPointsHtml,
            'main_points_raw' => $data['main_points'] ?? [],
            'analysis' => $analysisHtml,
            'analysis_raw' => $data['analysis'] ?? [],
            'suggested_categories' => $data['suggested_categories'] ?? []
        ];
    }

    /**
     * Parser la réponse pour un prompt
     */
    private function parsePromptResponse(array $response): array {
        if (!isset($response['text'])) {
            return ['error' => 'Réponse vide'];
        }

        $text = trim($response['text']);

        // Nettoyer le JSON si nécessaire
        $text = preg_replace('/^```json\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'Erreur de parsing JSON: ' . json_last_error_msg(), 'raw' => $text];
        }

        // Formater les cas d'usage en HTML
        $mainPointsHtml = '<ul>';
        foreach ($data['main_points'] ?? [] as $point) {
            $mainPointsHtml .= '<li>' . htmlspecialchars($point) . '</li>';
        }
        $mainPointsHtml .= '</ul>';

        // Formater l'analyse du prompt en HTML
        $analysisHtml = $this->formatPromptAnalysis($data['analysis'] ?? []);

        return [
            'type' => 'prompt',
            'title' => $data['title'] ?? 'Sans titre',
            'summary' => $data['summary'] ?? '',
            'main_points' => $mainPointsHtml,
            'main_points_raw' => $data['main_points'] ?? [],
            'formatted_prompt' => $data['formatted_prompt'] ?? '',
            'analysis' => $analysisHtml,
            'analysis_raw' => $data['analysis'] ?? [],
            'suggested_categories' => $data['suggested_categories'] ?? []
        ];
    }

    /**
     * Formater l'analyse d'un article IA en HTML
     */
    private function formatArticleAnalysis(array $analysis): string {
        $html = '<div class="article-analysis">';

        // Type et niveau
        if (!empty($analysis['topic_type']) || !empty($analysis['difficulty_level'])) {
            $html .= '<div class="analysis-meta">';
            if (!empty($analysis['topic_type'])) {
                $html .= '<span class="badge badge-info">' . htmlspecialchars($analysis['topic_type']) . '</span> ';
            }
            if (!empty($analysis['difficulty_level'])) {
                $html .= '<span class="badge badge-level">' . htmlspecialchars($analysis['difficulty_level']) . '</span>';
            }
            $html .= '</div>';
        }

        // Technologies mentionnées
        if (!empty($analysis['technologies_mentioned'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>Technologies mentionnées</h4>';
            $html .= '<div class="tech-tags">';
            foreach ($analysis['technologies_mentioned'] as $tech) {
                $html .= '<span class="tech-tag">' . htmlspecialchars($tech) . '</span>';
            }
            $html .= '</div></div>';
        }

        // Points clés à retenir
        if (!empty($analysis['key_takeaways'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>À retenir</h4>';
            $html .= '<ul>';
            foreach ($analysis['key_takeaways'] as $takeaway) {
                $html .= '<li>' . htmlspecialchars($takeaway) . '</li>';
            }
            $html .= '</ul></div>';
        }

        // Applications pratiques
        if (!empty($analysis['practical_applications'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>Applications pratiques</h4>';
            $html .= '<ul>';
            foreach ($analysis['practical_applications'] as $app) {
                $html .= '<li>' . htmlspecialchars($app) . '</li>';
            }
            $html .= '</ul></div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Formater l'analyse d'un prompt en HTML
     */
    private function formatPromptAnalysis(array $analysis): string {
        $html = '<div class="prompt-analysis">';

        // Type et complexité
        if (!empty($analysis['prompt_type']) || !empty($analysis['complexity'])) {
            $html .= '<div class="analysis-meta">';
            if (!empty($analysis['prompt_type'])) {
                $html .= '<span class="badge badge-type">' . htmlspecialchars($analysis['prompt_type']) . '</span> ';
            }
            if (!empty($analysis['complexity'])) {
                $html .= '<span class="badge badge-complexity">' . htmlspecialchars($analysis['complexity']) . '</span>';
            }
            $html .= '</div>';
        }

        // Variables du prompt
        if (!empty($analysis['variables'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>Variables</h4>';
            $html .= '<div class="variable-tags">';
            foreach ($analysis['variables'] as $var) {
                $html .= '<code class="variable-tag">' . htmlspecialchars($var) . '</code>';
            }
            $html .= '</div></div>';
        }

        // Bonnes pratiques
        if (!empty($analysis['best_practices'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>Bonnes pratiques utilisées</h4>';
            $html .= '<ul class="best-practices">';
            foreach ($analysis['best_practices'] as $practice) {
                $html .= '<li>' . htmlspecialchars($practice) . '</li>';
            }
            $html .= '</ul></div>';
        }

        // Suggestions d'amélioration
        if (!empty($analysis['suggestions'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>Suggestions d\'amélioration</h4>';
            $html .= '<ul class="suggestions">';
            foreach ($analysis['suggestions'] as $suggestion) {
                $html .= '<li>' . htmlspecialchars($suggestion) . '</li>';
            }
            $html .= '</ul></div>';
        }

        $html .= '</div>';

        return $html;
    }
}
