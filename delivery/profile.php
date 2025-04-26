<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require delivery personnel authentication
require_auth();
require_role('delivery');

$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $conn->prepare(
    "SELECT * FROM users WHERE id = ? AND role = 'delivery'"
);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect_with_message('/delivery/index.php', 
        'Utilisateur non trouvé.', 'danger');
}

// Get performance metrics
$stmt = $conn->prepare(
    "SELECT 
        COUNT(*) as total_deliveries,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_deliveries,
        AVG(CASE 
            WHEN status = 'completed' 
            THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at)
            ELSE NULL 
        END) as avg_delivery_time,
        SUM(total_amount) as total_earnings
     FROM orders 
     WHERE delivery_user_id = ?
     AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
);
$stmt->execute([$user_id]);
$metrics = $stmt->fetch();

// Get monthly statistics
$stmt = $conn->prepare(
    "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_deliveries,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_deliveries,
        SUM(total_amount) as total_amount
     FROM orders 
     WHERE delivery_user_id = ?
     AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month DESC"
);
$stmt->execute([$user_id]);
$monthly_stats = $stmt->fetchAll();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $vehicle_type = trim($_POST['vehicle_type']);
    $vehicle_number = trim($_POST['vehicle_number']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Validate required fields
    if (empty($first_name)) $errors[] = "Le prénom est requis.";
    if (empty($last_name)) $errors[] = "Le nom est requis.";
    if (empty($email)) $errors[] = "L'email est requis.";
    if (empty($phone)) $errors[] = "Le téléphone est requis.";
    if (empty($vehicle_type)) $errors[] = "Le type de véhicule est requis.";
    if (empty($vehicle_number)) $errors[] = "Le numéro de véhicule est requis.";

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide.";
    }

    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé.";
        }
    }

    // Handle password change if requested
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Le mot de passe actuel est incorrect.";
        } elseif (empty($new_password)) {
            $errors[] = "Le nouveau mot de passe est requis.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
        }
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Update user information
            $sql = "UPDATE users SET 
                        first_name = ?,
                        last_name = ?,
                        email = ?,
                        phone = ?,
                        vehicle_type = ?,
                        vehicle_number = ?";
            $params = [
                $first_name, $last_name, $email, $phone,
                $vehicle_type, $vehicle_number
            ];

            // Add password update if changing
            if (!empty($new_password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            $conn->commit();
            redirect_with_message('/delivery/profile.php', 
                'Profil mis à jour avec succès.', 'success');
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Une erreur est survenue lors de la mise à jour.";
        }
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Mon profil</h1>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Form -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="vehicle_type" class="form-label">Type de véhicule</label>
                                <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="bike" <?php 
                                        echo $user['vehicle_type'] === 'bike' ? 'selected' : ''; 
                                    ?>>Vélo</option>
                                    <option value="scooter" <?php 
                                        echo $user['vehicle_type'] === 'scooter' ? 'selected' : ''; 
                                    ?>>Scooter</option>
                                    <option value="car" <?php 
                                        echo $user['vehicle_type'] === 'car' ? 'selected' : ''; 
                                    ?>>Voiture</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="vehicle_number" class="form-label">
                                    Numéro d'immatriculation
                                </label>
                                <input type="text" class="form-control" id="vehicle_number" 
                                       name="vehicle_number"
                                       value="<?php echo htmlspecialchars($user['vehicle_number']); ?>" 
                                       required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6>Changer le mot de passe</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">
                                    Mot de passe actuel
                                </label>
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password">
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">
                                    Nouveau mot de passe
                                </label>
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password">
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">
                                    Confirmer le mot de passe
                                </label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="col-lg-4">
            <!-- Current Month Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Performance du mois</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <h6 class="text-muted mb-1">Total livraisons</h6>
                            <h3 class="mb-0">
                                <?php echo number_format($metrics['total_deliveries']); ?>
                            </h3>
                        </div>
                        <div class="col-6 mb-3">
                            <h6 class="text-muted mb-1">Complétées</h6>
                            <h3 class="mb-0 text-success">
                                <?php echo number_format($metrics['completed_deliveries']); ?>
                            </h3>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted mb-1">Temps moyen</h6>
                            <h3 class="mb-0">
                                <?php echo $metrics['avg_delivery_time'] ? 
                                    round($metrics['avg_delivery_time']) . ' min' : 'N/A'; ?>
                            </h3>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted mb-1">Gains</h6>
                            <h3 class="mb-0">
                                <?php echo number_format($metrics['total_earnings'], 2); ?> €
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historique mensuel</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Mois</th>
                                    <th class="text-end">Livraisons</th>
                                    <th class="text-end">Gains</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_stats as $stat): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $date = DateTime::createFromFormat('Y-m', $stat['month']);
                                            echo $date->format('M Y');
                                            ?>
                                        </td>
                                        <td class="text-end">
                                            <?php echo number_format($stat['completed_deliveries']); ?> / 
                                            <?php echo number_format($stat['total_deliveries']); ?>
                                        </td>
                                        <td class="text-end">
                                            <?php echo number_format($stat['total_amount'], 2); ?> €
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($monthly_stats)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-3">
                                            Aucune donnée disponible
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
