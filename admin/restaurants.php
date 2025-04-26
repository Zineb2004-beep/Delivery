<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Admin.php';

// Require admin authentication
require_auth();
require_role('admin');

$admin = new Admin($conn);

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : null;
$restaurants = $admin->getRestaurantsList($search);

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $restaurant_id = (int)$_POST['restaurant_id'];
    $is_active = (int)$_POST['is_active'];
    
    try {
        $admin->updateRestaurant($restaurant_id, [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'address' => $_POST['address'],
            'phone' => $_POST['phone'],
            'image_url' => $_POST['image_url'],
            'is_active' => $is_active
        ]);
        
        redirect_with_message('/admin/restaurants.php', 
            'Le statut du restaurant a été mis à jour.', 'success');
    } catch (Exception $e) {
        redirect_with_message('/admin/restaurants.php', 
            'Une erreur est survenue lors de la mise à jour.', 'danger');
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3">Gestion des restaurants</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="restaurant-add.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Nouveau restaurant
            </a>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" id="search" name="search"
                           placeholder="Rechercher un restaurant..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Rechercher</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Restaurants List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Restaurant</th>
                            <th>Contact</th>
                            <th>Repas</th>
                            <th>Note moyenne</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($restaurants as $restaurant): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($restaurant['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($restaurant['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($restaurant['name']); ?>"
                                                 class="rounded me-3" style="width: 48px; height: 48px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($restaurant['name']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($restaurant['address']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($restaurant['phone']); ?>
                                </td>
                                <td>
                                    <?php echo $restaurant['meals_count']; ?> repas
                                </td>
                                <td>
                                    <?php if ($restaurant['avg_rating']): ?>
                                        <div class="text-warning">
                                            <?php
                                            $rating = round($restaurant['avg_rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating ? '★' : '☆';
                                            }
                                            ?>
                                            <small class="text-muted">
                                                (<?php echo number_format($restaurant['avg_rating'], 1); ?>)
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">Aucun avis</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="restaurant_id" 
                                               value="<?php echo $restaurant['id']; ?>">
                                        <input type="hidden" name="name" 
                                               value="<?php echo htmlspecialchars($restaurant['name']); ?>">
                                        <input type="hidden" name="description" 
                                               value="<?php echo htmlspecialchars($restaurant['description']); ?>">
                                        <input type="hidden" name="address" 
                                               value="<?php echo htmlspecialchars($restaurant['address']); ?>">
                                        <input type="hidden" name="phone" 
                                               value="<?php echo htmlspecialchars($restaurant['phone']); ?>">
                                        <input type="hidden" name="image_url" 
                                               value="<?php echo htmlspecialchars($restaurant['image_url']); ?>">
                                        <input type="hidden" name="is_active" 
                                               value="<?php echo $restaurant['is_active'] ? '0' : '1'; ?>">
                                        <button type="submit" name="toggle_status" 
                                                class="btn btn-sm btn-<?php echo $restaurant['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $restaurant['is_active'] ? 'Actif' : 'Inactif'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="restaurant-edit.php?id=<?php echo $restaurant['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary me-2">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <a href="meals.php?restaurant_id=<?php echo $restaurant['id']; ?>" 
                                       class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-utensils"></i> Repas
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

<?php require_once '../includes/footer.php'; ?>
