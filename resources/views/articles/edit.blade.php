@extends('layouts.app') {{-- ou ton layout par défaut --}}

@section('title', 'Modifier un article')

@section('content')
<h2>Modifier l’article</h2>
<form action="{{ route('articles.update', $article->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <label>Titre :</label>
    <input type="text" name="title" value="{{ $article->title }}" required><br><br>

    <label>Image :</label>
    @if($article->image)
    <img src="{{ asset('storage/'.$article->image) }}" alt="Image" width="100"><br>
    @endif
    <input type="file" name="image"><br><br>

    <label>Contenu :</label>
    <textarea name="content" required>{{ $article->content }}</textarea><br><br>

    <button type="submit">Mettre à jour</button>
</form>
@endsection