<?php
// Chargement via bootstrap.php (et non vendor/autoload.php seul) : c'est lui qui
// charge le .env, sans quoi Database::getConnection ne peut pas se connecter.
require '../config/bootstrap.php';

use App\Database;
use App\ServeurRepository;
use App\Journal;
use App\Securite;
use App\ControleAcces;

// Une suppression n'a de sens que pour un administrateur connecté : la session
// est nécessaire pour disposer de $_SESSION['admin_id'], sur lequel porte le
// contrôle d'accès ci-dessous.
Securite::verifierConnexion();

// Vérification que l'ID est bien présent dans l'URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int) $_GET['id'];

    try {
        $pdo      = Database::getConnection();
        $repo     = new ServeurRepository($pdo);
        $journal  = new Journal($pdo);
        $controle = new ControleAcces($pdo);

        // Contrôle d'accès applicatif (séance 5) : la fonction de l'administrateur
        // autorise-t-elle le delete sur serveurs ? On vérifie AVANT d'agir.
        if (!$controle->possedeDroit($_SESSION['admin_id'], 'serveurs', 'delete')) {
            // Journalisation de la tentative refusée (séance 2).
            $journal->enregistrer(
                Journal::ACCES_REFUSE,
                null,
                $_SESSION['admin_id'],
                'serveur:' . $id,
                'Suppression refusée : droit manquant'
            );
            http_response_code(403);
            exit('Opération non autorisée.');
        }

        // Seulement ici, la suppression effective.
        $repo->supprimerParId($id);

    } catch (Exception $e) {
        die("Erreur lors de la suppression : " . $e->getMessage());
    }
}

// Redirection vers le dashboard dans tous les cas
header("Location: dashboard.php?success=1");
exit();
