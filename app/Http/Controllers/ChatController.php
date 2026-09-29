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

        try {
            $response = $this->geminiService->generateResponse(
                $validated['message']
            );

            return response()->json([
                'success' => true,
                'message' => $response,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la communication avec l\'IA.',
            ], 500);
        }
    }
}
