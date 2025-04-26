<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Admin.php';

// Require admin authentication
require_auth();
require_role('admin');

$admin = new Admin($conn);

// Handle filters
$status = isset($_GET['status']) ? $_GET['status'] : null;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

// Build query
$sql = "SELECT o.*, 
               u.first_name, u.last_name, u.email,
               du.first_name as delivery_first_name, 
               du.last_name as delivery_last_name,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN users du ON o.delivery_user_id = du.id
        WHERE 1=1";
$params = [];

if ($status) {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}
if ($user_id) {
    $sql .= " AND o.user_id = ?";
    $params[] = $user_id;
}
if ($restaurant_id) {
    $sql .= " AND EXISTS (
        SELECT 1 FROM order_items oi 
        JOIN meals m ON oi.meal_id = m.id 
        WHERE oi.order_id = o.id AND m.restaurant_id = ?
    )";
    $params[] = $restaurant_id;
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
$orders = $stmt->fetchAll();

// Get delivery personnel for assignment
$stmt = $conn->prepare(
    "SELECT id, first_name, last_name 
     FROM users 
     WHERE role = 'delivery' AND is_active = 1 
     ORDER BY first_name, last_name"
);
$stmt->execute();
$delivery_personnel = $stmt->fetchAll();

// Get restaurants for filter
$stmt = $conn->query("SELECT id, name FROM restaurants WHERE is_active = 1 ORDER BY name");
$restaurants = $stmt->fetchAll();

// Handle delivery assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_delivery'])) {
    $order_id = (int)$_POST['order_id'];
    $delivery_user_id = (int)$_POST['delivery_user_id'];
    
    try {
        $stmt = $conn->prepare(
            "UPDATE orders 
             SET delivery_user_id = ?, status = 'delivering', 
                 updated_at = NOW() 
             WHERE id = ?"
        );
        $stmt->execute([$delivery_user_id, $order_id]);
        
        redirect_with_message('/admin/orders.php', 
            'Le livreur a été assigné avec succès.', 'success');
    } catch (Exception $e) {
        redirect_with_message('/admin/orders.php', 
            'Une erreur est survenue lors de l\'assignation.', 'danger');
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare(
            "UPDATE orders 
             SET status = ?, updated_at = NOW() 
             WHERE id = ?"
        );
        $stmt->execute([$status, $order_id]);
        
        redirect_with_message('/admin/orders.php', 
            'Le statut a été mis à jour avec succès.', 'success');
    } catch (Exception $e) {
        redirect_with_message('/admin/orders.php', 
            'Une erreur est survenue lors de la mise à jour.', 'danger');
    }
}
$stmt = $conn->prepare("SELECT * FROM orders");?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Gestion des commandes</h1>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>
                            En attente
                        </option>
                        <option value="preparing" <?php echo $status === 'preparing' ? 'selected' : ''; ?>>
                            En préparation
                        </option>
                        <option value="delivering" <?php echo $status === 'delivering' ? 'selected' : ''; ?>>
                            En livraison
                        </option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>
                            Livrée
                        </option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>
                            Annulée
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="restaurant_id" class="form-label">Restaurant</label>
                    <select class="form-select" id="restaurant_id" name="restaurant_id">
                        <option value="">Tous les restaurants</option>
                        <?php foreach ($restaurants as $restaurant): ?>
                            <option value="<?php echo $restaurant['id']; ?>" 
                                    <?php echo $restaurant_id == $restaurant['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($restaurant['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date début</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo $date_from; ?>">
                </div>

                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date fin</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo $date_to; ?>">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Articles</th>
                            <th>Total</th>
                            <th>Livreur</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                    </strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($order['email']); ?>
                                    </small>
                                </td>
                                <td><?php echo $order['items_count']; ?></td>
                                <td><?php echo number_format($order['total_amount'], 2); ?> €</td>
                                <td>
                                    <?php if ($order['delivery_user_id']): ?>
                                        <?php echo htmlspecialchars(
                                            $order['delivery_first_name'] . ' ' . $order['delivery_last_name']
                                        ); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Non assigné</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <form method="POST" action="" class="d-inline me-2">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="status" value="preparing">
                                            <button type="submit" name="update_status" 
                                                    class="btn btn-sm btn-info">
                                                Préparer
                                            </button>
                                        </form>
                                    <?php elseif ($order['status'] === 'preparing'): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select class="form-select form-select-sm d-inline-block w-auto me-2" 
                                                    name="delivery_user_id" required>
                                                <option value="">Choisir un livreur</option>
                                                <?php foreach ($delivery_personnel as $dp): ?>
                                                    <option value="<?php echo $dp['id']; ?>">
                                                        <?php echo htmlspecialchars(
                                                            $dp['first_name'] . ' ' . $dp['last_name']
                                                        ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_delivery" 
                                                    class="btn btn-sm btn-primary">
                                                Assigner
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-<?php
                                            switch ($order['status']) {
                                                case 'delivering': echo 'primary'; break;
                                                case 'completed': echo 'success'; break;
                                                case 'cancelled': echo 'danger'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>">
                                            <?php
                                            switch ($order['status']) {
                                                case 'delivering': echo 'En livraison'; break;
                                                case 'completed': echo 'Livrée'; break;
                                                case 'cancelled': echo 'Annulée'; break;
                                                default: echo ucfirst($order['status']);
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                </td>
                                <td>
                                    <a href="order.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Détails
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <p class="text-muted mb-0">Aucune commande trouvée</p>
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
