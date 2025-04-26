<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero text-center py-5" style="background: url('/images/hero.jpg') no-repeat center center/cover; color: black; position: relative;">
    <div class="overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.5); z-index: 1;"></div>
    <div class="container" style="position: relative; z-index: 2;">
        <div class="row align-items-center">
            <div class="col-lg-6 mx-auto animate-fadeInUp">
                <h1 class="display-4 mb-4" style="color: black;">Vos repas préférés livrés chez vous</h1>
                <p class="lead mb-4" style="color: black;">Découvrez les meilleurs restaurants de votre région et faites-vous livrer en quelques clics</p>
                <a href="/restaurants.php" class="btn btn-warning btn-lg">
                    <i class="fas fa-utensils me-2"></i>Commander maintenant
                </a>
            </div>
            <div class="col-lg-6 text-center">
                <img src="/images/delivery-guy.svg" alt="Delivery Guy" class="img-fluid" style="max-height: 300px;">
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Explorez nos catégories</h2>
            <p class="text-muted">Trouvez des plats délicieux dans chaque catégorie</p>
        </div>
        <div class="row">
            <?php
            $stmt = $conn->query("SELECT * FROM categories LIMIT 6");
            while ($category = $stmt->fetch()) {
                $image = $category['image_url'] ?? 'https://images.unsplash.com/photo-1562967916-eb82221dfb22?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400';
                ?>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card shadow-sm">
                        <img src="<?php echo htmlspecialchars($image); ?>" class="card-img-top img-fluid" alt="<?php echo htmlspecialchars($category['name']); ?>" style="max-height: 200px; object-fit: cover;">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($category['description']); ?></p>
                            <a href="/meals.php?category=<?php echo $category['id']; ?>" class="btn btn-outline-primary">Découvrir</a>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</section>

<!-- Featured Restaurants Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Nos restaurants partenaires</h2>
        <p class="text-center text-muted">Découvrez les meilleurs restaurants près de chez vous</p>
        <div class="row">
            <?php
            $stmt = $conn->query("SELECT * FROM restaurants WHERE is_active = 1 LIMIT 6");
            while ($restaurant = $stmt->fetch()) {
                $image = $restaurant['image_url'] ?? 'https://images.unsplash.com/photo-1601924582971-7cbb10f7d7e6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400';
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <img src="<?php echo htmlspecialchars($image); ?>" class="card-img-top img-fluid" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" style="max-height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($restaurant['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($restaurant['description']); ?></p>
                            <a href="/restaurant.php?id=<?php echo $restaurant['id']; ?>" class="btn btn-primary">Voir le menu</a>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</section>

<!-- Featured Meals Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Nos plats populaires</h2>
        <p class="text-center text-muted">Savourez nos plats les plus appréciés</p>
        <div class="row">
            <?php
            $stmt = $conn->query("SELECT * FROM meals WHERE is_featured = 1 LIMIT 6");
            while ($meal = $stmt->fetch()) {
                $image = $meal['image_url'] ?? 'https://images.unsplash.com/photo-1604908177225-0e7c3e7b6c8b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=400';
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <img src="<?php echo htmlspecialchars($image); ?>" class="card-img-top img-fluid" alt="<?php echo htmlspecialchars($meal['name']); ?>" style="max-height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($meal['name']); ?></h5>
                            <p class="card-text">Prix: <?php echo htmlspecialchars($meal['price']); ?> €</p>
                            <a href="/meal.php?id=<?php echo $meal['id']; ?>" class="btn btn-outline-success">Commander</a>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Comment ça marche ?</h2>
        <div class="row text-center">
            <div class="col-md-4">
                <div class="mb-4">
                    <i class="fas fa-search fa-3x text-primary"></i>
                </div>
                <h4>1. Choisissez</h4>
                <p>Parcourez nos restaurants partenaires et sélectionnez vos plats préférés</p>
            </div>
            <div class="col-md-4">
                <div class="mb-4">
                    <i class="fas fa-shopping-cart fa-3x text-primary"></i>
                </div>
                <h4>2. Commandez</h4>
                <p>Passez votre commande en quelques clics et payez en toute sécurité</p>
            </div>
            <div class="col-md-4">
                <div class="mb-4">
                    <i class="fas fa-motorcycle fa-3x text-primary"></i>
                </div>
                <h4>3. Profitez</h4>
                <p>Recevez votre commande directement chez vous en un temps record</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
