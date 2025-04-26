<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require delivery personnel authentication
require_auth();
require_role('delivery');

$user_id = $_SESSION['user_id'];

// Handle filters
$status = isset($_GET['status']) ? $_GET['status'] : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');

// Build query
$sql = "SELECT o.*, 
               u.first_name as customer_first_name,
               u.last_name as customer_last_name,
               u.phone as customer_phone,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count,
               (SELECT GROUP_CONCAT(DISTINCT r.name)
                FROM order_items oi
                JOIN meals m ON oi.meal_id = m.id
                JOIN restaurants r ON m.restaurant_id = r.id
                WHERE oi.order_id = o.id) as restaurants
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.delivery_user_id = ?";
$params = [$user_id];

if ($status && in_array($status, ['delivering', 'completed'])) {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}

if ($date_from) {
    $sql .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $sql .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$deliveries = $stmt->fetchAll();

// Calculate statistics
$total_deliveries = count($deliveries);
$total_amount = array_sum(array_column($deliveries, 'total_amount'));
$completed_deliveries = count(array_filter($deliveries, function($d) {
    return $d['status'] === 'completed';
}));
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Historique des livraisons</h1>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="delivering" <?php echo $status === 'delivering' ? 'selected' : ''; ?>>
                            En cours
                        </option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>
                            Terminées
                        </option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="date_from" class="form-label">Date début</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo $date_from; ?>">
                </div>

                <div class="col-md-4">
                    <label for="date_to" class="form-label">Date fin</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo $date_to; ?>">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="history.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total des livraisons</h6>
                    <h2 class="card-title mb-0">
                        <?php echo number_format($total_deliveries); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Livraisons complétées</h6>
                    <h2 class="card-title mb-0 text-success">
                        <?php echo number_format($completed_deliveries); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Montant total</h6>
                    <h2 class="card-title mb-0">
                        <?php echo number_format($total_amount, 2); ?> €
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Deliveries List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Restaurants</th>
                            <th>Articles</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliveries as $delivery): ?>
                            <tr>
                                <td><?php echo $delivery['id']; ?></td>
                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars(
                                            $delivery['customer_first_name'] . ' ' . 
                                            $delivery['customer_last_name']
                                        ); ?>
                                    </strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($delivery['customer_phone']); ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($delivery['restaurants']); ?></td>
                                <td><?php echo $delivery['items_count']; ?></td>
                                <td><?php echo number_format($delivery['total_amount'], 2); ?> €</td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $delivery['status'] === 'completed' ? 'success' : 'primary';
                                    ?>">
                                        <?php
                                        echo $delivery['status'] === 'completed' ? 'Terminée' : 'En cours';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($delivery['created_at'])); ?>
                                </td>
                                <td>
                                    <a href="delivery.php?id=<?php echo $delivery['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($deliveries)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <p class="text-muted mb-0">Aucune livraison trouvée</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
