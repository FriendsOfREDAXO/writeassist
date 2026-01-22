<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\WriteAssist\AiProvider;

class WriteAssistAiProviderGemini extends WriteAssistAiProviderAbstract
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model
    ) {}
    
    public function getKey(): string
    {
        return 'gemini';
    }
    
    public function getLabel(): string
    {
        return 'Google Gemini';
    }
    
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
    
    public function generate(string $prompt, string $text = '', array $options = []): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Gemini API key not configured');
        }
        
        $fullPrompt = $prompt;
        if ($text !== '') {
            $fullPrompt .= "\n\n" . $text;
        }
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $fullPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'maxOutputTokens' => $options['max_tokens'] ?? 2048,
                'topP' => $options['top_p'] ?? 0.95,
            ]
        ];
        
        // Safety settings
        $payload['safetySettings'] = [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_ONLY_HIGH'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
        ];
        
        $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
        $url = $baseUrl . $this->model . ':generateContent?key=' . $this->apiKey;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $this->handleCurlError($ch);
        }
        curl_close($ch);
        
        if ($httpCode !== 200) {
             $errorData = json_decode($response, true);
             $errorMessage = $errorData['error']['message'] ?? 'HTTP Error ' . $httpCode;
             throw new \Exception('API Error: ' . $errorMessage);
        }
        
        $responseData = json_decode($response, true);
        
        // Extract text
        $generatedText = '';
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            $generatedText = $responseData['candidates'][0]['content']['parts'][0]['text'];
        }
        
        $result = ['text' => $generatedText];
        
        // Include usage if available
        if (isset($responseData['usageMetadata'])) {
            $result['usage'] = [
                'prompt_tokens' => $responseData['usageMetadata']['promptTokenCount'] ?? 0,
                'completion_tokens' => $responseData['usageMetadata']['candidatesTokenCount'] ?? 0,
                'total_tokens' => $responseData['usageMetadata']['totalTokenCount'] ?? 0,
            ];
        }
        
        return $result;
    }
    
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'API-Key nicht konfiguriert'];
        }

        try {
            $result = $this->generate('Antworte mit: OK', '', ['max_tokens' => 10]);
            
            $usageInfo = '';
            if (isset($result['usage'])) {
                $usageInfo = sprintf(' (Tokens: %d)', $result['usage']['total_tokens']);
            }
            
            return [
                'success' => true, 
                'message' => 'Verbindung erfolgreich! Modell: ' . $this->model . $usageInfo
            ];
        } catch (\Exception $e) {
            return [
                'success' => false, 
                'message' => 'Fehler: ' . $e->getMessage()
            ];
        }
    }
}
