<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // =====================================================
    // INSCRIPTION
    // =====================================================

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $imageUrl = null;

        // =================================================
        // UPLOAD PHOTO VERS SUPABASE
        // =================================================

        if ($request->hasFile('image')) {

            $file = $request->file('image');

            $extension = $file->getClientOriginalExtension();

            $fileName =
                'user_' .
                uniqid() .
                '.' .
                $extension;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
            ])->attach(
                'file',
                file_get_contents($file),
                $fileName
            )->post(
                env('SUPABASE_URL') .
                '/storage/v1/object/users/' .
                $fileName
            );

            if ($response->failed()) {

                Log::error(
                    'Upload photo utilisateur lors de l\'inscription échoué',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return response()->json([
                    'message' =>
                        'Impossible d\'envoyer la photo vers Supabase.',
                ], 500);
            }

            $imageUrl =
                env('SUPABASE_URL') .
                '/storage/v1/object/public/users/' .
                $fileName;
        }

        // =================================================
        // CRÉATION UTILISATEUR
        // =================================================

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $imageUrl,
        ]);

        // =================================================
        // IMAGE URL
        // =================================================

        $user->image_url = $user->image ?: null;

        // =================================================
        // PAS DE TOKEN
        // =================================================
        // L'authentification utilise désormais
        // les sessions Laravel + cookies HttpOnly.

        return response()->json([
            'status' => 'success',
            'message' => 'Inscription réussie',
            'user' => $user,
        ], 201, [], JSON_UNESCAPED_UNICODE);
    }


    // =====================================================
    // CONNEXION
    // =====================================================

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // =================================================
        // PROTECTION CONTRE LES TENTATIVES RÉPÉTÉES
        // =================================================

        $email = Str::lower($request->email);

        $key =
            'login|' .
            $request->ip() .
            '|' .
            $email;

        if (RateLimiter::tooManyAttempts($key, 5)) {

            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'status' => 'error',
                'message' =>
                    'Trop de tentatives. Veuillez réessayer dans ' .
                    $seconds .
                    ' secondes.',
            ], 429);
        }

        // =================================================
        // RECHERCHER L'UTILISATEUR
        // =================================================

        $user = User::where('email', $email)->first();

        // =================================================
        // VÉRIFIER LES IDENTIFIANTS
        // =================================================

        if (
            !$user ||
            !Hash::check(
                $request->password,
                $user->password
            )
        ) {

            RateLimiter::hit($key, 60);

            return response()->json([
                'status' => 'error',
                'message' => 'Email ou mot de passe incorrect',
            ], 401);
        }

        // =================================================
        // CONNEXION RÉUSSIE
        // =================================================

        RateLimiter::clear($key);

        // Authentification par session Laravel
        auth()->login($user);

        // Régénération de l'identifiant de session
        // pour éviter la fixation de session.
        $request->session()->regenerate();

        // =================================================
        // IMAGE
        // =================================================

        $user->image_url = $user->image ?: null;

        return response()->json([
            'status' => 'success',
            'message' => 'Connexion réussie',
            'user' => $user,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    // =====================================================
    // UTILISATEUR PAR EMAIL
    // =====================================================

    public function userByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where(
            'email',
            $request->email
        )->first();

        $imageUrl =
            $user && $user->image
                ? $user->image
                : '/images/default-avatar2.webp';

        return response()->json([
            'image_url' => $imageUrl,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    // =====================================================
    // UTILISATEUR CONNECTÉ
    // =====================================================

    public function user(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' =>
                    'Utilisateur non authentifié',
            ], 401);
        }

        // =================================================
        // IMAGE
        // =================================================

        $user->image_url =
            $user->image
                ? $user->image
                : null;

        return response()->json([
            'user' => $user,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    // =====================================================
    // DÉCONNEXION
    // =====================================================

    public function logout(Request $request)
    {
        // Déconnexion de la session Laravel
        auth()->logout();

        // Invalider complètement la session
        $request->session()->invalidate();

        // Générer un nouveau token CSRF
        $request->session()->regenerateToken();

        return response()->json([
            'status' => 'success',
            'message' => 'Déconnexion réussie',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    // =====================================================
    // MOT DE PASSE OUBLIÉ
    // =====================================================

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT

            ? response()->json([
                'message' =>
                    'Lien de réinitialisation envoyé à votre email',
            ])

            : response()->json([
                'error' =>
                    'Impossible d’envoyer le lien',
            ], 400);
    }


    // =====================================================
    // RÉINITIALISATION DU MOT DE PASSE
    // =====================================================

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),

            function ($user, $password) {

                $user->forceFill([
                    'password' =>
                        Hash::make($password),
                ])->save();

                // Les anciens tokens Sanctum ne sont plus
                // utilisés par l'authentification actuelle.
                $user->tokens()->delete();
            }
        );

        return $status === Password::PASSWORD_RESET

            ? response()->json([
                'message' =>
                    'Mot de passe réinitialisé avec succès',
            ])

            : response()->json([
                'error' =>
                    'Token invalide ou expiré',
            ], 400);
    }
}