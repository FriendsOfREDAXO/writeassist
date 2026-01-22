<?php

declare(strict_types=1);

use FriendsOfREDAXO\WriteAssist\GeminiApi;

/**
 * API Endpoint for Gemini Text Generation
 */
class rex_api_writeassist_generate extends rex_api_function
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

        $action = rex_post('action', 'string', '') ?: rex_request('action', 'string', 'custom');
        $text = rex_post('text', 'string', '') ?: rex_request('text', 'string', '');
        $prompt = rex_post('prompt', 'string', '') ?: rex_request('prompt', 'string', '');
        $style = rex_post('style', 'string', '') ?: rex_request('style', 'string', 'professional');
        $type = rex_post('type', 'string', '') ?: rex_request('type', 'string', 'paragraph');
        $language = rex_post('language', 'string', '') ?: rex_request('language', 'string', 'php');

        // Code-Aktionen nur für Admins
        $codeActions = ['code_generate', 'code_explain', 'code_improve', 'code_ask'];
        if (in_array($action, $codeActions, true) && !$user->isAdmin()) {
            rex_response::setStatus(rex_response::HTTP_FORBIDDEN);
            rex_response::sendJson([
                'success' => false,
                'error' => 'Code generation is only available for administrators'
            ]);
            exit;
        }

        try {
            $api = new GeminiApi();
            
            if (!$api->isConfigured()) {
                rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
                rex_response::sendJson([
                    'success' => false,
                    'error' => 'Gemini API key not configured'
                ]);
                exit;
            }

            $result = match($action) {
                'rewrite' => $api->rewrite($text, $style),
                'summarize' => $api->summarize($text),
                'expand' => $api->expand($text),
                'generate' => $api->generateFromTopic($text, $type, $prompt),
                'custom' => $api->custom($prompt, $text),
                'code_generate' => $api->generateCode($text, $language),
                'code_explain' => $api->explainCode($text, $language),
                'code_improve' => $api->improveCode($text, $language),
                'code_ask' => $api->askAboutCode($prompt, $text, $language),
                default => throw new \Exception('Unknown action: ' . $action)
            };
            
            rex_response::sendJson([
                'success' => true,
                'text' => $result['text'],
                'usage' => $result['usage'] ?? null
            ]);
        } catch (\Exception $e) {
            rex_response::setStatus(rex_response::HTTP_INTERNAL_ERROR);
            rex_response::sendJson([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }
}
