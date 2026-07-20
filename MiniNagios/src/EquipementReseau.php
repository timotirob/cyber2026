<?php
namespace App; // On déclare que cette classe appartient à l'espace de nom "App"

class EquipementReseau
{
    // ATTRIBUTS : Les caractéristiques de l'objet
    // Visibilité "protected" : accessible par la classe elle-même ET ses enfants (héritage)
    // Nous n'utilisons pas "public" pour respecter l'ENCAPSULATION.
    protected string $hostname;
    protected string $ip;

    // CONSTRUCTEUR : La méthode appelée automatiquement à la création (new)
    // C'est ici qu'on force la cohérence de l'objet.
    // Pas de setter ! Un équipement DOIT avoir une IP dès sa naissance.
    public function __construct(string $hostname, string $ip)
    {
        // ÉTAPE 1 : Validation défensive
        // Avant même d'assigner quoi que ce soit, on vérifie !
        if (!Validateur::estIpValide($ip)) {
            // Si l'IP est pourrie, on lance une Exception (une erreur fatale contrôlée)
            throw new \Exception("ERREUR DE SÉCURITÉ : L'IP '$ip' n'est pas valide !");
        }

        // 2. Validation du Hostname (CORRECTION ICI)
        if (!Validateur::estHostnameValide($hostname)) {
            throw new \Exception("SÉCURITÉ : Le nom '$hostname' contient des caractères interdits (Espaces ou accents).");
        }

        $this->hostname = $hostname;
        $this->ip = $ip;
    }
    public function getHostname(): string {
        return $this->hostname;
    }
    public function getIp(): string {
        return $this->ip;
    }

    // MÉTHODE : Une action que l'objet peut faire
    public function afficherStatut(): string
    {
        return "Équipement : $this->hostname ($this->ip)";
    }
}