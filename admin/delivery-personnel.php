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
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$sql = "SELECT u.*,
               (SELECT COUNT(*) FROM orders WHERE delivery_user_id = u.id) as total_deliveries,
               (SELECT COUNT(*) FROM orders 
                WHERE delivery_user_id = u.id 
                AND status = 'completed') as completed_deliveries,
               (SELECT COUNT(*) FROM orders 
                WHERE delivery_user_id = u.id 
                AND status = 'delivering') as active_deliveries
        FROM users u 
        WHERE u.role = 'delivery'";
$params = [];

if ($status === 'active') {
    $sql .= " AND u.is_active = 1";
} elseif ($status === 'inactive') {
    $sql .= " AND u.is_active = 0";
}

if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$sql .= " ORDER BY u.first_name, u.last_name";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$delivery_personnel = $stmt->fetchAll();

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $user_id = (int)$_POST['user_id'];
    $is_active = (int)$_POST['is_active'];
    
    try {
        // Check if user has active deliveries before deactivating
        if (!$is_active) {
            $stmt = $conn->prepare(
                "SELECT COUNT(*) FROM orders 
                 WHERE delivery_user_id = ? AND status = 'delivering'"
            );
            $stmt->execute([$user_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Ce livreur a des livraisons en cours.");
            }
        }
        
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'delivery'");
        $stmt->execute([$is_active, $user_id]);
        
        redirect_with_message('/admin/delivery-personnel.php', 
            'Le statut du livreur a été mis à jour avec succès.', 'success');
    } catch (Exception $e) {
        redirect_with_message('/admin/delivery-personnel.php', 
            $e->getMessage(), 'danger');
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Gestion des livreurs</h1>
                <a href="delivery-add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouveau livreur
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
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>
                            Actif
                        </option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>
                            Inactif
                        </option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Nom, email, téléphone...">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="delivery-personnel.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Delivery Personnel List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Livreur</th>
                            <th>Contact</th>
                            <th class="text-center">Livraisons totales</th>
                            <th class="text-center">Livraisons complétées</th>
                            <th class="text-center">Livraisons en cours</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($delivery_personnel as $dp): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-placeholder me-2">
                                            <?php echo strtoupper(substr($dp['first_name'], 0, 1) . 
                                                                substr($dp['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong>
                                                <?php echo htmlspecialchars(
                                                    $dp['first_name'] . ' ' . $dp['last_name']
                                                ); ?>
                                            </strong>
                                            <br>
                                            <small class="text-muted">
                                                Inscrit le <?php echo date('d/m/Y', strtotime($dp['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($dp['email']); ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo htmlspecialchars($dp['phone']); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">
                                        <?php echo $dp['total_deliveries']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success">
                                        <?php echo $dp['completed_deliveries']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary">
                                        <?php echo $dp['active_deliveries']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($dp['is_active']): ?>
                                        <span class="badge bg-success">Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="delivery-edit.php?id=<?php echo $dp['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delivery-stats.php?id=<?php echo $dp['id']; ?>" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                        <form method="POST" action="" class="d-inline" 
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir <?php 
                                                  echo $dp['is_active'] ? 'désactiver' : 'activer'; 
                                              ?> ce livreur ?');">
                                            <input type="hidden" name="user_id" value="<?php echo $dp['id']; ?>">
                                            <input type="hidden" name="is_active" 
                                                   value="<?php echo $dp['is_active'] ? '0' : '1'; ?>">
                                            <button type="submit" name="toggle_status" 
                                                    class="btn btn-sm btn-outline-<?php 
                                                        echo $dp['is_active'] ? 'danger' : 'success'; 
                                                    ?>">
                                                <i class="fas fa-<?php 
                                                    echo $dp['is_active'] ? 'times' : 'check'; 
                                                ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($delivery_personnel)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <p class="text-muted mb-0">Aucun livreur trouvé</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-placeholder {
    width: 40px;
    height: 40px;
    background-color: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #6c757d;
}
</style>

<?php require_once '../includes/footer.php'; ?>
