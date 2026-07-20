<?php
require '../config/bootstrap.php';

use App\Database;

try {
    $pdo = Database::getConnection();

    $email = 'admin@mininagios.local';
    $motDePasseClair = 'BtsSlam2026!';

    // 1. On génère le VRAI hachage BCRYPT
    $hash = password_hash($motDePasseClair, PASSWORD_BCRYPT);

    // 2. On nettoie si l'ancien faux compte existe.
    // Même ici, où la variable ne vient pas de l'utilisateur, on passe par une
    // requête préparée : une habitude prise sur les cas faciles est une habitude
    // qui tiendra sur les cas dangereux.
    $suppression = $pdo->prepare("DELETE FROM administrateurs WHERE email = :email");
    $suppression->execute(['email' => $email]);

    // 3. On insère le compte proprement
    $stmt = $pdo->prepare("INSERT INTO administrateurs (email, password_hash) VALUES (:email, :hash)");
    $stmt->execute([
        'email' => $email,
        'hash'  => $hash
    ]);

    echo "<h3 style='color:green'>✅ Administrateur créé avec succès !</h3>";
    echo "<p>Email : <strong>$email</strong></p>";
    echo "<p>Mot de passe : <strong>$motDePasseClair</strong></p>";
    echo "<p>Hash généré en BDD : <br><code>$hash</code></p>";
    echo "<a href='login.php'>Aller à la page de connexion</a>";

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}