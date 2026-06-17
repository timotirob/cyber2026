<?php
namespace App;

class Imprimante extends EquipementReseau
{
    private string $type;
    private bool $estCouleur;

    public function __construct(string $hostname, string $ip, string $type, bool $estCouleur)
    {
        // Validation AVANT le parent (Fail Fast)
        if (!Validator::isPrinterTypeValid($type)) {
            throw new \Exception("ERREUR : Le type d'imprimante '$type' n'est pas supporté par la DSI.");
        }

        parent::__construct($hostname, $ip); //

        $this->type = $type; //
        $this->estCouleur = $estCouleur; //
    }

    public function afficherStatut(): string
    {
        $couleurStr = $this->estCouleur ? "OUI" : "NON"; //
        return parent::afficherStatut() . " | Type : $this->type | Couleur : $couleurStr"; //
    }
}