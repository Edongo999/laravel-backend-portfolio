@extends('layouts.app') {{-- ou ton layout par défaut --}}

@section('title', 'Liste des articles')

@section('content')
<h2>Liste des articles</h2>
<a href="{{ route('articles.create') }}" class="btn btn-success mb-3">➕ Publier un nouvel article</a>

<table border="1" cellpadding="8">
    <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Image</th>
            <th>Contenu</th>
            <th>Statut</th>
            <th>👁️ Vues</th>
            <th>❤️ Likes</th>
            <th>💬 Commentaires</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($articles as $article)
        <tr>
            <td>{{ $article->id }}</td>
            <td>{{ $article->title }}</td>
            <td>
                @if($article->image)
                    <img src="{{ asset('storage/'.$article->image) }}" width="100">
                @else
                    <span>Aucune image</span>
                @endif
            </td>
            <td>{{ Str::limit($article->content, 100) }}</td>
            <td>
                @if($article->archived)
                    <span style="color:gray;">Archivé</span>
                @else
                    <span style="color:green;">Actif</span>
                @endif
            </td>

            <!-- Vues -->
            <td>{{ $article->views }}</td>

            <!-- Likes -->
            <td>
                <form action="{{ route('articles.like', $article->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit">❤️ {{ $article->likes->count() }}</button>
                </form>
            </td>

            <!-- Aperçu des commentaires -->
            <td>
                @if($article->comments->count() > 0)
                    <ul>
                        @foreach($article->comments->take(2) as $comment)
                            <li><strong>{{ $comment->author ?? 'Anonyme' }}</strong> : {{ Str::limit($comment->content, 30) }}</li>
                        @endforeach
                    </ul>
                    <small><a href="{{ route('articles.comments', $article->id) }}">Voir tous</a></small>
                @else
                    <span>Aucun</span>
                @endif
            </td>

            <!-- Actions -->
            <td>
                <a href="{{ route('articles.edit', $article->id) }}">✏️ Modifier</a>

                <form action="{{ route('articles.destroy', $article->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit">🗑️ Supprimer</button>
                </form>

                @if(!$article->archived)
                    <form action="{{ route('articles.archive', $article->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit">📦 Archiver</button>
                    </form>
                @else
                    <form action="{{ route('articles.unarchive', $article->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit">🔓 Désarchiver</button>
                    </form>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
