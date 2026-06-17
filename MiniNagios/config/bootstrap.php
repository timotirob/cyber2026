<?php
// On charge l'autoloader une seule fois ici
require_once __DIR__ . '/../vendor/autoload.php';

// On charge les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();