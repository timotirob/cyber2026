<?php
require '../config/bootstrap.php';

use App\Serveur;
use App\Database;
use App\ServeurRepository;
use App\Securite;
use App\CryptoService ;

// 🔒 Protéger également le traitement !
Securite::verifierConnexion();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nom = $_POST['hostname'];
    $ip  = $_POST['ip'];
    $os  = $_POST['os'];
    // Le mot de passe saisi en clair dans le formulaire
    $motDePasseClair = $_POST['root_pass'];

    try {
        // 1. CHIFFREMENT HYBRIDE
        $crypto = new CryptoService();
        $motDePasseChiffre = $crypto->chiffrerSensible($motDePasseClair);

        // 2. INSTANCIATION (On passe le mdp chiffré au constructeur)
        $nouveauServeur = new Serveur($nom, $ip, $os, $motDePasseChiffre);

        // 3. PERSISTANCE
        $pdo = Database::getConnection();
        $repo = new ServeurRepository($pdo);
        $repo->sauvegarder($nouveauServeur);

        header("Location: dashboard.php?success=1");
        exit();

    } catch (Exception $e) {
        // Affichage de l'erreur (IP invalide, OS interdit, ou BDD plantée)
        echo "<div style='color:red; border:1px solid red; padding:10px;'>";
        echo "Erreur : " . $e->getMessage();
        echo "</div>";
        echo "<br><a href='ajouter_machine.php'>Retour au formulaire</a>";
    }

} else {
    header("Location: ajouter_machine.php");
}