<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '../config/bootstrap.php';

use App\Database;
use App\Journal;

/**
 * Hash leurre servant uniquement à égaliser le temps de réponse.
 *
 * Sans lui, une tentative sur un compte inexistant répondrait instantanément
 * (aucun hash à vérifier), tandis qu'une tentative sur un compte existant
 * prendrait le temps de calcul d'Argon2id. Un attaquant qui chronomètre les
 * réponses saurait donc quels comptes existent : c'est une attaque temporelle.
 *
 * Ce hash a été produit à partir de 32 octets aléatoires jetés ensuite : il
 * n'est le hash de rien de connu, et aucun mot de passe ne peut le valider.
 */
const HASH_LEURRE = '$argon2id$v=19$m=65536,t=4,p=1$ckFaamNOdlNteWIyRUFXUg$Kx5+MeEEWJqW/7VRoBOtPIt7rnueoT3oLA+aWzQY/C0';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $pdo = Database::getConnection();
        $journal = new Journal($pdo);

        // 1. On cherche l'utilisateur par son email
        $stmt = $pdo->prepare("SELECT id, email, password_hash FROM administrateurs WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch();

        // 2. On choisit le hash à vérifier.
        // Compte inconnu, ou compte provisionné mais pas encore activé
        // (password_hash à NULL) : dans les deux cas on vérifie quand même le
        // mot de passe, contre le leurre. Le calcul est donc toujours effectué,
        // et il échoue toujours.
        $hashAVerifier = $admin['password_hash'] ?? HASH_LEURRE;

        // 3. Vérification cryptographique. password_verify() lit l'algorithme
        // dans l'en-tête du hash stocké : les anciens comptes BCRYPT et les
        // nouveaux comptes Argon2id cohabitent sans rien changer ici.
        $motDePasseValide = password_verify($password, $hashAVerifier);

        if ($admin && $admin['password_hash'] !== null && $motDePasseValide) {
            // Succès : on ouvre la session
            session_start();

            // Anti-fixation de session : on change l'identifiant de session au
            // moment précis où l'utilisateur gagne des droits. Un identifiant
            // qu'un attaquant aurait imposé à la victime avant la connexion
            // devient ainsi inutilisable.
            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];

            // Une connexion réussie se journalise autant qu'un échec : sans
            // elle, impossible de repérer une connexion administrateur à 3 h du
            // matin, ni de reconstituer le parcours d'un compte compromis.
            $journal->enregistrer(
                Journal::CONNEXION,
                $admin['email'],
                (int) $admin['id'],
                'authentification'
            );

            header("Location: dashboard.php");
            exit();
        }

        // Échec. Les trois cas — email inconnu, compte non activé, mot de passe
        // faux — passent par cette unique sortie et produisent le même message.
        // Dire « cet email n'existe pas » offrirait à un attaquant la liste des
        // comptes valides, sur laquelle concentrer ensuite sa force brute.
        //
        // On trace l'échec avec l'identifiant SAISI — et surtout pas le mot de
        // passe. L'identifiant saisi est conservé même s'il ne correspond à
        // aucun compte : c'est précisément ce qui permettra de distinguer plus
        // tard le balayage aveugle de l'attaque ciblée.
        //
        // Noter la dissymétrie assumée : message pauvre pour l'utilisateur,
        // journal riche pour l'exploitant. L'attaquant ne lit pas les journaux.
        $journal->enregistrer(
            Journal::ECHEC_AUTH,
            $email,
            null,
            'authentification',
            'Tentative de connexion infructueuse'
        );

        header("Location: login.php?erreur=1");
        exit();

    } catch (Exception $e) {
        die("Erreur de base de données : " . $e->getMessage());
    }
}
