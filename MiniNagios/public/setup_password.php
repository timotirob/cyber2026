<?php
require '../config/bootstrap.php';

use App\Database;
use App\SecuritePassword;

if (!isset($_GET['token'])) {
    die("Lien invalide ou corrompu.");
}

$token = $_GET['token'];
$pdo   = Database::getConnection();

// ÉTAPE A : trouver et valider le jeton.
// Les deux conditions tiennent dans la MÊME requête. Vérifier l'expiration en
// PHP après avoir récupéré la ligne fonctionnerait aussi, mais laisse la porte
// ouverte à l'oubli d'un test ; ici, un jeton périmé ne remonte tout simplement
// pas de la base.
$stmt = $pdo->prepare(
    "SELECT id, email
     FROM administrateurs
     WHERE reset_token = :token
       AND token_expires_at > NOW()"
);
$stmt->execute(['token' => $token]);
$admin = $stmt->fetch();

if (!$admin) {
    // Message volontairement identique pour un jeton inconnu et pour un jeton
    // périmé : l'utilisateur légitime n'a pas besoin de la nuance, et un
    // attaquant n'apprend rien.
    die("Lien invalide ou expiré. Veuillez contacter le support DSI.");
}

$erreur  = null;
$succes  = false;

// ÉTAPE B : traitement du formulaire.
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['new_password'])) {

    $nouveauMotDePasse = $_POST['new_password'];

    // 1. La politique de robustesse s'applique ici aussi : c'est le seul endroit
    // où l'utilisateur choisit lui-même son mot de passe, donc le seul endroit
    // où il peut en choisir un mauvais.
    if (!SecuritePassword::estRobuste($nouveauMotDePasse)) {
        $erreur = "Le mot de passe ne respecte pas la politique de sécurité de l'entreprise : "
                . "12 caractères minimum, avec majuscule, minuscule, chiffre et caractère spécial.";
    } else {
        // 2. Hachage en Argon2id.
        $hashSecurise = password_hash($nouveauMotDePasse, PASSWORD_ARGON2ID);

        // 3. On enregistre le hash ET on détruit le jeton dans un SEUL UPDATE.
        // En deux instructions séparées, une panne entre les deux laisserait un
        // lien magique encore actif sur un compte désormais protégé : le lien
        // deviendrait une porte dérobée permanente.
        $maj = $pdo->prepare(
            "UPDATE administrateurs
             SET password_hash    = :hash,
                 reset_token      = NULL,
                 token_expires_at = NULL
             WHERE id = :id"
        );
        $maj->execute([
            'hash' => $hashSecurise,
            'id'   => $admin['id']
        ]);

        $succes = true;
    }
}
?>

<!-- ÉTAPE C : affichage -->
<div style="max-width: 400px; margin: 50px auto; font-family: sans-serif;">

<?php if ($succes): ?>

    <div style="color: green;">Mot de passe enregistré ! Votre compte est activé.</div>
    <p><a href="login.php">Aller à la page de connexion</a></p>

<?php else: ?>

    <h2>Bienvenue, <?= htmlspecialchars($admin['email']) ?></h2>
    <p>Pour des raisons de sécurité, veuillez définir votre mot de passe d'accès :</p>

    <?php if ($erreur !== null): ?>
        <div style="color: red; margin-bottom: 10px;"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="password" name="new_password" required placeholder="Mot de passe robuste" style="width: 100%; padding: 10px; margin-bottom: 10px;">
        <button type="submit" style="width: 100%; padding: 10px; background: green; color: white; border: none;">Enregistrer et se connecter</button>
    </form>

<?php endif; ?>

</div>
