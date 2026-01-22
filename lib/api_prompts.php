<?php

declare(strict_types=1);

/**
 * API Endpoint for Managing Saved Prompts
 */
class rex_api_writeassist_prompts extends rex_api_function
{
    protected $published = true;

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        $user = rex::getUser();
        if (!$user) {
            rex_response::setStatus(rex_response::HTTP_UNAUTHORIZED);
            rex_response::sendJson(['error' => 'Unauthorized']);
            exit;
        }

        $action = rex_request('action', 'string', 'list');
        $id = rex_request('id', 'string', '');
        $title = rex_request('title', 'string', '');
        $content = rex_request('content', 'string', '');
        
        $prompts = $this->getPrompts();

        try {
            switch ($action) {
                case 'list':
                    rex_response::sendJson(['success' => true, 'prompts' => array_values($prompts)]);
                    break;
                    
                case 'save':
                    if (empty($title) || empty($content)) {
                        throw new \Exception('Title and content required');
                    }
                    if (empty($id)) {
                        $id = uniqid('prompt_');
                    }
                    
                    $prompts[$id] = [
                        'id' => $id,
                        'title' => $title,
                        'content' => $content,
                        'updated' => time()
                    ];
                    
                    $this->savePrompts($prompts);
                    rex_response::sendJson(['success' => true, 'id' => $id, 'prompts' => array_values($prompts)]);
                    break;
                    
                case 'delete':
                    if (empty($id)) {
                         throw new \Exception('ID required');
                    }
                    if (isset($prompts[$id])) {
                        unset($prompts[$id]);
                        $this->savePrompts($prompts);
                    }
                    rex_response::sendJson(['success' => true, 'prompts' => array_values($prompts)]);
                    break;
                    
                default:
                    throw new \Exception('Unknown action');
            }
        } catch (\Throwable $e) {
             rex_response::sendJson(['success' => false, 'error' => $e->getMessage()]);
        }

        exit;
    }
    
    private function getPrompts(): array
    {
        $data = rex_config::get('writeassist', 'saved_prompts', '[]');
        $prompts = json_decode($data, true);
        return is_array($prompts) ? $prompts : [];
    }
    
    private function savePrompts(array $prompts): void
    {
        rex_config::set('writeassist', 'saved_prompts', json_encode($prompts));
    }
}
