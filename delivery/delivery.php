<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require delivery personnel authentication
require_auth();
require_role('delivery');

$user_id = $_SESSION['user_id'];

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details
$stmt = $conn->prepare(
    "SELECT o.*, 
            u.first_name as customer_first_name,
            u.last_name as customer_last_name,
            u.email as customer_email,
            u.phone as customer_phone
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.id = ? AND o.delivery_user_id = ?"
);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect_with_message('/delivery/index.php', 
        'Livraison non trouvée.', 'danger');
}

// Get order items grouped by restaurant
$stmt = $conn->prepare(
    "SELECT oi.*, m.name, m.price as unit_price,
            r.id as restaurant_id, r.name as restaurant_name,
            r.address as restaurant_address, r.phone as restaurant_phone
     FROM order_items oi
     JOIN meals m ON oi.meal_id = m.id
     JOIN restaurants r ON m.restaurant_id = r.id
     WHERE oi.order_id = ?
     ORDER BY r.name, m.name"
);
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Group items by restaurant
$restaurants = [];
foreach ($items as $item) {
    $rid = $item['restaurant_id'];
    if (!isset($restaurants[$rid])) {
        $restaurants[$rid] = [
            'name' => $item['restaurant_name'],
            'address' => $item['restaurant_address'],
            'phone' => $item['restaurant_phone'],
            'items' => [],
            'subtotal' => 0
        ];
    }
    $restaurants[$rid]['items'][] = $item;
    $restaurants[$rid]['subtotal'] += $item['quantity'] * $item['unit_price'];
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare(
            "UPDATE orders 
             SET status = ?, updated_at = NOW() 
             WHERE id = ? AND delivery_user_id = ?"
        );
        $stmt->execute([$status, $order_id, $user_id]);
        
        redirect_with_message('/delivery/delivery.php?id=' . $order_id, 
            'Le statut de la livraison a été mis à jour.', 'success');
    } catch (Exception $e) {
        redirect_with_message('/delivery/delivery.php?id=' . $order_id, 
            'Une erreur est survenue lors de la mise à jour.', 'danger');
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Détails de la livraison #<?php echo $order_id; ?></h1>
                <div>
                    <?php if ($order['status'] === 'delivering'): ?>
                        <form method="POST" action="" class="d-inline me-2" 
                              onsubmit="return confirm('Confirmer la livraison ?');">
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" name="update_status" class="btn btn-success">
                                <i class="fas fa-check"></i> Marquer comme livrée
                            </button>
                        </form>
                    <?php endif; ?>
                    <a href="<?php echo $order['status'] === 'completed' ? 'history' : 'index'; ?>.php" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Details -->
        <div class="col-lg-8">
            <!-- Status Timeline -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="delivery-timeline">
                        <div class="timeline-item">
                            <i class="fas fa-circle text-primary"></i>
                            <div class="ms-3">
                                <div class="fw-bold">Commande créée</div>
                                <div class="text-muted small">
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($order['status'] === 'delivering' || $order['status'] === 'completed'): ?>
                            <div class="timeline-item">
                                <i class="fas fa-circle text-primary"></i>
                                <div class="ms-3">
                                    <div class="fw-bold">En livraison</div>
                                    <div class="text-muted small">
                                        <?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($order['status'] === 'completed'): ?>
                            <div class="timeline-item">
                                <i class="fas fa-circle text-success"></i>
                                <div class="ms-3">
                                    <div class="fw-bold">Livrée</div>
                                    <div class="text-muted small">
                                        <?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Restaurants and Items -->
            <?php foreach ($restaurants as $restaurant): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($restaurant['name']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <p class="mb-1">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo htmlspecialchars($restaurant['address']); ?>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?php 
                                    echo urlencode($restaurant['address']); 
                                ?>" class="btn btn-sm btn-outline-primary ms-2" target="_blank">
                                    <i class="fas fa-directions"></i> Itinéraire
                                </a>
                            </p>
                            <p class="mb-3">
                                <i class="fas fa-phone me-2"></i>
                                <?php echo htmlspecialchars($restaurant['phone']); ?>
                            </p>
                        </div>

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
                                    <?php foreach ($restaurant['items'] as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                                            <td class="text-end">
                                                <?php echo number_format($item['unit_price'], 2); ?> €
                                            </td>
                                            <td class="text-end">
                                                <?php echo number_format(
                                                    $item['quantity'] * $item['unit_price'], 2
                                                ); ?> €
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="3" class="text-end">
                                            <strong>Sous-total restaurant</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong><?php echo number_format($restaurant['subtotal'], 2); ?> €</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Customer Info and Summary -->
        <div class="col-lg-4">
            <!-- Customer Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations client</h5>
                </div>
                <div class="card-body">
                    <h6>
                        <?php echo htmlspecialchars(
                            $order['customer_first_name'] . ' ' . $order['customer_last_name']
                        ); ?>
                    </h6>
                    <p class="mb-1">
                        <i class="fas fa-envelope me-2"></i>
                        <?php echo htmlspecialchars($order['customer_email']); ?>
                    </p>
                    <p class="mb-1">
                        <i class="fas fa-phone me-2"></i>
                        <?php echo htmlspecialchars($order['customer_phone']); ?>
                    </p>
                    <p class="mb-3">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                    </p>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php 
                        echo urlencode($order['delivery_address']); 
                    ?>" class="btn btn-outline-primary w-100" target="_blank">
                        <i class="fas fa-directions"></i> Itinéraire de livraison
                    </a>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Résumé de la commande</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Sous-total</td>
                            <td class="text-end">
                                <?php echo number_format($order['total_amount'] - 2.99, 2); ?> €
                            </td>
                        </tr>
                        <tr>
                            <td>Frais de livraison</td>
                            <td class="text-end">2.99 €</td>
                        </tr>
                        <tr>
                            <td><strong>Total</strong></td>
                            <td class="text-end">
                                <strong><?php echo number_format($order['total_amount'], 2); ?> €</strong>
                            </td>
                        </tr>
                    </table>

                    <?php if ($order['payment_method']): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Paiement par <?php echo $order['payment_method']; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.delivery-timeline {
    position: relative;
    padding: 1rem 0;
}

.timeline-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem 0;
    border-left: 2px solid #e9ecef;
    margin-left: 0.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-item i {
    margin-left: -0.85rem;
    background: white;
    padding: 0.25rem;
}
</style>

<?php require_once '../includes/footer.php'; ?>
