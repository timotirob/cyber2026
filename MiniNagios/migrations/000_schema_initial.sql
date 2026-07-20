-- =============================================================================
-- Migration 000 : schéma initial du Mini Nagios (état de fin de 1re année)
-- =============================================================================
-- Ce script décrit la base telle qu'elle existait AVANT la séance 1 du bloc 3 :
-- des serveurs supervisés, et des administrateurs qui possèdent tous déjà un
-- mot de passe. L'enrôlement par lien magique arrive avec la migration 001.
--
-- Pourquoi versionner le schéma dans le dépôt ?
-- Parce qu'une base créée « à la main dans la console SQL » n'est reproductible
-- par personne d'autre. Un collègue qui clone le projet doit pouvoir rejouer les
-- migrations dans l'ordre et obtenir exactement la même base que vous.
--
-- Application :
--   docker exec -i bloc3 psql -U prof_sio -d mininagios < migrations/000_schema_initial.sql
--
-- Le script est écrit pour être rejouable sans erreur sur une base déjà à jour
-- (IF NOT EXISTS) : le relancer par mégarde ne détruit rien.
-- =============================================================================

-- Les équipements supervisés.
CREATE TABLE IF NOT EXISTS serveurs (
    id                    SERIAL       PRIMARY KEY,
    hostname              VARCHAR(255) NOT NULL,
    ip                    VARCHAR(15)  NOT NULL,
    os                    VARCHAR(100) NOT NULL,
    date_creation         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    root_password_hybride TEXT         NULL
);

-- Les comptes autorisés à accéder à la console de supervision.
-- password_hash fait 255 caractères : largement de quoi loger un hash Argon2id,
-- qui est bien plus long qu'un hash BCRYPT.
CREATE TABLE IF NOT EXISTS administrateurs (
    id            SERIAL       PRIMARY KEY,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);
