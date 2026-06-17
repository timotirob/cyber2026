<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Validator;

class ValidatorTest extends TestCase
{
    public function testIpValide()
    {
        $this->assertTrue(Validator::isIpValid("192.168.1.1"));
    }

    public function testIpInvalide()
    {
        $this->assertFalse(Validator::isIpValid("Patate"));
        $this->assertFalse(Validator::isIpValid("999.999.999.999"));
    }

    // CORRECTION EXERCICE 1 : Tests Hostname
    public function testHostnameValide()
    {
        // Cas nominaux
        $this->assertTrue(Validator::isHostnameValid("SRV-WEB-01"));
        $this->assertTrue(Validator::isHostnameValid("srv-web"));
        $this->assertTrue(Validator::isHostnameValid("123-SERV"));
    }

    public function testHostnameInvalide()
    {
        // Cas d'erreurs
        $this->assertFalse(Validator::isHostnameValid("SRV WEB"), "Espace interdit");
        $this->assertFalse(Validator::isHostnameValid("SRV_WEB"), "Underscore interdit");
        $this->assertFalse(Validator::isHostnameValid("Hôte"), "Accents interdits");
        $this->assertFalse(Validator::isHostnameValid(""), "Vide interdit");
    }
}