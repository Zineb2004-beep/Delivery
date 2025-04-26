<?php
require_once 'config/database.php';

try {
    // Créer un nouvel administrateur
    $email = 'admin@azdelivery.com';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $first_name = 'Admin';
    $last_name = 'System';
    $phone = '+33123456789';
    $address = 'AZ Delivery HQ';
    $role = 'admin';

    // Supprimer l'ancien admin s'il existe
    $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute([$email]);

    // Insérer le nouvel admin
    $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$email, $password, $first_name, $last_name, $phone, $address, $role]);

    echo "Administrateur créé avec succès !\n";
    echo "Email: admin@azdelivery.com\n";
    echo "Mot de passe: admin123\n";

} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
