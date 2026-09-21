<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\Notification; // ✅ Import du modèle Notification

class UserController extends Controller
{
    // ✅ Récupérer les informations de l'utilisateur connecté
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->image = $user->image ? Storage::url($user->image) : null;

        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    }

    // ✅ Modifier le nom
    public function updateName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $user->name = $request->name;
        $user->save();

        // 🔔 Notification
        Notification::create([
            'type' => 'user',
            'message' => 'Votre nom a été mis à jour avec succès',
            'user_id' => $user->id,
        ]);

        $user->image = $user->image ? Storage::url($user->image) : null;

        return response()->json([
            'message' => 'Nom mis à jour avec succès',
            'user' => $user
        ]);
    }

    // ✅ Modifier la photo
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $path = $request->file('photo')->store('users', 'public');

        $user = $request->user();
        $user->image = $path;
        $user->save();

        // 🔔 Notification
        Notification::create([
            'type' => 'user',
            'message' => 'Votre photo de profil a été modifiée',
            'user_id' => $user->id,
        ]);

        $user->image = Storage::url($user->image);

        return response()->json([
            'message' => 'Photo mise à jour avec succès',
            'user' => $user
        ]);
    }

    // ✅ Modifier le mot de passe
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Mot de passe actuel incorrect'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        // 🔔 Notification
        Notification::create([
            'type' => 'user',
            'message' => 'Votre mot de passe a été changé',
            'user_id' => $user->id,
        ]);

        return response()->json(['message' => 'Mot de passe mis à jour avec succès']);
    }
}