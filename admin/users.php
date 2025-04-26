<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Admin.php';

// Require admin authentication
require_auth();
require_role('admin');

$admin = new Admin($conn);

// Handle filters
$role = isset($_GET['role']) ? $_GET['role'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Get users list
$users = $admin->getUsersList($role, $search);

// Handle user status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $user_id = (int)$_POST['user_id'];
    $is_active = (int)$_POST['is_active'];
    
    try {
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$is_active, $user_id]);
        
        redirect_with_message('/admin/users.php', 
            'Le statut de l\'utilisateur a été mis à jour.', 'success');
    } catch (Exception $e) {
        redirect_with_message('/admin/users.php', 
            'Une erreur est survenue lors de la mise à jour.', 'danger');
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3">Gestion des utilisateurs</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="user-add.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Nouvel utilisateur
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="role" class="form-label">Rôle</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">Tous les rôles</option>
                        <option value="client" <?php echo $role === 'client' ? 'selected' : ''; ?>>Clients</option>
                        <option value="delivery" <?php echo $role === 'delivery' ? 'selected' : ''; ?>>Livreurs</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Administrateurs</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Nom, email ou téléphone..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Contact</th>
                            <th>Rôle</th>
                            <th>Commandes</th>
                            <th>Inscription</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-2 me-3">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <strong>
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['phone']): ?>
                                        <i class="fas fa-phone-alt me-1"></i>
                                        <?php echo htmlspecialchars($user['phone']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Non renseigné</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['address']): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($user['address']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php
                                        switch ($user['role']) {
                                            case 'admin': echo 'danger'; break;
                                            case 'delivery': echo 'info'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php
                                        switch ($user['role']) {
                                            case 'admin': echo 'Administrateur'; break;
                                            case 'delivery': echo 'Livreur'; break;
                                            default: echo 'Client';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['orders_count'] > 0): ?>
                                        <a href="orders.php?user_id=<?php echo $user['id']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo $user['orders_count']; ?> commande(s)
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Aucune commande</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span title="<?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>">
                                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="is_active" 
                                                   value="<?php echo $user['is_active'] ? '0' : '1'; ?>">
                                            <button type="submit" name="toggle_status" 
                                                    class="btn btn-sm btn-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-success">Actif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="user-edit.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary me-2">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <?php if ($user['role'] === 'delivery'): ?>
                                        <a href="delivery-stats.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-chart-line"></i> Statistiques
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <p class="text-muted mb-0">Aucun utilisateur trouvé</p>
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
