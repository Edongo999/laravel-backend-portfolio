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
    // LISTE PAGINÉE (Dashboard)
    // =====================================================
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $articles = Article::orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($articles);
    }

    // =====================================================
    // LISTE SIMPLE (Portfolio public)
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
                'image' => $article->image,
                'created_at' => $article->created_at,
            ];
        });

        return response()->json(['data' => $articles], 200);
    }



 // =====================================================
// PUBLIER UN ARTICLE
// =====================================================
public function store(Request $request)
{
    $request->validate([
        'title'    => 'required|string|max:255',
        'content'  => 'required|string',
        'category' => 'required|string|in:Tech,Design,Actu',
        'image'    => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
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
    $data = $request->only(
        'title',
        'content',
        'category'
    );

    // Version française
    $data['title_fr'] = $request->title;
    $data['content_fr'] = $request->content;

    // Pas encore de traduction anglaise
    $data['title_en'] = null;
    $data['content_en'] = null;

    // Upload de l'image
    if ($request->hasFile('image')) {
        $data['image'] = $request->file('image')
            ->store('articles', 'public');
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
    ], 201);
}

// =====================================================
// MODIFIER UN ARTICLE
// =====================================================
public function update(Request $request, Article $article)
{
    $request->validate([
        'title'    => 'required|string|max:255',
        'content'  => 'required|string',
        'category' => 'required|string|in:Tech,Design,Actu',
        'image'    => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
    ]);

    // Données de base
    $data = $request->only(
        'title',
        'content',
        'category'
    );

    // Nouvelle version française
    $data['title_fr'] = $request->title;
    $data['content_fr'] = $request->content;

    // La traduction précédente n'est plus forcément valide
    $data['title_en'] = null;
    $data['content_en'] = null;

    // Remplacer l'image si nécessaire
    if ($request->hasFile('image')) {

        if ($article->image) {
            Storage::disk('public')->delete($article->image);
        }

        $data['image'] = $request->file('image')
            ->store('articles', 'public');
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
    ], 200);
}

// =====================================================
// TRADUIRE UN ARTICLE EN ANGLAIS
// =====================================================
public function translate(Article $article)
{
    try {
        $trEn = new GoogleTranslate('en');

        // S'assurer que le français existe
        $article->title_fr = $article->title;
        $article->content_fr = $article->content;

        // Traduction du titre
        $article->title_en = $trEn->translate(
            $article->title_fr
        );

        // Traduction du contenu
        $article->content_en = $trEn->translate(
            $article->content_fr
        );

        $article->save();

        return response()->json([
            'message' => 'Article traduit avec succès',
            'article' => $article->fresh(),
        ], 200);

    } catch (\Throwable $e) {

        \Log::error('Erreur de traduction', [
            'article_id' => $article->id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'message' => 'Impossible de traduire l’article.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    // =====================================================
    // SUPPRIMER UN ARTICLE
    // =====================================================

    public function destroy(Article $article, Request $request)
    {
        // Garder le titre avant suppression
        $articleTitle = $article->title;

        // Supprimer l'image
        if ($article->image) {
            Storage::disk('public')->delete($article->image);
        }

        // Supprimer l'article
        $article->delete();

        // =================================================
        // NOTIFICATION
        // =================================================

        Notification::create([
            'type' => 'article',
            'message' => 'Article supprimé : ' . $articleTitle,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Article supprimé avec succès'
        ]);
    }


    // =====================================================
    // ARCHIVER UN ARTICLE
    // =====================================================

    public function archive(Article $article, Request $request)
    {
        $article->update([
            'archived' => true
        ]);

        // =================================================
        // NOTIFICATION
        // =================================================

        Notification::create([
            'type' => 'article',
            'message' => 'Article archivé : ' . $article->title,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Article archivé',
            'article' => $article
        ]);
    }


    // =====================================================
    // DÉSARCHIVER UN ARTICLE
    // =====================================================

    public function unarchive(Article $article, Request $request)
    {
        $article->update([
            'archived' => false
        ]);

        // =================================================
        // NOTIFICATION
        // =================================================

        Notification::create([
            'type' => 'article',
            'message' => 'Article désarchivé : ' . $article->title,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Article désarchivé',
            'article' => $article
        ]);
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

        ]);
    }
}