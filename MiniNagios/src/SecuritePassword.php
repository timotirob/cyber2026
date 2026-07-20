<?php
namespace App;

/**
 * Politique de robustesse des mots de passe.
 *
 * Cette classe répond à une question que le hachage ne règle pas : Argon2id
 * protège un mot de passe volé en base, mais il ne peut rien contre un mot de
 * passe faible. « soleil123 » haché en Argon2id reste retrouvé en quelques
 * secondes par une attaque par dictionnaire. La robustesse se contrôle donc
 * AVANT le hachage, à la saisie.
 *
 * La classe est statique : elle ne détient aucun état, elle applique une règle.
 */
class SecuritePassword
{
    /**
     * Vérifie qu'un mot de passe respecte la politique de l'entreprise,
     * alignée sur les recommandations de l'ANSSI : au moins 12 caractères,
     * avec majuscule, minuscule, chiffre et caractère spécial.
     *
     * La regex se lit par ses quatre « anticipations » (?=...), qui vérifient
     * chacune la présence d'une famille de caractères sans consommer la chaîne,
     * puis .{12,} qui impose la longueur :
     *   (?=.*[A-Z])   quelque part, une majuscule
     *   (?=.*[a-z])   quelque part, une minuscule
     *   (?=.*\d)      quelque part, un chiffre
     *   (?=.*[\W_])   quelque part, un caractère non alphanumérique
     *
     * @param string $mdp le mot de passe en clair, tel que saisi
     * @return bool true si la politique est respectée
     */
    public static function estRobuste(string $mdp): bool
    {
        // preg_match() retourne 1, 0 ou false : on compare explicitement à 1
        // plutôt que de se fier à la conversion implicite en booléen.
        return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{12,}$/', $mdp) === 1;
    }
}
