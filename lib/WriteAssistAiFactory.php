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
        'gemini' => 'Google Gemini',
        'openwebui' => 'OpenWebUI / OpenAI Compatible'
    ];
    
    public static function factory(): WriteAssistAiProviderInterface
    {
        $providerKey = rex_config::get('writeassist', 'ai_provider', 'gemini');
        
        return match($providerKey) {
            'openwebui' => new WriteAssistAiProviderOpenAiCompatible(
                (string) rex_config::get('writeassist', 'openwebui_api_key', ''),
                (string) rex_config::get('writeassist', 'openwebui_base_url', 'http://localhost:3000'),
                (string) rex_config::get('writeassist', 'openwebui_model', 'llava')
            ),
            default => new WriteAssistAiProviderGemini(
                (string) rex_config::get('writeassist', 'gemini_api_key', ''),
                (string) rex_config::get('writeassist', 'gemini_model', 'gemini-2.5-flash')
            ),
        };
    }
}
