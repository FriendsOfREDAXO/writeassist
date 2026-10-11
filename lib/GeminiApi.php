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
        // Hintergrund zur Website (Einstellung „Kontext für Textwerkzeuge“) - bei jeder
        // Generierung vorangestellt, damit Stil, Anrede und Fachbegriffe passen.
        $context = self::generationContext();
        if ('' !== $context) {
            $prompt = "Hintergrund (nur zur Orientierung, nicht wiedergeben): " . $context . "\n\n" . $prompt;
        }

        return $this->provider->generate($prompt, $text, $options);
    }

    public static function generationContext(): string
    {
        return trim((string) rex_addon::get('writeassist')->getConfig('generation_context', ''));
    }

    /**
     * Textwerkzeuge (Editor-Menü, Info-Center): eine Aktion auf einen Text anwenden.
     * $html = true: Eingabe ist HTML aus dem Editor, das Ergebnis ist wieder HTML mit
     * derselben Struktur (Absätze, Listen, Links, Hervorhebungen bleiben erhalten).
     *
     * @return array{text: string, usage?: array<string, int>}
     */
    public function transform(string $action, string $content, string $style = '', string $instructions = '', bool $html = false): array
    {
        $styles = [
            'professional' => 'sachlich und professionell',
            'casual' => 'locker und persönlich',
            'simple' => 'einfach und leicht verständlich',
            'formal' => 'formell',
            'creative' => 'lebendig und ansprechend',
            'concise' => 'knapp und prägnant',
        ];
        $prompt = match ($action) {
            'improve' => 'Korrigiere Rechtschreibung, Grammatik und Zeichensetzung im folgenden Text und glätte holprige Formulierungen behutsam. Inhalt, Fakten, Namen, Zahlen und Länge bleiben gleich.',
            'shorten' => 'Kürze den folgenden Text deutlich (etwa auf die Hälfte). Wichtige Fakten, Namen, Daten und Zahlen bleiben erhalten.',
            'simplify' => 'Formuliere den folgenden Text leicht verständlich: kurze Sätze, einfache Wörter, aktive Sprache. Fakten bleiben erhalten.',
            'expand' => 'Erweitere den folgenden Text um passende Erläuterungen. Erfinde keine Fakten, Namen, Daten oder Zahlen.',
            'summarize' => 'Fasse den folgenden Text sachlich in höchstens drei Sätzen zusammen. Nur Angaben aus dem Text.',
            'teaser' => 'Schreibe zum folgenden Text einen Teaser (Anreißer): zwei bis drei sachliche, gut lesbare Sätze mit dem Wichtigsten zuerst (wer, was, wann, Ergebnis). Höchstens 300 Zeichen. Nur Angaben aus dem Text, nichts erfinden, keine Werbefloskeln wie „Erleben Sie …“ oder „Jetzt mehr erfahren“.',
            'headline' => 'Schlage fünf kurze, treffende Überschriften für den folgenden Text vor (als Liste).',
            'continue' => 'Schreibe den folgenden Text im gleichen Stil sinnvoll mit ein bis zwei Absätzen weiter. Erfinde keine konkreten Fakten, Namen, Daten oder Zahlen. Gib nur die neuen Absätze aus.',
            'rewrite' => 'Formuliere den folgenden Text ' . ($styles[$style] ?? $styles['professional']) . ' um. Die Bedeutung bleibt gleich.',
            'custom' => '' !== trim($instructions) ? trim($instructions) : throw new \InvalidArgumentException('Bitte eine Anweisung eingeben.'),
            default => throw new \InvalidArgumentException('Unbekannte Aktion: ' . $action),
        };
        if ('custom' !== $action && '' !== trim($instructions)) {
            $prompt .= "\nZusätzlich: " . trim($instructions);
        }
        if ('' !== $style && 'rewrite' !== $action && isset($styles[$style])) {
            $prompt .= "\nTonfall: " . $styles[$style] . '.';
        }
        $prompt .= $html
            ? "\nDer Text ist HTML. Behalte Absätze, Listen, Links (mit href) und Hervorhebungen bei. Antworte ausschließlich mit dem Ergebnis als HTML-Fragment – ohne Codeblock, ohne <html> oder <body>, ohne Einleitung oder Erklärung."
            : "\nAntworte ausschließlich mit dem Ergebnis, ohne Einleitung oder Erklärung.";

        $result = $this->generate($prompt, "Text:\n" . $content);
        $result['text'] = self::cleanResult($result['text'], $html);

        return $result;
    }

    /**
     * Antwort der KI aufräumen: Codeblock-Zäune und Einleitungen entfernen; im HTML-Modus
     * Text ohne Tags (oder mit Markdown) in Absätze/Listen umwandeln.
     */
    public static function cleanResult(string $text, bool $html): string
    {
        $text = trim($text);
        // <think>…</think> (Reasoning-Modelle) und ```html … ```-Zäune entfernen
        $text = trim((string) preg_replace('~<think>.*?</think>~s', '', $text));
        if (preg_match('~^```[a-z]*\s*\n(.*?)\n```$~s', $text, $m)) {
            $text = trim($m[1]);
        }
        $text = (string) preg_replace('~^(Hier ist|Hier sind|Gerne|Klar|Sure|Here is)[^\n:]*:\s*~iu', '', $text);
        if (!$html) {
            return trim($text);
        }
        if (preg_match('~<(p|ul|ol|h[1-6]|blockquote|table|div|br|a|strong|em)\b~i', $text)) {
            return trim($text);
        }

        // Markdown/Fließtext → HTML
        $esc = static fn (string $s): string => (string) preg_replace(
            ['~\*\*(.+?)\*\*~u', '~(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])~u'],
            ['<strong>$1</strong>', '<em>$1</em>'],
            htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        );
        $out = '';
        foreach (preg_split('~\n\s*\n~', $text) ?: [] as $block) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", trim($block))), static fn ($l) => '' !== $l));
            if ([] === $lines) {
                continue;
            }
            $isList = array_reduce($lines, static fn ($c, $l) => $c && 1 === preg_match('~^([-*•]|\d+[.)])\s+~u', $l), true);
            if ($isList) {
                $ordered = 1 === preg_match('~^\d+[.)]~', $lines[0]);
                $out .= $ordered ? '<ol>' : '<ul>';
                foreach ($lines as $l) {
                    $out .= '<li>' . $esc((string) preg_replace('~^([-*•]|\d+[.)])\s+~u', '', $l)) . '</li>';
                }
                $out .= $ordered ? '</ol>' : '</ul>';
                continue;
            }
            if (preg_match('~^#{1,6}\s+(.+)$~u', $lines[0], $h) && 1 === count($lines)) {
                $out .= '<h3>' . $esc($h[1]) . '</h3>';
                continue;
            }
            $out .= '<p>' . implode('<br>', array_map($esc, $lines)) . '</p>';
        }

        return $out;
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


