<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Admin.php';

// Require admin authentication
require_auth();
require_role('admin');

$admin = new Admin($conn);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $image_url = sanitize($_POST['image_url']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) $errors[] = "Le nom est requis";
    if (empty($description)) $errors[] = "La description est requise";
    if (empty($address)) $errors[] = "L'adresse est requise";
    if (empty($phone)) $errors[] = "Le téléphone est requis";

    if (empty($errors)) {
        try {
            $admin->createRestaurant([
                'name' => $name,
                'description' => $description,
                'address' => $address,
                'phone' => $phone,
                'image_url' => $image_url,
                'is_active' => $is_active
            ]);
            
            redirect_with_message('/admin/restaurants.php', 
                'Le restaurant a été créé avec succès.', 'success');
        } catch (Exception $e) {
            $errors[] = "Une erreur est survenue lors de la création du restaurant.";
        }
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Nouveau restaurant</h1>
                <a href="restaurants.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Retour aux restaurants
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
                            <label for="name" class="form-label">Nom du restaurant</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <div class="form-text">
                                Décrivez brièvement le restaurant et sa cuisine.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <textarea class="form-control" id="address" name="address" 
                                      rows="2" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="image_url" class="form-label">URL de l'image</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" 
                                   value="<?php echo isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : ''; ?>">
                            <div class="form-text">
                                L'URL d'une image représentative du restaurant.
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" 
                                       name="is_active" value="1" 
                                       <?php echo isset($_POST['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Activer le restaurant immédiatement
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Créer le restaurant
                            </button>
                            <a href="restaurants.php" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
