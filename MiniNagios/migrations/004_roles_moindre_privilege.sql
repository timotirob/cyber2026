-- =============================================================================
-- Migration 004 : rôles à moindre privilège (séance 3 — Droits SGBD)
-- =============================================================================
-- Jusqu'ici, l'application se connectait avec le compte superutilisateur :
-- une seule injection SQL réussie aurait suffi à détruire la base entière.
-- Cette migration crée deux comptes restreints, chacun limité à ce dont son
-- usage a strictement besoin. C'est le principe de moindre privilège, réponse
-- à la faille A01:2021 – Contrôles d'accès défaillants.
--
-- À exécuter en tant que SUPERUTILISATEUR (DB_SUPER_USER) :
--   docker exec -i bloc3 psql -U prof_sio -d mininagios < migrations/004_roles_moindre_privilege.sql
--
-- Les rôles sont créés SANS mot de passe : un mot de passe écrit dans un
-- fichier versionné resterait lisible dans tout l'historique Git. La commande
-- qui le définit, alimentée par le .env, est documentée dans le README :
--   ALTER ROLE mininagios_app WITH PASSWORD '...';
-- Tant qu'elle n'a pas été exécutée, le compte ne peut pas se connecter par
-- le réseau — c'est un état sûr, pas un oubli.
--
-- Le script est rejouable : CREATE ROLE n'ayant pas de IF NOT EXISTS en
-- PostgreSQL, la création est protégée par un test explicite sur pg_roles.
-- Les GRANT, eux, sont naturellement rejouables (ré-accorder un droit déjà
-- accordé ne fait rien).
-- =============================================================================

-- 1. Fermer ce qui est ouvert à tous.
-- Le pseudo-rôle PUBLIC, auquel tout rôle appartient implicitement, reçoit
-- CONNECT par défaut sur chaque base créée. Restreindre nos comptes n'aurait
-- aucun sens si n'importe quel rôle pouvait déjà se connecter.
REVOKE ALL ON DATABASE mininagios FROM PUBLIC;

-- 2. Création des deux rôles (sans mot de passe, voir l'en-tête).
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'mininagios_app') THEN
        CREATE ROLE mininagios_app WITH LOGIN;
    END IF;
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'mininagios_audit') THEN
        CREATE ROLE mininagios_audit WITH LOGIN;
    END IF;
END
$$;

-- 3. Le compte applicatif : ce que l'application web fait au quotidien,
-- et rien de plus.
GRANT CONNECT ON DATABASE mininagios TO mininagios_app;
GRANT USAGE   ON SCHEMA public       TO mininagios_app;

-- Table serveurs : l'application gère le cycle de vie complet.
GRANT SELECT, INSERT, UPDATE, DELETE ON serveurs TO mininagios_app;
GRANT USAGE, SELECT ON SEQUENCE serveurs_id_seq TO mininagios_app;

-- Table administrateurs : lecture et mise à jour, mais AUCUNE suppression.
-- Supprimer un administrateur est une opération d'exploitation, pas une
-- opération applicative.
GRANT SELECT, INSERT, UPDATE ON administrateurs TO mininagios_app;
GRANT USAGE, SELECT ON SEQUENCE administrateurs_id_seq TO mininagios_app;

-- Table journal_evenements : INSERT et SELECT uniquement.
-- Ni UPDATE ni DELETE : le journal est en écriture seule (append-only).
-- Conséquence : même un attaquant qui contrôle entièrement l'application
-- ne peut pas effacer ses traces. C'est ce qui transforme un journal en
-- preuve — l'aboutissement de la séance 2 et de son ON DELETE SET NULL.
GRANT SELECT, INSERT ON journal_evenements TO mininagios_app;
GRANT USAGE, SELECT ON SEQUENCE journal_evenements_id_seq TO mininagios_app;

-- Le piège classique : INSERT sur la table ne suffit pas. Les colonnes SERIAL
-- s'appuient sur des séquences, objets distincts ; sans USAGE sur la séquence,
-- l'insertion échoue avec "permission denied for sequence ...". D'où les
-- GRANT ... ON SEQUENCE ci-dessus.

-- 4. Le compte d'audit : lecture du journal, rien d'autre.
-- Destiné à la future console de supervision — qui n'a aucune raison de
-- pouvoir écrire, ni de lire les autres tables.
GRANT CONNECT ON DATABASE mininagios TO mininagios_audit;
GRANT USAGE   ON SCHEMA public       TO mininagios_audit;
GRANT SELECT  ON journal_evenements  TO mininagios_audit;
