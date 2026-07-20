-- =============================================================================
-- Migration 002 : élargissement de la colonne ip pour accepter l'IPv6
-- =============================================================================
-- Une adresse IPv4 en notation décimale pointée tient en 15 caractères
-- (255.255.255.255). Dimensionner la colonne sur cette valeur était donc
-- correct... en 1998.
--
-- Une adresse IPv6 peut atteindre 45 caractères. Le cas le plus long est
-- l'écriture mixte IPv4-mappée, par exemple :
--   0000:0000:0000:0000:0000:ffff:255.255.255.255
--
-- Le défaut était visible côté application : Validator::isIpValid() accepte
-- déjà l'IPv6 (filter_var avec FILTER_VALIDATE_IP, sans drapeau restrictif),
-- mais l'insertion échouait ensuite au niveau de PostgreSQL. La validation
-- métier et le schéma disaient deux choses différentes.
--
-- 45 est la valeur à retenir pour l'épreuve : répondre « 15 » sur une
-- application moderne est une erreur classique.
--
-- Application :
--   docker exec -i bloc3 psql -U prof_sio -d mininagios < migrations/002_ip_ipv6.sql
--
-- Cette migration est sans risque de perte : on élargit une colonne, on ne la
-- rétrécit pas. Les lignes existantes sont conservées telles quelles.
-- =============================================================================

ALTER TABLE serveurs
    ALTER COLUMN ip TYPE VARCHAR(45);
