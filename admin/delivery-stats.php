<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Admin.php';

// Require admin authentication
require_auth();
require_role('admin');

$admin = new Admin($conn);

// Get delivery person ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get delivery person details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'delivery'");
$stmt->execute([$user_id]);
$delivery_person = $stmt->fetch();

if (!$delivery_person) {
    redirect_with_message('/admin/delivery-personnel.php', 
        'Livreur non trouvé.', 'danger');
}

// Get date range
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');

// Get delivery statistics
$stmt = $conn->prepare(
    "SELECT COUNT(*) as total_deliveries,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_deliveries,
            SUM(CASE WHEN status = 'delivering' THEN 1 ELSE 0 END) as active_deliveries,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_deliveries,
            AVG(CASE 
                WHEN status = 'completed' 
                THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at) 
                ELSE NULL 
            END) as avg_delivery_time
     FROM orders 
     WHERE delivery_user_id = ?
     AND DATE(created_at) BETWEEN ? AND ?"
);
$stmt->execute([$user_id, $date_from, $date_to]);
$stats = $stmt->fetch();

// Get daily deliveries for chart
$stmt = $conn->prepare(
    "SELECT DATE(created_at) as date,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
     FROM orders 
     WHERE delivery_user_id = ?
     AND DATE(created_at) BETWEEN ? AND ?
     GROUP BY DATE(created_at)
     ORDER BY date"
);
$stmt->execute([$user_id, $date_from, $date_to]);
$daily_stats = $stmt->fetchAll();

// Get recent deliveries
$stmt = $conn->prepare(
    "SELECT o.*, 
            u.first_name as customer_first_name,
            u.last_name as customer_last_name,
            u.phone as customer_phone
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.delivery_user_id = ?
     ORDER BY o.created_at DESC
     LIMIT 10"
);
$stmt->execute([$user_id]);
$recent_deliveries = $stmt->fetchAll();
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">
                    Statistiques de livraison - 
                    <?php echo htmlspecialchars(
                        $delivery_person['first_name'] . ' ' . $delivery_person['last_name']
                    ); ?>
                </h1>
                <a href="delivery-personnel.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Retour aux livreurs
                </a>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="id" value="<?php echo $user_id; ?>">
                <div class="col-md-5">
                    <label for="date_from" class="form-label">Date début</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-5">
                    <label for="date_to" class="form-label">Date fin</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Livraisons totales</h6>
                    <h2 class="card-title mb-0">
                        <?php echo number_format($stats['total_deliveries']); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Livraisons complétées</h6>
                    <h2 class="card-title mb-0 text-success">
                        <?php echo number_format($stats['completed_deliveries']); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Livraisons en cours</h6>
                    <h2 class="card-title mb-0 text-primary">
                        <?php echo number_format($stats['active_deliveries']); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Temps moyen de livraison</h6>
                    <h2 class="card-title mb-0">
                        <?php 
                        $avg_time = round($stats['avg_delivery_time']);
                        if ($avg_time > 60) {
                            echo floor($avg_time / 60) . 'h' . ($avg_time % 60) . 'm';
                        } else {
                            echo $avg_time . ' min';
                        }
                        ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Delivery Chart -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Évolution des livraisons</h5>
                </div>
                <div class="card-body">
                    <canvas id="deliveryChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Deliveries -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Dernières livraisons</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_deliveries as $delivery): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Commande #<?php echo $delivery['id']; ?></h6>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(
                                                $delivery['customer_first_name'] . ' ' . 
                                                $delivery['customer_last_name']
                                            ); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php
                                        switch ($delivery['status']) {
                                            case 'delivering': echo 'primary'; break;
                                            case 'completed': echo 'success'; break;
                                            case 'cancelled': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php
                                        switch ($delivery['status']) {
                                            case 'delivering': echo 'En cours'; break;
                                            case 'completed': echo 'Livrée'; break;
                                            case 'cancelled': echo 'Annulée'; break;
                                            default: echo ucfirst($delivery['status']);
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <small>
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($delivery['created_at'])); ?>
                                    </small>
                                    <br>
                                    <small>
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($delivery['delivery_address']); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($recent_deliveries)): ?>
                            <div class="list-group-item text-center py-4">
                                <p class="text-muted mb-0">Aucune livraison récente</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('deliveryChart').getContext('2d');
    
    const data = {
        labels: <?php echo json_encode(array_map(function($stat) {
            return date('d/m', strtotime($stat['date']));
        }, $daily_stats)); ?>,
        datasets: [{
            label: 'Livraisons totales',
            data: <?php echo json_encode(array_map(function($stat) {
                return $stat['total'];
            }, $daily_stats)); ?>,
            borderColor: '#0d6efd',
            backgroundColor: '#0d6efd20',
            fill: true
        }, {
            label: 'Livraisons complétées',
            data: <?php echo json_encode(array_map(function($stat) {
                return $stat['completed'];
            }, $daily_stats)); ?>,
            borderColor: '#198754',
            backgroundColor: '#19875420',
            fill: true
        }]
    };

    new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
