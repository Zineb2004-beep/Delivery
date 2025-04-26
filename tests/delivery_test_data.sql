-- Create test delivery personnel
INSERT INTO users (first_name, last_name, email, password, phone, role, created_at) VALUES
('Jean', 'Dupont', 'jean.dupont@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0612345678', 'delivery',  NOW()),
('Marie', 'Martin', 'marie.martin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0687654321', 'delivery',  NOW());
-- Note: Password for both users is 'password'

-- Create test restaurants
INSERT INTO restaurants (name, description, address, phone, is_active, created_at) VALUES
('Pizza Express', 'Pizzeria italienne', '123 Rue de la Pizza, Paris', '0123456789', 1, NOW()),
('Burger House', 'Les meilleurs burgers', '456 Avenue du Burger, Paris', '0123456780', 1, NOW());

-- Create test meals
INSERT INTO meals (restaurant_id, name, description, price, is_active, created_at) VALUES
((SELECT id FROM restaurants WHERE name = 'Pizza Express'), 'Margherita', 'Tomate, mozzarella, basilic', 12.99, 1, NOW()),
((SELECT id FROM restaurants WHERE name = 'Pizza Express'), 'Regina', 'Tomate, mozzarella, jambon, champignons', 14.99, 1, NOW()),
((SELECT id FROM restaurants WHERE name = 'Burger House'), 'Classic Burger', 'Boeuf, salade, tomate, oignon', 13.99, 1, NOW()),
((SELECT id FROM restaurants WHERE name = 'Burger House'), 'Cheese Burger', 'Boeuf, cheddar, salade, tomate', 15.99, 1, NOW());

-- Create test customers
INSERT INTO users (first_name, last_name, email, password, phone, role, created_at) VALUES
('Pierre', 'Dubois', 'pierre.dubois@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0611111111', 'client', NOW()),
('Sophie', 'Laurent', 'sophie.laurent@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0622222222', 'client', NOW());

-- Create test orders (mix of active and completed)
INSERT INTO orders (user_id, delivery_user_id, status, delivery_address, total_amount, payment_method, created_at, updated_at) VALUES
((SELECT id FROM users WHERE email = 'pierre.dubois@test.com'),
 (SELECT id FROM users WHERE email = 'jean.dupont@test.com'),
 'delivering',
 '789 Boulevard du Client, Paris',
 30.97,
 'card',
 NOW(),
 NOW()),
((SELECT id FROM users WHERE email = 'sophie.laurent@test.com'),
 (SELECT id FROM users WHERE email = 'jean.dupont@test.com'),
 'completed',
 '321 Avenue du Test, Paris',
 42.96,
 'cash',
 DATE_SUB(NOW(), INTERVAL 2 HOUR),
 DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- Add order items
INSERT INTO order_items (order_id, meal_id, quantity, price) VALUES
((SELECT MAX(id) FROM orders WHERE status = 'delivering'),
 (SELECT id FROM meals WHERE name = 'Margherita'),
 2,
 12.99),
((SELECT MAX(id) FROM orders WHERE status = 'delivering'),
 (SELECT id FROM meals WHERE name = 'Classic Burger'),
 1,
 13.99),
((SELECT MAX(id) FROM orders WHERE status = 'completed'),
 (SELECT id FROM meals WHERE name = 'Regina'),
 2,
 14.99),
((SELECT MAX(id) FROM orders WHERE status = 'completed'),
 (SELECT id FROM meals WHERE name = 'Cheese Burger'),
 1,
 15.99);

-- Create some historical orders for statistics
INSERT INTO orders (user_id, delivery_user_id, status, delivery_address, total_amount, payment_method, created_at, updated_at)
SELECT 
    (SELECT id FROM users WHERE email = 'pierre.dubois@test.com'),
    (SELECT id FROM users WHERE email = 'jean.dupont@test.com'),
    'completed',
    '789 Boulevard du Client, Paris',
    ROUND(RAND() * 50 + 20, 2),
    CASE WHEN RAND() > 0.5 THEN 'card' ELSE 'cash' END,
    DATE_SUB(NOW(), INTERVAL n DAY),
    DATE_SUB(NOW(), INTERVAL n DAY) + INTERVAL FLOOR(RAND() * 60) MINUTE
FROM (
    SELECT a.N + b.N * 10 + c.N * 100 as n
    FROM (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
         (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
         (SELECT 0 as N UNION SELECT 1) c
    WHERE a.N + b.N * 10 + c.N * 100 < 180
) numbers
ORDER BY RAND()
LIMIT 50;