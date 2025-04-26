<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'models/Restaurant.php';

$restaurantModel = new Restaurant($conn);
$restaurants = $restaurantModel->getAllActive();

// Filter by category if provided
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
if ($category_id) {
    $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
}
?>

<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero text-center py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto animate-fadeInUp">
                <h1 class="display-4 mb-4">
                    <?php echo $category_id ? 
                        "Restaurants " . htmlspecialchars($category['name']) : 
                        "Découvrez nos restaurants"; ?>
                </h1>
                <p class="lead mb-4">
                    <?php echo $category_id ? 
                        "Les meilleurs restaurants pour vos " . strtolower(htmlspecialchars($category['name'])) : 
                        "Une sélection des meilleurs restaurants de votre région"; ?>
                </p>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <!-- Categories filter -->
    <div class="categories-filter text-center mb-5">
        <a href="/restaurants.php" 
           class="btn <?php echo !$category_id ? 'btn-primary' : 'btn-outline-primary'; ?>">
            <i class="fas fa-utensils me-2"></i>Tous
        </a>
        <?php
        $stmt = $conn->query("SELECT * FROM categories ORDER BY name");
        while ($cat = $stmt->fetch()) {
            $active = $category_id == $cat['id'] ? 'btn-primary' : 'btn-outline-primary';
            $icons = [
                'Entrées' => 'fas fa-carrot',
                'Plats principaux' => 'fas fa-hamburger',
                'Desserts' => 'fas fa-ice-cream',
                'Boissons' => 'fas fa-glass-martini-alt',
                'Végétarien' => 'fas fa-leaf',
                'Spécialités' => 'fas fa-star'
            ];
            $icon = isset($icons[$cat['name']]) ? $icons[$cat['name']] : 'fas fa-utensils';
            echo "<a href='/restaurants.php?category={$cat['id']}' 
                    class='btn {$active} ms-2'>
                    <i class='{$icon} me-2'></i>" . 
                    htmlspecialchars($cat['name']) . 
                 "</a>";
        }
        ?>
    </div>

    <div class="row">
        <?php foreach ($restaurants as $restaurant): ?>
            <div class="col-md-6 col-lg-4 mb-4 animate-fadeInUp">
                <div class="restaurant-card">
                    <img src="<?php echo htmlspecialchars($restaurant['image_url'] ?? '/assets/img/restaurant-default.jpg'); ?>" 
                         class="w-100" alt="<?php echo htmlspecialchars($restaurant['name']); ?>">
                    
                    <div class="restaurant-info">
                        <h5 class="card-title"><?php echo htmlspecialchars($restaurant['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($restaurant['description']); ?></p>
                        
                        <?php
                        // Get restaurant categories
                        $stmt = $conn->prepare(
                            "SELECT DISTINCT c.name 
                            FROM categories c 
                            JOIN meals m ON c.id = m.category_id 
                            WHERE m.restaurant_id = ? 
                            LIMIT 3"
                        );
                        $stmt->execute([$restaurant['id']]);
                        $categories = $stmt->fetchAll();
                        ?>
                        
                        <div class="mb-3">
                            <?php foreach ($categories as $cat): ?>
                                <span class="badge bg-secondary me-1">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="rating">
                                <?php
                                $rating = $restaurantModel->getAverageRating($restaurant['id']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '⭐' : '☆';
                                }
                                ?>
                            </div>
                            <a href="/restaurant.php?id=<?php echo $restaurant['id']; ?>" 
                               class="btn btn-primary">Voir le menu</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($restaurants)): ?>
        <div class="text-center py-5">
            <h3>Aucun restaurant trouvé</h3>
            <p>Essayez de modifier vos critères de recherche</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
