<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Cart.php';

// Require authentication
require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    
    if ($cart_id <= 0) {
        redirect_with_message('/cart.php', 'Article invalide.', 'danger');
    }

    try {
        $cart = new Cart($conn);
        $cart->updateQuantity($cart_id, $_SESSION['user_id'], $quantity);
        
        // If it's an AJAX request, return JSON response
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $cartItems = $cart->getCartItems($_SESSION['user_id']);
            $total = $cart->getCartTotal($_SESSION['user_id']);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'total' => number_format($total, 2),
                'count' => count($cartItems)
            ]);
            exit;
        }

        redirect_with_message('/cart.php', 'Panier mis à jour avec succès !', 'success');
    } catch (Exception $e) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        redirect_with_message('/cart.php', $e->getMessage(), 'danger');
    }
} else {
    redirect_with_message('/cart.php', 'Méthode non autorisée.', 'danger');
}
