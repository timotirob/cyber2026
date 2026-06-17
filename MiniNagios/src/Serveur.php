<?php
namespace App;

class Serveur extends EquipementReseau
{
    private string $os;
    private array $services = []; // Le tableau de stockage

    private ?string $rootPasswordHybride; // "Nullable" au cas où on n'en a pas

    // TDD : Propriété ajoutée pour faire passer le test "Mode Maintenance"
    private bool $maintenance = false;

    public function __construct(string $hostname, string $ip, string $os, ?string $rootPasswordHybride = null)
    {
        // Validation de l'OS via la liste blanche
        if (!Validator::isOsSupported($os)) {
            throw new \Exception("POLITIQUE DSI : L'OS '$os' est interdit ou obsolète.");
        }

        parent::__construct($hostname, $ip); //
        $this->os = $os; //
        $this->rootPasswordHybride = $rootPasswordHybride;
    }

    // TDD : Méthodes getters/setters pour la maintenance
    public function activerMaintenance(): void
    {
        $this->maintenance = true;
    }

    public function enMaintenance(): bool
    {
        return $this->maintenance;
    }

    public function getOs(): string {
        return $this->os;
    }

    public function getRootPasswordHybride(): ?string {
        return $this->rootPasswordHybride;
    }


    // Méthode pour "clipser" un service (Composition)
    public function ajouterService(Service $service): void
    {
        $this->services[] = $service;
    }

    // LOGIQUE EXERCICE 3 : Santé Globale
    public function verifierSante(): string
    {
        foreach ($this->services as $service) {
            // Si un service critique est éteint -> ALERTE
            if ($service->estCritique() && !$service->estDemarre()) {
                return "<span style='color:red; font-weight:bold; font-size:1.2em'>⚠️ DANGER (Service Critique HS)</span>";
            }
        }
        return "<span style='color:green; font-weight:bold'>✅ Système Nominal</span>";
    }

    public function afficherStatut(): string
    {
        $html = parent::afficherStatut() . " | OS : $this->os <br>";


        // Affichage de la santé globale (Ex 3)
        $html .= "Santé Globale : " . $this->verifierSante() . "<br>";

        // Boucle sur les services
        if (empty($this->services)) {
            $html .= "<em>Aucun service installé.</em>";
        } else {
            foreach ($this->services as $service) {
                $html .= $service->getStatut();
            }
        }

        // TDD : Modification de l'affichage si maintenance
        if ($this->maintenance) {
            $html = "🚧 [MAINTENANCE] 🚧 " . $html;
        }

        return $html;
    }

    // Helper pour faciliter le test de composition (Optionnel mais pratique)
    public function getNbServices(): int
    {
        return count($this->services);
    }
}