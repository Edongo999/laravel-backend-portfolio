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

        // =================================================
        // TOKEN SANCTUM
        // =================================================
        //
        // On supprime les anciens tokens de connexion
        // pour éviter d'accumuler des tokens actifs.
        //

        $user->tokens()->delete();

        $token = $user
            ->createToken('dashboard-token')
            ->plainTextToken;

        // =================================================
        // IMAGE
        // =================================================

        $user->image_url = $user->image ?: null;

        // =================================================
        // RÉPONSE
        // =================================================

        return response()->json([
            'status' => 'success',
            'message' => 'Connexion réussie',
            'token' => $token,
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
        $user = $request->user();

        if ($user) {

            // Supprimer uniquement le token utilisé
            $currentToken = $user->currentAccessToken();

            if ($currentToken) {
                $currentToken->delete();
            }
        }

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

                // Révoquer tous les anciens tokens
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