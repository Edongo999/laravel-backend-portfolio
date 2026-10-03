<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ChatController extends Controller
{
    public function __construct(
        private GeminiService $geminiService
    ) {
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = trim($validated['message']);

        try {
            // Détection de la langue
            $lang = $this->detectLanguage($message);

            // Charger les informations du portfolio
            $filePath = resource_path("lang/{$lang}.json");

            $profile = [];

            if (file_exists($filePath)) {
                $json = file_get_contents($filePath);
                $decoded = json_decode($json, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $profile = $decoded;
                }
            }

            // Envoyer la question + les informations du portfolio à Gemini
            $response = $this->geminiService->generateResponse(
                $message,
                $profile,
                $lang
            );

            return response()->json([
                'success' => true,
                'message' => $response,
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => $lang === 'fr'
                    ? 'Une erreur est survenue lors du traitement de votre demande.'
                    : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Détection simple de la langue
     */
    private function detectLanguage(string $message): string
    {
        // Mots français courants
        if (preg_match(
            '/\b(qui|quoi|comment|quel|quelle|quels|quelles|frank|compétence|projet|service|contact|prix|tarif|formation|parcours|expérience|site|logo|création)\b/ui',
            $message
        )) {
            return 'fr';
        }

        // Mots anglais courants
        if (preg_match(
            '/\b(who|what|how|which|skills|projects|services|contact|price|training|experience|website|logo|about)\b/i',
            $message
        )) {
            return 'en';
        }

        // Français par défaut
        return 'fr';
    }
}

