-- =============================================================================
-- Migration 005 : rôle de démonstration en lecture seule (séance 3, ex. 5)
-- =============================================================================
-- Cas d'usage : présenter l'application à un client sans risque qu'une fausse
-- manipulation modifie les données. Le rôle mininagios_demo peut TOUT lire et
-- ne peut RIEN écrire.
--
-- À exécuter en tant que SUPERUTILISATEUR (DB_SUPER_USER) :
--   docker exec -i bloc3 psql -U prof_sio -d mininagios < migrations/005_role_demo.sql
--
-- Même politique que la migration 004 : pas de mot de passe dans un fichier
-- versionné. À définir ensuite si nécessaire, via ALTER ROLE.
--
-- Réponse à la question 2 de l'exercice : avec ce compte, la CONNEXION à
-- l'application réussit (lecture d'administrateurs et password_verify), mais
-- l'exécution s'arrête au moment de journaliser le succès — premier INSERT,
-- premier refus. C'est un bon rappel que le moindre privilège se raisonne par
-- OPÉRATION, pas par page : une page « de consultation » peut très bien écrire.
-- =============================================================================

DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'mininagios_demo') THEN
        CREATE ROLE mininagios_demo WITH LOGIN;
    END IF;
END
$$;

GRANT CONNECT ON DATABASE mininagios TO mininagios_demo;
GRANT USAGE   ON SCHEMA public       TO mininagios_demo;

-- SELECT sur toutes les tables EXISTANTES du schéma.
GRANT SELECT ON ALL TABLES IN SCHEMA public TO mininagios_demo;

-- Réponse à la question 3 de l'exercice : non, un futur CREATE TABLE
-- n'accorderait RIEN automatiquement — « ALL TABLES » est évalué au moment du
-- GRANT, pas en continu. ALTER DEFAULT PRIVILEGES comble ce trou : il
-- pré-accorde le SELECT sur les tables que le rôle courant (le
-- superutilisateur, qui exécute les migrations) créera à l'avenir.
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT SELECT ON TABLES TO mininagios_demo;
