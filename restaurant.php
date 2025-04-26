<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'models/Restaurant.php';

// Get restaurant ID from URL
$restaurant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$restaurantModel = new Restaurant($conn);
$restaurant = $restaurantModel->getById($restaurant_id);

// Redirect if restaurant not found
if (!$restaurant) {
    redirect_with_message('/restaurants.php', 'Restaurant non trouvé.', 'danger');
}

// Get restaurant categories and meals
$categories = $restaurantModel->getCategories($restaurant_id);
$meals = $restaurantModel->getMeals($restaurant_id);
$reviews = $restaurantModel->getReviews($restaurant_id, 5);

// Group meals by category
$meals_by_category = [];
foreach ($meals as $meal) {
    $meals_by_category[$meal['category_name']][] = $meal;
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="container py-5">
    <!-- Restaurant Header -->
    <div class="row mb-5">
        <div class="col-md-4">
            <img src="<?php echo htmlspecialchars($restaurant['image_url'] ?? '/assets/img/restaurant-default.jpg'); ?>" 
                 class="img-fluid rounded" alt="<?php echo htmlspecialchars($restaurant['name']); ?>">
        </div>
        <div class="col-md-8">
            <h1 class="mb-3"><?php echo htmlspecialchars($restaurant['name']); ?></h1>
            <p class="lead mb-3"><?php echo htmlspecialchars($restaurant['description']); ?></p>
            
            <div class="mb-3">
                <strong>Adresse:</strong> <?php echo htmlspecialchars($restaurant['address']); ?><br>
                <strong>Téléphone:</strong> <?php echo htmlspecialchars($restaurant['phone']); ?>
            </div>

            <div class="rating mb-3">
                <?php
                $rating = $restaurantModel->getAverageRating($restaurant_id);
                for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $rating ? '⭐' : '☆';
                }
                echo " ($rating/5)";
                ?>
            </div>
        </div>
    </div>

    <!-- Categories Navigation -->
    <div class="categories-nav mb-4">
        <div class="d-flex flex-wrap">
            <?php foreach ($categories as $category): ?>
                <a href="#category-<?php echo $category['id']; ?>" 
                   class="btn btn-outline-primary me-2 mb-2">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Menu Section -->
    <div class="row">
        <div class="col-md-8">
            <?php foreach ($meals_by_category as $category_name => $category_meals): ?>
                <section id="category-<?php echo $category_meals[0]['category_id']; ?>" class="mb-5">
                    <h2 class="mb-4"><?php echo htmlspecialchars($category_name); ?></h2>
                    
                    <?php foreach ($category_meals as $meal): ?>
                        <div class="card mb-3">
                            <div class="row g-0">
                                <?php if ($meal['image_url']): ?>
                                    <div class="col-md-3">
                                        <img src="<?php echo htmlspecialchars($meal['image_url']); ?>" 
                                             class="img-fluid rounded-start" 
                                             alt="<?php echo htmlspecialchars($meal['name']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="<?php echo $meal['image_url'] ? 'col-md-9' : 'col-md-12'; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="card-title"><?php echo htmlspecialchars($meal['name']); ?></h5>
                                            <h5 class="text-primary"><?php echo number_format($meal['price'], 2); ?> €</h5>
                                        </div>
                                        <p class="card-text"><?php echo htmlspecialchars($meal['description']); ?></p>
                                        
                                        <?php if (is_logged_in()): ?>
                                            <form action="/cart/add.php" method="POST" class="d-inline">
                                                <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
                                                <div class="input-group" style="max-width: 200px;">
                                                    <input type="number" name="quantity" value="1" min="1" max="10" 
                                                           class="form-control">
                                                    <button type="submit" class="btn btn-primary">
                                                        Ajouter au panier
                                                    </button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <a href="/login.php" class="btn btn-outline-primary">
                                                Connectez-vous pour commander
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>

        <!-- Reviews Section -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title h5 mb-0">Avis des clients</h3>
                </div>
                <div class="card-body">
                    <?php if ($reviews): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name'][0] . '.'); ?></strong>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="rating mb-2">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['rating'] ? '⭐' : '☆';
                                    }
                                    ?>
                                </div>
                                <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Aucun avis pour le moment.</p>
                    <?php endif; ?>

                    <?php if (is_logged_in()): ?>
                        <a href="/review.php?restaurant_id=<?php echo $restaurant_id; ?>" 
                           class="btn btn-primary w-100">
                            Laisser un avis
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
