<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\WriteAssist;

use FriendsOfREDAXO\WriteAssist\AiProvider\WriteAssistAiProviderInterface;
use FriendsOfREDAXO\WriteAssist\AiProvider\WriteAssistAiProviderAiPlatform;
use FriendsOfREDAXO\WriteAssist\AiProvider\WriteAssistAiProviderGemini;
use FriendsOfREDAXO\WriteAssist\AiProvider\WriteAssistAiProviderOpenAiCompatible;
use rex_config;

class WriteAssistAiFactory
{
    public const PROVIDERS = [
        'disabled'    => 'Deaktiviert',
        'gemini'      => 'Google Gemini',
        'openai'      => 'OpenAI (ChatGPT)',
        'openwebui'   => 'OpenWebUI / OpenAI Compatible',
        'ai_platform' => 'ai_platform-Addon',
    ];

    public const OPENAI_BASE_URL = 'https://api.openai.com/v1';

    /**
     * @param array<string, string> $overrides Config-Werte, die statt der gespeicherten Werte
     *                                          verwendet werden sollen (z.B. fuer den Verbindungstest
     *                                          mit noch ungespeicherten Formularwerten). Fehlende Keys
     *                                          fallen weiterhin auf die gespeicherte Config zurueck.
     */
    public static function factory(array $overrides = []): WriteAssistAiProviderInterface
    {
        $get = static fn(string $key, string $default = ''): string
            => $overrides[$key] ?? (string) rex_config::get('writeassist', $key, $default);

        $providerKey = $overrides['ai_provider'] ?? rex_config::get('writeassist', 'ai_provider', 'gemini');

        return match($providerKey) {
            'openai' => new WriteAssistAiProviderOpenAiCompatible(
                $openAiKey = trim($get('openai_api_key')),
                $openAiKey !== '' ? self::OPENAI_BASE_URL : '',
                $get('openai_model', 'gpt-4o-mini')
            ),
            'openwebui' => new WriteAssistAiProviderOpenAiCompatible(
                $get('openwebui_api_key'),
                $get('openwebui_base_url'),
                $get('openwebui_model')
            ),
            'ai_platform' => new WriteAssistAiProviderAiPlatform(
                self::resolveProfileId($get('ai_platform_text_profile_id'))
            ),
            'disabled' => new WriteAssistAiProviderGemini('', ''),
            default => new WriteAssistAiProviderGemini(
                $get('gemini_api_key'),
                $get('gemini_model', 'gemini-2.5-flash')
            ),
        };
    }

    private static function resolveProfileId(string $raw): ?int
    {
        $value = trim($raw);

        return '' === $value ? null : (int) $value;
    }
}
