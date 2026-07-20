# Mini Nagios — application de supervision (BTS SIO SLAM, bloc 3)

Application web PHP 8 + PostgreSQL sous Docker, support des TP de cybersécurité.
Chaque séance ajoute une évolution sécurisée : authentification, journalisation,
droits SGBD, injection SQL, API REST.

## Prérequis

Docker Desktop, et rien d'autre. PHP, PostgreSQL et Composer tournent dans des
conteneurs : vous n'avez rien à installer sur votre poste.

## Installation (première fois)

### 1. Créer votre fichier de configuration

Le fichier `.env` contient les mots de passe : il est **volontairement exclu de
Git** et n'existe donc pas dans votre copie fraîchement clonée. Sans lui, ni
Docker ni l'application ne démarreront.

```bash
cd MiniNagios
cp .env.example .env
```

Ouvrez ensuite `.env` et remplacez tous les `changez_moi`. Choisissez ce que
vous voulez, mais retenez-le : ces valeurs servent à créer la base.

> **Ne versionnez jamais votre `.env`.** Un secret poussé sur un dépôt reste
> lisible dans l'historique Git même après suppression, dans tous les commits
> antérieurs et dans toutes les copies déjà clonées.

### 2. Installer les dépendances PHP

Le dossier `vendor/` est exclu de Git lui aussi : on ne versionne pas des
bibliothèques téléchargeables.

```bash
docker run --rm -v "${PWD}:/app" composer:latest install
```

Sous PowerShell, remplacez `${PWD}` par le chemin complet du dossier
`MiniNagios`.

### 3. Démarrer les conteneurs

```bash
docker compose up -d
```

| Service | Conteneur | Accès |
|---|---|---|
| Serveur web PHP 8.4 + Apache | `nagios_php_server` | http://localhost:8082 |
| PostgreSQL 16 | `nagios_postgres` | `localhost:5434` |
| Mailpit (capture des mails) | `nagios_mailpit` | http://localhost:8027 |
| Redis | `nagios_redis` | `localhost:6379` |

### 4. Créer le schéma de la base

Les migrations s'appliquent **dans l'ordre des numéros**. Chacune est rejouable
sans risque : la relancer par mégarde affiche des `NOTICE ... skipping` et ne
détruit rien.

```bash
for f in migrations/*.sql; do
    echo "--- $f"
    docker exec -i nagios_postgres psql -U prof_sio -d mininagios < "$f"
done
```

La boucle les applique toutes, dans l'ordre alphabétique — c'est précisément à
ça que sert le préfixe numérique des noms de fichiers. Rejouez-la après chaque
nouvelle séance : les migrations déjà appliquées ne feront rien.

Adaptez `-U prof_sio -d mininagios` si vous avez changé `DB_SUPER_USER` /
`DB_NAME` dans votre `.env` : les migrations s'exécutent toujours avec le
**superutilisateur**, jamais avec le compte applicatif.

### 5. Créer le compte administrateur

Ouvrez http://localhost:8082/public/setup.php — le compte
`admin@mininagios.local` est créé avec le mot de passe `BtsSlam2026!`.

C'est un script de **mise en route pédagogique**, pas un mécanisme de
production : il affiche le mot de passe en clair dans la page. Un vrai
provisionnement passe par le lien magique (voir ci-dessous).

Vous pouvez maintenant vous connecter sur http://localhost:8082/public/login.php

## Les pages de l'application

| Page | Rôle |
|---|---|
| `public/login.php` | Connexion |
| `public/dashboard.php` | Console de supervision |
| `public/ajouter_admin.php` | Provisionne un compte et génère un lien magique |
| `public/setup_password.php` | Page d'atterrissage du lien magique |
| `public/setup.php` | Crée le compte administrateur de démonstration |
| `public/api/serveurs.php` | API REST, protégée par l'en-tête `X-API-KEY` |

Appel de l'API :

```bash
curl -H "X-API-KEY: votre_cle" http://localhost:8082/public/api/serveurs.php
```

La clé est celle de `API_SECRET_KEY` dans votre `.env`. Sans elle, l'API répond
`401`.

## Organisation du code

```
MiniNagios/
├── config/bootstrap.php   Autoloader + chargement du .env
├── migrations/            Schéma versionné, une migration par évolution
├── src/                   Classes métier (namespace App, une classe par fichier)
├── public/                Pages web et API
└── tests/                 Tests unitaires PHPUnit
```

Lancer les tests :

```bash
docker exec nagios_php_server ./vendor/bin/phpunit
```

## Paire de clés de démonstration

Le dossier `config/` contient une paire de clés RSA 2048 bits, `private_key.pem`
et `public_key.pem`, utilisée par `App\CryptoService` pour chiffrer les données
sensibles. Elle est fournie pour ceux qui n'ont pas réussi à monter leur propre
PKI, afin que personne ne reste bloqué sur cette étape.

> ### ⚠️ Cette clé privée est versionnée *volontairement*, et c'est une exception
>
> **Dans une application réelle, une clé privée ne se trouve jamais dans un dépôt
> Git.** Elle serait lisible par toute personne ayant accès au code — y compris
> un prestataire, un stagiaire, ou n'importe qui en cas de fuite du dépôt. Et
> comme Git conserve tout, elle resterait récupérable dans l'historique **même
> après avoir été supprimée**, dans chaque commit antérieur et dans chaque copie
> déjà clonée. C'est précisément pour cette raison que le fichier `.env`, lui,
> est exclu de Git.
>
> Cette paire-ci n'est qu'un jouet : elle ne protège aucune donnée réelle et
> chacun peut la lire, donc elle ne garantit **aucune confidentialité**. Ne la
> réutilisez jamais ailleurs que dans ce TP.
>
> En production, une clé privée se stocke hors du dépôt : variable
> d'environnement, volume monté, ou coffre-fort de secrets (Vault, KMS,
> Docker secrets).

### Générer votre propre paire

C'est la démarche recommandée. Placez-vous dans le dossier `MiniNagios` :

```bash
# 1. La clé privée (RSA 2048 bits, format PKCS#8)
docker exec nagios_php_server openssl genpkey \
    -algorithm RSA -pkeyopt rsa_keygen_bits:2048 \
    -out /var/www/html/config/private_key.pem

# 2. La clé publique, dérivée de la privée
docker exec nagios_php_server openssl pkey \
    -in /var/www/html/config/private_key.pem \
    -pubout -out /var/www/html/config/public_key.pem
```

Vérifier que les deux clés forment bien une paire — les deux empreintes doivent
être **identiques** :

```bash
docker exec nagios_php_server sh -c 'openssl pkey -in /var/www/html/config/private_key.pem -pubout -outform DER | openssl dgst -sha256'
docker exec nagios_php_server sh -c 'openssl pkey -pubin -in /var/www/html/config/public_key.pem -outform DER | openssl dgst -sha256'
```

Si vous générez votre propre clé privée, **ajoutez-la à `.gitignore`** avant de
committer quoi que ce soit :

```
config/private_key.pem
```

La clé publique, elle, peut rester versionnée : c'est sa raison d'être d'être
diffusée.

## Suivre la progression des séances

Le dépôt porte un tag par séance, correspondant à son état **corrigé** :

```bash
git tag -l                        # lister les séances disponibles
git checkout seance-01-fondations # se placer sur l'état corrigé d'une séance
```

Le tag d'une séance est le point de départ de la suivante : si vous vous êtes
perdu pendant un TP, vous pouvez repartir du dernier tag sans avoir tout à
refaire.

## En cas de problème

**« Impossible de lire le fichier .env »** — l'étape 1 n'a pas été faite, ou le
`.env` n'est pas dans le dossier `MiniNagios`, à côté du `docker-compose.yml`.

**`vendor/autoload.php` introuvable** — l'étape 2 n'a pas été faite.

**Erreur d'authentification PostgreSQL après avoir changé `DB_SUPER_PASS`** —
le mot de passe du superutilisateur est figé à la création de la base. Changer
`.env` ensuite ne suffit pas, il faut recréer le volume :

```bash
docker compose down -v   # ATTENTION : efface toutes les données
docker compose up -d
```

Puis rejouer les migrations et `setup.php`.

**La page est blanche** — regardez les journaux Apache :

```bash
docker logs nagios_php_server --tail 30
```
