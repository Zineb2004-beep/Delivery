<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $role = 'client'; // Default role for registration

    // Validation
    $errors = [];
    
    if (empty($first_name)) $errors[] = "Le prénom est requis";
    if (empty($last_name)) $errors[] = "Le nom est requis";
    if (empty($email)) $errors[] = "L'email est requis";
    if (!is_valid_email($email)) $errors[] = "L'email n'est pas valide";
    if (empty($password)) $errors[] = "Le mot de passe est requis";
    if ($password !== $confirm_password) $errors[] = "Les mots de passe ne correspondent pas";
    if (empty($phone)) $errors[] = "Le numéro de téléphone est requis";
    if (empty($address)) $errors[] = "L'adresse est requise";

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Cette adresse email est déjà utilisée";
    }

    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $email, $hashed_password, $phone, $address, $role]);
            
            redirect_with_message('/login.php', 'Inscription réussie ! Vous pouvez maintenant vous connecter.', 'success');
        } catch (PDOException $e) {
            $errors[] = "Une erreur est survenue lors de l'inscription";
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="auth-page">
    <div class="container">
        <div class="row justify-content-center align-items-center py-5">
            <div class="col-md-8 col-lg-6">
                <div class="auth-form">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h2 class="auth-title">Créer un compte</h2>
                        <p class="text-muted">Rejoignez AZ Delivery et profitez de nos services</p>
                    </div>

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
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">
                                    <i class="fas fa-user me-2"></i>Prénom
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo isset($first_name) ? $first_name : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">
                                    <i class="fas fa-user me-2"></i>Nom
                                </label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo isset($last_name) ? $last_name : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($email) ? $email : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Mot de passe
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Confirmer le mot de passe
                            </label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone me-2"></i>Téléphone
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($phone) ? $phone : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">
                                <i class="fas fa-map-marker-alt me-2"></i>Adresse
                            </label>
                            <textarea class="form-control" id="address" name="address" 
                                      rows="3" required><?php echo isset($address) ? $address : ''; ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>S'inscrire
                        </button>

                        <div class="text-center mt-4">
                            <p class="mb-0">
                                Déjà inscrit ? 
                                <a href="/login.php" class="text-primary">
                                    <i class="fas fa-sign-in-alt me-1"></i>Connectez-vous
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
