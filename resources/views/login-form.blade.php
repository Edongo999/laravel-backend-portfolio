<!DOCTYPE html>
<html>

<head>
    <title>Connexion</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        text-align: center;
        margin-top: 50px;
    }

    .login-box {
        width: 300px;
        margin: auto;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 10px;
        background: #f9f9f9;
    }

    img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        margin-bottom: 20px;
    }

    input {
        width: 90%;
        padding: 8px;
        margin: 8px 0;
    }

    button {
        padding: 10px 20px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
    }

    a {
        display: block;
        margin-top: 10px;
        color: #007bff;
        text-decoration: none;
    }
    </style>
</head>

<body>
    <div class="login-box">
        <!-- Photo de profil -->
        <img src="{{ asset('images/landry.jpeg') }}" alt="Photo de profil">

        <h2>Connexion à mon espace</h2>
        <form method="POST" action="{{ url('/api/login') }}">
            @csrf
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="password" name="password" placeholder="Mot de passe" required><br>

            <button type="submit">Se connecter</button>
        </form>

        <!-- Lien mot de passe oublié -->
        <a href="{{ url('/forgot-password') }}">Mot de passe oublié ?</a>
    </div>
</body>

</html>