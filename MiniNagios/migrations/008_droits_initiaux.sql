-- =====================================================================
-- Séance 5 : peuplement des droits par fonction
-- =====================================================================
-- On insère les autorisations en SÉLECTIONNANT les identifiants par leur
-- libellé (jamais d'id numérique en dur : fragile car dépendant de l'ordre
-- d'insertion). Le produit cartésien FROM fonction, operation, table_bdd,
-- filtré par le WHERE, génère exactement les triplets voulus.
-- À exécuter en tant que SUPERUTILISATEUR (DB_SUPER_USER).

-- Superviseur : tous les droits sur serveurs et administrateurs
INSERT INTO autorise (id_fonction, id_operation, id_table_bdd)
SELECT f.id, o.id, t.id
FROM fonction f, operation o, table_bdd t
WHERE f.libelle = 'Superviseur'
  AND t.nom IN ('serveurs', 'administrateurs')
ON CONFLICT DO NOTHING;

-- Technicien : lecture/écriture sur serveurs, mais pas de delete
INSERT INTO autorise (id_fonction, id_operation, id_table_bdd)
SELECT f.id, o.id, t.id
FROM fonction f, operation o, table_bdd t
WHERE f.libelle = 'Technicien'
  AND t.nom = 'serveurs'
  AND o.nom IN ('select', 'insert', 'update')
ON CONFLICT DO NOTHING;

-- Observateur : lecture seule sur serveurs et journal
INSERT INTO autorise (id_fonction, id_operation, id_table_bdd)
SELECT f.id, o.id, t.id
FROM fonction f, operation o, table_bdd t
WHERE f.libelle = 'Observateur'
  AND o.nom = 'select'
  AND t.nom IN ('serveurs', 'journal_evenements')
ON CONFLICT DO NOTHING;
