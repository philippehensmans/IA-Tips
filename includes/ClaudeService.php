<?php
/**
 * Service d'intégration avec l'API Claude
 * Analyse du contenu sous l'angle des droits humains
 */
class ClaudeService {

    private $apiKey;
    private $apiUrl;
    private $model;

    public function __construct() {
        $this->apiKey = CLAUDE_API_KEY;
        $this->apiUrl = CLAUDE_API_URL;
        $this->model = CLAUDE_MODEL;
    }

    /**
     * Analyser un contenu web et générer un article structuré
     */
    public function analyzeContent(string $content, string $sourceUrl = ''): array {
        $prompt = $this->buildAnalysisPrompt($content, $sourceUrl);

        $response = $this->callApi($prompt);

        if (isset($response['error'])) {
            return ['error' => $response['error']];
        }

        return $this->parseResponse($response);
    }

    /**
     * Construire le prompt d'analyse
     */
    private function buildAnalysisPrompt(string $content, string $sourceUrl): string {
        return <<<PROMPT
Tu es un expert en droits humains, droits civils et politiques, droits économiques, sociaux et culturels, ainsi qu'en droit international humanitaire. Analyse le contenu suivant et fournis une réponse structurée en JSON.

SOURCE: $sourceUrl

CONTENU À ANALYSER:
$content

---

Réponds UNIQUEMENT avec un objet JSON valide (sans markdown, sans ```json) contenant exactement cette structure:

{
    "title": "Titre proposé pour l'article (concis et informatif)",
    "summary": "Résumé détaillé du contenu en 4-5 paragraphes. Inclure le contexte, les faits principaux, les enjeux, les acteurs impliqués et les perspectives. Le résumé doit être suffisamment complet pour qu'un lecteur comprenne l'essentiel sans lire l'article original.",
    "main_points": [
        "Point principal 1",
        "Point principal 2",
        "Point principal 3",
        "Point principal 4",
        "Point principal 5"
    ],
    "human_rights_analysis": {
        "civil_political_rights": {
            "relevant": true/false,
            "points": ["Point d'attention 1", "Point d'attention 2"],
            "concerns": ["Préoccupation éventuelle"]
        },
        "economic_social_cultural_rights": {
            "relevant": true/false,
            "points": ["Point d'attention 1"],
            "concerns": ["Préoccupation éventuelle"]
        },
        "international_humanitarian_law": {
            "relevant": true/false,
            "points": ["Point d'attention 1"],
            "concerns": ["Préoccupation éventuelle"]
        },
        "overall_assessment": "Évaluation globale sous l'angle des droits humains (2-3 phrases)",
        "recommendations": ["Recommandation 1", "Recommandation 2"]
    },
    "suggested_categories": ["droits-civils-politiques", "non-discrimination"]
}

Les catégories disponibles sont: droits-civils-politiques, droits-economiques-sociaux, droits-culturels, droit-humanitaire, droits-refugies, droits-enfants, droits-femmes, non-discrimination

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
     * Parser la réponse de Claude
     */
    private function parseResponse(array $response): array {
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

        // Formater l'analyse des droits humains en HTML
        $analysisHtml = $this->formatHumanRightsAnalysis($data['human_rights_analysis'] ?? []);

        return [
            'title' => $data['title'] ?? 'Sans titre',
            'summary' => $data['summary'] ?? '',
            'main_points' => $mainPointsHtml,
            'main_points_raw' => $data['main_points'] ?? [],
            'human_rights_analysis' => $analysisHtml,
            'human_rights_analysis_raw' => $data['human_rights_analysis'] ?? [],
            'suggested_categories' => $data['suggested_categories'] ?? []
        ];
    }

    /**
     * Formater l'analyse des droits humains en HTML
     */
    private function formatHumanRightsAnalysis(array $analysis): string {
        $html = '<div class="human-rights-analysis">';

        // Droits civils et politiques
        if (!empty($analysis['civil_political_rights']['relevant'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>Droits civils et politiques</h4>';
            $html .= $this->formatAnalysisPoints($analysis['civil_political_rights']);
            $html .= '</div>';
        }

        // Droits économiques, sociaux et culturels
        if (!empty($analysis['economic_social_cultural_rights']['relevant'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>Droits économiques, sociaux et culturels</h4>';
            $html .= $this->formatAnalysisPoints($analysis['economic_social_cultural_rights']);
            $html .= '</div>';
        }

        // Droit international humanitaire
        if (!empty($analysis['international_humanitarian_law']['relevant'])) {
            $html .= '<div class="analysis-section">';
            $html .= '<h4>Droit international humanitaire</h4>';
            $html .= $this->formatAnalysisPoints($analysis['international_humanitarian_law']);
            $html .= '</div>';
        }

        // Évaluation globale
        if (!empty($analysis['overall_assessment'])) {
            $html .= '<div class="analysis-section overall">';
            $html .= '<h4>Évaluation globale</h4>';
            $html .= '<p>' . htmlspecialchars($analysis['overall_assessment']) . '</p>';
            $html .= '</div>';
        }

        // Recommandations
        if (!empty($analysis['recommendations'])) {
            $html .= '<div class="analysis-section recommendations">';
            $html .= '<h4>Recommandations</h4>';
            $html .= '<ul>';
            foreach ($analysis['recommendations'] as $rec) {
                $html .= '<li>' . htmlspecialchars($rec) . '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Formater les points d'une section d'analyse
     */
    private function formatAnalysisPoints(array $section): string {
        $html = '';

        if (!empty($section['points'])) {
            $html .= '<div class="points"><strong>Points d\'attention:</strong><ul>';
            foreach ($section['points'] as $point) {
                $html .= '<li>' . htmlspecialchars($point) . '</li>';
            }
            $html .= '</ul></div>';
        }

        if (!empty($section['concerns'])) {
            $html .= '<div class="concerns"><strong>Préoccupations:</strong><ul>';
            foreach ($section['concerns'] as $concern) {
                $html .= '<li class="concern">' . htmlspecialchars($concern) . '</li>';
            }
            $html .= '</ul></div>';
        }

        return $html;
    }
}
