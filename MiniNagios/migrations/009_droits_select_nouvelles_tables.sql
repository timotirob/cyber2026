-- =====================================================================
-- Séance 5 : droits SELECT du compte applicatif sur le modèle de droits
-- =====================================================================
-- Le contrôle d'accès applicatif (src/ControleAcces::possedeDroit) interroge
-- les tables du modèle de droits pour savoir si un administrateur peut agir.
-- Or le compte mininagios_app (migration 004) n'a de droits que sur les trois
-- tables d'origine. Sans les GRANT ci-dessous, possedeDroit échouerait avec
-- « permission denied for table autorise ».
--
-- À exécuter en tant que SUPERUTILISATEUR (DB_SUPER_USER) :
--   docker exec -i bloc3 psql -U prof_sio -d mininagios < migrations/009_droits_select_nouvelles_tables.sql
--
-- SELECT uniquement : l'application LIT ces tables (contrôle d'accès, affichage
-- du groupe d'un serveur), elle ne les modifie pas. Le peuplement des droits
-- relève de l'exploitation (migrations exécutées en superutilisateur), pas de
-- l'application. On ne grante donc NI INSERT/UPDATE/DELETE, NI les séquences :
-- accorder un droit dont l'application ne se sert pas contredirait le moindre
-- privilège mis en place en séance 3.

GRANT SELECT ON fonction  TO mininagios_app;
GRANT SELECT ON operation TO mininagios_app;
GRANT SELECT ON table_bdd TO mininagios_app;
GRANT SELECT ON autorise  TO mininagios_app;
GRANT SELECT ON groupe    TO mininagios_app;
