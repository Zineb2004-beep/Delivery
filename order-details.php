<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'models/Order.php';

// Require authentication
require_auth();

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize Order model
$order = new Order($conn);

// Get order details
$orderDetails = $order->getOrderById($order_id, $_SESSION['user_id']);
if (!$orderDetails) {
    redirect_with_message('/account.php', 'Commande non trouvée.', 'danger');
}

// Get order items
$orderItems = $order->getOrderItems($order_id);

// Get delivery person info if assigned
$delivery_person = null;
if ($orderDetails['delivery_user_id']) {
    $stmt = $conn->prepare("SELECT first_name, last_name, phone FROM users WHERE id = ?");
    $stmt->execute([$orderDetails['delivery_user_id']]);
    $delivery_person = $stmt->fetch();
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Commande #<?php echo $order_id; ?></h1>
                <a href="/account.php" class="btn btn-outline-primary">Retour aux commandes</a>
            </div>

            <!-- Order Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statut de la commande</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-4">
                        <?php
                        $statuses = [
                            'pending' => ['Confirmée', 1],
                            'preparing' => ['En préparation', 2],
                            'delivering' => ['En livraison', 3],
                            'completed' => ['Livrée', 4]
                        ];
                        
                        $current_step = $statuses[$orderDetails['status']][1];
                        foreach ($statuses as $status => $info):
                            $active = $info[1] <= $current_step ? 'active' : '';
                        ?>
                            <div class="text-center flex-fill">
                                <div class="status-circle <?php echo $active; ?>">
                                    <?php echo $info[1]; ?>
                                </div>
                                <div class="status-text"><?php echo $info[0]; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($delivery_person && $orderDetails['status'] === 'delivering'): ?>
                        <div class="alert alert-info mb-0">
                            <h6 class="alert-heading">Information de livraison</h6>
                            <p class="mb-0">
                                Votre commande est en cours de livraison par 
                                <?php echo htmlspecialchars($delivery_person['first_name'] . ' ' . $delivery_person['last_name']); ?><br>
                                Téléphone: <?php echo htmlspecialchars($delivery_person['phone']); ?>
                            </p>
                        </div>
                    <?php endif; ?>
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
                            <h6>Date de commande</h6>
                            <p><?php echo date('d/m/Y H:i', strtotime($orderDetails['created_at'])); ?></p>
                            
                            <h6>Adresse de livraison</h6>
                            <p><?php echo nl2br(htmlspecialchars($orderDetails['delivery_address'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Contact</h6>
                            <p>
                                <?php echo htmlspecialchars($orderDetails['first_name'] . ' ' . $orderDetails['last_name']); ?><br>
                                <?php echo htmlspecialchars($orderDetails['phone']); ?><br>
                                <?php echo htmlspecialchars($orderDetails['email']); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <h6>Articles commandés</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th class="text-center">Quantité</th>
                                    <th class="text-end">Prix unitaire</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($item['name']); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($item['restaurant_name']); ?>
                                            </small>
                                        </td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end"><?php echo number_format($item['price'], 2); ?> €</td>
                                        <td class="text-end">
                                            <?php echo number_format($item['price'] * $item['quantity'], 2); ?> €
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end">Sous-total</td>
                                    <td class="text-end">
                                        <?php echo number_format($orderDetails['total_amount'] - 2.99, 2); ?> €
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">Frais de livraison</td>
                                    <td class="text-end">2.99 €</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total</strong></td>
                                    <td class="text-end">
                                        <strong><?php echo number_format($orderDetails['total_amount'], 2); ?> €</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <?php if ($orderDetails['status'] === 'completed'): ?>
                <!-- Review Section -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Évaluer votre commande</h5>
                    </div>
                    <div class="card-body">
                        <p>Comment s'est passée votre expérience avec cette commande ?</p>
                        <a href="/review.php?order_id=<?php echo $order_id; ?>" 
                           class="btn btn-primary">
                            Laisser un avis
                        </a>
                    </div>
                </div>
            <?php endif; ?>
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
