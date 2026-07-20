-- =====================================================================
-- Séance 5 : groupes de serveurs (relation 1,n)
-- =====================================================================
-- À exécuter en tant que SUPERUTILISATEUR (DB_SUPER_USER) :
--   docker exec -i bloc3 psql -U prof_sio -d mininagios < migrations/006_groupes_serveurs.sql

CREATE TABLE IF NOT EXISTS groupe (
    id      SERIAL PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL,
    CONSTRAINT uq_groupe_libelle UNIQUE (libelle)
);

-- La clé étrangère va du côté « plusieurs » : c'est le SERVEUR qui porte le groupe.
ALTER TABLE serveurs ADD COLUMN IF NOT EXISTS id_groupe INTEGER;

-- ADD CONSTRAINT n'a pas de forme « IF NOT EXISTS » en PostgreSQL : on garde la
-- création derrière un test sur pg_constraint, pour que la migration reste
-- rejouable sans erreur (même principe que les CREATE ROLE de la migration 004).
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_constraint WHERE conname = 'fk_serveur_groupe') THEN
        ALTER TABLE serveurs
            ADD CONSTRAINT fk_serveur_groupe
            FOREIGN KEY (id_groupe) REFERENCES groupe(id)
            ON DELETE RESTRICT;
    END IF;
END
$$;

-- Quelques groupes de départ
INSERT INTO groupe (libelle) VALUES ('Production'), ('Recette'), ('Sauvegarde')
    ON CONFLICT (libelle) DO NOTHING;
