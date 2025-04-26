<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Admin.php';

// Require admin authentication
require_auth();
require_role('admin');

$admin = new Admin($conn);

// Get restaurant ID from URL if provided
$restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : null;

// Get restaurant details if restaurant_id is provided
$restaurant = null;
if ($restaurant_id) {
    $stmt = $conn->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch();
    
    if (!$restaurant) {
        redirect_with_message('/admin/restaurants.php', 
            'Restaurant non trouvé.', 'danger');
    }
}

// Get all meals
$meals = $admin->getMealsList($restaurant_id);

// Get all restaurants for the filter
$stmt = $conn->query("SELECT id, name FROM restaurants ORDER BY name");
$restaurants = $stmt->fetchAll();

// Get all categories
$stmt = $conn->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Handle meal status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $meal_id = (int)$_POST['meal_id'];
    $is_available = (int)$_POST['is_available'];
    
    try {
        $admin->updateMeal($meal_id, [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'category_id' => $_POST['category_id'],
            'image_url' => $_POST['image_url'],
            'is_available' => $is_available
        ]);
        
        redirect_with_message('/admin/meals.php' . ($restaurant_id ? "?restaurant_id=$restaurant_id" : ''), 
            'Le statut du repas a été mis à jour.', 'success');
    } catch (Exception $e) {
        redirect_with_message('/admin/meals.php' . ($restaurant_id ? "?restaurant_id=$restaurant_id" : ''), 
            'Une erreur est survenue lors de la mise à jour.', 'danger');
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3">
                <?php if ($restaurant): ?>
                    Repas de <?php echo htmlspecialchars($restaurant['name']); ?>
                <?php else: ?>
                    Gestion des repas
                <?php endif; ?>
            </h1>
        </div>
        <div class="col-md-6 text-end">
            <?php if ($restaurant): ?>
                <a href="restaurant-edit.php?id=<?php echo $restaurant_id; ?>" 
                   class="btn btn-outline-primary me-2">
                    <i class="fas fa-edit"></i> Modifier le restaurant
                </a>
            <?php endif; ?>
            <a href="meal-add.php<?php echo $restaurant_id ? "?restaurant_id=$restaurant_id" : ''; ?>" 
               class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Nouveau repas
            </a>
        </div>
    </div>

    <!-- Filters -->
    <?php if (!$restaurant): ?>
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <label for="restaurant_id" class="form-label">Restaurant</label>
                        <select class="form-select" id="restaurant_id" name="restaurant_id">
                            <option value="">Tous les restaurants</option>
                            <?php foreach ($restaurants as $r): ?>
                                <option value="<?php echo $r['id']; ?>" 
                                        <?php echo $restaurant_id == $r['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Meals List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Repas</th>
                            <?php if (!$restaurant): ?>
                                <th>Restaurant</th>
                            <?php endif; ?>
                            <th>Catégorie</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meals as $meal): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($meal['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($meal['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($meal['name']); ?>"
                                                 class="rounded me-3" style="width: 48px; height: 48px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($meal['name']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($meal['description']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <?php if (!$restaurant): ?>
                                    <td><?php echo htmlspecialchars($meal['restaurant_name']); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($meal['category_name']); ?></td>
                                <td><?php echo number_format($meal['price'], 2); ?> €</td>
                                <td>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
                                        <input type="hidden" name="name" 
                                               value="<?php echo htmlspecialchars($meal['name']); ?>">
                                        <input type="hidden" name="description" 
                                               value="<?php echo htmlspecialchars($meal['description']); ?>">
                                        <input type="hidden" name="price" value="<?php echo $meal['price']; ?>">
                                        <input type="hidden" name="category_id" 
                                               value="<?php echo $meal['category_id']; ?>">
                                        <input type="hidden" name="image_url" 
                                               value="<?php echo htmlspecialchars($meal['image_url']); ?>">
                                        <input type="hidden" name="is_available" 
                                               value="<?php echo $meal['is_available'] ? '0' : '1'; ?>">
                                        <button type="submit" name="toggle_status" 
                                                class="btn btn-sm btn-<?php echo $meal['is_available'] ? 'success' : 'danger'; ?>">
                                            <?php echo $meal['is_available'] ? 'Disponible' : 'Indisponible'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="meal-edit.php?id=<?php echo $meal['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($meals)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <p class="text-muted mb-0">Aucun repas trouvé</p>
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
