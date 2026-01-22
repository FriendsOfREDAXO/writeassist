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
    
    /**
     * Generate code with REDAXO context
     * @return array{text: string, usage?: array<string, int>}
     */
    public function generateCode(string $description, string $language = 'php'): array
    {
        $redaxoContext = $this->getRedaxoContext();
        
        $prompt = "Du bist ein erfahrener {$language}-Entwickler mit Expertise in REDAXO CMS. " .
                  "Hier sind wichtige Informationen zum Projekt:\n\n" .
                  $redaxoContext . "\n\n" .
                  "WICHTIG: Verwende immer modernen PHP 8.2+ Code mit:\n" .
                  "- declare(strict_types=1)\n" .
                  "- Typed Properties und Return Types\n" .
                  "- Readonly Properties wo sinnvoll\n" .
                  "- Named Arguments bei Bedarf\n" .
                  "- Match Expressions statt switch\n" .
                  "- Nullsafe Operator (?->) wo passend\n" .
                  "- Arrow Functions für einfache Callbacks\n\n" .
                  "Generiere sauberen, gut kommentierten {$language}-Code für folgende Anforderung. " .
                  "Verwende REDAXO Core-Methoden wo möglich (rex_sql, rex_file, rex_path, rex_addon, rex_article, rex_category, rex_media, rex_clang, rex_user, rex_fragment, rex_view, rex_i18n etc.). " .
                  "Antworte NUR mit dem Code, ohne zusätzliche Erklärungen. " .
                  "Verwende Best Practices und moderne Syntax.\n\n" .
                  "Anforderung: {$description}";
        
        return $this->generate($prompt, '', ['temperature' => 0.3, 'max_tokens' => 4096]);
    }
    
    /**
     * Explain code with REDAXO context
     * @return array{text: string, usage?: array<string, int>}
     */
    public function explainCode(string $code, string $language = 'php'): array
    {
        $prompt = "Du bist ein REDAXO CMS Experte. " .
                  "Erkläre den folgenden {$language}-Code auf Deutsch. " .
                  "Beschreibe was der Code macht und erkläre REDAXO-spezifische Methoden. " .
                  "Weise auf mögliche Probleme oder Verbesserungen hin.";
        
        return $this->generate($prompt, $code);
    }
    
    /**
     * Ask a question about code or REDAXO
     * @return array{text: string, usage?: array<string, int>}
     */
    public function askAboutCode(string $question, string $code = '', string $language = 'php'): array
    {
        $redaxoContext = $this->getRedaxoContext();
        
        $prompt = "Du bist ein REDAXO CMS und {$language} Experte. " .
                  "Hier sind Informationen zum Projekt:\n\n" .
                  $redaxoContext . "\n\n" .
                  "Beantworte die folgende Frage auf Deutsch. " .
                  "Wenn Code-Beispiele nötig sind, verwende modernen PHP 8.2+ Code und REDAXO Core-Methoden.\n\n" .
                  "Frage: {$question}";
        
        return $this->generate($prompt, $code);
    }
    
    /**
     * Improve/refactor code with REDAXO context
     * @return array{text: string, usage?: array<string, int>}
     */
    public function improveCode(string $code, string $language = 'php'): array
    {
        $redaxoContext = $this->getRedaxoContext();
        
        $prompt = "Du bist ein erfahrener {$language}-Entwickler mit Expertise in REDAXO CMS. " .
                  "Hier sind Informationen zum Projekt:\n\n" .
                  $redaxoContext . "\n\n" .
                  "WICHTIG: Verwende immer modernen PHP 8.2+ Code mit:\n" .
                  "- declare(strict_types=1)\n" .
                  "- Typed Properties und Return Types\n" .
                  "- Readonly Properties wo sinnvoll\n" .
                  "- Match Expressions statt switch\n" .
                  "- Nullsafe Operator (?->) wo passend\n" .
                  "- Arrow Functions für einfache Callbacks\n\n" .
                  "Verbessere und refaktoriere den folgenden Code. " .
                  "Verwende REDAXO Core-Methoden wo möglich. " .
                  "Achte auf: Performance, Lesbarkeit, Best Practices, Sicherheit. " .
                  "Antworte NUR mit dem verbesserten Code, ohne Erklärungen.";
        
        return $this->generate($prompt, $code, ['temperature' => 0.2, 'max_tokens' => 4096]);
    }
    
    /**
     * Get REDAXO project context (no sensitive data!)
     */
    private function getRedaxoContext(): string
    {
        $context = [];
        
        // REDAXO Version
        $context[] = "REDAXO Version: " . \rex::getVersion();
        $context[] = "PHP Version: " . PHP_VERSION;
        
        // Important REDAXO resources
        $context[] = "\nWichtige REDAXO Ressourcen:";
        $context[] = "- REDAXO Core: https://github.com/redaxo/redaxo";
        $context[] = "- REDAXO Dokumentation: https://github.com/redaxo/docs";
        $context[] = "- Friends Of REDAXO AddOns: https://github.com/FriendsOfREDAXO";
        $context[] = "- API Dokumentation: https://redaxo.org/api/main/";
        $context[] = "- MForm (Formular-Builder): https://github.com/FriendsOfREDAXO/mform";
        $context[] = "- MBlock (Wiederholbare Blöcke): https://github.com/FriendsOfREDAXO/mblock";
        
        // Installed AddOns (names only, no config!)
        $addons = [];
        foreach (\rex_addon::getAvailableAddons() as $addon) {
            $addons[] = $addon->getName() . ' (' . $addon->getVersion() . ')';
        }
        $context[] = "\nInstallierte AddOns: " . implode(', ', $addons);
        
        // Languages
        $languages = [];
        foreach (\rex_clang::getAll() as $clang) {
            $languages[] = $clang->getCode();
        }
        $context[] = "Sprachen: " . implode(', ', $languages);
        
        // Database tables (structure only, no data!)
        $sql = \rex_sql::factory();
        $tables = $sql->getTablesAndViews();
        $rexTables = array_filter($tables, fn($t) => str_starts_with($t, \rex::getTablePrefix()));
        $context[] = "Datenbank-Tabellen: " . implode(', ', $rexTables);
        
        return implode("\n", $context);
    }
}


