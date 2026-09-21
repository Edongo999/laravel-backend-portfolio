@extends('layouts.app')

@section('title', 'Commentaires - Admin')

@section('content')
<h2>Commentaires pour : {{ $article->title }}</h2>

{{-- Commentaires principaux (parent_id = null) --}}
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

    {{-- Bouton supprimer --}}
    <form action="{{ route('comments.destroy', $comment->id) }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit">🗑️ Supprimer</button>
    </form>

    {{-- Formulaire de réponse admin --}}
    <form action="{{ route('comments.reply', $comment->id) }}" method="POST" style="margin-top:10px;">
        @csrf
        <textarea name="content" placeholder="Réponse admin"></textarea>
        <button type="submit">Répondre en tant qu’admin</button>
    </form>

    {{-- Réponses imbriquées --}}
    @foreach($comment->replies as $reply)
    <div style="margin-left:30px; border-left:2px solid #ddd; padding-left:10px; margin-top:5px;">
        <strong>{{ $reply->author ?? 'Anonyme' }}</strong>
        @if($reply->is_admin)
        <span style="color:red; font-weight:bold;">🔑 Admin</span>
        @endif
        <p>{{ $reply->content }}</p>
        <small style="color:#666; font-size:12px;">
            {{ $reply->created_at->format('d/m/Y H:i') }}
        </small>

        {{-- Bouton supprimer pour la réponse --}}
        <form action="{{ route('comments.destroy', $reply->id) }}" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
            <button type="submit">🗑️ Supprimer</button>
        </form>
    </div>
    @endforeach
</div>
@endforeach

{{-- Pagination si nécessaire --}}
{{ $comments->links() }}
@endsection