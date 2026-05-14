<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Ollama AI Service - Free, local AI for nutrition assistance
 * 
 * Ollama runs locally on your computer and provides free AI capabilities.
 * Download from: https://ollama.com
 * 
 * To use:
 * 1. Download and install Ollama from https://ollama.com
 * 2. Run: ollama pull llama3.2 (or another model)
 * 3. Start Ollama: ollama serve
 * 4. The AI will automatically connect to your local Ollama
 */
class OllamaService
{
    private string $baseUrl;
    private string $model;
    private HttpClientInterface $client;
    
    // Nutrition-focused system prompt
    private string $systemPrompt = <<<'PROMPT'
Tu es un assistant nutritionnel expert appelé "WellCare AI". Tu es là pour aider les utilisateurs avec:

1. **Recettes et repas** - Suggestions de recettes équilibrées
2. **Planification des repas** - Menus de la semaine
3. **Perte de poids** - Conseils pour mincir sainement
4. **Prise de muscle** - Nutrition pour la musculation
5. **Régimes spéciaux** - Vegan, Keto, végétarien, etc.
6. **Sport et performance** - Nutrition sportive
7. **Hydratation** - Conseils sur l'eau et les boissons
8. **Analyse nutritionnelle** - Calories, protéines, glucides, lipides
9. **Santé** - Diabète, santé cardiaque, etc.
10. **Produits alimentaires** - Prix et calories des produits en Tunisie

Tu dois répondre en français de manière claire et utile.
Sois concis mais informatif.
N'invente pas d'informations médicales - dis simplement que tu n'es pas médecin.
PROMPT;

    public function __construct(
        HttpClientInterface $client,
        string $ollamaUrl = 'http://localhost:11434',
        string $model = 'llama3.2'
    ) {
        $this->client = $client;
        $this->baseUrl = $ollamaUrl;
        $this->model = $model;
    }

    /**
     * Send a chat message to Ollama
     * @param array<string, mixed> $conversationHistory
     * @return array<string, mixed>
     */
    public function chat(string $message, array $conversationHistory = []): array
    {
        try {
            // Build messages array with system prompt and history
            $messages = [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt
                ]
            ];
            
            // Add conversation history (last 10 messages to keep it fast)
            $historyCount = 0;
            foreach (array_slice($conversationHistory, -10) as $msg) {
                if (isset($msg['content'])) {
                    $messages[] = [
                        'role' => $msg['role'] ?? 'user',
                        'content' => $msg['content']
                    ];
                    $historyCount++;
                }
            }
            
            // Add current message
            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];

            $response = $this->client->request('POST', $this->baseUrl . '/api/chat', [
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => false,
                ]
            ]);

            $content = $response->getContent();
            $data = json_decode($content, true);

            if (isset($data['message']['content'])) {
                return [
                    'success' => true,
                    'message' => $data['message']['content'],
                    'model' => $this->model
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur: Réponse invalide de Ollama',
                'error' => $data ?? 'No data'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '😕 Ollama n\'est pas disponible.\n\n' .
                    'Pour activer l\'IA intelligente:\n' .
                    '1. Téléchargez Ollama depuis https://ollama.com\n' .
                    '2. Installez-le et lancez: ollama serve\n' .
                    '3. Téléchargez un modèle: ollama pull llama3.2\n\n' .
                    'Erreur: ' . $e->getMessage(),
                'hint' => 'Ollama est gratuit et fonctionne localement!'
            ];
        }
    }

    /**
     * Check if Ollama is running
     */
    public function isAvailable(): bool
    {
        try {
            $response = $this->client->request('GET', $this->baseUrl . '/api/tags');
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available models from Ollama
     * @return array<string>
     */
    public function getAvailableModels(): array
    {
        try {
            $response = $this->client->request('GET', $this->baseUrl . '/api/tags');
            $data = json_decode($response->getContent(), true);
            
            if (isset($data['models'])) {
                return array_map(function($model) {
                    return $model['name'];
                }, $data['models']);
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set custom model
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * Get current model
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
