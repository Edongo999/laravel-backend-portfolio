<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\Comment;

class DashboardController extends Controller
{
    public function index()
    {
        $articlesCount = Article::count();
        $viewsTotal = Article::sum('views');
        $likesTotal = Article::withCount('likes')->get()->sum('likes_count');
        $commentsCount = Comment::count();

        return view('dashboard.index', compact(
            'articlesCount',
            'viewsTotal',
            'likesTotal',
            'commentsCount'
        ));
    }
}