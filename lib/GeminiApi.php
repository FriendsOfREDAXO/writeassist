<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\WriteAssist;

use FriendsOfREDAXO\WriteAssist\AiProvider\WriteAssistAiProviderInterface;
use rex_addon;
use rex_clang;
use rex_sql;

/**
 * AI Service Wrapper
 * 
 * Delegates to the configured AI Provider (Gemini, OpenWebUI, etc.)
 * Provides specialized prompts for varied tasks.
 */
class GeminiApi
{
    private WriteAssistAiProviderInterface $provider;
    
    public function __construct()
    {
        $this->provider = WriteAssistAiFactory::factory();
    }
    
    /**
     * Check if API is configured
     */
    public function isConfigured(): bool
    {
        return $this->provider->isConfigured();
    }
    
    /**
     * Generate text content
     * 
     * @param string $prompt The prompt/instruction
     * @param string $text Optional text to process
     * @param array<string, mixed> $options Additional options
     * @return array{text: string, usage?: array<string, int>}
     * @throws \Exception on API error
     */
    public function generate(string $prompt, string $text = '', array $options = []): array
    {
        return $this->provider->generate($prompt, $text, $options);
    }

    
    /**
     * Rewrite/improve text
     * @return array{text: string, usage?: array<string, int>}
     */
    public function rewrite(string $text, string $style = 'professional'): array
    {
        $styles = [
            'professional' => 'Schreibe den folgenden Text professioneller und formeller um. Behalte die Bedeutung bei.',
            'casual' => 'Schreibe den folgenden Text lockerer und umgangssprachlicher um. Behalte die Bedeutung bei.',
            'simple' => 'Vereinfache den folgenden Text. Verwende einfache Wörter und kurze Sätze.',
            'formal' => 'Schreibe den folgenden Text in einem sehr formellen, geschäftlichen Stil um.',
            'creative' => 'Schreibe den folgenden Text kreativer und ansprechender um.',
            'concise' => 'Kürze den folgenden Text auf das Wesentliche. Entferne Füllwörter und Redundanzen.',
        ];
        
        $prompt = $styles[$style] ?? $styles['professional'];
        $prompt .= ' Antworte nur mit dem umgeschriebenen Text, ohne Erklärungen.';
        
        return $this->generate($prompt, $text);
    }
    
    /**
     * Summarize text
     * @return array{text: string, usage?: array<string, int>}
     */
    public function summarize(string $text, int $maxSentences = 3): array
    {
        $prompt = "Fasse den folgenden Text in maximal {$maxSentences} Sätzen zusammen. " .
                  "Antworte nur mit der Zusammenfassung, ohne Erklärungen.";
        
        return $this->generate($prompt, $text);
    }
    
    /**
     * Expand/elaborate text
     * @return array{text: string, usage?: array<string, int>}
     */
    public function expand(string $text): array
    {
        $prompt = "Erweitere den folgenden Text mit mehr Details und Erklärungen. " .
                  "Behalte den Stil bei. Antworte nur mit dem erweiterten Text.";
        
        return $this->generate($prompt, $text);
    }
    
    /**
     * Generate text from keywords/topic
     * @return array{text: string, usage?: array<string, int>}
     */
    public function generateFromTopic(string $topic, string $type = 'paragraph', string $instructions = ''): array
    {
        $prompt = match($type) {
            'headline' => 'Schreibe 5 Vorschläge für eine Überschrift zum folgenden Thema.',
            'bullet_points' => 'Erstelle eine Liste mit Stichpunkten zum folgenden Thema.',
            'intro' => 'Schreibe eine Einleitung für einen Artikel zum folgenden Thema.',
            'meta_description' => 'Schreibe eine SEO Meta-Description (max. 160 Zeichen) zum folgenden Thema.',
            default => 'Schreibe einen Absatz zum folgenden Thema.'
        };
        
        if ($instructions !== '') {
            $prompt .= "\nZusätzliche Anweisungen: " . $instructions;
        }
        
        $prompt .= "\nAntworte nur mit dem generierten Text.";
        
        return $this->generate($prompt, $topic);
    }
    
}


