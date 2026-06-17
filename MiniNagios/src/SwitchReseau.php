<?php
namespace App;

class SwitchReseau extends EquipementReseau
{
    private int $nombrePorts;
    private int $vlanGestion; // Nouvel attribut Exercice 5

    // On ajoute le VLAN dans le constructeur
    public function __construct(string $hostname, string $ip, int $nombrePorts, int $vlanGestion)
    {
        // Validation du nombre de ports (Exercice 2)
        if ($nombrePorts < 1 || $nombrePorts > 128) {
            throw new \Exception("CONFIG : Un switch doit avoir entre 1 et 128 ports.");
        }

        // Validation du VLAN (Exercice 5) - Plage 1 à 4094
        if ($vlanGestion < 1 || $vlanGestion > 4094) {
            throw new \Exception("CONFIG : Le VLAN de gestion '$vlanGestion' est invalide (Max 4094).");
        }

        parent::__construct($hostname, $ip); //
        $this->nombrePorts = $nombrePorts; //
        $this->vlanGestion = $vlanGestion;
    }

    public function scannerPorts(): void
    {
        echo "<h3>Scan des ports pour $this->hostname (VLAN $this->vlanGestion) :</h3>"; //

        for ($i = 1; $i <= $this->nombrePorts; $i++) { //
            $estConnecte = rand(0, 1); //

            if ($estConnecte) { //
                echo "Port $i : <span style='color:green; font-weight:bold'>Connecté</span><br>"; //
            } else {
                echo "Port $i : <span style='color:red'>Déconnecté</span><br>"; //
            }
        }
        echo "<hr>"; //
    }
}