<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '../config/bootstrap.php';

use App\Database;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $pdo = Database::getConnection();

        // 1. On cherche l'utilisateur par son email
        $stmt = $pdo->prepare("SELECT id, password_hash FROM administrateurs WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch();

        // 2. Vérification cryptographique du mot de passe
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Succès : On ouvre la session
            session_start();
            $_SESSION['admin_id'] = $admin['id'];

            header("Location: dashboard.php");
            exit();
        } else {
            // Échec (On ne dit jamais si c'est l'email ou le mdp qui est faux !)
            header("Location: login.php?erreur=1");
            exit();
        }

    } catch (Exception $e) {
        die("Erreur de base de données : " . $e->getMessage());
    }
}