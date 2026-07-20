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

    // 3. On insère le compte, ou on met à jour son mot de passe s'il existe.
    // L'ancienne version faisait un DELETE puis un INSERT. Or depuis la
    // séance 3, le compte applicatif n'a plus le droit DELETE sur cette
    // table : supprimer un administrateur est une opération d'exploitation,
    // pas une opération applicative. Le "UPSERT" ci-dessous rend le DELETE
    // inutile — et conserve au passage l'id du compte, donc les traces du
    // journal qui pointent vers lui.
    // (Équivalent MySQL, pour l'épreuve : INSERT ... ON DUPLICATE KEY UPDATE.)
    $stmt = $pdo->prepare(
        "INSERT INTO administrateurs (email, password_hash)
         VALUES (:email, :hash)
         ON CONFLICT (email) DO UPDATE SET password_hash = EXCLUDED.password_hash"
    );
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