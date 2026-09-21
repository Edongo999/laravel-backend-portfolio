<!DOCTYPE html>
<html>
<head>
    <title>Mot de passe oublié</title>
</head>
<body>
    <h2>Réinitialiser le mot de passe</h2>
    <form method="POST" action="{{ url('/api/forgot-password') }}">
        @csrf
        <input type="email" name="email" placeholder="Votre email" required><br>
        <button type="submit">Envoyer le lien</button>
    </form>
</body>
</html>
