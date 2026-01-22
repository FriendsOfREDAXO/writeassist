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
            // We use the factory which pulls from config
            // Note: In a real test scenario, we might want to test the draft config values
            // but rex_config is already saved when 'Apply' is clicked.
            // If we wanted to test before save, we'd need to pass params here.
            
            $provider = WriteAssistAiFactory::factory();
            
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
