<?php

declare(strict_types=1);

use FriendsOfREDAXO\WriteAssist\DeeplApi;
use FriendsOfREDAXO\WriteAssist\WriteAssistAiFactory;

/**
 * API Endpoint for DeepL Translation
 */
class rex_api_writeassist_translate extends rex_api_function
{
    protected $published = true;

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        $user = rex::getUser();
        if (!$user) {
            rex_response::setStatus(rex_response::HTTP_UNAUTHORIZED);
            rex_response::sendJson(['error' => 'Unauthorized']);
            exit;
        }

        // Try POST first, fallback to REQUEST
        $text = rex_post('text', 'string', '') ?: rex_request('text', 'string', '');
        $targetLang = rex_post('target_lang', 'string', '') ?: rex_request('target_lang', 'string', 'EN');
        $sourceLang = rex_post('source_lang', 'string', '') ?: rex_request('source_lang', 'string', '');
        $preserveFormatting = rex_post('preserve_formatting', 'bool', false) || rex_request('preserve_formatting', 'bool', false);

        if ($text === '') {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['error' => 'No text provided']);
            exit;
        }

        try {
            $providerType = rex_addon::get('writeassist')->getConfig('translation_provider', 'deepl');
            
            if ($providerType === 'ai') {
                $ai = WriteAssistAiFactory::factory();
                if (!$ai->isConfigured()) {
                    throw new Exception('Der ausgewählte KI-Provider ist nicht korrekt konfiguriert.');
                }
                
                $formatNote = $preserveFormatting ? 'Erhalte jegliche HTML-Formatierung exakt bei (Texte innerhalb der Tags übersetzen, Tags beibehalten).' : 'Antworte in einfachem Text ohne Formatierung.';
                $context = \FriendsOfREDAXO\WriteAssist\AutoTranslateService::getTranslationContext();

                $prompt = "Übersetze den folgenden Text in die Sprache/den Sprachcode: " . $targetLang . ".

";

                if ('' !== $context) {
                    $prompt .= "Kontext zur Website: " . $context . "

";
                }

                $prompt .= $formatNote . "
"
                        . "Antworte AUSSCHLIESSLICH mit dem übersetzten Text. Keine Einleitung, keine Erklärungen, füge auch keine Markdown-Codeblöcke hinzu, falls der Originaltext diese nicht enthielt.

"
                        . "Zutübersetzender Text:
" . $text;
                        
                $aiResponse = $ai->generate($prompt);
                
                // Clean up possible markdown code blocks if the AI still added them around the entire response
                $translation = trim($aiResponse['text']);
                if (str_starts_with($translation, '```html')) {
                    $translation = preg_replace('/^```html\s*|\s*```$/i', '', $translation) ?? $translation;
                } elseif (str_starts_with($translation, '```')) {
                    $translation = preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $translation) ?? $translation;
                }
                
                rex_response::sendJson([
                    'success' => true,
                    'translation' => trim($translation),
                    'detected_source_language' => null
                ]);
            } else {
                $api = new DeeplApi();
                $result = $api->translate($text, $targetLang, $sourceLang !== '' ? $sourceLang : null, $preserveFormatting);
                
                rex_response::sendJson([
                    'success' => true,
                    'translation' => $result['text'],
                    'detected_source_language' => $result['detected_source_language']
                ]);
            }
        } catch (Exception $e) {
            rex_response::setStatus(rex_response::HTTP_INTERNAL_ERROR);
            rex_response::sendJson([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }
}
