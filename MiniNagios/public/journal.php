<?php
require '../config/bootstrap.php';

use App\Database;
use App\Journal;
use App\Securite;

// Une console de supervision accessible à tous serait un comble.
Securite::verifierConnexion();

// Connexion d'AUDIT, pas la connexion applicative : cette page ne fait que
// lire. Si elle contenait une faille, l'attaquant hériterait d'un compte qui
// ne peut ni écrire, ni voir autre chose que le journal.
$pdo     = Database::getConnectionAudit();
$journal = new Journal($pdo);

// Les natures proposées dans la liste déroulante : les constantes de la classe,
// pas des chaînes retapées à la main — une seule source de vérité.
$natures = [
    Journal::CONNEXION, Journal::ECHEC_AUTH, Journal::DECONNEXION,
    Journal::CREATION,  Journal::SUPPRESSION, Journal::ACCES_REFUSE,
    Journal::ERREUR
];

// Un champ vide signifie « pas de filtre », donc null — c'est lui qui
// neutralisera la condition dans la requête de listerFiltre().
$natureChoisie = ($_POST['nature'] ?? '') !== '' ? $_POST['nature'] : null;
$dateChoisie   = ($_POST['date_choisie'] ?? '') !== '' ? $_POST['date_choisie'] : null;

$evenements = $journal->listerFiltre($natureChoisie, $dateChoisie);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Journal des évènements - Mini-Nagios</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        form { margin-bottom: 20px; padding: 15px; background: #f4f4f4; border-radius: 5px; }
        select, input, button { padding: 8px; margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f4f4f4; }
        .alerte { background-color: #ffdddd; }
    </style>
</head>
<body>
<h1>📜 Journal des évènements</h1>
<p><a href="dashboard.php">&larr; Retour au tableau de bord</a></p>

<form method="POST">
    <label>Nature :
        <select name="nature">
            <option value="">TOUS</option>
            <?php foreach ($natures as $nature): ?>
                <option value="<?= htmlspecialchars($nature) ?>"
                    <?= $nature === $natureChoisie ? 'selected' : '' ?>>
                    <?= htmlspecialchars($nature) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Date :
        <input type="date" name="date_choisie" value="<?= htmlspecialchars($dateChoisie ?? '') ?>">
    </label>
    <button type="submit">Filtrer</button>
</form>

<table>
    <thead>
    <tr><th>Date</th><th>Nature</th><th>Identifiant</th><th>IP</th><th>Ressource</th></tr>
    </thead>
    <tbody>
    <?php foreach ($evenements as $evt): ?>
        <?php
        // Les échecs et refus ressortent sur fond rouge clair : ce sont eux
        // que l'administrateur cherche en priorité.
        $enAlerte = in_array($evt['nature'], [Journal::ECHEC_AUTH, Journal::ACCES_REFUSE]);
        ?>
        <tr class="<?= $enAlerte ? 'alerte' : '' ?>">
            <td><?= htmlspecialchars($evt['date_heure']) ?></td>
            <td><?= htmlspecialchars($evt['nature']) ?></td>
            <?php
            // identifiant_saisi est une chaîne FOURNIE PAR L'UTILISATEUR —
            // donc potentiellement par un attaquant. Sans htmlspecialchars(),
            // saisir <script>...</script> comme identifiant créerait une
            // faille XSS dans notre propre console de sécurité.
            ?>
            <td><?= htmlspecialchars($evt['identifiant_saisi'] ?? '') ?></td>
            <td><?= htmlspecialchars($evt['adresse_ip']) ?></td>
            <td><?= htmlspecialchars($evt['ressource'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($evenements)): ?>
        <tr><td colspan="5" style="text-align:center;">Aucun évènement pour ces filtres.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>
