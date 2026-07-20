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
     * Liste les évènements du journal, avec deux filtres optionnels.
     *
     * L'astuce (:param IS NULL OR colonne = :param) vient du sujet 2026 :
     * si le paramètre n'est pas fourni, la condition est vraie pour toutes
     * les lignes — le filtre est neutralisé ; s'il est fourni, on compare
     * normalement. Une seule requête préparée couvre ainsi les quatre
     * combinaisons de filtres, sans jamais concaténer de SQL.
     *
     * Particularité PostgreSQL absente des sujets MySQL : sans le CAST,
     * PostgreSQL ne sait pas déduire le type du paramètre dans « :param
     * IS NULL » et rejette la requête (« could not determine data type »).
     * CAST(x AS type) existe dans les deux SGBD.
     *
     * @param string|null $nature une des constantes de classe, ou null (toutes)
     * @param string|null $date   au format AAAA-MM-JJ, ou null (toutes)
     */
    public function listerFiltre(?string $nature, ?string $date): array
    {
        $sql = "SELECT * FROM journal_evenements
                WHERE (CAST(:date AS DATE) IS NULL OR DATE(date_heure) = CAST(:date AS DATE))
                  AND (CAST(:nature AS VARCHAR) IS NULL OR nature = CAST(:nature AS VARCHAR))
                ORDER BY date_heure DESC
                LIMIT 200";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nature' => $nature,
            'date'   => $date
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Détecte une attaque par force brute : les adresses IP ayant produit
     * plus de $seuil échecs d'authentification sur les $minutes dernières
     * minutes.
     *
     * La condition de date va dans WHERE (elle porte sur une colonne brute,
     * et écarte les lignes AVANT le regroupement) ; la condition sur COUNT(*)
     * va forcément dans HAVING : on ne peut pas compter avant d'avoir groupé.
     *
     * L'intervalle est paramétré par « :minutes * INTERVAL '1 minute' » :
     * on ne peut pas placer un paramètre à l'intérieur d'un littéral
     * INTERVAL, mais multiplier un intervalle d'une minute fonctionne.
     * (Équivalent MySQL : DATE_SUB(NOW(), INTERVAL 10 MINUTE).)
     */
    public function detecterForceBrute(int $seuil = 5, int $minutes = 10): array
    {
        $sql = "SELECT adresse_ip, COUNT(*) AS nb_echecs
                FROM journal_evenements
                WHERE nature = :nature
                  AND date_heure >= NOW() - :minutes * INTERVAL '1 minute'
                GROUP BY adresse_ip
                HAVING COUNT(*) > :seuil
                ORDER BY nb_echecs DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nature'  => self::ECHEC_AUTH,
            'minutes' => $minutes,
            'seuil'   => $seuil
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Compte les évènements par nature sur les dernières heures.
     * C'est la requête « vue d'ensemble » : un pic soudain d'ECHEC_AUTH ou
     * d'ACCES_REFUSE se repère ici avant toute analyse fine.
     */
    public function compterParNature(int $heures = 24): array
    {
        $sql = "SELECT nature, COUNT(*) AS nombre
                FROM journal_evenements
                WHERE date_heure >= NOW() - :heures * INTERVAL '1 hour'
                GROUP BY nature
                ORDER BY nombre DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['heures' => $heures]);
        return $stmt->fetchAll();
    }

    /**
     * Les connexions réussies survenues avant 7 h ou après 20 h au cours des
     * 7 derniers jours. Une connexion administrateur à 3 h du matin est un
     * signal faible classique de compromission : le mot de passe est bon,
     * mais est-ce bien l'administrateur qui le tape ?
     */
    public function activiteHorsHeuresOuvrees(): array
    {
        $sql = "SELECT * FROM journal_evenements
                WHERE nature = :nature
                  AND date_heure >= NOW() - INTERVAL '7 days'
                  AND (EXTRACT(HOUR FROM date_heure) < 7
                       OR EXTRACT(HOUR FROM date_heure) >= 20)
                ORDER BY date_heure DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['nature' => self::CONNEXION]);
        return $stmt->fetchAll();
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
