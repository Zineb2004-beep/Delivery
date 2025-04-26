<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Admin.php';

// Require admin authentication
require_auth();
require_role('admin');

$admin = new Admin($conn);

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details
$stmt = $conn->prepare(
    "SELECT o.*, 
            u.first_name, u.last_name, u.email, u.phone,
            du.id as delivery_user_id,
            du.first_name as delivery_first_name, 
            du.last_name as delivery_last_name,
            du.phone as delivery_phone
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     LEFT JOIN users du ON o.delivery_user_id = du.id
     WHERE o.id = ?"
);
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect_with_message('/admin/orders.php', 
        'Commande non trouvée.', 'danger');
}

// Get order items
$stmt = $conn->prepare(
    "SELECT oi.*, m.name, m.price as unit_price, 
            r.id as restaurant_id, r.name as restaurant_name
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
            'items' => [],
            'subtotal' => 0
        ];
    }
    $restaurants[$rid]['items'][] = $item;
    $restaurants[$rid]['subtotal'] += $item['quantity'] * $item['unit_price'];
}

// Get delivery personnel for assignment
$stmt = $conn->prepare(
    "SELECT id, first_name, last_name, phone 
     FROM users 
     WHERE role = 'delivery' AND is_active = 1 
     ORDER BY first_name, last_name"
);
$stmt->execute();
$delivery_personnel = $stmt->fetchAll();

// Handle delivery assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_delivery'])) {
    $delivery_user_id = (int)$_POST['delivery_user_id'];
    
    try {
        $stmt = $conn->prepare(
            "UPDATE orders 
             SET delivery_user_id = ?, status = 'delivering', 
                 updated_at = NOW() 
             WHERE id = ?"
        );
        $stmt->execute([$delivery_user_id, $order_id]);
        
        redirect_with_message('/admin/order.php?id=' . $order_id, 
            'Le livreur a été assigné avec succès.', 'success');
    } catch (Exception $e) {
        redirect_with_message('/admin/order.php?id=' . $order_id, 
            'Une erreur est survenue lors de l\'assignation.', 'danger');
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare(
            "UPDATE orders 
             SET status = ?, updated_at = NOW() 
             WHERE id = ?"
        );
        $stmt->execute([$status, $order_id]);
        
        redirect_with_message('/admin/order.php?id=' . $order_id, 
            'Le statut a été mis à jour avec succès.', 'success');
    } catch (Exception $e) {
        redirect_with_message('/admin/order.php?id=' . $order_id, 
            'Une erreur est survenue lors de la mise à jour.', 'danger');
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Commande #<?php echo $order_id; ?></h1>
                <a href="orders.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Retour aux commandes
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Details -->
        <div class="col-md-8">
            <!-- Status Management -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Gestion de la commande</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h6>Statut actuel</h6>
                            <span class="badge bg-<?php
                                switch ($order['status']) {
                                    case 'pending': echo 'warning'; break;
                                    case 'preparing': echo 'info'; break;
                                    case 'delivering': echo 'primary'; break;
                                    case 'completed': echo 'success'; break;
                                    case 'cancelled': echo 'danger'; break;
                                }
                            ?> fs-6">
                                <?php
                                switch ($order['status']) {
                                    case 'pending': echo 'En attente'; break;
                                    case 'preparing': echo 'En préparation'; break;
                                    case 'delivering': echo 'En livraison'; break;
                                    case 'completed': echo 'Livrée'; break;
                                    case 'cancelled': echo 'Annulée'; break;
                                }
                                ?>
                            </span>
                        </div>
                        <div class="col-md-8">
                            <?php if ($order['status'] === 'pending'): ?>
                                <form method="POST" action="" class="d-inline me-2">
                                    <input type="hidden" name="status" value="preparing">
                                    <button type="submit" name="update_status" class="btn btn-info">
                                        <i class="fas fa-utensils"></i> Passer en préparation
                                    </button>
                                </form>
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" name="update_status" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Annuler la commande
                                    </button>
                                </form>
                            <?php elseif ($order['status'] === 'preparing'): ?>
                                <form method="POST" action="" class="d-inline">
                                    <div class="input-group">
                                        <select class="form-select" name="delivery_user_id" required>
                                            <option value="">Choisir un livreur</option>
                                            <?php foreach ($delivery_personnel as $dp): ?>
                                                <option value="<?php echo $dp['id']; ?>">
                                                    <?php echo htmlspecialchars(
                                                        $dp['first_name'] . ' ' . $dp['last_name']
                                                    ); ?>
                                                    (<?php echo htmlspecialchars($dp['phone']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="assign_delivery" class="btn btn-primary">
                                            <i class="fas fa-motorcycle"></i> Assigner un livreur
                                        </button>
                                    </div>
                                </form>
                            <?php elseif ($order['status'] === 'delivering'): ?>
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" name="update_status" class="btn btn-success">
                                        <i class="fas fa-check"></i> Marquer comme livrée
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Détails de la commande</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($restaurants as $restaurant): ?>
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">
                                <?php echo htmlspecialchars($restaurant['name']); ?>
                            </h6>
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
                    <?php endforeach; ?>

                    <div class="border-top pt-3">
                        <div class="row justify-content-end">
                            <div class="col-md-5">
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer and Delivery Info -->
        <div class="col-md-4">
            <!-- Customer Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations client</h5>
                </div>
                <div class="card-body">
                    <h6><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></h6>
                    <p class="mb-1">
                        <i class="fas fa-envelope me-2"></i>
                        <?php echo htmlspecialchars($order['email']); ?>
                    </p>
                    <p class="mb-1">
                        <i class="fas fa-phone me-2"></i>
                        <?php echo htmlspecialchars($order['phone']); ?>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                    </p>
                </div>
            </div>

            <!-- Delivery Info -->
            <?php if ($order['delivery_user_id']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informations livreur</h5>
                    </div>
                    <div class="card-body">
                        <h6>
                            <?php echo htmlspecialchars(
                                $order['delivery_first_name'] . ' ' . $order['delivery_last_name']
                            ); ?>
                        </h6>
                        <p class="mb-0">
                            <i class="fas fa-phone me-2"></i>
                            <?php echo htmlspecialchars($order['delivery_phone']); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Timeline -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historique</h5>
                </div>
                <div class="card-body p-0">
                    <div class="timeline p-3">
                        <div class="timeline-item">
                            <i class="fas fa-circle text-primary"></i>
                            <div class="ms-3">
                                <div class="fw-bold">Commande créée</div>
                                <div class="text-muted small">
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($order['status'] !== 'pending'): ?>
                            <div class="timeline-item">
                                <i class="fas fa-circle text-info"></i>
                                <div class="ms-3">
                                    <div class="fw-bold">En préparation</div>
                                    <div class="text-muted small">
                                        <?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

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

                        <?php if ($order['status'] === 'cancelled'): ?>
                            <div class="timeline-item">
                                <i class="fas fa-circle text-danger"></i>
                                <div class="ms-3">
                                    <div class="fw-bold">Annulée</div>
                                    <div class="text-muted small">
                                        <?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
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
