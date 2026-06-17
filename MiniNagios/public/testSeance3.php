<?php
require '../vendor/autoload.php';

use App\Serveur;
use App\Service;

echo "<h1>Test de Composition & Monitoring</h1>";

try {
    // 1. Création du Serveur (Le Contenant)
    $srvWeb = new Serveur("SRV-PROD-01", "192.168.1.100", "Debian 12");

    // 2. Création des Services (Les Contenus)
    // Apache est Critique (3ème argument à true)
    $apache = new Service("Apache HTTP", 80, true);

    // SSH n'est pas critique
    $ssh    = new Service("SSH Secure", 22, false);

    // 3. Composition : On attache les services au serveur
    $srvWeb->ajouterService($apache);
    $srvWeb->ajouterService($ssh);

    // ----------------------------------------------------
    // SCÉNARIO 1 : Tout va bien
    // ----------------------------------------------------
    $apache->demarrer(); // Le service critique est ON
    $ssh->demarrer();

    echo "<h3>État 1 : Tout est démarré</h3>";
    echo $srvWeb->afficherStatut();
    echo "<hr>";

    // ----------------------------------------------------
    // SCÉNARIO 2 : Panne Critique !
    // ----------------------------------------------------
    $apache->arreter(); // Le service critique tombe !

    echo "<h3>État 2 : Panne du service Web (Critique)</h3>";
    echo $srvWeb->afficherStatut();
    // Doit afficher "DANGER"
    echo "<hr>";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}