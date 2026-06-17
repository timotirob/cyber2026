<?php
// Fichier : public/api/serveurs.php (VERSION EXERCICE 1)
require '../../config/bootstrap.php';

use App\Database;
use App\ServeurRepository;
use App\Securite;

// Le cadenas ajouté à l'Exercice 2 !
Securite::verifierCleApi();
header("Content-Type: application/json; charset=UTF-8");

$pdo = Database::getConnection();
$repo = new ServeurRepository($pdo);
echo json_encode($repo->listerTous());