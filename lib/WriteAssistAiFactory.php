<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\WriteAssist;

use FriendsOfREDAXO\WriteAssist\AiProvider\WriteAssistAiProviderInterface;
use FriendsOfREDAXO\WriteAssist\AiProvider\WriteAssistAiProviderGemini;
use FriendsOfREDAXO\WriteAssist\AiProvider\WriteAssistAiProviderOpenAiCompatible;
use rex_config;

class WriteAssistAiFactory
{
    public const PROVIDERS = [
        'disabled'  => 'Deaktiviert',
        'gemini'    => 'Google Gemini',
        'openai'    => 'OpenAI (ChatGPT)',
        'openwebui' => 'OpenWebUI / OpenAI Compatible'
    ];

    public const OPENAI_BASE_URL = 'https://api.openai.com/v1';
    
    public static function factory(): WriteAssistAiProviderInterface
    {
        $providerKey = rex_config::get('writeassist', 'ai_provider', 'gemini');
        
        return match($providerKey) {
            'openai' => new WriteAssistAiProviderOpenAiCompatible(
                $openAiKey = trim((string) rex_config::get('writeassist', 'openai_api_key', '')),
                $openAiKey !== '' ? self::OPENAI_BASE_URL : '',
                (string) rex_config::get('writeassist', 'openai_model', 'gpt-4o-mini')
            ),
            'openwebui' => new WriteAssistAiProviderOpenAiCompatible(
                (string) rex_config::get('writeassist', 'openwebui_api_key', ''),
                (string) rex_config::get('writeassist', 'openwebui_base_url', ''),
                (string) rex_config::get('writeassist', 'openwebui_model', '')
            ),
            'disabled' => new WriteAssistAiProviderGemini('', ''),
            default => new WriteAssistAiProviderGemini(
                (string) rex_config::get('writeassist', 'gemini_api_key', ''),
                (string) rex_config::get('writeassist', 'gemini_model', 'gemini-2.5-flash')
            ),
        };
    }
}
