<?php
require '../config/bootstrap.php';

use App\Routeur;
use App\Database;
use App\RouteurRepository;

if ($_SERVER["REQUEST_METHOD"] == "POST") { //

    // Récupération des données du formulaire
    $nom   = $_POST['hostname']; // [cite: 346]
    $ip    = $_POST['ip'];
    $ports = (int) $_POST['ports'];

    try { // [cite: 346]
        // 1. Instanciation (Validation automatique via le constructeur)
        $nouveauRouteur = new Routeur($nom, $ip, $ports); // [cite: 347]

        // 2. Connexion PDO
        $pdo = Database::getConnection(); // [cite: 348]

        // 3. Sauvegarde via le Repository
        $repo = new RouteurRepository($pdo);
        $repo->sauvegarder($nouveauRouteur); // [cite: 348]

        // 4. Message de succès
        echo "<div style='color:green; border: 1px solid green; padding: 10px;'>";
        echo "✅ Routeur enregistré avec succès ! <br>"; // [cite: 349]
        echo $nouveauRouteur->afficherStatut();
        echo "</div>";
        echo "<br><a href='ajouter_routeur.php'>Ajouter un autre routeur</a>"; // [cite: 349]

    } catch (Exception $e) { // [cite: 346]
        // Si la validation échoue (ex: 500 ports) ou erreur SQL
        echo "<div style='color:red; border: 1px solid red; padding: 10px;'>";
        echo "🛑 Erreur : " . $e->getMessage(); // [cite: 350]
        echo "</div>";
        echo "<br><a href='ajouter_routeur.php'>&larr; Retour au formulaire</a>";
    }

} else {
    // Redirection si on accède à la page sans valider le formulaire
    header("Location: ajouter_routeur.php");
}