# ⚠️ Laboratoire de code VOLONTAIREMENT VULNÉRABLE

Ce dossier contient des exemples de code **intentionnellement faillibles**, à
usage strictement pédagogique. Ils servent à *comprendre* une attaque pour
mieux la défendre — jamais à être utilisés.

## Règles absolues

- **Ne jamais déployer** ce dossier sur un serveur accessible.
- **Ne jamais recopier** son contenu dans `public/`.
- **Ne jamais corriger** ces fichiers : leur vulnérabilité est le sujet d'étude.
  La version *corrigée* de chaque démonstration vit ailleurs — par exemple
  `public/recherche.php` pour la recherche par nom d'hôte.

C'est un objet d'étude sous cloche, pas un morceau de l'application.

## Pas de mécanisme de déploiement dans ce dépôt

Le projet n'embarque aujourd'hui aucun script de mise en ligne, aucune image
Docker qui copierait le code applicatif : le conteneur monte le dossier du
projet directement (`volumes: .:/var/www/html`). Il n'y a donc pas de
`.dockerignore` ni de liste de déploiement où inscrire une exclusion.

**Si un tel mécanisme est ajouté un jour**, ce dossier `demos-vulnerables/`
doit y figurer comme exclu. Ce fichier sert de rappel.

## Contenu

| Fichier | Faille démontrée | Version corrigée |
|---|---|---|
| `recherche_vulnerable.php` | Injection SQL (concaténation dans `query()`) | `public/recherche.php` |
