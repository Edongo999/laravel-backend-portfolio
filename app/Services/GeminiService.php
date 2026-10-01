<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function generateResponse(string $message): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key={$this->apiKey}";

        $response = Http::timeout(60)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $message]
                        ]
                    ]
                ]
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Erreur Gemini : " . $response->body());
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Pas de réponse générée.';
    }
}



