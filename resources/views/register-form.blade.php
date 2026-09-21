<!DOCTYPE html>
<html>
<head>
    <title>Inscription</title>
</head>
<body>
    <h2>Formulaire d'inscription</h2>
    <form method="POST" action="{{ url('/api/register') }}">
        @csrf
        <label>Nom :</label>
        <input type="text" name="name" required><br>

        <label>Email :</label>
        <input type="email" name="email" required><br>

        <label>Mot de passe :</label>
        <input type="password" name="password" required><br>

        <label>Confirmer mot de passe :</label>
        <input type="password" name="password_confirmation" required><br>

        <button type="submit">S'inscrire</button>
    </form>
</body>
</html>
