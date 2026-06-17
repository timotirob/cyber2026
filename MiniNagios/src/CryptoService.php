<?php
namespace App;

class CryptoService
{
    private string $clePubliqueRsa;

    public function __construct()
    {
        // En réalité, on charge le fichier .pem de l'entreprise
        $this->clePubliqueRsa = file_get_contents('../config/public_key.pem');
    }

    /**
     * Chiffre une donnée avec une méthode hybride (AES + RSA)
     * Retourne une chaîne encodée en Base64 contenant l'enveloppe
     */
    public function chiffrerSensible(string $donneeClaire): string
    {
        // 1. Générer une clé AES aléatoire de 32 octets
        $cleAes = openssl_random_pseudo_bytes(32);

        // 2. Générer un vecteur d'initialisation (IV) pour AES
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // 3. Chiffrer la donnée avec AES-256
        $donneeChiffree = openssl_encrypt($donneeClaire, 'aes-256-cbc', $cleAes, 0, $iv);

        // 4. Chiffrer la clé AES avec la Clé Publique RSA
        openssl_public_encrypt($cleAes, $cleAesChiffreeRsa, $this->clePubliqueRsa);

        // 5. On assemble le tout (IV + Clé AES Chiffrée + Donnée Chiffrée) et on encode en Base64 pour le stockage texte
        $enveloppe = [
            'iv' => base64_encode($iv),
            'key' => base64_encode($cleAesChiffreeRsa),
            'data' => $donneeChiffree
        ];

        return json_encode($enveloppe);
    }
}