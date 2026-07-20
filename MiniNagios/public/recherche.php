<?php
require '../config/bootstrap.php';

use App\Database;
use App\Securite;

// Cette page est la version CORRIGÉE de demos-vulnerables/recherche_vulnerable.php.
// La différence tient en une chose : la donnée saisie ne touche jamais la
// structure de la requête. On la protège aussi par la session, comme toute
// page de public/.
Securite::verifierConnexion();

$pdo       = Database::getConnection();
$recherche = $_GET['hostname'] ?? '';
$resultats = [];

if ($recherche !== '') {
    // 1. On prépare la commande, avec un emplacement nommé (:hostname).
    //    À cet instant, la base connaît la STRUCTURE de la requête, et la fige.
    $sql  = "SELECT id, hostname, ip, os FROM serveurs WHERE hostname = :hostname";
    $stmt = $pdo->prepare($sql);

    // 2. On fournit la donnée SÉPARÉMENT. Elle ne pourra jamais changer la
    //    structure figée à l'étape 1 : même si $recherche vaut ' OR '1'='1,
    //    cette chaîne est cherchée telle quelle comme nom d'hôte, et ne
    //    correspond à aucun serveur — d'où zéro résultat, et non toute la table.
    $stmt->execute(['hostname' => $recherche]);
    $resultats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recherche de serveur - Mini-Nagios</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        form { margin-bottom: 20px; }
        input { padding: 8px; width: 250px; }
        button { padding: 8px 15px; }
        table { border-collapse: collapse; min-width: 50%; margin-top: 15px; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
<h1>🔍 Recherche de serveur par nom d'hôte</h1>
<p><a href="dashboard.php">&larr; Retour au tableau de bord</a></p>

<form method="GET">
    <input type="text" name="hostname" placeholder="Nom d'hôte exact"
           value="<?= htmlspecialchars($recherche) ?>">
    <button type="submit">Rechercher</button>
</form>

<?php if ($recherche !== ''): ?>
    <table>
        <thead>
        <tr><th>ID</th><th>Hostname</th><th>IP</th><th>OS</th></tr>
        </thead>
        <tbody>
        <?php foreach ($resultats as $srv): ?>
            <tr>
                <td><?= htmlspecialchars($srv['id']) ?></td>
                <td><?= htmlspecialchars($srv['hostname']) ?></td>
                <td><?= htmlspecialchars($srv['ip']) ?></td>
                <td><?= htmlspecialchars($srv['os']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($resultats)): ?>
            <tr><td colspan="4" style="text-align:center;">Aucun serveur pour ce nom d'hôte.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
