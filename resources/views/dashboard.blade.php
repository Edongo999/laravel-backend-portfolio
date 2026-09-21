<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>

<body>
    <h1>Bienvenue sur ton Dashboard, {{ Auth::user()->name }}</h1>
    <p>Tu es connecté avec l’email : {{ Auth::user()->email }}</p>
    <a href="{{ route('articles.index') }}">📄 Gérer mes articles</a>
</body>

</html>
