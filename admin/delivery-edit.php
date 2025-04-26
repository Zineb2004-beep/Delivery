<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
require_once '../models/Admin.php';

// Require admin authentication
require_auth();
require_role('admin');

$admin = new Admin($conn);
$errors = [];

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get delivery person details
$stmt = $conn->prepare(
    "SELECT u.*, d.vehicle_type, d.vehicle_number 
     FROM users u 
     LEFT JOIN delivery_details d ON u.id = d.user_id 
     WHERE u.id = ? AND u.role = 'delivery'"
);
$stmt->execute([$user_id]);
$delivery_person = $stmt->fetch();

if (!$delivery_person) {
    redirect_with_message('/admin/delivery-personnel.php', 
        'Livreur non trouvé.', 'danger');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $vehicle_type = sanitize($_POST['vehicle_type']);
    $vehicle_number = sanitize($_POST['vehicle_number']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($first_name)) $errors[] = "Le prénom est requis";
    if (empty($last_name)) $errors[] = "Le nom est requis";
    if (empty($email)) $errors[] = "L'email est requis";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide";
    if (empty($phone)) $errors[] = "Le numéro de téléphone est requis";
    if (empty($vehicle_type)) $errors[] = "Le type de véhicule est requis";
    if (empty($vehicle_number)) $errors[] = "Le numéro d'immatriculation est requis";

    // Check if email already exists (excluding current user)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = "Cet email est déjà utilisé";
    }

    // Password validation only if provided
    if (!empty($password) && $password !== $password_confirm) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    // Check if user has active deliveries before deactivating
    if (!$is_active) {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM orders 
             WHERE delivery_user_id = ? AND status = 'delivering'"
        );
        $stmt->execute([$user_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ce livreur a des livraisons en cours et ne peut pas être désactivé.";
        }
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Update user
            $sql = "UPDATE users SET 
                    first_name = ?, last_name = ?, email = ?, 
                    phone = ?, address = ?, is_active = ?";
            $params = [
                $first_name, $last_name, $email, 
                $phone, $address, $is_active
            ];

            // Add password update if provided
            if (!empty($password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Update delivery details
            $stmt = $conn->prepare(
                "INSERT INTO delivery_details (user_id, vehicle_type, vehicle_number) 
                 VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE 
                 vehicle_type = VALUES(vehicle_type), 
                 vehicle_number = VALUES(vehicle_number)"
            );
            $stmt->execute([$user_id, $vehicle_type, $vehicle_number]);

            $conn->commit();
            
            redirect_with_message('/admin/delivery-personnel.php', 
                'Le livreur a été mis à jour avec succès.', 'success');
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Une erreur est survenue lors de la mise à jour du livreur.";
        }
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Modifier le livreur</h1>
                <div>
                    <a href="delivery-stats.php?id=<?php echo $user_id; ?>" 
                       class="btn btn-info me-2">
                        <i class="fas fa-chart-line"></i> Statistiques
                    </a>
                    <a href="delivery-personnel.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Retour aux livreurs
                    </a>
                </div>
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
                        <!-- Personal Information -->
                        <h5 class="mb-4">Informations personnelles</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($delivery_person['first_name']); ?>" 
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($delivery_person['last_name']); ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($delivery_person['email']); ?>" 
                                   required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">
                                    Nouveau mot de passe
                                    <small class="text-muted">(laisser vide pour ne pas modifier)</small>
                                </label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="password_confirm" 
                                       name="password_confirm">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($delivery_person['phone']); ?>" 
                                   required>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label">Adresse</label>
                            <textarea class="form-control" id="address" name="address" 
                                      rows="2"><?php echo htmlspecialchars($delivery_person['address']); ?></textarea>
                        </div>

                        <!-- Vehicle Information -->
                        <h5 class="mb-4">Informations du véhicule</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="vehicle_type" class="form-label">Type de véhicule</label>
                                <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                    <option value="">Sélectionner un type</option>
                                    <option value="bike" <?php echo $delivery_person['vehicle_type'] === 'bike' ? 'selected' : ''; ?>>
                                        Vélo
                                    </option>
                                    <option value="scooter" <?php echo $delivery_person['vehicle_type'] === 'scooter' ? 'selected' : ''; ?>>
                                        Scooter
                                    </option>
                                    <option value="car" <?php echo $delivery_person['vehicle_type'] === 'car' ? 'selected' : ''; ?>>
                                        Voiture
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="vehicle_number" class="form-label">
                                    Numéro d'immatriculation
                                    <small class="text-muted">(si applicable)</small>
                                </label>
                                <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" 
                                       value="<?php echo htmlspecialchars($delivery_person['vehicle_number']); ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" 
                                       name="is_active" value="1" 
                                       <?php echo $delivery_person['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Compte actif
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                            <a href="delivery-personnel.php" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
