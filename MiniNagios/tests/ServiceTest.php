<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Service;

class ServiceTest extends TestCase
{
    public function testCreationService()
    {
        // 1. Arrange (Préparation)
        $service = new Service("Apache", 80);

        // 2. Act (Action) & 3. Assert (Vérification)
        // Vérifions que le service est éteint par défaut
        $this->assertFalse($service->estDemarre());
    }

    public function testDemarrageEtArret()
    {
        $service = new Service("MySQL", 3306);

        // On démarre
        $service->demarrer();
        $this->assertTrue($service->estDemarre(), "Le service devrait être démarré");

        // On arrête
        $service->arreter();
        $this->assertFalse($service->estDemarre(), "Le service devrait être arrêté");
    }

    public function testPortInvalideLanceException()
    {
        // Ici, on teste que notre code PLANTE correctement quand on l'attaque
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("invalide");

        // Cette ligne doit provoquer l'exception
        new Service("BadService", 999999);
    }
}