<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Validateur;

class ValidateurTest extends TestCase
{
    public function testIpValide()
    {
        $this->assertTrue(Validateur::estIpValide("192.168.1.1"));
    }

    public function testIpInvalide()
    {
        $this->assertFalse(Validateur::estIpValide("Patate"));
        $this->assertFalse(Validateur::estIpValide("999.999.999.999"));
    }

    // CORRECTION EXERCICE 1 : Tests Hostname
    public function testHostnameValide()
    {
        // Cas nominaux
        $this->assertTrue(Validateur::estHostnameValide("SRV-WEB-01"));
        $this->assertTrue(Validateur::estHostnameValide("srv-web"));
        $this->assertTrue(Validateur::estHostnameValide("123-SERV"));
    }

    public function testHostnameInvalide()
    {
        // Cas d'erreurs
        $this->assertFalse(Validateur::estHostnameValide("SRV WEB"), "Espace interdit");
        $this->assertFalse(Validateur::estHostnameValide("SRV_WEB"), "Underscore interdit");
        $this->assertFalse(Validateur::estHostnameValide("Hôte"), "Accents interdits");
        $this->assertFalse(Validateur::estHostnameValide(""), "Vide interdit");
    }
}