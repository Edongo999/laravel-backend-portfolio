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
            'lang' => ['nullable', 'string', 'in:fr,en'],
        ]);

        $originalMessage = trim($validated['message']);
        $message = mb_strtolower($originalMessage, 'UTF-8');

        try {
            /*
            |--------------------------------------------------------------------------
            | Détection de la langue
            |--------------------------------------------------------------------------
            */

            $lang = $validated['lang'] ?? $this->detectLanguage($message);

            /*
            |--------------------------------------------------------------------------
            | Chargement des données du portfolio
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | 1. PROJETS
            |--------------------------------------------------------------------------
            | Cette vérification vient avant "Frank Landry" afin d'éviter
            | qu'une question contenant son nom soit classée comme "profil".
            |--------------------------------------------------------------------------
            */

            if (
                $this->containsAny($message, [
                    'projet',
                    'projets',
                    'réalisation',
                    'réalisations',
                    'travaux',
                    'portfolio',
                    'project',
                    'projects',
                    'work',
                    'works'
                ])
            ) {
                $projects = $data['projects'] ?? [];

                if (!empty($projects)) {
                    $lines = [];

                    foreach ($projects as $project) {
                        $title = $project['title'] ?? '';
                        $description = $project['description'] ?? '';
                        $tools = $project['tools'] ?? '';

                        $lines[] =
                            "• {$title}\n" .
                            "  {$description}\n" .
                            "  Technologies : {$tools}";
                    }

                    $response = $lang === 'fr'
                        ? "Voici quelques projets réalisés par Frank Landry :\n\n" . implode("\n\n", $lines)
                        : "Here are some projects developed by Frank Landry:\n\n" . implode("\n\n", $lines);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 2. COMPÉTENCES
            |--------------------------------------------------------------------------
            */

            elseif (
                $this->containsAny($message, [
                    'compétence',
                    'compétences',
                    'skill',
                    'skills',
                    'savoir-faire',
                    'maîtrise',
                    'technologie',
                    'technologies',
                    'technology'
                ])
            ) {
                $skills = $data['skills'] ?? [];

                if (!empty($skills)) {
                    $response = $lang === 'fr'
                        ? "Frank Landry possède notamment les compétences suivantes :\n\n• "
                            . implode("\n• ", $skills)
                        : "Frank Landry has the following skills:\n\n• "
                            . implode("\n• ", $skills);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 3. PARCOURS / EXPÉRIENCE / FORMATION
            |--------------------------------------------------------------------------
            */

            elseif (
                $this->containsAny($message, [
                    'expérience',
                    'expériences',
                    'parcours',
                    'formation',
                    'formations',
                    'étude',
                    'études',
                    'diplôme',
                    'diplômes',
                    'experience',
                    'experiences',
                    'background',
                    'education',
                    'studies',
                    'degree'
                ])
            ) {
                $experiences = $data['experience'] ?? [];

                if (!empty($experiences)) {
                    $lines = [];

                    foreach ($experiences as $experience) {
                        $title = $experience['title'] ?? '';
                        $place = $experience['place'] ?? '';
                        $date = $experience['date'] ?? '';

                        $lines[] = "• {$title}\n  {$place}\n  {$date}";
                    }

                    $response = $lang === 'fr'
                        ? "Le parcours de Frank Landry comprend notamment :\n\n" . implode("\n\n", $lines)
                        : "Frank Landry's background includes:\n\n" . implode("\n\n", $lines);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 4. SERVICES
            |--------------------------------------------------------------------------
            */

            elseif (
                $this->containsAny($message, [
                    'service',
                    'services',
                    'prestation',
                    'prestations',
                    'tarif',
                    'tarifs',
                    'prix',
                    'coût',
                    'cout',
                    'price',
                    'prices',
                    'service'
                ])
            ) {
                $services = $data['services'] ?? [];

                if (!empty($services)) {
                    $lines = [];

                    foreach ($services as $service) {
                        $name = $service['name'] ?? '';
                        $description = $service['description'] ?? '';
                        $price = $service['price'] ?? '';

                        $lines[] =
                            "• {$name}\n" .
                            "  {$description}\n" .
                            "  Tarif : {$price}";
                    }

                    $response = $lang === 'fr'
                        ? "Frank Landry propose notamment les services suivants :\n\n"
                            . implode("\n\n", $lines)
                        : "Frank Landry offers the following services:\n\n"
                            . implode("\n\n", $lines);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 5. CONTACT
            |--------------------------------------------------------------------------
            */

            elseif (
                $this->containsAny($message, [
                    'contact',
                    'contacter',
                    'joindre',
                    'email',
                    'e-mail',
                    'mail',
                    'téléphone',
                    'telephone',
                    'numéro',
                    'numero',
                    'phone',
                    'reach',
                    'email address'
                ])
            ) {
                $contact = $data['contact'] ?? [];

                $email = $contact['email'] ?? 'Non disponible';
                $phone = $contact['phone'] ?? 'Non disponible';
                $location = $contact['location'] ?? 'Non disponible';

                $response = $lang === 'fr'
                    ? "Vous pouvez contacter Frank Landry avec les coordonnées suivantes :\n\n"
                        . "• Email : {$email}\n"
                        . "• Téléphone : {$phone}\n"
                        . "• Localisation : {$location}"
                    : "You can contact Frank Landry using the following details:\n\n"
                        . "• Email: {$email}\n"
                        . "• Phone: {$phone}\n"
                        . "• Location: {$location}";
            }

            /*
            |--------------------------------------------------------------------------
            | 6. LOCALISATION
            |--------------------------------------------------------------------------
            */

            elseif (
                $this->containsAny($message, [
                    'où habite',
                    'ou habite',
                    'où réside',
                    'ou reside',
                    'habite',
                    'réside',
                    'reside',
                    'ville',
                    'localisation',
                    'location',
                    'where does',
                    'where is he',
                    'live'
                ])
            ) {
                $location = $data['contact']['location'] ?? (
                    $lang === 'fr'
                        ? 'Localisation non disponible'
                        : 'Location not available'
                );

                $response = $lang === 'fr'
                    ? "Frank Landry réside actuellement à {$location}."
                    : "Frank Landry is currently based in {$location}.";
            }

            /*
            |--------------------------------------------------------------------------
            | 7. PRÉSENTATION / PROFIL
            |--------------------------------------------------------------------------
            */

            elseif (
                $this->containsAny($message, [
                    'qui est frank',
                    'qui est frank landry',
                    'présente frank',
                    'présenter frank',
                    'parle de frank',
                    'à propos de frank',
                    'a propos de frank',
                    'profil de frank',
                    'présentation de frank',
                    'about frank',
                    'who is frank',
                    'introduce frank',
                    'frank landry'
                ])
            ) {
                $about = $data['about'] ?? [];

                $name = $about['name'] ?? 'Frank Landry';
                $title = $about['title'] ?? '';
                $summary = $about['summary'] ?? '';

                $response = $lang === 'fr'
                    ? "{$name} est {$title}.\n\n{$summary}"
                    : "{$name} is a {$title}.\n\n{$summary}";
            }

            /*
            |--------------------------------------------------------------------------
            | 8. PRÉSENTATION DE L'ASSISTANT
            |--------------------------------------------------------------------------
            */

            elseif (
                $this->containsAny($message, [
                    'qui es-tu',
                    'qui es tu',
                    'présente-toi',
                    'presente toi',
                    'présente toi',
                    'parle de toi',
                    'quel est ton rôle',
                    'quel est ton role',
                    'who are you',
                    'introduce yourself',
                    'what can you do'
                ])
            ) {
                $response = $lang === 'fr'
                    ? "Je suis l'assistant virtuel de Frank Landry. Je peux vous renseigner sur son profil, ses compétences, ses projets, son parcours, ses services et ses coordonnées. Je peux également répondre à certaines questions générales liées au développement web, au design graphique et à l'informatique."
                    : "I am Frank Landry's virtual assistant. I can provide information about his profile, skills, projects, background, services and contact details. I can also answer general questions related to web development, graphic design and IT.";
            }

            /*
            |--------------------------------------------------------------------------
            | 9. FALLBACK GEMINI
            |--------------------------------------------------------------------------
            */

            if (!$response) {
                $response = $this->geminiService->generateResponse(
                    $originalMessage,
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
     * Vérifie si le message contient au moins un des termes recherchés.
     */
    private function containsAny(string $message, array $terms): bool
    {
        foreach ($terms as $term) {
            if (str_contains($message, mb_strtolower($term, 'UTF-8'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détection simple de la langue lorsque le frontend
     * ne transmet pas "lang".
     */
    private function detectLanguage(string $message): string
    {
        if (preg_match('/[àâçéèêëîïôûùüÿœ]/iu', $message)) {
            return 'fr';
        }

        if (preg_match(
            '/\b(who|where|what|which|skills|projects|experience|contact|live|introduce|about|services|price|work)\b/i',
            $message
        )) {
            return 'en';
        }

        return 'fr';
    }
}