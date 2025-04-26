<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'models/Order.php';

// Require authentication
require_auth();

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Initialize Order model
$order = new Order($conn);

// Get order details
$orderDetails = $order->getOrderById($order_id, $_SESSION['user_id']);
if (!$orderDetails) {
    redirect_with_message('/account.php', 'Commande non trouvée.', 'danger');
}

// Get order items
$orderItems = $order->getOrderItems($order_id);
?>

<?php require_once 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="text-center mb-5">
                <div class="display-1 text-success mb-3">✓</div>
                <h1 class="mb-4">Merci pour votre commande !</h1>
                <p class="lead">Votre commande #<?php echo $order_id; ?> a été confirmée et est en cours de préparation.</p>
            </div>

            <!-- Order Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statut de la commande</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-4">
                        <div class="text-center flex-fill">
                            <div class="status-circle active">1</div>
                            <div class="status-text">Confirmée</div>
                        </div>
                        <div class="text-center flex-fill">
                            <div class="status-circle">2</div>
                            <div class="status-text">En préparation</div>
                        </div>
                        <div class="text-center flex-fill">
                            <div class="status-circle">3</div>
                            <div class="status-text">En livraison</div>
                        </div>
                        <div class="text-center flex-fill">
                            <div class="status-circle">4</div>
                            <div class="status-text">Livrée</div>
                        </div>
                    </div>
                    <p class="text-center mb-0">
                        Temps de livraison estimé: <strong>30-45 minutes</strong>
                    </p>
                </div>
            </div>

            <!-- Order Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Détails de la commande</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Adresse de livraison</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($orderDetails['delivery_address'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Contact</h6>
                            <p class="mb-0">
                                <?php echo htmlspecialchars($orderDetails['first_name'] . ' ' . $orderDetails['last_name']); ?><br>
                                <?php echo htmlspecialchars($orderDetails['phone']); ?>
                            </p>
                        </div>
                    </div>

                    <h6>Articles commandés</h6>
                    <?php foreach ($orderItems as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="me-2"><?php echo $item['quantity']; ?>x</span>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </div>
                            <span><?php echo number_format($item['price'] * $item['quantity'], 2); ?> €</span>
                        </div>
                    <?php endforeach; ?>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Sous-total</span>
                        <span><?php echo number_format($orderDetails['total_amount'] - 2.99, 2); ?> €</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Frais de livraison</span>
                        <span>2.99 €</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <strong>Total</strong>
                        <strong><?php echo number_format($orderDetails['total_amount'], 2); ?> €</strong>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="/account.php" class="btn btn-primary">Voir mes commandes</a>
                <a href="/restaurants.php" class="btn btn-outline-primary ms-2">Commander à nouveau</a>
            </div>
        </div>
    </div>
</div>

<style>
.status-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    border: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-weight: bold;
    color: #6c757d;
}

.status-circle.active {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}

.status-text {
    font-size: 0.875rem;
    color: #6c757d;
}

@media (max-width: 768px) {
    .status-text {
        font-size: 0.75rem;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
