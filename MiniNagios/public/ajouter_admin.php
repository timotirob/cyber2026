<?php
require '../config/bootstrap.php';

use App\Database;

// Pour le TP, on simule l'email saisi dans un formulaire.
// uniqid() évite de buter sur la contrainte UNIQUE de la colonne email à chaque
// rafraîchissement de la page pendant vos tests.
$emailNouveauTech = 'alice.' . uniqid() . '@mininagios.local';

try {
    $pdo = Database::getConnection();

    // 1. Génération d'un jeton aléatoire de 32 octets (64 caractères hexadécimaux).
    // random_bytes() puise dans le générateur cryptographique du système, à la
    // différence de rand() ou uniqid() dont la sortie est prédictible : un jeton
    // devinable rendrait tout le dispositif inutile.
    $token = bin2hex(random_bytes(32));

    // 2. Date d'expiration (maintenant + 2 heures).
    // Une fenêtre courte réduit la durée pendant laquelle un lien intercepté
    // reste exploitable.
    $expiresAt = date('Y-m-d H:i:s', strtotime('+2 hours'));

    // 3. Insertion en base : le mot de passe est laissé NULL.
    // C'est tout l'intérêt du procédé — le DSI provisionne le compte sans jamais
    // connaître le secret de son collaborateur.
    $stmt = $pdo->prepare("INSERT INTO administrateurs (email, reset_token, token_expires_at) VALUES (:email, :token, :expire)");
    $stmt->execute([
        'email'  => $emailNouveauTech,
        'token'  => $token,
        'expire' => $expiresAt
    ]);

    // 4. Simulation de l'envoi de l'email.
    $lienMagique = "http://localhost:8082/public/setup_password.php?token=" . $token;

    echo "<h3>✅ Compte provisionné avec succès !</h3>";
    echo "<p>Compte créé : <strong>" . htmlspecialchars($emailNouveauTech) . "</strong></p>";
    echo "<p>Veuillez transmettre ce lien d'activation sécurisé au collaborateur (expiration dans 2 h) :</p>";
    echo "<a href='" . htmlspecialchars($lienMagique) . "'>Activer le compte (lien vers setup_password.php)</a>";

} catch (Exception $e) {
    echo "Erreur BDD : " . htmlspecialchars($e->getMessage());
}
