<?php
namespace App;

class ServeurRepository
{
    private \PDO $pdo;

    // Injection de dépendance : Le repository a besoin de PDO pour fonctionner
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listerTous(): array
    {
        $sql = "SELECT * FROM serveurs ORDER BY date_creation DESC"; // [cite: 320]
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(); // [cite: 321]
    }

    // CORRECTION EXERCICE 2 : Supprimer un serveur
    public function supprimerParId(int $id): void
    {
        $sql = "DELETE FROM serveurs WHERE id = :id"; // [cite: 329]
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    /**
     * Sauvegarde un objet Serveur dans la base de données
     */
    public function sauvegarder(Serveur $serveur): void
    {
        $sql = "INSERT INTO serveurs (hostname, ip, os, root_password_hybride) 
                VALUES (:hostname, :ip, :os, :root_pass)";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            'hostname'  => $serveur->getHostname(),
            'ip'        => $serveur->getIp(),
            'os'        => $serveur->getOs(),
            'root_pass' => $serveur->getRootPasswordHybride() // Récupération via Getter
        ]);
    }
}