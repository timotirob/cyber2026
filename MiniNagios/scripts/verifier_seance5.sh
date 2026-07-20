#!/usr/bin/env bash
# =============================================================================
# Vérification de fin de séance 5 — Modélisation et évolution de schéma
# =============================================================================
# Contrôle en une seule exécution l'état attendu en fin de séance :
#   - les migrations 006-008 : tables, colonnes, clés étrangères et leur
#     comportement ON DELETE, données de référence, droits peuplés ;
#   - la migration 009 : mininagios_app lit le modèle de droits, sans l'écrire ;
#   - le contrôle d'accès applicatif (possedeDroit) et son branchement sur
#     supprimer.php : un Observateur est refusé (403 + ACCES_REFUSE journalisé),
#     un Superviseur est autorisé ;
#   - la page test_droits.php ;
#   - la non-régression des pages existantes.
#
# Les travaux laissés en exercice (classes Droit/Fonction, table intervention)
# sont marqués NON FAIT, pas ÉCHEC : c'est un état, pas une erreur.
#
# Usage, depuis le dossier MiniNagios :
#   bash scripts/verifier_seance5.sh
#
# Prérequis : conteneurs démarrés, .env rempli, migrations appliquées, les
# séances 3 et 4 en place.
#
# Effets de bord assumés, tous nettoyés en fin de course : le script crée un
# administrateur de test (verif-s5@local), un groupe et un serveur témoins, et
# ajoute quelques lignes au journal. Il NE TOUCHE PAS à votre compte admin réel.
#
# ATTENTION : ce script révèle des éléments de réponse des exercices.
# Ne pas le diffuser aux étudiants avant la fin de la séance.
# =============================================================================

cd "$(dirname "$0")/.." || exit 1

# --- Configuration -----------------------------------------------------------
CONTENEUR_DB=nagios_postgres
CONTENEUR_WEB=nagios_php_server
HOTE="${HOTE:-http://localhost:8082}"
URL_BASE="$HOTE/public"
TEST_EMAIL=verif-s5@local
TEST_PASS='VerifS5Pass2026!'
GRP_TEST=GRP-VERIF-S5
SRV_TEST=srv-verif-s5

# --- Couleurs et compteurs ---------------------------------------------------
if [ -t 1 ]; then V=$'\033[32m'; R=$'\033[31m'; J=$'\033[33m'; B=$'\033[1m'; N=$'\033[0m'
else V=""; R=""; J=""; B=""; N=""; fi
NB_OK=0; NB_KO=0; NB_NF=0

ok()    { echo "  ${V}[OK]      ${N}$1"; NB_OK=$((NB_OK+1)); }
ko()    { echo "  ${R}[ÉCHEC]   ${N}$1"; NB_KO=$((NB_KO+1)); }
nf()    { echo "  ${J}[NON FAIT]${N} $1"; NB_NF=$((NB_NF+1)); }
info()  { echo "  ${J}[INFO]    ${N}$1"; }
titre() { echo; echo "${B}== $1 ==${N}"; }

# --- Aides -------------------------------------------------------------------
sql_super() { docker exec "$CONTENEUR_DB" psql -U "$DB_SUPER_USER" -d "$DB_NAME" -t -A -c "$1" 2>&1; }
sql_app()   { docker exec "$CONTENEUR_DB" psql -U mininagios_app  -d "$DB_NAME" -t -A -c "$1" 2>&1; }
attendu()   { if [ "$1" = "$2" ]; then ok "$3"; else ko "$3 (obtenu « $1 », attendu « $2 »)"; fi; }

JAR="$(mktemp 2>/dev/null || echo /tmp/verif_s5_jar)"
nettoyer() {
    rm -f "$JAR"
    sql_super "DELETE FROM serveurs WHERE hostname = '$SRV_TEST';" >/dev/null 2>&1
    sql_super "DELETE FROM groupe   WHERE libelle  = '$GRP_TEST';" >/dev/null 2>&1
    sql_super "DELETE FROM administrateurs WHERE email = '$TEST_EMAIL';" >/dev/null 2>&1
}
trap nettoyer EXIT

# =============================================================================
titre "Prérequis"
# =============================================================================
if [ ! -f .env ]; then echo "${R}Fichier .env introuvable — lancez le script depuis MiniNagios/.${N}"; exit 1; fi
source <(tr -d '\r' < .env)
[ -n "$DB_SUPER_USER" ] && ok "variables .env chargées" || { ko "DB_SUPER_USER absent du .env"; exit 1; }
for c in "$CONTENEUR_DB" "$CONTENEUR_WEB"; do
    [ "$(docker inspect -f '{{.State.Running}}' "$c" 2>/dev/null)" = "true" ] \
        && ok "conteneur $c démarré" || { ko "conteneur $c arrêté"; exit 1; }
done
command -v curl >/dev/null 2>&1 && ok "curl disponible" || { ko "curl introuvable"; exit 1; }

# =============================================================================
titre "Migrations 006-007 — Tables et colonnes"
# =============================================================================
for t in groupe fonction operation table_bdd autorise; do
    [ "$(sql_super "SELECT to_regclass('public.$t') IS NOT NULL;")" = "t" ] \
        && ok "table $t présente" || ko "table $t absente — migration 006/007 non appliquée ?"
done
[ "$(sql_super "SELECT count(*) FROM information_schema.columns WHERE table_name='serveurs' AND column_name='id_groupe';")" = "1" ] \
    && ok "serveurs.id_groupe présente" || ko "serveurs.id_groupe absente"
[ "$(sql_super "SELECT count(*) FROM information_schema.columns WHERE table_name='administrateurs' AND column_name='id_fonction';")" = "1" ] \
    && ok "administrateurs.id_fonction présente" || ko "administrateurs.id_fonction absente"

# =============================================================================
titre "Migrations 006-007 — Comportement des clés étrangères (ON DELETE)"
# =============================================================================
# confdeltype : a=NO ACTION, r=RESTRICT, c=CASCADE, n=SET NULL.
attendu "$(sql_super "SELECT confdeltype FROM pg_constraint WHERE conname='fk_serveur_groupe';")"     "r" "fk_serveur_groupe = RESTRICT"
attendu "$(sql_super "SELECT confdeltype FROM pg_constraint WHERE conname='fk_admin_fonction';")"     "n" "fk_admin_fonction = SET NULL"
attendu "$(sql_super "SELECT confdeltype FROM pg_constraint WHERE conname='fk_autorise_fonction';")"  "c" "fk_autorise_fonction = CASCADE"
attendu "$(sql_super "SELECT confdeltype FROM pg_constraint WHERE conname='fk_autorise_operation';")" "r" "fk_autorise_operation = RESTRICT"
attendu "$(sql_super "SELECT confdeltype FROM pg_constraint WHERE conname='fk_autorise_table';")"     "r" "fk_autorise_table = RESTRICT"

# =============================================================================
titre "Migration 008 — Données de référence et droits peuplés"
# =============================================================================
attendu "$(sql_super "SELECT count(*) FROM operation;")" "4" "operation : 4 lignes (select/insert/update/delete)"
attendu "$(sql_super "SELECT count(*) FROM table_bdd;")" "3" "table_bdd : 3 lignes"
attendu "$(sql_super "SELECT count(*) FROM fonction;")"  "3" "fonction : 3 lignes"

droits_de() { sql_super "SELECT count(*) FROM autorise au JOIN fonction f ON f.id=au.id_fonction WHERE f.libelle='$1';"; }
attendu "$(droits_de Superviseur)" "8" "Superviseur : 8 droits (serveurs+administrateurs × 4)"
attendu "$(droits_de Technicien)"  "3" "Technicien : 3 droits (serveurs sans delete)"
attendu "$(droits_de Observateur)" "2" "Observateur : 2 droits (select serveurs+journal)"
attendu "$(sql_super "SELECT count(*) FROM autorise au JOIN fonction f ON f.id=au.id_fonction JOIN operation o ON o.id=au.id_operation WHERE f.libelle='Technicien' AND o.nom='delete';")" \
    "0" "Technicien : aucun droit de delete"

# =============================================================================
titre "Migration 009 — mininagios_app lit le modèle sans l'écrire"
# =============================================================================
for t in fonction operation table_bdd autorise groupe; do
    if sql_app "SELECT count(*) FROM $t;" | grep -q "denied"; then
        ko "mininagios_app ne peut pas lire $t — migration 009 non appliquée ?"
    else
        ok "mininagios_app lit $t"
    fi
done
sql_app "INSERT INTO autorise (id_fonction,id_operation,id_table_bdd) VALUES (1,1,1);" | grep -q "denied" \
    && ok "mininagios_app ne peut PAS écrire dans autorise (moindre privilège préservé)" \
    || ko "mininagios_app a pu écrire dans autorise — privilège de trop"

# =============================================================================
titre "Relation 1,n — ON DELETE RESTRICT en action"
# =============================================================================
sql_super "INSERT INTO groupe (libelle) VALUES ('$GRP_TEST') ON CONFLICT (libelle) DO NOTHING;" >/dev/null
sql_super "INSERT INTO serveurs (hostname, ip, os, id_groupe)
           SELECT '$SRV_TEST','10.55.55.55','Debian 12', g.id FROM groupe g WHERE g.libelle='$GRP_TEST'
           AND NOT EXISTS (SELECT 1 FROM serveurs WHERE hostname='$SRV_TEST');" >/dev/null
SRV_ID=$(sql_super "SELECT id FROM serveurs WHERE hostname='$SRV_TEST';")
sql_super "DELETE FROM groupe WHERE libelle='$GRP_TEST';" | grep -q "violates foreign key" \
    && ok "supprimer un groupe peuplé est refusé (RESTRICT protège les serveurs)" \
    || ko "la suppression d'un groupe peuplé n'a pas été bloquée"

# =============================================================================
titre "Contrôle d'accès applicatif — possedeDroit et supprimer.php"
# =============================================================================
if [ ! -f src/ControleAcces.php ]; then
    nf "src/ControleAcces.php absent"
elif [ ! -f public/supprimer.php ] || ! grep -q "possedeDroit" public/supprimer.php; then
    nf "le contrôle n'est pas branché sur public/supprimer.php"
else
    # Administrateur de test dédié (le compte admin réel n'est pas touché).
    HASH=$(docker exec "$CONTENEUR_WEB" php -r "echo password_hash('$TEST_PASS', PASSWORD_ARGON2ID);")
    sql_super "INSERT INTO administrateurs (email, password_hash) VALUES ('$TEST_EMAIL','$HASH')
               ON CONFLICT (email) DO UPDATE SET password_hash=EXCLUDED.password_hash;" >/dev/null
    TEST_ID=$(sql_super "SELECT id FROM administrateurs WHERE email='$TEST_EMAIL';")

    fixe_fonction() { sql_super "UPDATE administrateurs SET id_fonction=(SELECT id FROM fonction WHERE libelle='$1') WHERE email='$TEST_EMAIL';" >/dev/null; }

    # possedeDroit en direct (couche métier).
    droit() { docker exec "$CONTENEUR_WEB" php -r "require '/var/www/html/config/bootstrap.php'; \$c=new App\ControleAcces(App\Database::getConnection()); echo \$c->possedeDroit($TEST_ID,'serveurs','delete')?'oui':'non';"; }
    fixe_fonction Observateur; attendu "$(droit)" "non" "possedeDroit(Observateur, delete serveurs) = non"
    fixe_fonction Superviseur; attendu "$(droit)" "oui" "possedeDroit(Superviseur, delete serveurs) = oui"

    # Session de l'administrateur de test.
    curl -s -c "$JAR" -o /dev/null -X POST -d "email=$TEST_EMAIL&password=$TEST_PASS" "$URL_BASE/traitement_login.php"
    CODE_SESSION=$(curl -s -b "$JAR" -o /dev/null -w "%{http_code}" "$URL_BASE/dashboard.php")

    if [ "$CODE_SESSION" != "200" ]; then
        ko "session de l'administrateur de test non ouverte (code $CODE_SESSION)"
    else
        # Observateur : suppression refusée (403), serveur conservé, ACCES_REFUSE journalisé.
        fixe_fonction Observateur
        CODE=$(curl -s -b "$JAR" -o /dev/null -w "%{http_code}" "$URL_BASE/supprimer.php?id=$SRV_ID")
        attendu "$CODE" "403" "Observateur : suppression refusée (403)"
        [ "$(sql_super "SELECT count(*) FROM serveurs WHERE id=$SRV_ID;")" = "1" ] \
            && ok "Observateur : le serveur n'a pas été supprimé" \
            || ko "Observateur : le serveur a été supprimé malgré le refus"
        DERNIERE=$(sql_super "SELECT nature||'|'||id_utilisateur||'|'||ressource FROM journal_evenements ORDER BY id DESC LIMIT 1;")
        [ "$DERNIERE" = "ACCES_REFUSE|$TEST_ID|serveur:$SRV_ID" ] \
            && ok "Observateur : refus journalisé (ACCES_REFUSE, admin $TEST_ID, serveur:$SRV_ID)" \
            || ko "Observateur : journalisation du refus incorrecte (« $DERNIERE »)"

        # Superviseur : suppression autorisée (302), serveur supprimé.
        fixe_fonction Superviseur
        CODE=$(curl -s -b "$JAR" -o /dev/null -w "%{http_code}" "$URL_BASE/supprimer.php?id=$SRV_ID")
        attendu "$CODE" "302" "Superviseur : suppression autorisée (302)"
        [ "$(sql_super "SELECT count(*) FROM serveurs WHERE id=$SRV_ID;")" = "0" ] \
            && ok "Superviseur : le serveur a bien été supprimé" \
            || ko "Superviseur : le serveur est toujours présent"
    fi
fi

# =============================================================================
titre "Page test_droits.php"
# =============================================================================
if [ ! -f public/test_droits.php ]; then
    nf "public/test_droits.php absent"
else
    CODE_SANS=$(curl -s -o /dev/null -w "%{http_code}" "$URL_BASE/test_droits.php")
    attendu "$CODE_SANS" "302" "page protégée : accès sans session redirigé"
    if [ -f "$JAR" ] && [ "${CODE_SESSION:-0}" = "200" ]; then
        curl -s -b "$JAR" "$URL_BASE/test_droits.php" | grep -q "✓\|✗" \
            && ok "matrice des droits affichée (✓ / ✗)" \
            || ko "matrice des droits non trouvée dans la page"
    fi
fi

# =============================================================================
titre "Non-régression — pages existantes sous mininagios_app"
# =============================================================================
JAR2="$(mktemp 2>/dev/null || echo /tmp/verif_s5_jar2)"
curl -s -c "$JAR2" -o /dev/null -X POST -d "email=${ADMIN_EMAIL:-admin@mininagios.local}&password=${ADMIN_PASS:-BtsSlam2026!}" "$URL_BASE/traitement_login.php"
for page in dashboard.php journal.php audit.php; do
    CODE=$(curl -s -b "$JAR2" -o /dev/null -w "%{http_code}" "$URL_BASE/$page")
    attendu "$CODE" "200" "page $page accessible"
done
rm -f "$JAR2"

# =============================================================================
titre "Travaux laissés en exercice"
# =============================================================================
{ [ -f src/Droit.php ] && [ -f src/Fonction.php ]; } \
    && ok "classes Droit / Fonction présentes (exercice 4)" \
    || nf "classes Droit / Fonction (exercice 4, version parcours de collection)"
[ "$(sql_super "SELECT to_regclass('public.intervention') IS NOT NULL;")" = "t" ] \
    && ok "table intervention présente (exercice 5)" \
    || nf "table intervention (exercice 5, évolution sous contrainte)"
info "Exercice 6 (cohérence droits applicatifs / SGBD, rédactionnel) : non vérifiable par script."

# =============================================================================
echo
echo "${B}=================== BILAN ===================${N}"
echo "  ${V}OK : $NB_OK${N}   ${R}ÉCHEC : $NB_KO${N}   ${J}NON FAIT : $NB_NF${N}"
if [ "$NB_KO" -gt 0 ]; then
    echo "  ${R}La séance 5 comporte des anomalies.${N}"; exit 1
elif [ "$NB_NF" -gt 0 ]; then
    echo "  Modèle et contrôle d'accès en place ; il reste des exercices à réaliser."
else
    echo "  ${V}Séance 5 complète, exercices compris.${N}"
fi
exit 0
