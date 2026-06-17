<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Serveur</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 600px; margin: auto;}
        label { display:block; margin-top: 15px; font-weight: bold;}
        input, select { margin-bottom: 10px; padding: 8px; width: 100%; display:block; box-sizing: border-box;}
        button { padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; width: 100%; font-size: 1.1em;}
        button:hover { background: #218838; }
    </style>
</head>
<body>
<h2>🏭 Provisionner un Serveur Sécurisé</h2>

<form method="POST" action="traitementPersisteServeur.php">
    <label>Nom d'hôte (Hostname) :</label>
    <input type="text" name="hostname" required placeholder="Ex: SRV-DB-02 (Lettres, chiffres, tirets)">

    <label>Adresse IP :</label>
    <input type="text" name="ip" required placeholder="Ex: 192.168.1.50">

    <label>Système d'Exploitation :</label>
    <select name="os">
        <option value="Debian 12">Debian 12</option>
        <option value="Ubuntu 24.04">Ubuntu 24.04</option>
        <option value="Windows Server 2022">Windows Server 2022</option>
        <option value="RedHat 9">RedHat 9</option>

        <option value="Windows XP">Windows XP (Interdit)</option>
        <option value="TempleOS">TempleOS (Interdit)</option>
    </select>

    <label>Mot de passe root:</label>

    <input type="password" name="root_pass" required>

    <button type="submit">Créer le serveur</button>
</form>
</body>
</html>