<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require delivery personnel authentication
require_auth();
require_role('delivery');

// Get delivery person's details
$user_id = $_SESSION['user_id'];

// Get today's statistics
$stmt = $conn->prepare(
    "SELECT 
        COUNT(*) as total_today,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_today,
        SUM(CASE WHEN status = 'delivering' THEN 1 ELSE 0 END) as active_today
     FROM orders 
     WHERE delivery_user_id = ? 
     AND DATE(created_at) = CURDATE()"
);
$stmt->execute([$user_id]);
$today_stats = $stmt->fetch();

$total_today = $today_stats['total_today'] ?? 0;
$completed_today = $today_stats['completed_today'] ?? 0;
$active_today = $today_stats['active_today'] ?? 0;

// Get current active delivery
$stmt = $conn->prepare(
    "SELECT o.*, 
            u.first_name as customer_first_name,
            u.last_name as customer_last_name,
            u.phone as customer_phone,
            (SELECT GROUP_CONCAT(
                CONCAT(r.name, ': ', GROUP_CONCAT(m.name SEPARATOR ', '))
                SEPARATOR ' | '
            )
            FROM order_items oi
            JOIN meals m ON oi.meal_id = m.id
            JOIN restaurants r ON m.restaurant_id = r.id
            WHERE oi.order_id = o.id
            GROUP BY oi.order_id) as order_details
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.delivery_user_id = ? 
     AND o.status = 'delivering'
     ORDER BY o.created_at ASC
     LIMIT 1"
);
$stmt->execute([$user_id]);
$active_delivery = $stmt->fetch();

// Get pending deliveries
$stmt = $conn->prepare(
    "SELECT o.*, 
            u.first_name as customer_first_name,
            u.last_name as customer_last_name,
            u.phone as customer_phone,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count,
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(r.name, ': ', m.name) SEPARATOR ' | ')
               FROM order_items oi
               JOIN meals m ON oi.meal_id = m.id
               JOIN restaurants r ON m.restaurant_id = r.id
               WHERE oi.order_id = o.id
            ) as order_details
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.delivery_user_id = ? 
     AND o.status = 'delivering'
     AND o.id != IFNULL(?, o.id)
     ORDER BY o.created_at ASC"
);
$stmt->execute([$user_id, $active_delivery ? $active_delivery['id'] : null]);
$pending_deliveries = $stmt->fetchAll();

// Get recent completed deliveries
$stmt = $conn->prepare(
    "SELECT o.*, 
            u.first_name as customer_first_name,
            u.last_name as customer_last_name
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.delivery_user_id = ? 
     AND o.status = 'completed'
     ORDER BY o.updated_at DESC
     LIMIT 5"
);
$stmt->execute([$user_id]);
$recent_deliveries = $stmt->fetchAll();

// Handle delivery status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];

    try {
        $stmt = $conn->prepare(
            "UPDATE orders 
             SET status = ?, updated_at = NOW() 
             WHERE id = ? AND delivery_user_id = ?"
        );
        $stmt->execute([$status, $order_id, $user_id]);

        redirect_with_message(
            '/delivery/index.php',
            'Le statut de la livraison a été mis à jour.',
            'success'
        );
    } catch (Exception $e) {
        redirect_with_message(
            '/delivery/index.php',
            'Une erreur est survenue lors de la mise à jour.',
            'danger'
        );
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <!-- Welcome Message -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Tableau de bord livreur</h1>
        </div>
    </div>

    <!-- Today's Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Livraisons aujourd'hui</h6>
                    <h2 class="card-title mb-0">
                        <?php echo number_format($total_today); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Livraisons complétées</h6>
                    <h2 class="card-title mb-0 text-success">
                        <?php echo number_format($completed_today); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Livraisons en cours</h6>
                    <h2 class="card-title mb-0 text-primary">
                        <?php echo number_format($active_today); ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Current Delivery -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Livraison en cours</h5>
                </div>
                <div class="card-body">
                    <?php if ($active_delivery): ?>
                        <div class="delivery-card">
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">
                                        Commande #<?php echo $active_delivery['id']; ?>
                                    </h6>
                                    <span class="badge bg-primary">En livraison</span>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Client:</strong> <?php echo htmlspecialchars($active_delivery['customer_first_name'] . ' ' . $active_delivery['customer_last_name']); ?></p>
                                        <p class="mb-1"><strong>Téléphone:</strong> <?php echo htmlspecialchars($active_delivery['customer_phone']); ?></p>
                                        <p class="mb-3"><strong>Adresse:</strong><br><?php echo nl2br(htmlspecialchars($active_delivery['delivery_address'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Détails de la commande:</strong><br><?php echo nl2br(htmlspecialchars($active_delivery['order_details'])); ?></p>
                                        <p class="mb-1"><strong>Total:</strong> <?php echo number_format($active_delivery['total_amount'], 2); ?> €</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">Aucune livraison en cours</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Deliveries -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Livraisons en attente</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php if (!empty($pending_deliveries)): ?>
                            <?php foreach ($pending_deliveries as $delivery): ?>
                                <!-- Affichez les détails des livraisons en attente -->
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">Aucune livraison en attente</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Deliveries -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Dernières livraisons</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (!empty($recent_deliveries)): ?>
                            <?php foreach ($recent_deliveries as $delivery): ?>
                                <!-- Affichez les détails des livraisons récentes -->
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">Aucune livraison récente</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>