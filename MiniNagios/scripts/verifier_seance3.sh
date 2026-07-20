#!/usr/bin/env bash
# =============================================================================
# Vérification de fin de séance 3 — Droits SGBD et moindre privilège
# =============================================================================
# Contrôle en une seule exécution l'état attendu en fin de séance :
#   - la mise en place (exercice 1) : rôles, droits, bascule de l'application ;
#   - la démonstration des protections (exercice 2) : ce que chaque compte
#     ne peut PLUS faire ;
#   - le parcours applicatif complet sous le compte restreint ;
#   - les travaux étudiants (exercices 3 à 5), marqués NON FAIT tant qu'ils
#     ne sont pas réalisés — ce n'est pas une erreur, c'est un état.
#
# Usage, depuis le dossier MiniNagios :
#   bash scripts/verifier_seance3.sh
#
# Prérequis : conteneurs démarrés, .env rempli, migrations appliquées.
# Effet de bord assumé : le script recrée le compte de démonstration via
# setup.php et ajoute quelques lignes au journal (c'est de l'activité normale).
#
# ATTENTION : ce script révèle des éléments de réponse des exercices 3 à 5.
# Ne pas le diffuser aux étudiants avant la fin de la séance.
# =============================================================================

cd "$(dirname "$0")/.." || exit 1

# --- Configuration -----------------------------------------------------------
CONTENEUR_DB=nagios_postgres
CONTENEUR_WEB=nagios_php_server
URL=http://localhost:8082/public
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@mininagios.local}"
ADMIN_PASS="${ADMIN_PASS:-BtsSlam2026!}"

# --- Couleurs et compteurs ---------------------------------------------------
if [ -t 1 ]; then V=$'\033[32m'; R=$'\033[31m'; J=$'\033[33m'; B=$'\033[1m'; N=$'\033[0m'
else V=""; R=""; J=""; B=""; N=""; fi
NB_OK=0; NB_KO=0; NB_NF=0

ok()    { echo "  ${V}[OK]      ${N}$1"; NB_OK=$((NB_OK+1)); }
ko()    { echo "  ${R}[ÉCHEC]   ${N}$1"; NB_KO=$((NB_KO+1)); }
nf()    { echo "  ${J}[NON FAIT]${N} $1"; NB_NF=$((NB_NF+1)); }
info()  { echo "  ${J}[INFO]    ${N}$1"; }
titre() { echo; echo "${B}== $1 ==${N}"; }

# --- Aides SQL ---------------------------------------------------------------
# sql_super : requête en superutilisateur, résultat brut (une valeur).
sql_super() { docker exec "$CONTENEUR_DB" psql -U "$DB_SUPER_USER" -d "$DB_NAME" -t -A -c "$1" 2>&1; }
# sql_role <role> <sql> : requête sous un rôle donné (socket local, sans mdp).
sql_role()  { docker exec "$CONTENEUR_DB" psql -U "$1" -d "$DB_NAME" -t -A -c "$2" 2>&1; }

# refuse <role> <sql> <libellé> : la requête DOIT échouer.
refuse() {
    if sql_role "$1" "$2" | grep -q "ERROR"; then ok "$3"; else ko "$3 — la requête est passée alors qu'elle devait être refusée"; fi
}
# autorise <role> <sql> <libellé> : la requête DOIT réussir.
autorise() {
    if sql_role "$1" "$2" | grep -q "ERROR"; then ko "$3 — refusée alors qu'elle est légitime"; else ok "$3"; fi
}

# =============================================================================
titre "Prérequis"
# =============================================================================
if [ ! -f .env ]; then echo "${R}Fichier .env introuvable — lancez le script depuis MiniNagios/.${N}"; exit 1; fi
# tr -d '\r' : un .env édité sous Windows peut contenir des fins de ligne CRLF,
# qui pollueraient les variables (mot de passe avec retour chariot invisible).
source <(tr -d '\r' < .env)
[ -n "$DB_SUPER_USER" ] && ok "variables .env chargées (DB_SUPER_USER, DB_USER=$DB_USER)" \
                        || { ko "DB_SUPER_USER absent du .env"; exit 1; }
for c in "$CONTENEUR_DB" "$CONTENEUR_WEB"; do
    [ "$(docker inspect -f '{{.State.Running}}' "$c" 2>/dev/null)" = "true" ] \
        && ok "conteneur $c démarré" || { ko "conteneur $c arrêté"; exit 1; }
done

# =============================================================================
titre "Exercice 1 — Mise en place du moindre privilège"
# =============================================================================
for role in mininagios_app mininagios_audit; do
    [ "$(sql_super "SELECT count(*) FROM pg_roles WHERE rolname='$role';")" = "1" ] \
        && ok "rôle $role créé" || ko "rôle $role absent — migration 004 non appliquée ?"
done

# Le compte que l'application utilise RÉELLEMENT (pas celui du .env : celui
# que PHP obtient au bout de la chaîne bootstrap → Database → PDO).
UTILISATEUR_APP=$(docker exec "$CONTENEUR_WEB" php -r '
    require "/var/www/html/config/bootstrap.php";
    echo App\Database::getConnection()->query("SELECT current_user")->fetchColumn();' 2>/dev/null)
[ "$UTILISATEUR_APP" = "mininagios_app" ] \
    && ok "l'application se connecte en mininagios_app" \
    || ko "l'application se connecte en « $UTILISATEUR_APP » — .env non basculé ou mot de passe non défini (README, étape 5)"

# PUBLIC ne doit plus avoir accès à la base.
ACL=$(sql_super "SELECT array_to_string(datacl, ',') FROM pg_database WHERE datname='$DB_NAME';")
if [ -z "$ACL" ]; then ko "REVOKE ALL ... FROM PUBLIC jamais appliqué (ACL par défaut)"
elif echo "$ACL" | grep -qE '(^|,)=[A-Za-z]+/'; then ko "le pseudo-rôle PUBLIC a encore des droits sur la base"
else ok "PUBLIC n'a plus aucun droit sur la base"; fi

# Les droits accordés, tels que le .adoc les prescrit.
[ "$(sql_super "SELECT count(*) FROM information_schema.role_table_grants WHERE grantee='mininagios_app' AND table_name='administrateurs' AND privilege_type='DELETE';")" = "0" ] \
    && ok "mininagios_app : pas de DELETE sur administrateurs" || ko "mininagios_app possède DELETE sur administrateurs"
[ "$(sql_super "SELECT count(*) FROM information_schema.role_table_grants WHERE grantee='mininagios_app' AND table_name='journal_evenements' AND privilege_type IN ('UPDATE','DELETE');")" = "0" ] \
    && ok "mininagios_app : journal en écriture seule (ni UPDATE ni DELETE)" || ko "le journal n'est PAS en écriture seule pour mininagios_app"
[ "$(sql_super "SELECT count(DISTINCT privilege_type) FROM information_schema.role_table_grants WHERE grantee='mininagios_app' AND table_name='serveurs' AND privilege_type IN ('SELECT','INSERT','UPDATE','DELETE');")" = "4" ] \
    && ok "mininagios_app : CRUD complet sur serveurs" || ko "droits incomplets sur serveurs pour mininagios_app"
[ "$(sql_super "SELECT count(*) FROM information_schema.role_table_grants WHERE grantee='mininagios_audit';")" = "1" ] \
    && ok "mininagios_audit : un seul droit de table (SELECT sur le journal)" || ko "mininagios_audit a plus de droits que prévu"

# =============================================================================
titre "Exercice 2 — Ce que l'attaquant ne peut plus faire"
# =============================================================================
refuse   mininagios_app "DROP TABLE serveurs;"                                        "app : DROP TABLE serveurs refusé (must be owner)"
refuse   mininagios_app "DELETE FROM journal_evenements;"                             "app : effacement du journal refusé"
refuse   mininagios_app "UPDATE journal_evenements SET nature='CONNEXION' WHERE id=1;" "app : falsification du journal refusée"
refuse   mininagios_app "CREATE TABLE porte_derobee (id INT);"                        "app : création de table refusée"
sql_super "DROP TABLE IF EXISTS porte_derobee;" > /dev/null   # filet si le test précédent a échoué
autorise mininagios_app "SELECT count(*) FROM administrateurs;"                       "app : lecture d'administrateurs autorisée (question de réflexion)"
# Ce qui protège cette lecture : les mots de passe sont hachés.
HASH=$(sql_role mininagios_app "SELECT password_hash FROM administrateurs LIMIT 1;")
case "$HASH" in '$argon2id$'*) ok "les hashs lus sont bien en Argon2id (la lecture ne livre aucun secret)";;
                *)             ko "password_hash inattendu : $HASH";; esac

autorise mininagios_audit "SELECT count(*) FROM journal_evenements;"                  "audit : lecture du journal autorisée"
refuse   mininagios_audit "SELECT count(*) FROM serveurs;"                            "audit : lecture de serveurs refusée"
refuse   mininagios_audit "SELECT count(*) FROM administrateurs;"                     "audit : lecture d'administrateurs refusée"
refuse   mininagios_audit "INSERT INTO journal_evenements (nature, adresse_ip) VALUES ('ERREUR','0.0.0.0');" "audit : écriture dans le journal refusée"

# =============================================================================
titre "Parcours applicatif sous le compte restreint"
# =============================================================================
curl -s "$URL/setup.php" | grep -q "Administrateur créé" \
    && ok "setup.php fonctionne (plus besoin du droit DELETE)" || ko "setup.php échoue"
REDIR=$(curl -s -o /dev/null -w "%{redirect_url}" -X POST -d "email=$ADMIN_EMAIL&password=$ADMIN_PASS" "$URL/traitement_login.php")
case "$REDIR" in *dashboard*) ok "connexion réussie → dashboard";; *) ko "connexion échouée (redirigé vers $REDIR)";; esac
curl -s -o /dev/null -X POST -d "email=$ADMIN_EMAIL&password=MauvaisMdp" "$URL/traitement_login.php"
[ "$(sql_super "SELECT nature FROM journal_evenements ORDER BY id DESC LIMIT 1;")" = "ECHEC_AUTH" ] \
    && ok "l'échec de connexion est journalisé (INSERT toujours possible)" || ko "l'échec de connexion n'apparaît pas dans le journal"
[ "$(curl -s -o /dev/null -w "%{http_code}" "$URL/api/serveurs.php")" = "401" ] \
    && ok "API sans clé → 401" || ko "API sans clé ne renvoie pas 401"
[ "$(curl -s -o /dev/null -w "%{http_code}" -H "X-API-KEY: $API_SECRET_KEY" "$URL/api/serveurs.php")" = "200" ] \
    && ok "API avec clé → 200" || ko "API avec clé ne renvoie pas 200"

# =============================================================================
titre "Exercice 3 (étudiant) — La faille résiduelle de setup.php"
# =============================================================================
if grep -qE 'echo .*motDePasseClair' public/setup.php; then
    nf "setup.php affiche encore le mot de passe en clair"
else
    ok "setup.php n'affiche plus le mot de passe"
fi

# =============================================================================
titre "Exercice 4 (étudiant) — La connexion d'audit séparée"
# =============================================================================
grep -q "getConnectionAudit" src/Database.php 2>/dev/null \
    && ok "Database::getConnectionAudit() présente" || nf "Database::getConnectionAudit() absente"
grep -q "DB_AUDIT_USER" .env 2>/dev/null \
    && ok "DB_AUDIT_USER présent dans .env" || nf "DB_AUDIT_USER absent du .env"
[ -f public/journal.php ] && ok "public/journal.php existe" || nf "public/journal.php absent (exercice 3 de la séance 2)"
[ -f public/audit.php ]   && ok "public/audit.php existe"   || nf "public/audit.php absent (exercice 4 de la séance 2)"

# =============================================================================
titre "Exercice 5 (étudiant) — Le rôle de démonstration"
# =============================================================================
if [ "$(sql_super "SELECT count(*) FROM pg_roles WHERE rolname='mininagios_demo';")" = "1" ]; then
    ok "rôle mininagios_demo créé"
    [ "$(sql_super "SELECT count(DISTINCT table_name) FROM information_schema.role_table_grants WHERE grantee='mininagios_demo' AND privilege_type='SELECT' AND table_name IN ('serveurs','administrateurs','journal_evenements');")" = "3" ] \
        && ok "demo : SELECT sur les trois tables" || ko "demo : SELECT manquant sur au moins une table"
    [ "$(sql_super "SELECT count(*) FROM information_schema.role_table_grants WHERE grantee='mininagios_demo' AND privilege_type <> 'SELECT';")" = "0" ] \
        && ok "demo : aucun droit d'écriture" || ko "demo : possède des droits au-delà du SELECT"
    [ "$(sql_super "SELECT count(*) FROM pg_default_acl WHERE array_to_string(defaclacl,',') LIKE '%mininagios_demo%';")" != "0" ] \
        && ok "demo : ALTER DEFAULT PRIVILEGES configuré pour les futures tables" \
        || info "demo : ALTER DEFAULT PRIVILEGES non configuré — question 3 à vérifier à l'oral"
else
    nf "rôle mininagios_demo absent"
fi
info "Exercice 6 (traduction MySQL sur papier) : non vérifiable par script."

# =============================================================================
echo
echo "${B}=================== BILAN ===================${N}"
echo "  ${V}OK : $NB_OK${N}   ${R}ÉCHEC : $NB_KO${N}   ${J}NON FAIT : $NB_NF${N}"
if [ "$NB_KO" -gt 0 ]; then
    echo "  ${R}La mise en place de la séance 3 comporte des erreurs.${N}"; exit 1
elif [ "$NB_NF" -gt 0 ]; then
    echo "  Mise en place correcte ; il reste des exercices étudiants à faire."
else
    echo "  ${V}Séance 3 complète, exercices compris.${N}"
fi
exit 0
