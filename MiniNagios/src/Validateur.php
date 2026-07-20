<?php
namespace App;

class Validateur
{
    // ... Méthodes estIpValide et estHostnameValide déjà existantes ...

    public static function estIpValide(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public static function estHostnameValide(string $hostname): bool
    {
        return preg_match('/^[a-zA-Z0-9-]+$/', $hostname);
    }

    /**
     * CORRECTION EXERCICE 3 : Validation du type d'imprimante
     */
    public static function estTypeImprimanteValide(string $type): bool
    {
        // Liste blanche des types autorisés
        $typesAutorises = ["Laser", "Jet d'encre", "Thermique", "Matricielle"];

        // in_array vérifie si $type est présent dans le tableau
        return in_array($type, $typesAutorises);
    }

    /**
     * CORRECTION EXERCICE 4 : Validation de l'OS Serveur
     */
    public static function estOsSupporte(string $os): bool
    {
        $osAutorises = ["Debian 12", "Ubuntu 24.04", "Windows Server 2022", "RedHat 9"];

        return in_array($os, $osAutorises);
    }
}