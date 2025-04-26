<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Cart.php';

// Require authentication
require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meal_id = isset($_POST['meal_id']) ? (int)$_POST['meal_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate input
    if ($meal_id <= 0 || $quantity <= 0 || $quantity > 10) {
        redirect_with_message('/restaurants.php', 'Quantité invalide.', 'danger');
    }

    try {
        $cart = new Cart($conn);
        $cart->addItem($_SESSION['user_id'], $meal_id, $quantity);
        
        redirect_with_message('/cart.php', 'Article ajouté au panier avec succès !', 'success');
    } catch (Exception $e) {
        redirect_with_message('/restaurants.php', $e->getMessage(), 'danger');
    }
} else {
    redirect_with_message('/restaurants.php', 'Méthode non autorisée.', 'danger');
}
