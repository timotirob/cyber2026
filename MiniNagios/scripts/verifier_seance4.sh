#!/usr/bin/env bash
# =============================================================================
# Vérification de fin de séance 4 — Injection SQL, de l'attaque à la défense
# =============================================================================
# Contrôle en une seule exécution l'état attendu en fin de séance :
#   - le laboratoire (exercices 1-2) EST bien vulnérable : contournement de
#     filtre, UNION, lecture de la table des administrateurs ;
#   - la page corrigée (exercice 4) RÉSISTE : les mêmes injections échouent ;
#   - la recherche partielle LIKE (exercice 6) fonctionne et reste sûre ;
#   - la démo à l'aveugle (exercice 7) fuit malgré le masquage, et la version
#     préparée la ferme aussi ;
#   - l'audit du code (exercice 5) : aucune requête à interpolation dans
#     public/ ni src/ ;
#   - la défense en profondeur : ce que l'injection livre reste un hash Argon2id.
#
# Les travaux non encore faits (fichier absent, LIKE non ajouté) sont marqués
# NON FAIT, pas ÉCHEC : c'est un état, pas une erreur.
#
# Usage, depuis le dossier MiniNagios :
#   bash scripts/verifier_seance4.sh
#
# Prérequis : conteneurs démarrés, .env rempli, migrations appliquées, la
# séance 3 en place (le compte applicatif restreint et le compte d'audit).
#
# Effets de bord assumés : le script recrée le compte de démonstration via
# setup.php, insère un serveur témoin (srv-verif-s4) supprimé en fin de course,
# et ajoute quelques lignes au journal (activité normale).
#
# ATTENTION : ce script exécute de vraies injections SQL contre VOTRE conteneur,
# en laboratoire fermé. Il révèle aussi des éléments de réponse des exercices.
# Ne pas le diffuser aux étudiants avant la fin de la séance.
# =============================================================================

cd "$(dirname "$0")/.." || exit 1

# --- Configuration -----------------------------------------------------------
CONTENEUR_DB=nagios_postgres
CONTENEUR_WEB=nagios_php_server
HOTE="${HOTE:-http://localhost:8082}"
URL_BASE="$HOTE/public"
URL_CORR="$URL_BASE/recherche.php"
URL_LAB="$HOTE/demos-vulnerables/recherche_vulnerable.php"
URL_BLIND="$HOTE/demos-vulnerables/recherche_aveugle_vulnerable.php"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@mininagios.local}"
ADMIN_PASS="${ADMIN_PASS:-BtsSlam2026!}"
HOTE_TEST=srv-verif-s4   # serveur témoin, nom unique, inséré puis supprimé

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

# Requêtes HTTP. On passe le payload en clair, curl s'occupe de l'encodage URL.
lab()   { curl -s -G "$URL_LAB"   --data-urlencode "hostname=$1"; }
blind() { curl -s -G "$URL_BLIND" --data-urlencode "hostname=$1"; }
corr()  { curl -s -b "$JAR" -G "$URL_CORR" "$@"; }

# Nombre de lignes de résultat affichées par la page vulnérable (un <br> chacune).
nb_lab() { lab "$1" | grep -o "<br>" | wc -l | tr -d '[:space:]'; }

JAR="$(mktemp 2>/dev/null || echo /tmp/verif_s4_jar)"
nettoyer() {
    rm -f "$JAR"
    docker exec "$CONTENEUR_DB" psql -U "$DB_SUPER_USER" -d "$DB_NAME" \
        -c "DELETE FROM serveurs WHERE hostname = '$HOTE_TEST';" >/dev/null 2>&1
}
trap nettoyer EXIT

# =============================================================================
titre "Prérequis"
# =============================================================================
if [ ! -f .env ]; then echo "${R}Fichier .env introuvable — lancez le script depuis MiniNagios/.${N}"; exit 1; fi
# tr -d '\r' : neutralise d'éventuelles fins de ligne CRLF d'un .env Windows.
source <(tr -d '\r' < .env)
[ -n "$DB_SUPER_USER" ] && ok "variables .env chargées" || { ko "DB_SUPER_USER absent du .env"; exit 1; }
for c in "$CONTENEUR_DB" "$CONTENEUR_WEB"; do
    [ "$(docker inspect -f '{{.State.Running}}' "$c" 2>/dev/null)" = "true" ] \
        && ok "conteneur $c démarré" || { ko "conteneur $c arrêté"; exit 1; }
done
command -v curl >/dev/null 2>&1 && ok "curl disponible" || { ko "curl introuvable"; exit 1; }

# Fixture : un administrateur (pour le UNION et la connexion) et un serveur témoin.
curl -s "$URL_BASE/setup.php" >/dev/null
sql_super "INSERT INTO serveurs (hostname, ip, os)
           SELECT '$HOTE_TEST', '192.168.42.42', 'Debian 12'
           WHERE NOT EXISTS (SELECT 1 FROM serveurs WHERE hostname = '$HOTE_TEST');" >/dev/null
TOTAL_SRV=$(sql_super "SELECT count(*) FROM serveurs;")
ok "jeu d'essai prêt : $TOTAL_SRV serveur(s), dont le témoin $HOTE_TEST"

# Session : on se connecte une fois, on réutilise le cookie pour la page corrigée.
curl -s -c "$JAR" -o /dev/null -X POST \
    -d "email=$ADMIN_EMAIL&password=$ADMIN_PASS" "$URL_BASE/traitement_login.php"
CODE_AVEC_SESSION=$(corr --data-urlencode "hostname=$HOTE_TEST" -o /dev/null -w "%{http_code}")
[ "$CODE_AVEC_SESSION" = "200" ] \
    && ok "session administrateur ouverte" \
    || info "session non ouverte (code $CODE_AVEC_SESSION) — les tests de la page corrigée seront ignorés"

# =============================================================================
titre "Exercices 1-2 — Le laboratoire DOIT être vulnérable"
# =============================================================================
if [ ! -f demos-vulnerables/recherche_vulnerable.php ]; then
    nf "demos-vulnerables/recherche_vulnerable.php absent"
else
    # Recherche normale : le serveur témoin ressort.
    [ "$(nb_lab "$HOTE_TEST")" -ge 1 ] \
        && ok "recherche normale : le serveur témoin est trouvé" \
        || ko "recherche normale : le serveur témoin n'est pas trouvé"

    # Contournement de filtre : ' OR '1'='1 doit retourner TOUTE la table.
    N_TOUT=$(nb_lab "' OR '1'='1")
    [ "$N_TOUT" = "$TOTAL_SRV" ] \
        && ok "injection ' OR '1'='1 : toute la table remonte ($N_TOUT/$TOTAL_SRV)" \
        || ko "injection ' OR '1'='1 : $N_TOUT lignes au lieu de $TOTAL_SRV"

    # Condition toujours fausse : zéro résultat.
    [ "$(nb_lab "' OR '1'='2")" = "0" ] \
        && ok "injection ' OR '1'='2 : zéro résultat, comme attendu" \
        || ko "injection ' OR '1'='2 : des lignes remontent alors qu'aucune ne devrait"

    # UNION : lecture de la table des administrateurs par l'URL.
    SORTIE_UNION=$(lab "' UNION SELECT id, email, password_hash, reset_token FROM administrateurs -- ")
    echo "$SORTIE_UNION" | grep -q "$ADMIN_EMAIL" \
        && ok "injection UNION : l'email administrateur fuit dans la page" \
        || ko "injection UNION : l'email administrateur ne remonte pas (droits ? admin absent ?)"
fi

# =============================================================================
titre "Exercice 4 — La page corrigée DOIT résister"
# =============================================================================
if [ ! -f public/recherche.php ]; then
    nf "public/recherche.php absent"
elif [ "$CODE_AVEC_SESSION" != "200" ]; then
    nf "page corrigée non testée (pas de session)"
else
    # Protection par session : sans cookie, redirection vers login.
    CODE_SANS=$(curl -s -o /dev/null -w "%{http_code}" -G "$URL_CORR" --data-urlencode "hostname=$HOTE_TEST")
    [ "$CODE_SANS" = "302" ] \
        && ok "page protégée : accès sans session redirigé (302)" \
        || ko "page NON protégée : accès sans session renvoie $CODE_SANS au lieu de 302"

    # Recherche normale : le témoin ressort.
    corr --data-urlencode "hostname=$HOTE_TEST" | grep -q "<td>$HOTE_TEST</td>" \
        && ok "recherche normale : le serveur témoin est trouvé" \
        || ko "recherche normale : le serveur témoin n'est pas trouvé"

    # ' OR '1'='1 : cherché LITTÉRALEMENT, donc zéro résultat (et non toute la table).
    SORTIE=$(corr --data-urlencode "hostname=' OR '1'='1")
    if echo "$SORTIE" | grep -q "Aucun serveur" && ! echo "$SORTIE" | grep -q "<td>$HOTE_TEST</td>"; then
        ok "injection ' OR '1'='1 : neutralisée (zéro serveur, pas toute la table)"
    else
        ko "injection ' OR '1'='1 : la page corrigée a renvoyé des serveurs"
    fi

    # UNION : aucune donnée d'une autre table ne doit apparaître.
    corr --data-urlencode "hostname=' UNION SELECT id, email, password_hash, reset_token FROM administrateurs -- " \
        | grep -q "$ADMIN_EMAIL" \
        && ko "injection UNION : l'email administrateur a fuité malgré la requête préparée" \
        || ok "injection UNION : aucune donnée d'administrateur exposée"
fi

# =============================================================================
titre "Exercice 6 — La recherche partielle (LIKE) préparée"
# =============================================================================
if [ ! -f public/recherche.php ] || ! grep -q "LIKE" public/recherche.php; then
    nf "recherche partielle LIKE absente de public/recherche.php"
elif [ "$CODE_AVEC_SESSION" != "200" ]; then
    nf "recherche partielle non testée (pas de session)"
else
    MOTIF=${HOTE_TEST%-*}   # "srv-verif" : un fragment du nom du témoin

    # Partielle : le fragment retrouve le témoin.
    corr --data-urlencode "hostname=$MOTIF" --data-urlencode "partielle=1" | grep -q "<td>$HOTE_TEST</td>" \
        && ok "recherche partielle : le fragment « $MOTIF » retrouve le témoin" \
        || ko "recherche partielle : le fragment ne retrouve pas le témoin"

    # Exacte : le même fragment ne retrouve rien (nom incomplet).
    corr --data-urlencode "hostname=$MOTIF" | grep -q "Aucun serveur" \
        && ok "recherche exacte : le fragment seul ne retrouve rien (normal)" \
        || ko "recherche exacte : le fragment retrouve un serveur, ce qui est anormal"

    # Injection en mode partiel : toujours sûre.
    corr --data-urlencode "hostname=' OR '1'='1" --data-urlencode "partielle=1" | grep -q "<td>$HOTE_TEST</td>" \
        && ko "injection en mode partiel : des serveurs ont fuité" \
        || ok "injection en mode partiel : neutralisée"

    # Joker % saisi : remonte tout — abus de LIKE, PAS une injection SQL.
    corr --data-urlencode "hostname=%" --data-urlencode "partielle=1" | grep -q "<td>$HOTE_TEST</td>" \
        && ok "joker % : agit comme caractère générique (abus LIKE documenté, pas une faille SQL)" \
        || info "joker % : comportement inattendu, à vérifier à la main"
fi

# =============================================================================
titre "Exercice 7 — L'injection à l'aveugle (blind SQLi)"
# =============================================================================
if [ ! -f demos-vulnerables/recherche_aveugle_vulnerable.php ]; then
    nf "demos-vulnerables/recherche_aveugle_vulnerable.php absent"
else
    # Réponse différentielle : condition vraie vs fausse.
    VRAI=$(blind "$HOTE_TEST' AND '1'='1"  | grep -o "Serveur trouvé\|Aucun serveur")
    FAUX=$(blind "$HOTE_TEST' AND '1'='2"  | grep -o "Serveur trouvé\|Aucun serveur")
    if [ "$VRAI" = "Serveur trouvé" ] && [ "$FAUX" = "Aucun serveur" ]; then
        ok "réponse différentielle : « vrai » et « faux » donnent des pages différentes"
    else
        ko "réponse différentielle absente (vrai=$VRAI / faux=$FAUX) — la démo ne fuit pas"
    fi

    # Exfiltration prouvée : on lit un bit du hash administrateur sans le voir.
    BIT=$(blind "zzz' OR (SELECT substr(password_hash,1,1) FROM administrateurs LIMIT 1) = '\$' AND '1'='1" \
          | grep -o "Serveur trouvé\|Aucun serveur")
    [ "$BIT" = "Serveur trouvé" ] \
        && ok "exfiltration à l'aveugle : le hash administrateur commence par « \$ » (lu bit à bit)" \
        || info "exfiltration à l'aveugle : réponse « $BIT » (le hash ne commencerait pas par \$ ?)"

    # La version préparée ferme aussi cette variante.
    if [ -f public/recherche.php ] && [ "$CODE_AVEC_SESSION" = "200" ]; then
        corr --data-urlencode "hostname=$HOTE_TEST' AND '1'='1" | grep -q "<td>$HOTE_TEST</td>" \
            && ko "page corrigée : la charge à l'aveugle a fonctionné" \
            || ok "page corrigée : la charge à l'aveugle est neutralisée"
    fi
fi

# =============================================================================
titre "Exercice 5 — Audit du code existant (aucune interpolation)"
# =============================================================================
# Heuristique : une variable interpolée directement dans la chaîne passée à
# query() / exec() / prepare() est le signal d'une concaténation vulnérable.
# On exclut demos-vulnerables/, dont la vulnérabilité est voulue.
SUSPECTS=$(grep -rnE '(query|exec|prepare)\s*\(\s*"[^"]*\$[a-zA-Z_]' public src --include=*.php 2>/dev/null)
if [ -z "$SUSPECTS" ]; then
    ok "aucune requête à interpolation directe dans public/ ni src/"
else
    ko "requête(s) suspecte(s) à vérifier :"
    echo "$SUSPECTS" | sed 's/^/            /'
fi

# =============================================================================
titre "Défense en profondeur — ce que l'injection livre reste inexploitable"
# =============================================================================
if [ -f demos-vulnerables/recherche_vulnerable.php ]; then
    echo "$SORTIE_UNION" | grep -q 'argon2id' \
        && ok "le mot de passe lu par UNION est un hash Argon2id (illisible — séance 1)" \
        || info "hash Argon2id non repéré dans la sortie UNION (compte de démo recréé ?)"
fi
info "Exercice 3 (analyse de payloads sur papier) : non vérifiable par script."

# =============================================================================
echo
echo "${B}=================== BILAN ===================${N}"
echo "  ${V}OK : $NB_OK${N}   ${R}ÉCHEC : $NB_KO${N}   ${J}NON FAIT : $NB_NF${N}"
if [ "$NB_KO" -gt 0 ]; then
    echo "  ${R}La séance 4 comporte des anomalies.${N}"; exit 1
elif [ "$NB_NF" -gt 0 ]; then
    echo "  Attaque et défense en place ; il reste des exercices à réaliser."
else
    echo "  ${V}Séance 4 complète : le labo attaque, la page corrigée résiste.${N}"
fi
exit 0
