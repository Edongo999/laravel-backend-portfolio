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
        $messageLower = mb_strtolower($message);

        // Valeur par défaut au cas où une erreur survient
        $lang = 'fr';

        try {
            // Détection de la langue
            $lang = $this->detectLanguage($message);

            // Chargement du fichier JSON correspondant
            $filePath = resource_path("lang/{$lang}.json");
            $data = [];

            if (file_exists($filePath)) {
                $json = file_get_contents($filePath);
                $decoded = json_decode($json, true);

                if (
                    json_last_error() === JSON_ERROR_NONE
                    && is_array($decoded)
                ) {
                    $data = $decoded;
                }
            }

            $response = null;

            /*
            |--------------------------------------------------------------------------
            | 1. PRÉSENTATION DE L'ASSISTANT
            |--------------------------------------------------------------------------
            */

            if (
                str_contains($messageLower, 'présente-toi')
                || str_contains($messageLower, 'presente-toi')
                || str_contains($messageLower, 'qui es-tu')
                || str_contains($messageLower, 'qui es tu')
                || str_contains($messageLower, 'parle de toi')
                || str_contains($messageLower, 'about you')
                || str_contains($messageLower, 'who are you')
                || str_contains($messageLower, 'introduce yourself')
            ) {
                $response = $lang === 'fr'
                    ? "Je suis l’assistant virtuel du portfolio de Frank Landry.\n\nJe peux vous renseigner sur :\n- Son profil\n- Ses compétences\n- Ses projets\n- Son parcours\n- Ses services\n- Ses coordonnées\n\nJe peux également répondre à des questions générales liées au développement web, au design graphique et à l’informatique."
                    : "I am the virtual assistant of Frank Landry's professional portfolio.\n\nI can tell you about:\n- His profile\n- His skills\n- His projects\n- His background\n- His services\n- His contact details\n\nI can also answer general questions related to web development, graphic design and IT.";
            }

            /*
            |--------------------------------------------------------------------------
            | 2. PROFIL DE FRANK LANDRY
            |--------------------------------------------------------------------------
            */

            elseif (
                str_contains($messageLower, 'qui est frank landry')
                || str_contains($messageLower, 'qui est frank')
                || str_contains($messageLower, 'a propos de frank')
                || str_contains($messageLower, 'à propos de frank')
                || str_contains($messageLower, 'parle-moi de frank')
                || str_contains($messageLower, 'parle moi de frank')
                || str_contains($messageLower, 'frank landry')
                || str_contains($messageLower, 'who is frank landry')
                || str_contains($messageLower, 'who is frank')
                || str_contains($messageLower, 'about frank')
            ) {
                $response = $data['about']['summary']
                    ?? (
                        $lang === 'fr'
                            ? "Les informations sur le profil de Frank Landry ne sont pas disponibles."
                            : "Information about Frank Landry's profile is not available."
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | 3. CONTACT
            |--------------------------------------------------------------------------
            */

            elseif (
                str_contains($messageLower, 'contact')
                || str_contains($messageLower, 'email')
                || str_contains($messageLower, 'e-mail')
                || str_contains($messageLower, 'téléphone')
                || str_contains($messageLower, 'telephone')
                || str_contains($messageLower, 'numéro')
                || str_contains($messageLower, 'numero')
                || str_contains($messageLower, 'joindre')
                || str_contains($messageLower, 'reach')
                || str_contains($messageLower, 'phone')
                || str_contains($messageLower, 'email address')
            ) {
                $contact = $data['contact'] ?? [];

                $response = $lang === 'fr'
                    ? "Vous pouvez contacter Frank Landry :\n"
                        . "- Email : " . ($contact['email'] ?? 'Non disponible') . "\n"
                        . "- Téléphone : " . ($contact['phone'] ?? 'Non disponible') . "\n"
                        . "- Localisation : " . ($contact['location'] ?? 'Non disponible')
                    : "You can contact Frank Landry:\n"
                        . "- Email: " . ($contact['email'] ?? 'Not available') . "\n"
                        . "- Phone: " . ($contact['phone'] ?? 'Not available') . "\n"
                        . "- Location: " . ($contact['location'] ?? 'Not available');
            }

            /*
            |--------------------------------------------------------------------------
            | 4. LOCALISATION
            |--------------------------------------------------------------------------
            */

            elseif (
                str_contains($messageLower, 'où réside')
                || str_contains($messageLower, 'ou reside')
                || str_contains($messageLower, 'où habite')
                || str_contains($messageLower, 'ou habite')
                || str_contains($messageLower, 'localisation')
                || str_contains($messageLower, 'ville')
                || str_contains($messageLower, 'résidence')
                || str_contains($messageLower, 'residence')
                || str_contains($messageLower, 'where does')
                || str_contains($messageLower, 'where does he live')
                || str_contains($messageLower, 'where does frank live')
                || str_contains($messageLower, 'location')
            ) {
                $contact = $data['contact'] ?? [];

                $response = $lang === 'fr'
                    ? "Frank Landry réside actuellement à : "
                        . ($contact['location'] ?? 'Localisation non disponible')
                    : "Frank Landry currently resides in: "
                        . ($contact['location'] ?? 'Location not available');
            }

            /*
            |--------------------------------------------------------------------------
            | 5. COMPÉTENCES
            |--------------------------------------------------------------------------
            */

            elseif (
                str_contains($messageLower, 'compétence')
                || str_contains($messageLower, 'competence')
                || str_contains($messageLower, 'compétences')
                || str_contains($messageLower, 'competences')
                || str_contains($messageLower, 'skills')
                || str_contains($messageLower, 'savoir-faire')
                || str_contains($messageLower, 'technologies')
                || str_contains($messageLower, 'technologie')
            ) {
                $skills = $data['skills'] ?? [];

                if (!empty($skills)) {
                    $skillsList = implode("\n- ", $skills);

                    $response = $lang === 'fr'
                        ? "Les principales compétences de Frank Landry sont :\n- " . $skillsList
                        : "Frank Landry's main skills are:\n- " . $skillsList;
                } else {
                    $response = $lang === 'fr'
                        ? "Les compétences de Frank Landry ne sont pas disponibles."
                        : "Frank Landry's skills are not available.";
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 6. PROJETS
            |--------------------------------------------------------------------------
            */

            elseif (
                str_contains($messageLower, 'projet')
                || str_contains($messageLower, 'projets')
                || str_contains($messageLower, 'réalisation')
                || str_contains($messageLower, 'realisations')
                || str_contains($messageLower, 'travaux')
                || str_contains($messageLower, 'portfolio')
                || str_contains($messageLower, 'project')
                || str_contains($messageLower, 'projects')
                || str_contains($messageLower, 'work')
            ) {
                $projects = $data['projects'] ?? [];

                if (!empty($projects)) {
                    $projectLines = [];

                    foreach ($projects as $project) {
                        $title = $project['title'] ?? 'Projet';
                        $tools = $project['tools'] ?? '';

                        $projectLines[] = $tools
                            ? $title . " → " . $tools
                            : $title;
                    }

                    $response = $lang === 'fr'
                        ? "Voici quelques projets réalisés par Frank Landry :\n- "
                            . implode("\n- ", $projectLines)
                        : "Here are some projects completed by Frank Landry:\n- "
                            . implode("\n- ", $projectLines);
                } else {
                    $response = $lang === 'fr'
                        ? "Aucun projet n'est actuellement disponible."
                        : "No projects are currently available.";
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 7. PARCOURS / FORMATION / EXPÉRIENCE
            |--------------------------------------------------------------------------
            */

            elseif (
                str_contains($messageLower, 'expérience')
                || str_contains($messageLower, 'experience')
                || str_contains($messageLower, 'parcours')
                || str_contains($messageLower, 'formation')
                || str_contains($messageLower, 'études')
                || str_contains($messageLower, 'etudes')
                || str_contains($messageLower, 'education')
                || str_contains($messageLower, 'background')
            ) {
                $experiences = $data['experience'] ?? [];

                if (!empty($experiences)) {
                    $experienceLines = [];

                    foreach ($experiences as $experience) {
                        $title = $experience['title'] ?? 'Expérience';
                        $date = $experience['date'] ?? '';

                        $experienceLines[] = $date
                            ? $title . " (" . $date . ")"
                            : $title;
                    }

                    $response = $lang === 'fr'
                        ? "Le parcours de Frank Landry comprend :\n- "
                            . implode("\n- ", $experienceLines)
                        : "Frank Landry's background includes:\n- "
                            . implode("\n- ", $experienceLines);
                } else {
                    $response = $lang === 'fr'
                        ? "Le parcours de Frank Landry n'est pas disponible."
                        : "Frank Landry's background is not available.";
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 8. SERVICES
            |--------------------------------------------------------------------------
            */

            elseif (
                str_contains($messageLower, 'service')
                || str_contains($messageLower, 'services')
                || str_contains($messageLower, 'prix')
                || str_contains($messageLower, 'tarif')
                || str_contains($messageLower, 'tarifs')
                || str_contains($messageLower, 'price')
                || str_contains($messageLower, 'prices')
            ) {
                // Pour l'instant, on laisse Gemini répondre.
                // Tu pourras plus tard ajouter les services/prix
                // directement dans fr.json et en.json.
                $response = null;
            }

            /*
            |--------------------------------------------------------------------------
            | 9. FALLBACK → GEMINI
            |--------------------------------------------------------------------------
            */

            if (!$response) {
                $response = $this->geminiService->generateResponse(
                    $message,
                    $lang
                );
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
                    ? "Une erreur est survenue lors du traitement de votre demande."
                    : "An error occurred while processing your request.",
            ], 500);
        }
    }

    /**
     * Détection simple de la langue.
     */
    private function detectLanguage(string $message): string
    {
        if (preg_match(
            '/[àâçéèêëîïôûùüÿœ]/iu',
            $message
        )) {
            return 'fr';
        }

        if (preg_match(
            '/\b(who|what|where|when|why|how|skills|projects|experience|contact|live|introduce|about|services|price|website|design)\b/i',
            $message
        )) {
            return 'en';
        }

        return 'fr';
    }
}