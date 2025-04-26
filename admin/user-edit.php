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

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect_with_message('/admin/users.php', 
        'Utilisateur non trouvé.', 'danger');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $role = sanitize($_POST['role']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($first_name)) $errors[] = "Le prénom est requis";
    if (empty($last_name)) $errors[] = "Le nom est requis";
    if (empty($email)) $errors[] = "L'email est requis";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide";
    if (!in_array($role, ['client', 'delivery', 'admin'])) $errors[] = "Le rôle n'est pas valide";

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

    if (empty($errors)) {
        try {
            $sql = "UPDATE users SET 
                    first_name = ?, last_name = ?, email = ?, 
                    phone = ?, address = ?, role = ?, is_active = ?";
            $params = [
                $first_name, $last_name, $email, 
                $phone, $address, $role, $is_active
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
            
            redirect_with_message('/admin/users.php', 
                'L\'utilisateur a été mis à jour avec succès.', 'success');
        } catch (Exception $e) {
            $errors[] = "Une erreur est survenue lors de la mise à jour de l'utilisateur.";
        }
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3">Modifier l'utilisateur</h1>
                <div>
                    <?php if ($user['role'] === 'delivery'): ?>
                        <a href="delivery-stats.php?id=<?php echo $user_id; ?>" 
                           class="btn btn-info me-2">
                            <i class="fas fa-chart-line"></i> Statistiques de livraison
                        </a>
                    <?php endif; ?>
                    <a href="users.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Retour aux utilisateurs
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
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
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
                                   value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <textarea class="form-control" id="address" name="address" 
                                      rows="2"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle</label>
                            <select class="form-select" id="role" name="role" required 
                                    <?php echo $user['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                <option value="client" <?php echo $user['role'] === 'client' ? 'selected' : ''; ?>>
                                    Client
                                </option>
                                <option value="delivery" <?php echo $user['role'] === 'delivery' ? 'selected' : ''; ?>>
                                    Livreur
                                </option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                                    Administrateur
                                </option>
                            </select>
                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                <div class="form-text">
                                    Vous ne pouvez pas modifier votre propre rôle.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" 
                                       name="is_active" value="1" 
                                       <?php echo $user['is_active'] ? 'checked' : ''; ?>
                                       <?php echo $user['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Compte actif
                                </label>
                            </div>
                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                <div class="form-text">
                                    Vous ne pouvez pas désactiver votre propre compte.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                            <a href="users.php" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
