<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;

class UserController extends Controller
{
    // =====================================================
    // RÉCUPÉRER LES INFORMATIONS DE L'UTILISATEUR CONNECTÉ
    // =====================================================

    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'user' => $user,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    // =====================================================
    // MODIFIER LE NOM
    // =====================================================

    public function updateName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();

        $user->name = $request->name;
        $user->save();

        //  Notification
        Notification::create([
            'type' => 'user',
            'message' => 'Votre nom a été mis à jour avec succès',
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Nom mis à jour avec succès',
            'user' => $user->fresh(),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    // =====================================================
    // MODIFIER LA PHOTO DE PROFIL
    // SUPABASE STORAGE → BUCKET users
    // =====================================================

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = $request->user();

        $file = $request->file('photo');

        // =================================================
        // NOM UNIQUE DU FICHIER
        // =================================================

        $extension = $file->getClientOriginalExtension();

        $fileName = 'user_' . $user->id . '_' . uniqid() . '.' . $extension;


        // =================================================
        // SUPPRIMER L'ANCIENNE PHOTO SUPABASE
        // =================================================

        if ($user->image) {

            $oldFileName = basename(parse_url($user->image, PHP_URL_PATH));

            if ($oldFileName) {

                $deleteResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
                ])->delete(
                    env('SUPABASE_URL') .
                    '/storage/v1/object/users/' .
                    $oldFileName
                );

                if ($deleteResponse->failed()) {
                    Log::warning(
                        'Impossible de supprimer l\'ancienne photo utilisateur Supabase',
                        [
                            'user_id' => $user->id,
                            'file' => $oldFileName,
                            'status' => $deleteResponse->status(),
                            'body' => $deleteResponse->body(),
                        ]
                    );
                }
            }
        }


        // =================================================
        // UPLOAD VERS SUPABASE
        // =================================================

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


        // =================================================
        // VÉRIFIER L'UPLOAD
        // =================================================

        if ($response->failed()) {

            Log::error(
                'Upload photo utilisateur vers Supabase échoué',
                [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]
            );

            return response()->json([
                'message' => 'Impossible d\'envoyer la photo vers Supabase.',
            ], 500);
        }


        // =================================================
        // URL PUBLIQUE SUPABASE
        // =================================================

        $imageUrl =
            env('SUPABASE_URL') .
            '/storage/v1/object/public/users/' .
            $fileName;


        // =================================================
        // ENREGISTRER L'URL DANS LA BASE
        // =================================================

        $user->image = $imageUrl;
        $user->save();


        // =================================================
        // NOTIFICATION
        // =================================================

        Notification::create([
            'type' => 'user',
            'message' => 'Votre photo de profil a été modifiée',
            'user_id' => $user->id,
        ]);


        // =================================================
        // RÉPONSE
        // =================================================

        return response()->json([
            'message' => 'Photo mise à jour avec succès',
            'user' => $user->fresh(),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }


    // =====================================================
    // MODIFIER LE MOT DE PASSE
    // =====================================================

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check(
            $request->current_password,
            $user->password
        )) {
            return response()->json([
                'error' => 'Mot de passe actuel incorrect',
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        //  Notification
        Notification::create([
            'type' => 'user',
            'message' => 'Votre mot de passe a été changé',
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
