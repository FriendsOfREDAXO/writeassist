<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\WriteAssist\AiProvider;

use FriendsOfRedaxo\AiPlatform\Service as AiPlatformService;

/**
 * Delegiert an das ai_platform-Addon statt eigene Provider-Zugangsdaten zu
 * verwalten - siehe ai_chat's AiPlatformService fuer dasselbe Muster in
 * diesem Projekt.
 */
class WriteAssistAiProviderAiPlatform extends WriteAssistAiProviderAbstract
{
    public function __construct(
        private readonly ?int $textProfileId
    ) {}

    public function getKey(): string
    {
        return 'ai_platform';
    }

    public function getLabel(): string
    {
        return 'ai_platform-Addon';
    }

    public function isConfigured(): bool
    {
        return null !== $this->textProfileId
            && \class_exists(AiPlatformService::class)
            && \rex_addon::get('ai_platform')->isAvailable();
    }

    public function generate(string $prompt, string $text = '', array $options = []): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('ai_platform: Kein Text-Profil ausgewählt oder Addon nicht verfügbar');
        }

        $fullPrompt = $prompt;
        if ($text !== '') {
            $fullPrompt .= "\n\n" . $text;
        }

        $generatedText = AiPlatformService::getInstance()->generateText($fullPrompt, '', $this->textProfileId);

        return ['text' => $generatedText];
    }

    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Kein Text-Profil ausgewählt oder ai_platform-Addon nicht verfügbar'];
        }

        try {
            $result = $this->generate('Antworte mit: OK');

            return [
                'success' => true,
                'message' => 'Verbindung erfolgreich! Antwort: ' . mb_substr($result['text'], 0, 50)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Fehler: ' . $e->getMessage()
            ];
        }
    }
}
