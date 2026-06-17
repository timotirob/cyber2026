<?php
namespace App;

class RouteurRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function sauvegarder(Routeur $routeur): void
    {
        // Requête préparée pour la cybersécurité
        $sql = "INSERT INTO routeurs (hostname, ip, nb_ports) VALUES (:hostname, :ip, :ports)";
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            'hostname' => $routeur->getHostname(),
            'ip'       => $routeur->getIp(),
            'ports'    => $routeur->getNbPorts()
        ]);
    }
}