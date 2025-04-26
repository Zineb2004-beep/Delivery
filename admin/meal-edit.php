<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Admin.php';

// Require admin authentication
require_auth();
require_role('admin');

$admin = new Admin($conn);
$errors = [];

// Get meal ID from URL
$meal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get meal details
$stmt = $conn->prepare(
    "SELECT m.*, r.name as restaurant_name 
     FROM meals m 
     JOIN restaurants r ON m.restaurant_id = r.id 
     WHERE m.id = ?"
);
$stmt->execute([$meal_id]);
$meal = $stmt->fetch();

if (!$meal) {
    redirect_with_message('/admin/meals.php', 
        'Repas non trouvé.', 'danger');
}

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
    $category_id = (int)$_POST['category_id'];
    $image_url = sanitize($_POST['image_url']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if (empty($name)) $errors[] = "Le nom est requis";
    if (empty($description)) $errors[] = "La description est requise";
    if ($price <= 0) $errors[] = "Le prix doit être supérieur à 0";
    if ($category_id <= 0) $errors[] = "La catégorie est requise";

    if (empty($errors)) {
        try {
            $admin->updateMeal($meal_id, [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'category_id' => $category_id,
                'image_url' => $image_url,
                'is_available' => $is_available
            ]);
            
            redirect_with_message('/admin/meals.php?restaurant_id=' . $meal['restaurant_id'], 
                'Le repas a été mis à jour avec succès.', 'success');
        } catch (Exception $e) {
            $errors[] = "Une erreur est survenue lors de la mise à jour du repas.";
        }
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Modifier le repas</h1>
                <a href="meals.php?restaurant_id=<?php echo $meal['restaurant_id']; ?>" 
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
                            <label class="form-label">Restaurant</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo htmlspecialchars($meal['restaurant_name']); ?>" 
                                   readonly>
                            <div class="form-text">
                                Le restaurant ne peut pas être modifié. Créez un nouveau repas si nécessaire.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Catégorie</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $meal['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du repas</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($meal['name']); ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" required><?php echo htmlspecialchars($meal['description']); ?></textarea>
                            <div class="form-text">
                                Décrivez les ingrédients et la préparation du repas.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Prix (€)</label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   value="<?php echo htmlspecialchars($meal['price']); ?>" 
                                   step="0.01" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label for="image_url" class="form-label">URL de l'image</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" 
                                   value="<?php echo htmlspecialchars($meal['image_url']); ?>">
                            <?php if ($meal['image_url']): ?>
                                <div class="mt-2">
                                    <img src="<?php echo htmlspecialchars($meal['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($meal['name']); ?>"
                                         class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            <?php endif; ?>
                            <div class="form-text">
                                L'URL d'une image du repas.
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_available" 
                                       name="is_available" value="1" 
                                       <?php echo $meal['is_available'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_available">
                                    Repas disponible à la commande
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                            <a href="meals.php?restaurant_id=<?php echo $meal['restaurant_id']; ?>" 
                               class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
