<?php
require '../config/bootstrap.php';

use App\Database;
use App\Securite;
use App\ControleAcces;

// Page réservée à un administrateur connecté.
Securite::verifierConnexion();

$pdo      = Database::getConnection();
$controle = new ControleAcces($pdo);
$idAdmin  = $_SESSION['admin_id'];

// On lit la liste des tables et des opérations depuis les tables de référence,
// plutôt que de les coder en dur : la matrice suit automatiquement le modèle.
$tables     = $pdo->query("SELECT nom FROM table_bdd ORDER BY nom")->fetchAll(\PDO::FETCH_COLUMN);
$operations = $pdo->query("SELECT nom FROM operation ORDER BY id")->fetchAll(\PDO::FETCH_COLUMN);

// La fonction de l'administrateur courant, pour l'afficher en tête.
$stmt = $pdo->prepare(
    "SELECT f.libelle
     FROM administrateurs a
     LEFT JOIN fonction f ON f.id = a.id_fonction
     WHERE a.id = :id"
);
$stmt->execute(['id' => $idAdmin]);
$fonction = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes droits applicatifs - Mini-Nagios</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 8px 14px; border: 1px solid #ccc; text-align: center; }
        th { background-color: #f4f4f4; }
        td.table-nom { text-align: left; font-weight: bold; background: #fafafa; }
        .oui { color: #1a7f37; font-weight: bold; }
        .non { color: #b00; }
    </style>
</head>
<body>
<h1>🔐 Mes droits applicatifs</h1>
<p><a href="dashboard.php">&larr; Retour au tableau de bord</a></p>

<p>Administrateur n° <?= htmlspecialchars($idAdmin) ?> —
   fonction : <strong><?= htmlspecialchars($fonction ?? 'aucune') ?></strong></p>

<table>
    <thead>
    <tr>
        <th>Table \ Opération</th>
        <?php foreach ($operations as $op): ?>
            <th><?= htmlspecialchars($op) ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($tables as $table): ?>
        <tr>
            <td class="table-nom"><?= htmlspecialchars($table) ?></td>
            <?php foreach ($operations as $op): ?>
                <?php $autorise = $controle->possedeDroit($idAdmin, $table, $op); ?>
                <td class="<?= $autorise ? 'oui' : 'non' ?>">
                    <?= $autorise ? '✓' : '✗' ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
