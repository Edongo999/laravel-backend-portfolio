<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Stichoza\GoogleTranslate\GoogleTranslate;

class ArticleController extends Controller
{

    // =====================================================
// LISTE PAGINÉE (Dashboard) - Supabase Storage
// =====================================================
public function index(Request $request)
{
    $perPage = $request->get('per_page', 10);

    $articles = Article::orderBy('created_at', 'desc')
        ->paginate($perPage);

    // ✅ L'image est déjà une URL publique Supabase
    $articles->getCollection()->transform(function ($article) {
        $article->image = $article->image ?: null;
        return $article;
    });

    return response()->json($articles, 200, [], JSON_UNESCAPED_UNICODE);
}


// =====================================================
// LISTE SIMPLE (Portfolio public) - Supabase Storage
// =====================================================
public function publicIndex(Request $request)
{
    $lang = $request->get('lang', 'fr'); // par défaut français

    $articles = Article::where('archived', 0)
        ->orderBy('created_at', 'desc')
        ->get();

    $articles = $articles->map(function ($article) use ($lang) {
        return [
            'id' => $article->id,
            // ✅ Fallback : si la traduction est vide, on prend la version FR ou le champ original
            'title' => $lang === 'en'
                ? ($article->title_en ?? $article->title_fr ?? $article->title)
                : ($article->title_fr ?? $article->title),
            'content' => $lang === 'en'
                ? ($article->content_en ?? $article->content_fr ?? $article->content)
                : ($article->content_fr ?? $article->content),
            'category' => $article->category,
            // ✅ L'image est déjà une URL publique Supabase
            'image' => $article->image ?: null,
            'created_at' => $article->created_at,
        ];
    });

    return response()->json(['data' => $articles], 200, [], JSON_UNESCAPED_UNICODE);
}


// =====================================================
// PUBLIER UN ARTICLE (Supabase Storage)
// =====================================================
public function store(Request $request)
{
    $request->validate([
        'title'    => 'required|string|max:255',
        'content'  => 'required|string',
        'category' => 'required|string|in:Tech,Design,Actu',
        'image'    => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4|max:51200', // 50 MB max
    ]);

    // Vérifier si l'article existe déjà
    $exists = Article::where('title', $request->title)
        ->where('content', $request->content)
        ->exists();

    if ($exists) {
        return response()->json([
            'message' => 'Cet article existe déjà.'
        ], 422);
    }

    // Données de base
    $data = $request->only('title', 'content', 'category');

    // Version française
    $data['title_fr'] = $request->title;
    $data['content_fr'] = $request->content;

    // Pas encore de traduction anglaise
    $data['title_en'] = null;
    $data['content_en'] = null;

    // Upload de l'image vers Supabase Storage
    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $fileName = uniqid().'.'.$file->getClientOriginalExtension();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('SUPABASE_KEY'),
            'Content-Type' => 'multipart/form-data',
        ])->attach(
            'file', file_get_contents($file), $fileName
        )->post(env('SUPABASE_URL').'/storage/v1/object/articles/'.$fileName);

        if ($response->failed()) {
            return response()->json(['error' => 'Upload vers Supabase échoué'], 500);
        }

        // URL publique Supabase
        $data['image'] = env('SUPABASE_URL').'/storage/v1/object/public/articles/'.$fileName;
    }

    // Création immédiate
    $article = Article::create($data);

    // Notification
    Notification::create([
        'type' => 'article',
        'message' => 'Nouvel article publié : ' . $article->title,
        'user_id' => $request->user()->id,
    ]);

    return response()->json([
        'message' => 'Article publié avec succès',
        'article' => $article->fresh(),
    ], 201, [], JSON_UNESCAPED_UNICODE);
}

// =====================================================
// MODIFIER UN ARTICLE (Supabase Storage)
// =====================================================
public function update(Request $request, Article $article)
{
    $request->validate([
        'title'    => 'required|string|max:255',
        'content'  => 'required|string',
        'category' => 'required|string|in:Tech,Design,Actu',
        'image'    => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4|max:51200', // 50 MB max
    ]);

    // Données de base
    $data = $request->only('title', 'content', 'category');

    // Nouvelle version française
    $data['title_fr'] = $request->title;
    $data['content_fr'] = $request->content;

    // La traduction précédente n'est plus forcément valide
    $data['title_en'] = null;
    $data['content_en'] = null;

    // Remplacer l'image si nécessaire
    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $fileName = uniqid().'.'.$file->getClientOriginalExtension();

        // Upload vers Supabase Storage (bucket articles)
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('SUPABASE_KEY'),
            'Content-Type' => 'multipart/form-data',
        ])->attach(
            'file', file_get_contents($file), $fileName
        )->post(env('SUPABASE_URL').'/storage/v1/object/articles/'.$fileName);

        if ($response->failed()) {
            return response()->json(['error' => 'Upload vers Supabase échoué'], 500);
        }

        // URL publique Supabase
        $data['image'] = env('SUPABASE_URL').'/storage/v1/object/public/articles/'.$fileName;
    }

    // Mise à jour immédiate
    $article->update($data);

    // Notification
    Notification::create([
        'type' => 'article',
        'message' => 'Article modifié : ' . $article->title,
        'user_id' => $request->user()->id,
    ]);

    return response()->json([
        'message' => 'Article modifié avec succès',
        'article' => $article->fresh(),
    ], 200, [], JSON_UNESCAPED_UNICODE);
}

   // =====================================================
// SUPPRIMER UN ARTICLE (Supabase Storage)
// =====================================================
public function destroy(Article $article, Request $request)
{
    // Garder le titre avant suppression
    $articleTitle = $article->title;

    // Supprimer l'image dans Supabase si elle existe
    if ($article->image) {
        // Extraire le nom du fichier depuis l'URL publique Supabase
        $filePath = basename($article->image);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('SUPABASE_KEY'),
        ])->delete(env('SUPABASE_URL').'/storage/v1/object/articles/'.$filePath);

        if ($response->failed()) {
            \Log::error('Erreur suppression image Supabase', [
                'file' => $filePath,
                'error' => $response->body(),
            ]);
        }
    }

    // Supprimer l'article
    $article->delete();

    // Notification
    Notification::create([
        'type' => 'article',
        'message' => 'Article supprimé : ' . $articleTitle,
        'user_id' => $request->user()->id,
    ]);

    return response()->json([
        'message' => 'Article supprimé avec succès'
    ], 200, [], JSON_UNESCAPED_UNICODE);
}



   // =====================================================
// ARCHIVER UN ARTICLE (Supabase Storage)
// =====================================================
public function archive(Article $article, Request $request)
{
    $article->update([
        'archived' => true
    ]);

    Notification::create([
        'type' => 'article',
        'message' => 'Article archivé : ' . $article->title,
        'user_id' => $request->user()->id,
    ]);

    //  L'image est déjà une URL publique Supabase, inutile de transformer
    return response()->json([
        'message' => 'Article archivé',
        'article' => $article->fresh(),
    ], 200, [], JSON_UNESCAPED_UNICODE);
}

// =====================================================
// DÉSARCHIVER UN ARTICLE (Supabase Storage)
// =====================================================
public function unarchive(Article $article, Request $request)
{
    $article->update([
        'archived' => false
    ]);

    Notification::create([
        'type' => 'article',
        'message' => 'Article désarchivé : ' . $article->title,
        'user_id' => $request->user()->id,
    ]);

    // ✅ L'image est déjà une URL publique Supabase, inutile de transformer
    return response()->json([
        'message' => 'Article désarchivé',
        'article' => $article->fresh(),
    ], 200, [], JSON_UNESCAPED_UNICODE);
}



    // =====================================================
    // STATISTIQUES DU DASHBOARD
    // =====================================================

    public function stats()
    {
        // =================================================
        // TOTAL DES ARTICLES
        // =================================================

        $totalArticles = Article::count();


        // =================================================
        // NOMBRE DE CATÉGORIES
        // =================================================

        $categoriesCount = Article::distinct('category')
            ->count('category');


        // =================================================
        // ARTICLES PUBLIÉS CE MOIS
        // =================================================

        $articlesThisMonth = Article::whereMonth(
            'created_at',
            now()->month
        )
            ->whereYear(
                'created_at',
                now()->year
            )
            ->count();


        // =================================================
        // ARTICLES PAR MOIS
        // =================================================
                    $articlesByMonth = Article::selectRaw(
                'EXTRACT(MONTH FROM created_at) as month_number, COUNT(*) as articles'
            )
                ->whereYear('created_at', now()->year)
                ->groupBy('month_number')
                ->orderBy('month_number')
                ->get();
        // =================================================
        // NOM DES MOIS
        // =================================================

        $months = [
            1  => 'Jan',
            2  => 'Fév',
            3  => 'Mar',
            4  => 'Avr',
            5  => 'Mai',
            6  => 'Juin',
            7  => 'Juil',
            8  => 'Août',
            9  => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Déc',
        ];


        // =================================================
        // CONSTRUIRE LES 12 MOIS
        // =================================================

        $lineData = collect(range(1, 12))
            ->map(function ($month) use (
                $articlesByMonth,
                $months
            ) {

                $result = $articlesByMonth->firstWhere(
                    'month_number',
                    $month
                );

                return [
                    'month' => $months[$month],
                    'articles' => $result
                        ? (int) $result->articles
                        : 0,
                ];
            })
            ->values();


        // =================================================
        // ARTICLES PAR CATÉGORIE
        // =================================================

        $articlesByCategory = Article::selectRaw(
            'category, COUNT(*) as count'
        )
            ->groupBy('category')
            ->orderBy('category')
            ->get()
            ->map(function ($item) {

                return [
                    'category' => $item->category,
                    'count' => (int) $item->count,
                ];

            })
            ->values();


        // =================================================
        // RÉPONSE API
        // =================================================

        return response()->json([

            // Cartes statistiques
            'totalArticles' => $totalArticles,

            'categoriesCount' => $categoriesCount,

            'articlesThisMonth' => $articlesThisMonth,


            // Graphique mensuel
            'articlesByMonth' => $lineData,


            // Graphique catégories
            'articlesByCategory' => $articlesByCategory,

       ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}