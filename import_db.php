<?php
try {
    // Créer la connexion
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    
    // Créer la base de données
    $pdo->exec('CREATE DATABASE IF NOT EXISTS az_delivery');
    echo "Base de données créée avec succès\n";
    
    // Sélectionner la base de données
    $pdo->exec('USE az_delivery');
    
    // Lire et exécuter le fichier SQL
    $sql = file_get_contents('database/az_delivery.sql');
    $pdo->exec($sql);
    echo "Tables et données importées avec succès\n";
    
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
