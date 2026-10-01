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
            // 🔎 Détection automatique de la langue
            $lang = $this->detectLanguage($message);

            // Charger le fichier JSON correspondant
            $filePath = resource_path("lang/{$lang}.json");
            $data = [];

            if (file_exists($filePath)) {
                $json = file_get_contents($filePath);
                $decoded = json_decode($json, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = $decoded;
                }
            }

            $response = null;

            // Présentation de l’assistant
            if (str_contains($message, 'présente-toi')
                || str_contains($message, 'qui es-tu')
                || str_contains($message, 'parle de toi')
                || str_contains($message, 'assistant')
                || str_contains($message, 'introduce yourself')
                || str_contains($message, 'who are you')
                || str_contains($message, 'about you')) {
                $response = $lang === 'fr'
                    ? "Je suis l’assistant virtuel de Frank Landry.\nMon rôle est de vous aider à découvrir :\n- Son profil\n- Ses compétences\n- Ses projets\n- Son parcours\n- Ses coordonnées"
                    : "I am Frank Landry's virtual assistant.\nMy role is to help you discover:\n- His profile\n- His skills\n- His projects\n- His background\n- His contact details";
            }
            // Contact
            elseif (str_contains($message, 'contact')
                || str_contains($message, 'email')
                || str_contains($message, 'téléphone')
                || str_contains($message, 'joindre')
                || str_contains($message, 'reach')
                || str_contains($message, 'phone')) {
                $contact = $data['contact'] ?? [];
                $response = ($lang === 'fr' ? "Vous pouvez contacter Frank Landry :" : "You can contact Frank Landry:") . "\n"
                          . "- Email : " . ($contact['email'] ?? 'Non disponible') . "\n"
                          . "- Téléphone : " . ($contact['phone'] ?? 'Non disponible') . "\n"
                          . "- Localisation : " . ($contact['location'] ?? 'Non disponible');
            }
            // Localisation / résidence
            elseif (str_contains($message, 'où réside')
                || str_contains($message, 'localisation')
                || str_contains($message, 'habite')
                || str_contains($message, 'ville')
                || str_contains($message, 'résidence')
                || str_contains($message, 'where does')
                || str_contains($message, 'live')
                || str_contains($message, 'location')) {
                $contact = $data['contact'] ?? [];
                $response = $lang === 'fr'
                    ? "Frank Landry réside actuellement à : " . ($contact['location'] ?? 'Localisation non disponible')
                    : "Frank Landry currently resides in: " . ($contact['location'] ?? 'Location not available');
            }
            // Profil
            elseif (str_contains($message, 'qui est frank landry')
                || str_contains($message, 'à propos de frank')
                || str_contains($message, 'frank landry')
                || str_contains($message, 'who is frank landry')
                || str_contains($message, 'about frank')) {
                $response = $data['about']['summary'] ?? ($lang === 'fr'
                    ? 'Frank Landry est développeur web Full Stack.'
                    : 'Frank Landry is a Full Stack web developer.');
            }
            // Compétences
            elseif (str_contains($message, 'compétence')
                || str_contains($message, 'skills')
                || str_contains($message, 'savoir-faire')) {
                $skills = !empty($data['skills']) ? implode("\n- ", $data['skills']) : ($lang === 'fr' ? 'Non spécifiées' : 'Not specified');
                $response = $lang === 'fr'
                    ? "Les principales compétences de Frank Landry sont :\n- " . $skills
                    : "Frank Landry's main skills are:\n- " . $skills;
            }
            // Projets
            elseif (str_contains($message, 'projet')
                || str_contains($message, 'réalisation')
                || str_contains($message, 'travaux')
                || str_contains($message, 'project')
                || str_contains($message, 'work')) {
                $projects = !empty($data['projects'])
                    ? array_map(fn($p) => $p['title'] . " → " . $p['tools'], $data['projects'])
                    : [$lang === 'fr' ? 'Aucun projet disponible' : 'No projects available'];
                $response = $lang === 'fr'
                    ? "Voici quelques projets réalisés :\n- " . implode("\n- ", $projects)
                    : "Here are some completed projects:\n- " . implode("\n- ", $projects);
            }
            // Expériences
            elseif (str_contains($message, 'expérience')
                || str_contains($message, 'parcours')
                || str_contains($message, 'formation')
                || str_contains($message, 'experience')
                || str_contains($message, 'background')
                || str_contains($message, 'education')) {
                $experiences = !empty($data['experience'])
                    ? array_map(fn($e) => $e['title'] . " (" . $e['date'] . ")", $data['experience'])
                    : [$lang === 'fr' ? 'Aucune expérience disponible' : 'No experience available'];
                $response = $lang === 'fr'
                    ? "Son parcours inclut :\n- " . implode("\n- ", $experiences)
                    : "His background includes:\n- " . implode("\n- ", $experiences);
            }

            // Si aucune correspondance → IA Gemini (dans la langue détectée)
            if (!$response) {
                $response = $this->geminiService->generateResponse($validated['message'], $lang);
            }

            return response()->json([
                'success' => true,
                'message' => $response,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $lang === 'fr'
                    ? 'Une erreur est survenue lors du traitement de la requête.'
                    : 'An error occurred while processing the request.',
            ], 500);
        }
    }

    /**
     * Détection simple de la langue du message
     */
    private function detectLanguage(string $message): string
    {
        // Si présence de caractères accentués → français
        if (preg_match('/[àâçéèêëîïôûùüÿñ]/i', $message)) {
            return 'fr';
        }

        // Si mots-clés anglais détectés → anglais
        if (preg_match('/\b(who|where|skills|projects|experience|contact|live|introduce|about)\b/i', $message)) {
            return 'en';
        }

        // Par défaut → français
        return 'fr';
    }
}
