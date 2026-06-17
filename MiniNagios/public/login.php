<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Mini-Nagios</title>
    <style>
        body { font-family: sans-serif; padding: 50px; text-align: center; }
        form { display: inline-block; text-align: left; padding: 20px; border: 1px solid #ccc; border-radius: 5px; background: #f9f9f9;}
        input { display: block; margin-bottom: 15px; padding: 10px; width: 250px; }
        button { padding: 10px; width: 100%; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
<h2>🔒 Accès Restreint</h2>

<?php if (isset($_GET['erreur'])): ?>
    <div style="color: red; margin-bottom: 15px;">Identifiants incorrects ou accès refusé.</div>
<?php endif; ?>

<form method="POST" action="traitement_login.php">
    <label>Email :</label>
    <input type="email" name="email" required placeholder="admin@mininagios.local">

    <label>Mot de passe :</label>
    <input type="password" name="password" required>

    <button type="submit">Se connecter</button>
</form>
</body>
</html>