<?php
namespace App;

class Routeur extends EquipementReseau
{
    private int $nbPorts;

    public function __construct(string $hostname, string $ip, int $nbPorts)
    {
        // 1. Validation spécifique au Routeur (CORRECTION ICI)
        // On vérifie AVANT d'appeler le parent ou d'assigner quoi que ce soit.
        if ($nbPorts < 1 || $nbPorts > 128) {
            throw new \Exception("CONFIG : Un routeur doit avoir entre 1 et 128 ports (Reçu: $nbPorts).");
        }

        // 2. Appel du constructeur parent (qui va valider IP et Hostname)
        parent::__construct($hostname, $ip);

        // 3. Assignation
        $this->nbPorts = $nbPorts;
    }

    public function getNbPorts(): int
    {
        return $this->nbPorts;
    }

    public function afficherStatut(): string
    {
        return parent::afficherStatut() . " | Ports : $this->nbPorts";
    }
}