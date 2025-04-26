<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Admin.php';

// Require admin authentication
require_auth();
require_role('admin');

$admin = new Admin($conn);
$stats = $admin->getDashboardStats();
$recent_orders = $admin->getRecentOrders(5);
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Tableau de bord administrateur</h1>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Commandes aujourd'hui
                            </div>
                            <div class="h5 mb-0 font-weight-bold">
                                <?php echo $stats['today_orders']; ?>
                            </div>
                            <div class="text-muted small">
                                <?php echo number_format($stats['today_revenue'], 2); ?> €
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Commandes en attente
                            </div>
                            <div class="h5 mb-0 font-weight-bold">
                                <?php echo $stats['pending_orders']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total des revenus
                            </div>
                            <div class="h5 mb-0 font-weight-bold">
                                <?php echo number_format($stats['total_revenue'], 2); ?> €
                            </div>
                            <div class="text-muted small">
                                <?php echo $stats['total_orders']; ?> commandes
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Restaurants actifs
                            </div>
                            <div class="h5 mb-0 font-weight-bold">
                                <?php echo $stats['total_restaurants']; ?>
                            </div>
                            <div class="text-muted small">
                                <?php echo $stats['total_users']; ?> utilisateurs
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-store fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-xl-8 col-lg-7">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Commandes récentes</h6>
                    <a href="orders.php" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Articles</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($order['email']); ?>
                                            </small>
                                        </td>
                                        <td><?php echo $order['items_count']; ?></td>
                                        <td><?php echo number_format($order['total_amount'], 2); ?> €</td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch ($order['status']) {
                                                    case 'pending': echo 'warning'; break;
                                                    case 'preparing': echo 'info'; break;
                                                    case 'delivering': echo 'primary'; break;
                                                    case 'completed': echo 'success'; break;
                                                    case 'cancelled': echo 'danger'; break;
                                                }
                                            ?>">
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
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td>
                                            <a href="order.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                Détails
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-xl-4 col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">Actions rapides</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <a href="restaurants.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-store mb-2"></i><br>
                                Restaurants
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="meals.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-utensils mb-2"></i><br>
                                Repas
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="users.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-users mb-2"></i><br>
                                Utilisateurs
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="orders.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-shopping-cart mb-2"></i><br>
                                Commandes
                            </a>
                        </div>
                    </div>

                    <hr>

                    <div class="d-grid gap-2">
                        <a href="restaurant-add.php" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Nouveau restaurant
                        </a>
                        <a href="meal-add.php" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Nouveau repas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
