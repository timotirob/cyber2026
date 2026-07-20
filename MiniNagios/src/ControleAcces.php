<?php
namespace App;

/**
 * Vérifie les droits applicatifs d'un administrateur, à partir du modèle
 * fonction / operation / table_bdd / autorise.
 *
 * Réplique le contrôle possedeDroit du dossier C de la session 2025,
 * transposé du diagramme de classes vers une interrogation directe de la base.
 */
class ControleAcces
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Indique si l'administrateur a le droit de réaliser l'opération
     * demandée sur la table demandée.
     *
     * @param int    $idAdministrateur identifiant de l'administrateur
     * @param string $uneTable         nom de la table visée
     * @param string $uneOperation     'select', 'insert', 'update' ou 'delete'
     * @return bool
     */
    public function possedeDroit(int $idAdministrateur, string $uneTable, string $uneOperation): bool
    {
        $sql = "SELECT COUNT(*) AS nb
                FROM administrateurs a
                JOIN autorise   au ON au.id_fonction  = a.id_fonction
                JOIN operation  o  ON o.id            = au.id_operation
                JOIN table_bdd  t  ON t.id            = au.id_table_bdd
                WHERE a.id   = :idAdmin
                  AND t.nom  = :uneTable
                  AND o.nom  = :uneOperation";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'idAdmin'      => $idAdministrateur,
            'uneTable'     => $uneTable,
            'uneOperation' => $uneOperation
        ]);

        $ligne = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ((int) $ligne['nb']) > 0;
    }
}
