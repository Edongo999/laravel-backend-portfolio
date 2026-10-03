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

    public function generateResponse(string $message, string $lang = 'fr'): string
    {
        $prompt = $lang === 'fr'
            ? <<<PROMPT
Tu es l'assistant virtuel du portfolio professionnel de Frank Landry.

Ton rôle est de répondre de manière professionnelle, claire, naturelle et concise.

Tu peux répondre aux questions générales concernant :
- le développement web
- le développement Full Stack
- React.js
- Laravel
- PHP
- JavaScript
- TypeScript
- les API
- le design graphique
- Photoshop
- Illustrator
- la maintenance informatique
- la formation informatique

IMPORTANT :
- Tu ne dois jamais inventer d'informations personnelles sur Frank Landry.
- Les informations précises concernant son profil, ses projets, ses compétences, son parcours, ses services et ses coordonnées sont gérées par l'application.
- Si une information précise sur Frank Landry n'est pas disponible, indique simplement qu'elle n'est pas disponible.
- Ne prétends pas être Frank Landry.
- Tu es son assistant virtuel.
- Pour parler de Frank Landry, utilise la troisième personne : "Frank Landry est...", "Il développe...", "Ses compétences...", etc.
- Ne réponds pas comme si tu étais Frank.
- Si la question est complètement étrangère au portfolio, au développement web, au design graphique ou à l'informatique, indique poliment que tu es spécialisé dans ces domaines.

Réponds uniquement en français.

Question de l'utilisateur :
{$message}
PROMPT
            : <<<PROMPT
You are the virtual assistant of Frank Landry's professional portfolio.

Your role is to answer clearly, naturally, professionally and concisely.

You can answer general questions about:
- web development
- Full Stack development
- React.js
- Laravel
- PHP
- JavaScript
- TypeScript
- APIs
- graphic design
- Photoshop
- Illustrator
- IT maintenance
- IT training

IMPORTANT:
- Never invent personal information about Frank Landry.
- Precise information about his profile, projects, skills, background, services and contact details is managed by the application.
- If a specific piece of information about Frank Landry is not available, simply say that it is not available.
- Do not pretend to be Frank Landry.
- You are his virtual assistant.
- When talking about Frank Landry, use the third person: "Frank Landry is...", "He develops...", "His skills...", etc.
- Do not answer as if you were Frank.
- If the question is completely unrelated to the portfolio, web development, graphic design or IT, politely explain that you specialize in these areas.

Respond only in English.

User's question:
{$message}
PROMPT;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key={$this->apiKey}";

        $response = Http::timeout(60)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ]
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "Erreur Gemini : " . $response->body()
            );
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text']
            ?? (
                $lang === 'fr'
                    ? "Je n'ai pas pu générer une réponse."
                    : "I could not generate a response."
            );
    }
}