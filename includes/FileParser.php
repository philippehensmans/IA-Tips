<?php
/**
 * Parser de fichiers (MD, PDF, TXT)
 */
class FileParser {

    /**
     * Extensions supportees
     */
    private static $supportedExtensions = ['md', 'txt', 'pdf'];

    /**
     * Verifier si l'extension est supportee
     */
    public static function isSupported(string $filename): bool {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, self::$supportedExtensions);
    }

    /**
     * Obtenir les extensions supportees
     */
    public static function getSupportedExtensions(): array {
        return self::$supportedExtensions;
    }

    /**
     * Parser un fichier uploade
     */
    public static function parse(array $uploadedFile): array {
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            return ['error' => self::getUploadError($uploadedFile['error'])];
        }

        $filename = $uploadedFile['name'];
        $tmpPath = $uploadedFile['tmp_name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!self::isSupported($filename)) {
            return ['error' => 'Extension non supportee. Extensions acceptees: ' . implode(', ', self::$supportedExtensions)];
        }

        switch ($ext) {
            case 'md':
            case 'txt':
                return self::parseTextFile($tmpPath);
            case 'pdf':
                return self::parsePdfFile($tmpPath);
            default:
                return ['error' => 'Type de fichier non gere'];
        }
    }

    /**
     * Parser un fichier texte (MD, TXT)
     */
    private static function parseTextFile(string $path): array {
        $content = file_get_contents($path);

        if ($content === false) {
            return ['error' => 'Impossible de lire le fichier'];
        }

        // Detecter et convertir l'encodage si necessaire
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return ['content' => trim($content)];
    }

    /**
     * Parser un fichier PDF
     */
    private static function parsePdfFile(string $path): array {
        // Methode 1: Essayer pdftotext (poppler-utils)
        $content = self::extractPdfWithPdftotext($path);
        if ($content !== null) {
            return ['content' => $content];
        }

        // Methode 2: Extraction basique PHP
        $content = self::extractPdfBasic($path);
        if ($content !== null && strlen(trim($content)) > 50) {
            return ['content' => $content];
        }

        return ['error' => 'Impossible d\'extraire le texte du PDF. Le fichier est peut-etre protege ou contient uniquement des images.'];
    }

    /**
     * Extraire le texte d'un PDF avec pdftotext
     */
    private static function extractPdfWithPdftotext(string $path): ?string {
        // Verifier si pdftotext est disponible
        $check = shell_exec('which pdftotext 2>/dev/null');
        if (empty($check)) {
            return null;
        }

        $output = [];
        $returnCode = 0;
        $escapedPath = escapeshellarg($path);

        exec("pdftotext -layout $escapedPath - 2>/dev/null", $output, $returnCode);

        if ($returnCode === 0 && !empty($output)) {
            return trim(implode("\n", $output));
        }

        return null;
    }

    /**
     * Extraction basique du texte d'un PDF (PHP pur)
     */
    private static function extractPdfBasic(string $path): ?string {
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $text = '';

        // Chercher les objets stream dans le PDF
        $pattern = '/stream\s*\n?(.*?)\n?endstream/s';
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $stream) {
                // Essayer de decompresser (zlib)
                $decompressed = @gzuncompress($stream);
                if ($decompressed === false) {
                    $decompressed = @gzinflate($stream);
                }
                if ($decompressed === false) {
                    $decompressed = $stream;
                }

                // Extraire le texte des operateurs PDF
                $extracted = self::extractTextFromPdfStream($decompressed);
                if (!empty($extracted)) {
                    $text .= $extracted . "\n";
                }
            }
        }

        // Nettoyer le texte
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return !empty($text) ? $text : null;
    }

    /**
     * Extraire le texte d'un stream PDF
     */
    private static function extractTextFromPdfStream(string $stream): string {
        $text = '';

        // Pattern pour les textes entre parentheses (Tj, TJ, ')
        if (preg_match_all('/\(([^)]*)\)\s*Tj/s', $stream, $matches)) {
            $text .= implode(' ', $matches[1]);
        }

        // Pattern pour les tableaux de texte TJ
        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $stream, $matches)) {
            foreach ($matches[1] as $tjContent) {
                if (preg_match_all('/\(([^)]*)\)/', $tjContent, $subMatches)) {
                    $text .= implode('', $subMatches[1]) . ' ';
                }
            }
        }

        // Decoder les caracteres echappes
        $text = str_replace(['\\(', '\\)', '\\n', '\\r', '\\t'], ['(', ')', "\n", "\r", "\t"], $text);

        return $text;
    }

    /**
     * Obtenir le message d'erreur d'upload
     */
    private static function getUploadError(int $errorCode): string {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier depasse la taille maximum autorisee par le serveur',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier depasse la taille maximum autorisee par le formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a ete que partiellement telecharge',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a ete telecharge',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Echec de l\'ecriture du fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrete le telechargement',
        ];

        return $errors[$errorCode] ?? 'Erreur inconnue lors du telechargement';
    }
}
