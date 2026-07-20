<?php
/* =====================================================================
 * CODE VOLONTAIREMENT VULNÉRABLE — SUPPORT DE COURS UNIQUEMENT
 * Ne jamais déployer. Ne jamais recopier dans public/.
 * Objet : démontrer une injection SQL en laboratoire fermé.
 * ===================================================================== */
require '../config/bootstrap.php';
use App\Database;

$pdo = Database::getConnection();
$recherche = $_GET['hostname'] ?? '';

// LA FAUTE EST ICI : la donnée est concaténée dans la commande.
$sql = "SELECT id, hostname, ip, os FROM serveurs WHERE hostname = '$recherche'";

echo "<p>Requête exécutée :</p><pre>" . htmlspecialchars($sql) . "</pre>";

try {
    $resultat = $pdo->query($sql);
    foreach ($resultat as $ligne) {
        echo htmlspecialchars("{$ligne['hostname']} — {$ligne['ip']} — {$ligne['os']}") . "<br>";
    }
} catch (\PDOException $e) {
    echo "<p>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
