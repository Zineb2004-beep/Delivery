<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'models/Cart.php';

// Require authentication
require_auth();

$cart = new Cart($conn);
$cartItems = $cart->getCartItems($_SESSION['user_id']);
$total = $cart->getCartTotal($_SESSION['user_id']);
$restaurant = $cart->getRestaurantInfo($_SESSION['user_id']);
?>

<?php require_once 'includes/header.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Mon Panier</h1>

    <?php echo display_flash_message(); ?>

    <?php if (!empty($cartItems)): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Commander chez <?php echo htmlspecialchars($restaurant['name']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item mb-3 pb-3 border-bottom">
                                <div class="row">
                                    <?php if ($item['image_url']): ?>
                                        <div class="col-md-2">
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 class="img-fluid rounded" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="<?php echo $item['image_url'] ? 'col-md-10' : 'col-md-12'; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <h5 class="text-primary mb-0">
                                                <?php echo number_format($item['price'] * $item['quantity'], 2); ?> €
                                            </h5>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div class="quantity-controls" style="width: 120px;">
                                                <div class="input-group">
                                                    <button type="button" class="btn btn-outline-secondary quantity-btn" 
                                                            data-action="decrease" data-cart-id="<?php echo $item['id']; ?>">-</button>
                                                    <input type="number" class="form-control text-center quantity-input" 
                                                           value="<?php echo $item['quantity']; ?>" 
                                                           min="1" max="10" 
                                                           data-cart-id="<?php echo $item['id']; ?>">
                                                    <button type="button" class="btn btn-outline-secondary quantity-btn" 
                                                            data-action="increase" data-cart-id="<?php echo $item['id']; ?>">+</button>
                                                </div>
                                            </div>
                                            
                                            <form action="/cart/remove.php" method="POST" class="d-inline">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-link text-danger">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
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
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong><?php echo number_format($total + 2.99, 2); ?> €</strong>
                        </div>

                        <a href="/checkout.php" class="btn btn-primary w-100">
                            Passer la commande
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <h3>Votre panier est vide</h3>
            <p>Découvrez nos restaurants et commencez votre commande !</p>
            <a href="/restaurants.php" class="btn btn-primary mt-3">Voir les restaurants</a>
        </div>
    <?php endif; ?>
</div>

<!-- Cart JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle quantity buttons
    document.querySelectorAll('.quantity-btn').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.quantity-input');
            const cartId = input.dataset.cartId;
            let value = parseInt(input.value);
            
            if (this.dataset.action === 'increase' && value < 10) {
                value++;
            } else if (this.dataset.action === 'decrease' && value > 1) {
                value--;
            }
            
            updateCartQuantity(cartId, value);
            input.value = value;
        });
    });

    // Handle quantity input changes
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const cartId = this.dataset.cartId;
            let value = parseInt(this.value);
            
            if (value < 1) value = 1;
            if (value > 10) value = 10;
            
            updateCartQuantity(cartId, value);
            this.value = value;
        });
    });

    // Function to update cart quantity via AJAX
    function updateCartQuantity(cartId, quantity) {
        fetch('/cart/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `cart_id=${cartId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to update all totals
                window.location.reload();
            } else {
                alert(data.message || 'Une erreur est survenue');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue');
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
