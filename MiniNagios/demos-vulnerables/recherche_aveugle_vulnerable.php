<?php
/* =====================================================================
 * CODE VOLONTAIREMENT VULNÉRABLE — SUPPORT DE COURS UNIQUEMENT
 * Ne jamais déployer. Ne jamais recopier dans public/.
 * Objet : démontrer une injection SQL À L'AVEUGLE (blind SQLi).
 *
 * Variante de recherche_vulnerable.php : les résultats ET les erreurs
 * sont masqués. La page ne dit plus QUOI elle a trouvé, seulement SI
 * elle a trouvé quelque chose. On veut montrer que masquer l'affichage
 * ne referme PAS la faille : l'attaquant lit l'information un bit à la
 * fois, en observant la seule différence « trouvé / pas trouvé ».
 * ===================================================================== */
require '../config/bootstrap.php';
use App\Database;

$pdo = Database::getConnection();
$recherche = $_GET['hostname'] ?? '';

// LA FAUTE EST TOUJOURS ICI : la donnée est concaténée dans la commande.
// Contrairement à recherche_vulnerable.php, on n'affiche NI la requête, NI
// les données, NI le message d'erreur — l'attaquant est « à l'aveugle ».
$sql = "SELECT id, hostname, ip, os FROM serveurs WHERE hostname = '$recherche'";

try {
    $resultat = $pdo->query($sql);
    $lignes = $resultat->fetchAll();

    // Seul signal renvoyé : trouvé ou non. Rien d'autre ne fuit... en apparence.
    if (count($lignes) > 0) {
        echo "<p>✅ Serveur trouvé.</p>";
    } else {
        echo "<p>❌ Aucun serveur.</p>";
    }
} catch (\PDOException $e) {
    // Même l'erreur est masquée : on renvoie la réponse « négative » neutre,
    // pour ne donner aucune prise supplémentaire. La faille reste pourtant
    // exploitable — c'est tout l'intérêt de la démonstration.
    echo "<p>❌ Aucun serveur.</p>";
}
