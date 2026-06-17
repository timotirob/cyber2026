<?php
namespace App;

class Database
{
    // Méthode statique car on a juste besoin de l'outil de connexion
    public static function getConnection(): \PDO
    {
        // Technique de "Blindage Absolu" (Null Coalescing Operator)
        // On cherche la variable dans $_ENV, puis dans $_SERVER, puis via getenv()
        $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST');
        $port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? getenv('DB_PORT') ?? '5432';
        $dbname = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME');
        $user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER');
        $pass = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? getenv('DB_PASS');

        // Sécurité : si la variable $host est toujours vide, c'est que le fichier .env n'est pas lu
        if (!$host) {
            throw new \Exception("ERREUR DEV : Impossible de lire le fichier .env. Avez-vous bien mis 'require ../config/bootstrap.php;' en haut de votre page ?");
        }

        try {
            // Le DSN pour PostgreSQL
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ];

            return new \PDO($dsn, $user, $pass, $options);

        } catch (\PDOException $e) {
            throw new \Exception("ERREUR CRITIQUE : Impossible de se connecter à PostgreSQL. " . $e->getMessage());
        }
    }
}