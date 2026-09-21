@extends('layouts.app') {{-- ou adminlte::page si tu utilises AdminLTE --}}

@section('title', 'Créer un article')

@section('content')
<h2>Publier un nouvel article</h2>
<form action="{{ route('articles.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <label>Titre :</label>
    <input type="text" name="title" required><br><br>

    <label>Image :</label>
    <input type="file" name="image"><br><br>

    <label>Contenu :</label>
    <textarea name="content" required></textarea><br><br>

    <button type="submit">Publier</button>
</form>

@endsection
