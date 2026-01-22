<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\WriteAssist\AiProvider;

class WriteAssistAiProviderOpenAiCompatible extends WriteAssistAiProviderAbstract
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $model
    ) {}
    
    public function getKey(): string
    {
        return 'openwebui';
    }
    
    public function getLabel(): string
    {
        return 'OpenWebUI / OpenAI Compatible';
    }
    
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl);
    }
    
    public function generate(string $prompt, string $text = '', array $options = []): array
    {
        // Smart URL handling
        $url = rtrim($this->baseUrl, '/');
        
        // 1. Wenn die URL bereits auf /chat/completions endet (User hat vollen Pfad eingegeben)
        if (str_ends_with($url, '/chat/completions')) {
            // URL ist bereits korrekt
        }
        // 2. Wenn es eine typische Base-URL ist (z.B. .../v1 oder .../api)
        elseif (str_ends_with($url, '/v1') || str_ends_with($url, '/api')) {
             $url .= '/chat/completions';
        }
        // 3. Fallback für OpenWebUI Standard (/api/chat/completions vs /v1/chat/completions)
        // Wir probieren hier eine Heuristik: Wenn kein /api oder /v1 drin ist, hängen wir /v1/chat/completions an
        else {
             $url .= '/v1/chat/completions';
        }
        
        $fullPrompt = $prompt;
        if ($text !== '') {
            $fullPrompt .= "\n\n" . $text;
        }

        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $fullPrompt
                ]
            ],
            'temperature' => (float)($options['temperature'] ?? 0.7),
            'max_tokens' => (int)($options['max_tokens'] ?? 2048),
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
             $this->handleCurlError($ch);
        }
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($httpCode !== 200) {
            $errorMessage = $responseData['error']['message'] ?? 'HTTP Error ' . $httpCode;
            throw new \Exception('API Error: ' . $errorMessage . ' (URL: ' . $url . ')');
        }
        
        $generatedText = $responseData['choices'][0]['message']['content'] ?? '';
        
        $result = ['text' => $generatedText];
        
        if (isset($responseData['usage'])) {
            $result['usage'] = [
                'prompt_tokens' => $responseData['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $responseData['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $responseData['usage']['total_tokens'] ?? 0,
            ];
        }
        
        return $result;
    }
    
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Base URL nicht konfiguriert'];
        }

        // Versuch Model-Liste abzurufen
        $baseUrl = rtrim($this->baseUrl, '/');
        if (str_ends_with($baseUrl, '/chat/completions')) {
            $baseUrl = str_replace('/chat/completions', '', $baseUrl);
        }
        
        // Versuche /models Endpoint
        $modelsUrl = $baseUrl . '/models';
        if (!str_ends_with($baseUrl, '/v1') && !str_ends_with($baseUrl, '/api')) {
             $modelsUrl = $baseUrl . '/v1/models'; // Standard OpenWebUI/Ollama
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $modelsUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 5
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $modelsList = '';
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['data']) && is_array($data['data'])) {
                $names = array_map(function($m) { return $m['id']; }, $data['data']);
                $modelsList = '<br><br><strong>Verfügbare Modelle:</strong><br>' . implode(', ', $names);
            }
        }

        try {
            $result = $this->generate('Antworte mit: OK', '', ['max_tokens' => 10]);
             return [
                'success' => true, 
                'message' => 'Verbindung erfolgreich!' . $modelsList
            ];
        } catch (\Exception $e) {
            return [
                'success' => false, 
                'message' => 'Fehler: ' . $e->getMessage() . $modelsList
            ];
        }
    }
}
