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
        if (empty($this->apiKey)) {
            return $lang === 'fr'
                ? "Je ne peux pas répondre pour le moment. Le service d'assistance n'est pas configuré."
                : "I cannot answer at the moment. The assistant service is not configured.";
        }

        $prompt = $lang === 'fr'
            ? <<<PROMPT
Tu es l'assistant virtuel du portfolio professionnel de Frank Landry.

Ton rôle est double :

1. Présenter et renseigner les visiteurs sur Frank Landry :
- son profil
- ses compétences
- ses projets
- son parcours
- ses services
- ses coordonnées

2. Répondre aux questions générales liées à :
- développement web
- développement Full Stack
- frontend et backend
- React.js
- Laravel
- PHP
- JavaScript
- TypeScript
- HTML et CSS
- API et API REST
- bases de données
- Git et GitHub
- design graphique
- Photoshop
- Illustrator
- maintenance informatique
- informatique
- formation et apprentissage dans ces domaines

IMPORTANT :

- Tu es l'assistant de Frank Landry, pas Frank Landry lui-même.
- Lorsque tu parles de Frank Landry, utilise toujours la troisième personne.
- Ne dis jamais "je suis Frank Landry".
- N'invente aucune information personnelle sur Frank Landry.
- Pour les informations précises sur Frank Landry, l'application fournit normalement les données nécessaires.
- Si une information personnelle sur Frank Landry n'est pas disponible, indique simplement qu'elle n'est pas disponible.
- Pour les questions générales sur le développement web, le design ou l'informatique, réponds normalement et de manière pédagogique.
- Tu peux expliquer des notions techniques, donner des exemples et aider à comprendre un problème.
- Si une question n'a absolument aucun rapport avec Frank Landry, le développement web, le design graphique ou l'informatique, indique poliment que ton rôle est principalement centré sur ces domaines.
- Ne réponds pas uniquement "je ne sais pas" lorsqu'une question technique générale peut être expliquée.
- Réponds clairement, naturellement et professionnellement.
- Réponds en français.

Question de l'utilisateur :
{$message}
PROMPT
            : <<<PROMPT
You are the virtual assistant of Frank Landry's professional portfolio.

Your role has two purposes:

1. Provide visitors with information about Frank Landry:
- his profile
- his skills
- his projects
- his background
- his services
- his contact information

2. Answer general questions related to:
- web development
- Full Stack development
- frontend and backend development
- React.js
- Laravel
- PHP
- JavaScript
- TypeScript
- HTML and CSS
- APIs and REST APIs
- databases
- Git and GitHub
- graphic design
- Photoshop
- Illustrator
- IT maintenance
- computer science and IT
- training and learning in these areas

IMPORTANT:

- You are Frank Landry's assistant, not Frank Landry himself.
- When talking about Frank Landry, always use the third person.
- Never say "I am Frank Landry".
- Never invent personal information about Frank Landry.
- The application normally provides the precise information about Frank Landry.
- If personal information about Frank Landry is unavailable, simply say that it is not available.
- For general questions about web development, design or IT, answer normally and helpfully.
- You may explain technical concepts, provide examples and help the user understand a technical problem.
- If a question has absolutely nothing to do with Frank Landry, web development, graphic design or IT, politely explain that your role is mainly focused on these areas.
- Do not simply answer "I don't know" when a general technical question can be explained.
- Answer clearly, naturally and professionally.
- Respond in English.

User's question:
{$message}
PROMPT;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key={$this->apiKey}";

        try {
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
                report(new RuntimeException(
                    "Erreur Gemini : " . $response->body()
                ));

                return $lang === 'fr'
                    ? "Je rencontre actuellement un problème avec le service de réponse. Veuillez réessayer dans quelques instants."
                    : "I am currently having a problem with the response service. Please try again shortly.";
            }

            $data = $response->json();

            $text = $data['candidates'][0]['content']['parts'][0]['text']
                ?? null;

            if (!$text) {
                return $lang === 'fr'
                    ? "Je n'ai pas pu générer une réponse pour cette question."
                    : "I could not generate a response to this question.";
            }

            return trim($text);

        } catch (\Throwable $e) {
            report($e);

            return $lang === 'fr'
                ? "Le service d'assistance rencontre momentanément un problème. Veuillez réessayer."
                : "The assistant service is temporarily unavailable. Please try again.";
        }
    }
}