<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'models/Cart.php';
require_once 'models/Order.php';

// Require authentication
require_auth();

$cart = new Cart($conn);
$cartItems = $cart->getCartItems($_SESSION['user_id']);
$total = $cart->getCartTotal($_SESSION['user_id']);
$restaurant = $cart->getRestaurantInfo($_SESSION['user_id']);

// Redirect if cart is empty
if (empty($cartItems)) {
    redirect_with_message('/cart.php', 'Votre panier est vide.', 'warning');
}

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_address = sanitize($_POST['delivery_address']);
    $phone = sanitize($_POST['phone']);
    
    $errors = [];
    
    if (empty($delivery_address)) {
        $errors[] = "L'adresse de livraison est requise";
    }
    if (empty($phone)) {
        $errors[] = "Le numéro de téléphone est requis";
    }

    if (empty($errors)) {
        try {
            // Update user phone if changed
            if ($phone !== $user['phone']) {
                $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
                $stmt->execute([$phone, $_SESSION['user_id']]);
            }

            // Create order
            $order = new Order($conn);
            $order_id = $order->createOrder($_SESSION['user_id'], $cartItems, $delivery_address);

            redirect_with_message(
                "/order-confirmation.php?order_id=$order_id",
                'Votre commande a été passée avec succès !',
                'success'
            );
        } catch (Exception $e) {
            $errors[] = "Une erreur est survenue lors de la commande : " . $e->getMessage();
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Finaliser la commande</h1>

    <?php
    if (!empty($errors)) {
        echo '<div class="alert alert-danger"><ul class="mb-0">';
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo '</ul></div>';
    }
    ?>

    <div class="row">
        <div class="col-md-8">
            <!-- Delivery Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations de livraison</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="checkout-form">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="name" 
                                   value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" 
                                   readonly>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   readonly>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                   required>
                            <small class="text-muted">Numéro sur lequel le livreur pourra vous joindre</small>
                        </div>

                        <div class="mb-3">
                            <label for="delivery_address" class="form-label">Adresse de livraison</label>
                            <textarea class="form-control" id="delivery_address" name="delivery_address" 
                                      rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            <small class="text-muted">Adresse précise où vous souhaitez être livré</small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Votre commande chez <?php echo htmlspecialchars($restaurant['name']); ?></h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted">Quantité: <?php echo $item['quantity']; ?></small>
                            </div>
                            <span><?php echo number_format($item['price'] * $item['quantity'], 2); ?> €</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Order Summary -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Résumé de la commande</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Sous-total</span>
                        <span><?php echo number_format($total, 2); ?> €</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Frais de livraison</span>
                        <span>2.99 €</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <strong>Total</strong>
                        <strong><?php echo number_format($total + 2.99, 2); ?> €</strong>
                    </div>

                    <button type="submit" form="checkout-form" class="btn btn-primary w-100">
                        Confirmer la commande
                    </button>

                    <div class="text-center mt-3">
                        <small class="text-muted">
                            En confirmant votre commande, vous acceptez nos conditions générales de vente
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
