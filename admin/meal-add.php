<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Admin.php';

// Require admin authentication
require_auth();
require_role('admin');

$admin = new Admin($conn);
$errors = [];

// Get restaurant ID from URL if provided
$restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : null;

// Get all restaurants
$stmt = $conn->query("SELECT id, name FROM restaurants ORDER BY name");
$restaurants = $stmt->fetchAll();

// Get all categories
$stmt = $conn->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $restaurant_id = (int)$_POST['restaurant_id'];
    $category_id = (int)$_POST['category_id'];
    $image_url = sanitize($_POST['image_url']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if (empty($name)) $errors[] = "Le nom est requis";
    if (empty($description)) $errors[] = "La description est requise";
    if ($price <= 0) $errors[] = "Le prix doit être supérieur à 0";
    if ($restaurant_id <= 0) $errors[] = "Le restaurant est requis";
    if ($category_id <= 0) $errors[] = "La catégorie est requise";

    if (empty($errors)) {
        try {
            $admin->createMeal([
                'restaurant_id' => $restaurant_id,
                'category_id' => $category_id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'image_url' => $image_url,
                'is_available' => $is_available
            ]);
            
            redirect_with_message('/admin/meals.php?restaurant_id=' . $restaurant_id, 
                'Le repas a été créé avec succès.', 'success');
        } catch (Exception $e) {
            $errors[] = "Une erreur est survenue lors de la création du repas.";
        }
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Nouveau repas</h1>
                <a href="<?php echo $restaurant_id ? "meals.php?restaurant_id=$restaurant_id" : 'meals.php'; ?>" 
                   class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Retour aux repas
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="restaurant_id" class="form-label">Restaurant</label>
                            <select class="form-select" id="restaurant_id" name="restaurant_id" required>
                                <option value="">Sélectionner un restaurant</option>
                                <?php foreach ($restaurants as $restaurant): ?>
                                    <option value="<?php echo $restaurant['id']; ?>" 
                                            <?php echo $restaurant_id == $restaurant['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($restaurant['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Catégorie</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo isset($_POST['category_id']) && $_POST['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du repas</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <div class="form-text">
                                Décrivez les ingrédients et la préparation du repas.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Prix (€)</label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" 
                                   step="0.01" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label for="image_url" class="form-label">URL de l'image</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" 
                                   value="<?php echo isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : ''; ?>">
                            <div class="form-text">
                                L'URL d'une image du repas.
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_available" 
                                       name="is_available" value="1" 
                                       <?php echo isset($_POST['is_available']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_available">
                                    Repas disponible à la commande
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Créer le repas
                            </button>
                            <a href="<?php echo $restaurant_id ? "meals.php?restaurant_id=$restaurant_id" : 'meals.php'; ?>" 
                               class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
