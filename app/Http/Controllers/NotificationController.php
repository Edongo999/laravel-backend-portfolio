<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Récupérer uniquement les notifications non lues
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->where('read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    // Marquer une notification comme lue
    public function markAsRead(Request $request, Notification $notification)
    {
        // Vérifier que la notification appartient bien
        // à l'utilisateur connecté
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Accès non autorisé.'
            ], 403);
        }

        $notification->update([
            'read' => true
        ]);

        return response()->json([
            'message' => 'Notification marquée comme lue',
            'notification' => $notification
        ]);
    }

    // Marquer toutes les notifications comme lues
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('read', false)
            ->update([
                'read' => true
            ]);

        return response()->json([
            'message' => 'Toutes les notifications ont été marquées comme lues.'
        ]);
    }
}
