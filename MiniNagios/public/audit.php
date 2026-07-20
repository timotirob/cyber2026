<?php
require '../config/bootstrap.php';

use App\Database;
use App\Journal;
use App\Securite;

Securite::verifierConnexion();

// Lecture seule : la connexion d'audit suffit, et c'est tout l'intérêt.
$pdo     = Database::getConnectionAudit();
$journal = new Journal($pdo);

$forceBrute = $journal->detecterForceBrute();
$parNature  = $journal->compterParNature();
$horsHeures = $journal->activiteHorsHeuresOuvrees();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Audit de sécurité - Mini-Nagios</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        h2 { margin-top: 30px; }
        table { border-collapse: collapse; min-width: 50%; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f4f4f4; }
        .vide { color: green; }
        .alerte { background-color: #ffdddd; }
    </style>
</head>
<body>
<h1>🔎 Audit de sécurité</h1>
<p><a href="dashboard.php">&larr; Retour au tableau de bord</a> |
   <a href="journal.php">Consulter le journal complet</a></p>

<h2>Force brute — IP au-delà de 5 échecs sur 10 minutes</h2>
<?php if (empty($forceBrute)): ?>
    <p class="vide">Aucune adresse IP suspecte.</p>
<?php else: ?>
    <table>
        <tr><th>Adresse IP</th><th>Nombre d'échecs</th></tr>
        <?php foreach ($forceBrute as $ligne): ?>
            <tr class="alerte">
                <td><?= htmlspecialchars($ligne['adresse_ip']) ?></td>
                <td><?= htmlspecialchars($ligne['nb_echecs']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Évènements par nature — dernières 24 heures</h2>
<?php if (empty($parNature)): ?>
    <p class="vide">Aucun évènement sur la période.</p>
<?php else: ?>
    <table>
        <tr><th>Nature</th><th>Nombre</th></tr>
        <?php foreach ($parNature as $ligne): ?>
            <tr>
                <td><?= htmlspecialchars($ligne['nature']) ?></td>
                <td><?= htmlspecialchars($ligne['nombre']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Connexions hors heures ouvrées — 7 derniers jours (avant 7 h / après 20 h)</h2>
<?php if (empty($horsHeures)): ?>
    <p class="vide">Aucune connexion hors heures ouvrées.</p>
<?php else: ?>
    <table>
        <tr><th>Date</th><th>Identifiant</th><th>Adresse IP</th></tr>
        <?php foreach ($horsHeures as $ligne): ?>
            <tr class="alerte">
                <td><?= htmlspecialchars($ligne['date_heure']) ?></td>
                <td><?= htmlspecialchars($ligne['identifiant_saisi'] ?? '') ?></td>
                <td><?= htmlspecialchars($ligne['adresse_ip']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</body>
</html>
