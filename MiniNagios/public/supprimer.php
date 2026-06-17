<?php
require '../vendor/autoload.php';

use App\Database;
use App\ServeurRepository;

// Vérification que l'ID est bien présent dans l'URL
if (isset($_GET['id']) && !empty($_GET['id'])) { // [cite: 331]
    $id = (int) $_GET['id'];

    try {
        $pdo = Database::getConnection();
        $repo = new ServeurRepository($pdo);

        // Appel de la méthode de suppression
        $repo->supprimerParId($id); // [cite: 331]

    } catch (Exception $e) {
        die("Erreur lors de la suppression : " . $e->getMessage());
    }
}

// Redirection vers le dashboard dans tous les cas
header("Location: dashboard.php?success=1"); // [cite: 331]
exit();