<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    public function generateResponse(string $message): string
    {
        $apiKey = config('services.gemini.api_key');

        if (!$apiKey) {
            throw new RuntimeException(
                'La clé API Gemini n\'est pas configurée.'
            );
        }

        $response = Http::timeout(60)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/interactions',
                [
                    'model' => 'gemini-3.8-flash',
                    'input' => $message,
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Erreur Gemini : ' . $response->body()
            );
        }

        /*
         * La réponse de l'Interactions API contient
         * les étapes de l'interaction.
         */
        $steps = $response->json('steps', []);

        foreach ($steps as $step) {
            if (($step['type'] ?? null) !== 'model_output') {
                continue;
            }

            foreach ($step['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'text') {
                    return $content['text'];
                }
            }
        }

        throw new RuntimeException(
            'Gemini n\'a retourné aucune réponse texte.'
        );
    }
}
