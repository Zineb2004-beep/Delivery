<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $errors = [];

    if (empty($email)) $errors[] = "L'email est requis";
    if (empty($password)) $errors[] = "Le mot de passe est requis";

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id, password, role, first_name, last_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Start session and store user data
                init_session();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

                // Debugging: Check the role
                echo "Rôle de l'utilisateur : " . $user['role'];

                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        redirect_with_message('/admin/index.php', 'Bienvenue dans votre espace administrateur !');
                        break;
                    case 'delivery':
                        redirect_with_message('/delivery/profile.php', 'Bienvenue dans votre espace livreur !');
                        break;
                    case 'client':
                        redirect_with_message('/account.php', 'Bienvenue dans votre espace client !');
                        break;
                    default:
                        redirect_with_message('/', 'Connexion réussie !');
                }
            } else {
                $errors[] = "Email ou mot de passe incorrect";
            }
        } catch (PDOException $e) {
            $errors[] = "Une erreur est survenue lors de la connexion";
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="auth-page">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="auth-form">
                    <div class="text-center mb-4">
                        <div class="brand-logo mb-4">
                            <img src="/assets/images/logo.png" alt="AZ Delivery" class="img-fluid" style="height: 60px;">
                        </div>
                        <i class="fas fa-user-circle mb-3"></i>
                        <h2 class="auth-title">Bienvenue</h2>
                        <p class="text-muted">Connectez-vous pour accéder à votre espace</p>
                    </div>

                    <div class="auth-separator mb-4">
                        <span>Connexion sécurisée</span>
                    </div>

        <?php
        if (!empty($errors)) {
            echo '<div class="alert alert-danger"><ul class="mb-0">';
            foreach ($errors as $error) {
                echo "<li>$error</li>";
            }
            echo '</ul></div>';
        }
        echo display_flash_message();
        ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope me-2"></i>Email
                </label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>" required>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Mot de passe
                </label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <div class="form-group mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>
                    <a href="/forgot-password.php" class="forgot-password">
                        <i class="fas fa-lock me-1"></i>Mot de passe oublié ?
                    </a>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-4">
                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
            </button>

            <div class="auth-separator mb-4">
                <span>ou</span>
            </div>

            <div class="social-login mb-4">
                <a href="#" class="btn btn-outline-primary w-100 mb-3">
                    <i class="fab fa-google me-2"></i>Continuer avec Google
                </a>
                <a href="#" class="btn btn-outline-primary w-100">
                    <i class="fab fa-facebook-f me-2"></i>Continuer avec Facebook
                </a>
            </div>

            <div class="text-center">
                <p class="mb-0">
                    Pas encore de compte ? 
                    <a href="/register.php" class="text-primary fw-bold">
                        <i class="fas fa-user-plus me-1"></i>Inscrivez-vous
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
