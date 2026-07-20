<?php
require '../config/bootstrap.php';

use App\Database;
use App\SecuritePassword;

try {
    $pdo = Database::getConnection();

    $email = 'admin@mininagios.local';
    $motDePasseClair = 'BtsSlam2026!';

    // 1. On contrôle la robustesse AVANT de hacher.
    // Hacher un mot de passe faible ne le rend pas fort : il le rend seulement
    // illisible en base, ce qui n'arrête pas une attaque par dictionnaire.
    if (!SecuritePassword::estRobuste($motDePasseClair)) {
        die("Le mot de passe ne respecte pas la politique de sécurité de l'entreprise.");
    }

    // 2. On génère le hachage en Argon2id, recommandé par l'ANSSI à la place de
    // BCRYPT : il exige beaucoup de mémoire vive, ce qui rend la force brute sur
    // carte graphique ou puce dédiée économiquement hors de portée.
    // PHP choisit seul le sel et les paramètres de coût.
    $hash = password_hash($motDePasseClair, PASSWORD_ARGON2ID);

    // 3. On nettoie si l'ancien faux compte existe.
    // Même ici, où la variable ne vient pas de l'utilisateur, on passe par une
    // requête préparée : une habitude prise sur les cas faciles est une habitude
    // qui tiendra sur les cas dangereux.
    $suppression = $pdo->prepare("DELETE FROM administrateurs WHERE email = :email");
    $suppression->execute(['email' => $email]);

    // 4. On insère le compte proprement
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