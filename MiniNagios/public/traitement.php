<?php
require '../config/bootstrap.php';
use App\Serveur;

echo "<a href='ajouter_machine.php'>&larr; Retour au formulaire</a><hr>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Récupération (avec protection basique XSS)
    $nom = htmlspecialchars($_POST['hostname']);
    $ip  = htmlspecialchars($_POST['ip']);
    $os  = htmlspecialchars($_POST['os']);

    try {
        // 2. Tentative de création
        // C'est ici que Validator et le Constructeur vont travailler
        $nouveauServeur = new Serveur($nom, $ip, $os);

        // 3. Si on arrive ici, c'est que tout est OK
        echo "<div style='background-color: #d4edda; color: #155724; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<h3>✅ Serveur provisionné avec succès !</h3>";
        echo $nouveauServeur->afficherStatut();
        echo "</div>";

    } catch (Exception $e) {
        // 4. Si une validation échoue (IP, Hostname ou OS)
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h3>🛑 Échec de la validation</h3>";
        echo "<p>Le système a rejeté votre demande pour la raison suivante :</p>";
        echo "<ul><li><strong>" . $e->getMessage() . "</strong></li></ul>";
        echo "</div>";
    }

} else {
    // Redirection si accès direct
    header("Location: ajouter_machine.php");
    exit();
}