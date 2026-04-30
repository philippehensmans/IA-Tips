<?php
/**
 * Service d'intégration Threads (Meta Graph API)
 * Permet de publier automatiquement sur Threads
 */

class ThreadsService
{
    private string $apiUrl = 'https://graph.threads.net/v1.0';
    private string $userId;
    private string $accessToken;

    public function __construct()
    {
        $this->userId = defined('THREADS_USER_ID') ? THREADS_USER_ID : '';
        $this->accessToken = defined('THREADS_ACCESS_TOKEN') ? THREADS_ACCESS_TOKEN : '';
    }

    public function isConfigured(): bool
    {
        return !empty($this->userId) && !empty($this->accessToken);
    }

    /**
     * Publie un post texte sur Threads (2 étapes : container puis publish)
     */
    public function createPost(string $text): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Threads non configuré. Vérifiez THREADS_USER_ID et THREADS_ACCESS_TOKEN.'];
        }

        // Étape 1 : créer le container
        $container = $this->request("/{$this->userId}/threads", [
            'media_type'   => 'TEXT',
            'text'         => $text,
            'access_token' => $this->accessToken,
        ]);

        if (empty($container['id'])) {
            return [
                'success' => false,
                'error'   => $container['error']['message'] ?? 'Impossible de créer le container Threads.',
            ];
        }

        // Étape 2 : publier le container
        $publish = $this->request("/{$this->userId}/threads_publish", [
            'creation_id'  => $container['id'],
            'access_token' => $this->accessToken,
        ]);

        if (empty($publish['id'])) {
            return [
                'success' => false,
                'error'   => $publish['error']['message'] ?? 'Impossible de publier le post Threads.',
            ];
        }

        $postUrl = "https://www.threads.net/@_/post/{$publish['id']}";
        return ['success' => true, 'id' => $publish['id'], 'url' => $postUrl];
    }

    /**
     * Génère le texte du post à partir d'un article (max 500 caractères)
     */
    public function formatArticlePost(array $article, string $articleUrl): string
    {
        $summary = strip_tags($article['summary'] ?? '');
        $summary = html_entity_decode($summary, ENT_QUOTES, 'UTF-8');
        $summary = preg_replace('/\s+/', ' ', trim($summary));

        $maxLength = 400; // réserve de la place pour l'URL et hashtags

        $accroche = $summary;
        if (mb_strlen($summary) > $maxLength) {
            $cutPoint = mb_strpos($summary, '. ', 0);
            if ($cutPoint !== false && $cutPoint < $maxLength && $cutPoint > 50) {
                $accroche = mb_substr($summary, 0, $cutPoint + 1);
            } else {
                $accroche = mb_substr($summary, 0, $maxLength);
                $lastSpace = mb_strrpos($accroche, ' ');
                if ($lastSpace > 100) {
                    $accroche = mb_substr($accroche, 0, $lastSpace) . '...';
                } else {
                    $accroche .= '...';
                }
            }
        }

        return "💡 {$accroche}\n\n{$articleUrl}\n\n#IA #IATips";
    }

    private function request(string $path, array $params): array
    {
        $url = $this->apiUrl . $path;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => ['message' => "Erreur cURL : {$error}"]];
        }

        return json_decode($response, true) ?? [];
    }
}
