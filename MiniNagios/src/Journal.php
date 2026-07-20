<?php
namespace App;

/**
 * Journal des évènements de sécurité de l'application.
 *
 * Toute la journalisation passe par cette classe. On aurait pu éparpiller des
 * INSERT INTO journal_evenements dans chaque page, mais le jour où la structure
 * du journal change, il faudrait retrouver et corriger chacun d'eux. C'est le
 * même raisonnement que pour le pattern Repository.
 *
 * Règle absolue : on journalise l'IDENTIFIANT de l'acteur et la NATURE de son
 * action, jamais le secret manipulé. Un journal qui consignerait les mots de
 * passe saisis transformerait le dispositif de sécurité en la pire des failles,
 * puisqu'un journal est un condensé de l'activité du système — donc une cible
 * de choix.
 */
class Journal
{
    // Constantes de classe : elles évitent les fautes de frappe dans les chaînes.
    // "CONNEXION" tapé en dur 15 fois dans le code, c'est 15 occasions de se tromper.
    // Une constante mal orthographiée provoque une erreur PHP immédiate ; une
    // chaîne mal orthographiée s'insère silencieusement en base et fausse toutes
    // les requêtes de détection.
    public const CONNEXION     = 'CONNEXION';
    public const ECHEC_AUTH    = 'ECHEC_AUTH';
    public const DECONNEXION   = 'DECONNEXION';
    public const CREATION      = 'CREATION';
    public const SUPPRESSION   = 'SUPPRESSION';
    public const ACCES_REFUSE  = 'ACCES_REFUSE';
    public const ERREUR        = 'ERREUR';

    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Enregistre un évènement dans le journal.
     *
     * @param string      $nature           une des constantes de classe ci-dessus
     * @param string|null $identifiantSaisi identifiant tel que saisi (peut être faux)
     * @param int|null    $idUtilisateur    id de l'admin si authentifié, null sinon
     * @param string|null $ressource        objet concerné (ex : "serveur:12")
     * @param string|null $details          complément d'information (JAMAIS de secret)
     */
    public function enregistrer(
        string  $nature,
        ?string $identifiantSaisi = null,
        ?int    $idUtilisateur = null,
        ?string $ressource = null,
        ?string $details = null
    ): void {
        $sql = "INSERT INTO journal_evenements
                    (nature, identifiant_saisi, id_utilisateur, adresse_ip, ressource, details)
                VALUES (:nature, :identifiant, :idUtil, :ip, :ressource, :details)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nature'      => $nature,
            'identifiant' => $identifiantSaisi,
            'idUtil'      => $idUtilisateur,
            'ip'          => $this->recupererIp(),
            'ressource'   => $ressource,
            'details'     => $details
        ]);
    }

    /**
     * Récupère l'adresse IP du client.
     *
     * Cette méthode est privée parce qu'elle est un détail d'implémentation :
     * l'appelant dit « enregistre une connexion », il n'a pas à savoir comment
     * l'adresse est obtenue. Une méthode publique est un engagement de long
     * terme envers le reste de l'application ; une méthode privée reste libre
     * d'évoluer.
     *
     * On s'en tient volontairement à REMOTE_ADDR, constaté par le serveur.
     * L'en-tête HTTP_X_FORWARDED_FOR, que l'on voit souvent utilisé ici, est
     * envoyé par le client : n'importe qui peut le falsifier et polluer le
     * journal de fausses adresses, rendant la traçabilité inexploitable.
     */
    private function recupererIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'inconnue';
    }
}
