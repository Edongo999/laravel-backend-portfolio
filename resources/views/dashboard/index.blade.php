@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h2 class="text-2xl font-bold mb-2">Bienvenue sur ton Dashboard, {{ Auth::user()->name }}</h2>
    <p class="text-gray-600 mb-6">Tu es connecté avec l’email : {{ Auth::user()->email }}</p>

    <!-- 📊 Statistiques -->
    <h3 class="text-xl font-semibold mb-4">📊 Statistiques</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Articles -->
        <div class="bg-blue-500 text-white rounded-lg shadow p-6">
            <h4 class="text-lg font-medium">Articles publiés</h4>
            <p class="text-3xl font-bold">{{ $articlesCount }}</p>
        </div>

        <!-- Vues -->
        <div class="bg-green-500 text-white rounded-lg shadow p-6">
            <h4 class="text-lg font-medium">Vues totales</h4>
            <p class="text-3xl font-bold">{{ $viewsTotal }}</p>
        </div>

        <!-- Likes -->
        <div class="bg-red-500 text-white rounded-lg shadow p-6">
            <h4 class="text-lg font-medium">Likes</h4>
            <p class="text-3xl font-bold">{{ $likesTotal }}</p>
        </div>

        <!-- Commentaires -->
        <div class="bg-yellow-500 text-white rounded-lg shadow p-6">
            <h4 class="text-lg font-medium">Commentaires</h4>
            <p class="text-3xl font-bold">{{ $commentsCount }}</p>
        </div>
    </div>

    <!-- 📝 Gestion -->
    <h3 class="text-xl font-semibold mt-8 mb-4">📝 Gestion</h3>
    <div class="flex space-x-4">
        <a href="{{ route('articles.index') }}"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow">
            📄 Gérer mes articles
        </a>
        <a href="{{ route('articles.create') }}"
            class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded shadow">
            ➕ Créer un nouvel article
        </a>
    </div>
</div>
@endsection

