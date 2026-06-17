<?php
namespace App;

class Service
{
    private string $nom;
    private int $port;
    private bool $estDemarre;
    private bool $estCritique; // Ajout Exercice 3

    // On ajoute un argument optionnel pour la criticité (par défaut false)
    public function __construct(string $nom, int $port, bool $estCritique = false)
    {
        // Validation basique du port (Ex 1)
        if ($port < 1 || $port > 65535) {
            throw new \Exception("SERVICE : Le port $port est invalide.");
        }

        $this->nom = $nom;
        $this->port = $port;
        $this->estCritique = $estCritique;
        $this->estDemarre = false; // Toujours éteint à la création
    }

    public function demarrer(): void
    {
        $this->estDemarre = true;
    }

    public function arreter(): void
    {
        $this->estDemarre = false;
    }

    // Getters nécessaires pour le Serveur (Ex 3)
    public function estDemarre(): bool
    {
        return $this->estDemarre;
    }

    public function estCritique(): bool
    {
        return $this->estCritique;
    }

    public function getStatut(): string
    {
        $couleur = $this->estDemarre ? "green" : "red";
        $etatStr = $this->estDemarre ? "ON" : "OFF";
        $critiqueStr = $this->estCritique ? " (CRITIQUE)" : "";

        return "<span style='color:$couleur; border:1px solid $couleur; padding:2px; margin-right:5px;'>
                    [$this->nom : $etatStr $critiqueStr]
                </span>";
    }
}