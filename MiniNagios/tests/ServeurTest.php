<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Serveur;
use App\Service;

class ServeurTest extends TestCase
{
    public function testModeMaintenance()
    {
        // CORRECTION ICI : "Linux" -> "Debian 12"
        $srv = new Serveur("Test-Srv", "10.0.0.1", "Debian 12");

        $this->assertFalse($srv->enMaintenance());

        $srv->activerMaintenance();
        $this->assertTrue($srv->enMaintenance());

        // Note : assurez-vous que votre méthode afficherStatut() gère bien l'icône
        $this->assertStringContainsString("🚧", $srv->afficherStatut());
    }

    public function testAjoutService()
    {
        // CORRECTION ICI : "Debian" -> "Debian 12"
        $srv = new Serveur("Web", "10.0.0.1", "Debian 12");
        $apache = new Service("Apache", 80);

        $srv->ajouterService($apache);

        // Si vous avez ajouté la méthode getNbServices()
        $this->assertEquals(1, $srv->getNbServices());
    }

    public function testAlerteRougeSiServiceCritiqueEteint()
    {
        // CORRECTION ICI : "Debian" -> "Debian 12"
        $srv = new Serveur("Prod", "10.0.0.1", "Debian 12");

        $db = new Service("MySQL", 3306, true);

        $srv->ajouterService($db);

        $this->assertStringContainsString("DANGER", $srv->verifierSante());

        $db->demarrer();

// On cherche "Système Nominal" ou "Nominal" car c'est ce que retourne notre classe
        $this->assertStringContainsString("Nominal", $srv->verifierSante());    }
}