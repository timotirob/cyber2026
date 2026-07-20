-- =============================================================================
-- Migration 001 : enrôlement par lien magique (séance 1 — Fondations)
-- =============================================================================
-- Objectif : permettre à un compte d'exister AVANT que son titulaire n'ait
-- choisi son mot de passe. C'est tout l'intérêt du flux « Magic Link » : le DSI
-- crée le compte sans jamais connaître le secret du collaborateur.
--
-- Application :
--   docker exec -i bloc3 psql -U prof_sio -d mininagios < migrations/001_enrolement_magic_link.sql
-- =============================================================================

-- Le jeton d'activation. 64 caractères, car il est produit par
-- bin2hex(random_bytes(32)) : 32 octets aléatoires deviennent 64 caractères
-- hexadécimaux. Il est stocké en clair car il est déjà imprévisible et à durée
-- de vie très courte — contrairement à un mot de passe, qui lui est choisi par
-- un humain et réutilisé ailleurs.
ALTER TABLE administrateurs
    ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) DEFAULT NULL;

-- La date de péremption du jeton. Un lien d'activation qui ne périme jamais est
-- une porte dérobée permanente : il suffit qu'il traîne dans une boîte mail
-- compromise deux ans plus tard.
ALTER TABLE administrateurs
    ADD COLUMN IF NOT EXISTS token_expires_at TIMESTAMP DEFAULT NULL;

-- On autorise enfin un compte à exister temporairement sans mot de passe.
-- Conséquence à ne pas oublier côté PHP : password_verify() ne sait pas traiter
-- un hash NULL. Le script de connexion doit donc gérer explicitement ce cas.
ALTER TABLE administrateurs
    ALTER COLUMN password_hash DROP NOT NULL;
