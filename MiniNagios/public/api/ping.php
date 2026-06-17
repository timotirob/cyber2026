<?php
// 1. L'en-tête (Header) : On prévient le client qu'il va recevoir du JSON, pas du HTML
header("Content-Type: application/json; charset=UTF-8");

// 2. On prépare nos données sous forme de tableau associatif PHP
$reponse = [
    "statut" => "OK",
    "message" => "Le moteur Mini-Nagios est en ligne et fonctionnel.",
    "version" => "1.0.0",
    "heure_serveur" => date("H:i:s")
];
// 3. La conversion (Magie) : On transforme le tableau PHP en chaîne JSON
echo json_encode($reponse);