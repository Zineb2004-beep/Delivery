<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'models/Order.php';

// Require authentication
require_auth();

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's orders
$order = new Order($conn);
$orders = $order->getUserOrders($_SESSION['user_id']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    $errors = [];
    
    if (empty($first_name)) $errors[] = "Le prénom est requis";
    if (empty($last_name)) $errors[] = "Le nom est requis";
    if (empty($phone)) $errors[] = "Le téléphone est requis";
    if (empty($address)) $errors[] = "L'adresse est requise";

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare(
                "UPDATE users 
                 SET first_name = ?, last_name = ?, phone = ?, address = ? 
                 WHERE id = ?"
            );
            $stmt->execute([$first_name, $last_name, $phone, $address, $_SESSION['user_id']]);
            
            redirect_with_message('/account.php', 'Profil mis à jour avec succès !', 'success');
        } catch (Exception $e) {
            $errors[] = "Une erreur est survenue lors de la mise à jour";
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <!-- Profile Section -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Mon Profil</h5>
                </div>
                <div class="card-body">
                    <?php echo display_flash_message(); ?>

                    <?php
                    if (!empty($errors)) {
                        echo '<div class="alert alert-danger"><ul class="mb-0">';
                        foreach ($errors as $error) {
                            echo "<li>$error</li>";
                        }
                        echo '</ul></div>';
                    }
                    ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <textarea class="form-control" id="address" name="address" 
                                      rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary w-100">
                            Mettre à jour le profil
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Orders Section -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Mes Commandes</h5>
                </div>
                <div class="card-body">
                    <?php if ($orders): ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-item mb-4 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">Commande #<?php echo $order['id']; ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?php
                                            switch ($order['status']) {
                                                case 'pending': echo 'warning'; break;
                                                case 'preparing': echo 'info'; break;
                                                case 'delivering': echo 'primary'; break;
                                                case 'completed': echo 'success'; break;
                                                case 'cancelled': echo 'danger'; break;
                                            }
                                        ?>">
                                            <?php
                                            switch ($order['status']) {
                                                case 'pending': echo 'En attente'; break;
                                                case 'preparing': echo 'En préparation'; break;
                                                case 'delivering': echo 'En livraison'; break;
                                                case 'completed': echo 'Livrée'; break;
                                                case 'cancelled': echo 'Annulée'; break;
                                            }
                                            ?>
                                        </span>
                                        <div class="mt-1">
                                            <strong><?php echo number_format($order['total_amount'], 2); ?> €</strong>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted"><?php echo $order['items_count']; ?> article(s)</small>
                                </div>
                                <div class="mt-2">
                                    <a href="/order-details.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        Voir les détails
                                    </a>
                                    <?php if ($order['status'] === 'delivering'): ?>
                                        <a href="/track-order.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-primary ms-2">
                                            Suivre la livraison
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="mb-3">Vous n'avez pas encore passé de commande</p>
                            <a href="/restaurants.php" class="btn btn-primary">Découvrir nos restaurants</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
