<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\WriteAssist\AiProvider;

abstract class WriteAssistAiProviderAbstract implements WriteAssistAiProviderInterface
{
    /**
     * Cleans up generated text
     */
    protected function cleanText(string $text): string
    {
        // Markdown Bold entfernen (**text** -> text)
        // preg_replace() kann bei einem PCRE-Fehler (z.B. Backtrack-Limit) null
        // zurückgeben; in dem Fall den Ausgangswert behalten statt null weiterzureichen.
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text) ?? $text;

        // Zitate am Anfang entfernen
        $text = preg_replace('/^["\']|["\']$/', '', trim($text)) ?? $text;
        
        // "Hier ist der Alt-Text:" o.ä. entfernen
        $prefixes = [
            'Hier ist', 'Sure', 'Okay', 'Here is', 'Certainly', 
            'Der Alt-Text', 'Generated', 'Answer:', 'Antwort:'
        ];
        
        foreach ($prefixes as $prefix) {
            if (stripos($text, $prefix) === 0) {
                $parts = explode(':', $text, 2);
                if (count($parts) > 1) {
                    $text = trim($parts[1]);
                }
            }
        }
        
        return trim($text);
    }
    
    /**
     * Handles cURL errors
     */
    protected function handleCurlError(\CurlHandle $ch): void
    {
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        
        if (stripos($error, 'Could not resolve host') !== false) {
            throw new \Exception('Verbindung fehlgeschlagen: Host nicht gefunden. Bitte Internetverbindung prüfen.');
        }
        
        if (stripos($error, 'timed out') !== false) {
            throw new \Exception('Zeitüberschreitung bei der Anfrage. Der Server hat zu lange nicht geantwortet.');
        }
        
        throw new \Exception('CURL Error (' . $errno . '): ' . $error);
    }
}
