<?php
namespace App;

class Securite
{
    /**
     * Méthode statique à appeler au début de chaque page à protéger.
     */
    public static function verifierConnexion(): void
    {
        // On démarre la session uniquement si elle n'est pas déjà active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si la variable de session n'existe pas, c'est un intrus
        if (!isset($_SESSION['admin_id'])) {
            header("Location: login.php?erreur=acces_refuse");
            exit();
        }
    }

    /**
     * Protège une API REST en vérifiant l'en-tête HTTP X-API-KEY
     */
    public static function verifierCleApi(): void
    {
        // La clé vient du fichier .env, jamais du code source.
        // Une clé écrite en dur part dans l'historique Git : la changer plus tard
        // ne l'efface pas, elle reste lisible dans tous les anciens commits et
        // dans toutes les copies du dépôt.
        $cleSecrete = $_ENV['API_SECRET_KEY'] ?? $_SERVER['API_SECRET_KEY'] ?? getenv('API_SECRET_KEY');

        if (!$cleSecrete) {
            throw new \Exception("ERREUR DEV : API_SECRET_KEY absente du fichier .env.");
        }

        // On cherche l'en-tête personnalisé envoyé par le client
        // PHP préfixe les en-têtes personnalisés par HTTP_ et remplace les tirets par des underscores
        $cleFournie = $_SERVER['HTTP_X_API_KEY'] ?? '';

        // hash_equals() compare les deux chaînes en un temps constant, quel que
        // soit le nombre de caractères déjà justes. Un !== classique s'arrête au
        // premier caractère qui diffère : en chronométrant les réponses, un
        // attaquant reconstituerait la clé caractère par caractère.
        if (!hash_equals($cleSecrete, $cleFournie)) {
            // Accès refusé : on modifie le statut HTTP à 401 (Unauthorized)
            http_response_code(401);

            // On prévient qu'on répond en JSON
            header("Content-Type: application/json; charset=UTF-8");

            // On envoie le message d'erreur
            echo json_encode(["erreur" => "Accès refusé, clé API invalide ou absente."]);

            // On coupe brutalement l'exécution pour protéger les données
            exit();
        }
    }
}