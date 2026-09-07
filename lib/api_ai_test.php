<?php

declare(strict_types=1);

use FriendsOfREDAXO\WriteAssist\AiProvider\WriteAssistAiProviderInterface;
use FriendsOfREDAXO\WriteAssist\WriteAssistAiFactory;

/**
 * API Endpoint for AI Connection Test
 */
class rex_api_writeassist_ai_test extends rex_api_function
{
    protected $published = false; 

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        $user = rex::getUser();
        if (!$user || !$user->isAdmin()) {
            rex_response::setStatus(rex_response::HTTP_UNAUTHORIZED);
            rex_response::sendJson(['error' => 'Unauthorized']);
            exit;
        }

        try {
            // Testet bewusst den aktuellen (ggf. noch ungespeicherten) Formularstand statt
            // nur der gespeicherten Config - der Test-Button sendet dafuer die aktuellen
            // Feldwerte mit. Nicht mitgesendete Felder fallen auf die gespeicherte Config
            // zurueck (siehe ai_chat's ChatTest.php fuer dasselbe Muster).
            $overrides = [];
            foreach (['ai_provider', 'gemini_api_key', 'gemini_model', 'openai_api_key', 'openai_model', 'openwebui_api_key', 'openwebui_base_url', 'openwebui_model', 'ai_platform_text_profile_id'] as $key) {
                $value = rex_request::post($key, 'string', null);
                if (null !== $value) {
                    $overrides[$key] = $value;
                }
            }

            $provider = WriteAssistAiFactory::factory($overrides);

            if (!$provider->isConfigured()) {
                rex_response::sendJson([
                    'success' => false, 
                    'message' => 'Provider ist nicht vollständig konfiguriert.'
                ]);
                exit;
            }

            $result = $provider->testConnection();
            
            rex_response::sendJson($result);

        } catch (\Throwable $e) {
            rex_response::sendJson([
                'success' => false,
                'message' => 'Fehler: ' . $e->getMessage()
            ]);
        }

        exit;
    }
}
