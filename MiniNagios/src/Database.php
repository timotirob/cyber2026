<?php
namespace App;

class Database
{
    // Méthodes statiques car on a juste besoin de l'outil de connexion

    /**
     * Connexion de l'application : le compte restreint mininagios_app
     * (CRUD sur serveurs, lecture-écriture sur administrateurs,
     * insertion seule dans le journal).
     */
    public static function getConnection(): \PDO
    {
        return self::creerConnexion('DB_USER', 'DB_PASS');
    }

    /**
     * Connexion d'audit : le compte mininagios_audit, qui ne sait QUE lire
     * le journal. C'est la connexion des pages de supervision (journal.php,
     * audit.php).
     *
     * Pourquoi une seconde connexion alors que les deux mots de passe sont
     * dans le même .env ? Parce que la restriction est appliquée par le SGBD,
     * pas par le PHP : même si une page d'audit contient un jour une faille
     * d'injection SQL, les requêtes de l'attaquant s'exécuteront avec un
     * compte qui ne peut rien écrire et ne voit qu'une seule table.
     */
    public static function getConnectionAudit(): \PDO
    {
        return self::creerConnexion('DB_AUDIT_USER', 'DB_AUDIT_PASS');
    }

    /**
     * Fabrique commune : seules les variables d'environnement changent
     * d'une connexion à l'autre, tout le reste (DSN, options) est identique.
     *
     * @param string $varUtilisateur nom de la variable d'environnement du compte
     * @param string $varMotDePasse  nom de la variable d'environnement du mot de passe
     */
    private static function creerConnexion(string $varUtilisateur, string $varMotDePasse): \PDO
    {
        // Technique de "Blindage Absolu" (Null Coalescing Operator)
        // On cherche la variable dans $_ENV, puis dans $_SERVER, puis via getenv()
        $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST');
        $port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? getenv('DB_PORT') ?? '5432';
        $dbname = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME');
        $user = $_ENV[$varUtilisateur] ?? $_SERVER[$varUtilisateur] ?? getenv($varUtilisateur);
        $pass = $_ENV[$varMotDePasse] ?? $_SERVER[$varMotDePasse] ?? getenv($varMotDePasse);

        // Sécurité : si la variable $host est toujours vide, c'est que le fichier .env n'est pas lu
        if (!$host) {
            throw new \Exception("ERREUR DEV : Impossible de lire le fichier .env. Avez-vous bien mis 'require ../config/bootstrap.php;' en haut de votre page ?");
        }

        if (!$user) {
            throw new \Exception("ERREUR DEV : la variable $varUtilisateur est absente du fichier .env.");
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
