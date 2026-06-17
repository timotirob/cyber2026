<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Routeur</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 600px; margin: auto;}
        label { display:block; margin-top: 15px; font-weight: bold;}
        input { margin-bottom: 10px; padding: 8px; width: 100%; display:block; box-sizing: border-box;}
        button { padding: 10px 20px; background: #17a2b8; color: white; border: none; cursor: pointer; width: 100%; font-size: 1.1em;}
    </style>
</head>
<body>
<h2>🔌 Provisionner un Routeur</h2>

<form method="POST" action="traitement_routeur.php"> <label>Nom d'hôte (Hostname) :</label>
    <input type="text" name="hostname" required placeholder="Ex: RTR-CORE-01"> <label>Adresse IP :</label>
    <input type="text" name="ip" required placeholder="Ex: 10.0.0.254"> <label>Nombre de ports :</label>
    <input type="number" name="ports" required placeholder="Entre 1 et 128"> <button type="submit">Créer le routeur</button>
</form>
</body>
</html>