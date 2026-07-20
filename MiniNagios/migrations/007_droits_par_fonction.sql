-- =====================================================================
-- Séance 5 : modèle de droits par fonction (relation n,n)
-- Réplique le dossier C de la session d'examen 2025.
-- =====================================================================
-- À exécuter en tant que SUPERUTILISATEUR (DB_SUPER_USER) :
--   docker exec -i bloc3 psql -U prof_sio -d mininagios < migrations/007_droits_par_fonction.sql

CREATE TABLE IF NOT EXISTS fonction (
    id      SERIAL PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL,
    CONSTRAINT uq_fonction_libelle UNIQUE (libelle)
);

CREATE TABLE IF NOT EXISTS operation (
    id  SERIAL PRIMARY KEY,
    nom VARCHAR(20) NOT NULL,
    CONSTRAINT uq_operation_nom UNIQUE (nom)
);

CREATE TABLE IF NOT EXISTS table_bdd (
    id  SERIAL PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    CONSTRAINT uq_table_bdd_nom UNIQUE (nom)
);

-- La table d'association. Sa clé primaire est le TRIPLET :
-- une autorisation = (une fonction, une opération, une table).
CREATE TABLE IF NOT EXISTS autorise (
    id_fonction  INTEGER NOT NULL,
    id_operation INTEGER NOT NULL,
    id_table_bdd INTEGER NOT NULL,
    PRIMARY KEY (id_fonction, id_operation, id_table_bdd),
    CONSTRAINT fk_autorise_fonction  FOREIGN KEY (id_fonction)  REFERENCES fonction(id)  ON DELETE CASCADE,
    CONSTRAINT fk_autorise_operation FOREIGN KEY (id_operation) REFERENCES operation(id) ON DELETE RESTRICT,
    CONSTRAINT fk_autorise_table     FOREIGN KEY (id_table_bdd) REFERENCES table_bdd(id) ON DELETE RESTRICT
);

-- Rattachement d'un administrateur à une fonction.
-- ADD CONSTRAINT gardé par un test sur pg_constraint pour rester rejouable.
ALTER TABLE administrateurs ADD COLUMN IF NOT EXISTS id_fonction INTEGER;
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_constraint WHERE conname = 'fk_admin_fonction') THEN
        ALTER TABLE administrateurs
            ADD CONSTRAINT fk_admin_fonction
            FOREIGN KEY (id_fonction) REFERENCES fonction(id) ON DELETE SET NULL;
    END IF;
END
$$;

-- --- Données de référence ---------------------------------------------
INSERT INTO operation (nom) VALUES ('select'),('insert'),('update'),('delete')
    ON CONFLICT (nom) DO NOTHING;

INSERT INTO table_bdd (nom) VALUES ('serveurs'),('administrateurs'),('journal_evenements')
    ON CONFLICT (nom) DO NOTHING;

INSERT INTO fonction (libelle) VALUES ('Superviseur'),('Technicien'),('Observateur')
    ON CONFLICT (libelle) DO NOTHING;
