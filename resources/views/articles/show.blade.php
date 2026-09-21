@extends('layouts.app')

@section('title', $article->title)

@section('content')
<div class="container">
    <h2>{{ $article->title }}</h2>
    @if($article->image)
    <img src="{{ asset('storage/'.$article->image) }}" width="300">
    @endif
    <p>{{ $article->content }}</p>

    <!-- Vues -->
    <p>👁️ {{ $article->views }} vues</p>

    <!-- Likes -->
    <form action="{{ route('articles.like', $article->id) }}" method="POST">
        @csrf
        <button type="submit">❤️ {{ $article->likes->count() }}</button>
    </form>

    <!-- Commentaires -->
    <h3>Commentaires</h3>
    @foreach($article->comments()->whereNull('parent_id')->latest()->get() as $comment)
    <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
        <strong>{{ $comment->author ?? 'Anonyme' }}</strong>
        @if($comment->is_admin)
        <span style="color:red; font-weight:bold;">🔑 Admin</span>
        @endif
        <p>{{ $comment->content }}</p>
        <small style="color:#666; font-size:12px;">
            {{ $comment->created_at->format('d/m/Y H:i') }}
        </small>

        <!-- Réponses imbriquées -->
        @foreach($comment->replies as $reply)
        <div style="margin-left:30px; border-left:2px solid #ddd; padding-left:10px; margin-top:5px;">
            <strong>{{ $reply->author }}
                @if($reply->is_admin)
                <span style="color:red; font-weight:bold;">🔑 Admin</span>
                @endif
            </strong>
            <p>{{ $reply->content }}</p>
            <small style="color:#666; font-size:12px;">
                {{ $reply->created_at->format('d/m/Y H:i') }}
            </small>
        </div>
        @endforeach

        <!-- Formulaire de réponse -->
        <form action="{{ route('comments.reply', $comment->id) }}" method="POST" style="margin-top:10px;">
            @csrf
            <textarea name="content" required placeholder="Votre réponse"></textarea>
            <button type="submit">Répondre</button>
        </form>
    </div>
    @endforeach

    <!-- Formulaire pour ajouter un commentaire principal -->
    <h4>Ajouter un commentaire</h4>
    <form action="{{ route('articles.comment', $article->id) }}" method="POST">
        @csrf
        <input type="text" name="author" placeholder="Votre nom (optionnel)">
        <textarea name="content" required placeholder="Votre commentaire"></textarea>
        <button type="submit">💬 Ajouter un commentaire</button>
    </form>
</div>
@endsection