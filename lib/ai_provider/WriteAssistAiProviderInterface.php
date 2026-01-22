<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\WriteAssist\AiProvider;

interface WriteAssistAiProviderInterface
{
    /**
     * Returns the key of the provider (e.g. 'gemini')
     */
    public function getKey(): string;
    
    /**
     * Returns the label of the provider
     */
    public function getLabel(): string;
    
    /**
     * Checks if the provider is correctly configured
     */
    public function isConfigured(): bool;
    
    /**
     * Generates text content
     * 
     * @param string $prompt The prompt/instruction
     * @param string $text Optional text to process
     * @param array<string, mixed> $options Additional options
     * @return array{text: string, usage?: array<string, int>}
     * @throws \Exception
     */
    public function generate(string $prompt, string $text = '', array $options = []): array;
    
    /**
     * Tests the connection to the API
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array;
}
