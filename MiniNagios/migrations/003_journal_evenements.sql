-- =============================================================================
-- Migration 003 : journal des évènements (séance 2 — Journalisation)
-- =============================================================================
-- Objectif : donner à l'application la capacité de dire QUI a fait QUOI et
-- QUAND. C'est le critère P (Preuve) du DICP, et la réponse à la faille
-- A09:2021 de l'OWASP — l'absence de journalisation.
--
-- Application :
--   docker exec -i bloc3 psql -U prof_sio -d mininagios < migrations/003_journal_evenements.sql
-- =============================================================================

CREATE TABLE IF NOT EXISTS journal_evenements (
    id                SERIAL       PRIMARY KEY,
    date_heure        TIMESTAMP    NOT NULL DEFAULT NOW(),
    nature            VARCHAR(20)  NOT NULL,
    id_utilisateur    INT          NULL,
    identifiant_saisi VARCHAR(255) NULL,
    adresse_ip        VARCHAR(45)  NOT NULL,
    ressource         VARCHAR(100) NULL,
    details           TEXT         NULL,

    -- ON DELETE SET NULL, et surtout PAS ON DELETE CASCADE : si un compte est
    -- supprimé, ses traces doivent survivre. Avec CASCADE, il suffirait à un
    -- attaquant ayant obtenu les droits de supprimer son propre compte pour
    -- effacer d'un coup toute la preuve de son passage.
    CONSTRAINT fk_journal_admin
        FOREIGN KEY (id_utilisateur) REFERENCES administrateurs(id)
        ON DELETE SET NULL
);

-- Deux colonnes portent la quasi-totalité des interrogations du journal :
-- la date (« que s'est-il passé ces 10 dernières minutes ? ») et la nature
-- (« montre-moi les échecs d'authentification »). Un journal se remplit vite ;
-- sans index, ces requêtes imposeraient une lecture complète de la table.
CREATE INDEX IF NOT EXISTS idx_journal_date   ON journal_evenements(date_heure);
CREATE INDEX IF NOT EXISTS idx_journal_nature ON journal_evenements(nature);
