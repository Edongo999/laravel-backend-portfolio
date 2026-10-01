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

        $message = strtolower($validated['message']);

        try {
            // Charger ton fichier JSON
            $data = json_decode(file_get_contents(resource_path('lang/fr.json')), true);

            $response = null;

            // Présentation de l’assistant
            if (str_contains($message, 'présente-toi')
                || str_contains($message, 'qui es-tu')
                || str_contains($message, 'parle de toi')
                || str_contains($message, 'assistant')) {
                $response = "Je suis l’assistant virtuel de Frank Landry.\n"
                          . "Mon rôle est de vous aider à découvrir :\n"
                          . "- Son profil\n"
                          . "- Ses compétences\n"
                          . "- Ses projets\n"
                          . "- Son parcours\n"
                          . "- Ses coordonnées";
            }
            // Profil de Frank Landry
            elseif (str_contains($message, 'qui est frank landry')
                || str_contains($message, 'à propos de frank')
                || str_contains($message, 'frank landry')) {
                $response = $data['about']['summary'] ?? 'Frank Landry est développeur web Full Stack.';
            }
            // Compétences
            elseif (str_contains($message, 'compétence')
                || str_contains($message, 'skills')
                || str_contains($message, 'savoir-faire')) {
                $skills = implode("\n- ", $data['skills']);
                $response = "Les principales compétences de Frank Landry sont :\n- " . $skills;
            }
            // Projets
            elseif (str_contains($message, 'projet')
                || str_contains($message, 'réalisation')
                || str_contains($message, 'travaux')) {
                $projects = array_map(fn($p) => $p['title'] . " → " . $p['tools'], $data['projects']);
                $response = "Voici quelques projets réalisés :\n- " . implode("\n- ", $projects);
            }
            // Expériences
            elseif (str_contains($message, 'expérience')
                || str_contains($message, 'parcours')
                || str_contains($message, 'formation')) {
                $experiences = array_map(fn($e) => $e['title'] . " (" . $e['date'] . ")", $data['experience']);
                $response = "Son parcours inclut :\n- " . implode("\n- ", $experiences);
            }
            // Contact
            elseif (str_contains($message, 'contact')
                || str_contains($message, 'email')
                || str_contains($message, 'téléphone')
                || str_contains($message, 'joindre')) {
                $response = "Vous pouvez contacter Frank Landry :\n"
                          . "- Email : " . $data['contact']['email'] . "\n"
                          . "- Téléphone : " . $data['contact']['phone'] . "\n"
                          . "- Localisation : " . $data['contact']['location'];
            }

            // Si aucune correspondance → IA Gemini
            if (!$response) {
                $response = $this->geminiService->generateResponse($validated['message']);
            }

            return response()->json([
                'success' => true,
                'message' => $response,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du traitement de la requête.',
            ], 500);
        }
    }
}