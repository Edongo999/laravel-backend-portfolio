<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('users', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $imagePath,
        ]);

        $user->image_url = $user->image
            ? asset('storage/' . $user->image)
            : null;

        // =================================================
        // CRÉATION DU TOKEN SANCTUM
        // =================================================

        $token = $user->createToken('dashboard-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Inscription réussie',
            'token' => $token,
            'user' => $user,
        ], 201);
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
    // 5 tentatives maximum par minute
    // par IP + adresse email
    // =================================================

    $email = Str::lower($request->email);
    $key = 'login|' . $request->ip() . '|' . $email;

    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);

        return response()->json([
            'status' => 'error',
            'message' => 'Trop de tentatives. Veuillez réessayer dans ' . $seconds . ' secondes.',
        ], 429);
    }

    $user = User::where('email', $email)->first();

    // Vérification des identifiants
    if (!$user || !Hash::check($request->password, $user->password)) {

        RateLimiter::hit($key, 60);

        return response()->json([
            'status' => 'error',
            'message' => 'Email ou mot de passe incorrect',
        ], 401);
    }

    // Connexion réussie → réinitialiser le compteur
    RateLimiter::clear($key);

    // =================================================
    // SUPPRIMER LES ANCIENS TOKENS
    // =================================================

    $user->tokens()->delete();

    // =================================================
    // CRÉER UN NOUVEAU TOKEN
    // =================================================

    $token = $user->createToken('dashboard-token')->plainTextToken;

    // =================================================
    // IMAGE
    // =================================================

    $user->image_url = $user->image
        ? asset('storage/' . $user->image)
        : null;

    return response()->json([
        'status' => 'success',
        'message' => 'Connexion réussie',
        'token' => $token,
        'user' => $user,
    ]);
}



    // =====================================================
    // UTILISATEUR PAR EMAIL
    // =====================================================
    public function userByEmail(Request $request)
{
    $request->validate([
        'email' => 'required|email',
    ]);

    $user = User::where('email', $request->email)->first();

    $imageUrl = $user && $user->image
        ? asset('storage/' . $user->image)
        : '/images/default-avatar2.webp';

    return response()->json([
        'image_url' => $imageUrl,
    ]);
}




    // =====================================================
    // UTILISATEUR CONNECTÉ
    // =====================================================

    public function user(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non authentifié',
            ], 401);
        }

        $user->image_url = $user->image
            ? asset('storage/' . $user->image)
            : null;

        return response()->json([
            'user' => $user,
        ]);
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
        ]);
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
                'message' => 'Lien de réinitialisation envoyé à votre email',
            ])
            : response()->json([
                'error' => 'Impossible d’envoyer le lien',
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
                    'password' => Hash::make($password),
                ])->save();

                // Sécurité :
                // supprimer les anciens tokens après changement
                // de mot de passe.
                $user->tokens()->delete();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json([
                'message' => 'Mot de passe réinitialisé avec succès',
            ])
            : response()->json([
                'error' => 'Token invalide ou expiré',
            ], 400);
    }
}
